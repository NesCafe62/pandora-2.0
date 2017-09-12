<?php
namespace core\libs;

use console;

use Exception;
use debug;
use logger;
use core\libs\request;

class app {

	protected $config = [];

	protected $plugins = [];

	public function __construct($config) {
		$this->config = $config;
		$this->layout = $config['layout'] ?? $this->layout;
	}

	public function getConfig() {
		return $this->config;
	}

	/* public function getDb() {
		return $this->db;
	} */

	protected function getPluginsRoutes() {
		$plugins_path = $this->path.'/plugins/';
		$plugins_namespace = str_replace('/','\\',$plugins_path);

		$route_plugins = include($plugins_path.'plugins.php');
		$routes = [];

		foreach ($route_plugins as $plugin_name) {
			$plugin_path = $plugins_path.$plugin_name;
			$plugin_routes = include($plugin_path.'/routes.php');
			$route_plugin = $plugins_namespace.$plugin_name;
			/* foreach ($plugin_routes as $location => $route_method) {
				$routes[$location] = [$plugins_namespace.$plugin_name, $route_method];
			} */
			foreach ($plugin_routes as $controller_class => $controller_routes) {
				if ($controller_class == 'self') {
					$route_controller = null;
				} else {
					$route_controller = $route_plugin.'\\controllers\\'.$controller_class;
				}
				foreach ($controller_routes as $route_method => $location) {
					if (is_numeric($route_method)) {
						if (!isset($location[0]) && !isset($location[1])) {
							trigger_error(debug::_('APP_ROUTE_PLUGIN_ROUTE_METHOD_NOT_SET', $plugin_name, $controller_class), E_WARNING);
							continue;
						}
						$route_method = array_shift($location);
					}
					$params_post = [];
					$params_get = [];
					if (is_array($location)) {
						if (!isset($location[0])) {
							trigger_error(debug::_('APP_ROUTE_PLUGIN_ROUTE_LOCATION_NOT_SET', $plugin_name, $controller_class, $route_method), E_WARNING);
							continue;
						}
						$location = $location[0];
						$params_post = $location['post'] ?? [];
						$params_get = $location['get'] ?? [];
					}
					$routes[] = [$location, $params_post, $params_get, $route_plugin, $route_controller, $route_method];
				}
			}
		}

		$segments_cache = [];
		$getSegments = function ($route) use (&$segments_cache) {
			[$location, $params_post, $params_get] = $route;
			if (!isset($segments_cache[$location])) {
				$segments_cache[$location] = preg_replace_callback('#(/[^/]+)|/#', function($match) {
					$segment = ltrim($match[0],'/');
					return (($segment[0] ?? '') === '$') ? 'D' : 'Z';
				}, $location);
			}
			$params_weight = str_repeat('C', count($params_post)).str_repeat('A', count($params_get));
			return $segments_cache[$location].$params_weight;
		};

		usort($routes, function($route1, $route2) use ($getSegments) {
			return $getSegments($route2) <=> $getSegments($route1); // strlen($route2) <=> strlen($route1);
		});

		return $routes;
	}

	protected function route($uri) {
		$routes = $this->getPluginsRoutes();

		// console::log($routes);

		foreach ($routes as $route) {
			[$location, $params_post, $params_get, $route_plugin, $route_controller, $route_method] = $route;

			$args_names = [];
			$arg_index = 1;
			$pattern = preg_replace_callback('#\$([^/]+)#', function($matches) use (&$args_names, &$arg_index) {
				$args_names[$matches[1]] = $arg_index;
				$arg_index++;
				return '([^/]+)';
			}, $location);

			$pattern = '#^'.str_replace(['-','*'], ['\-','.+'], $pattern).'$#'; // << $ if strict match

			// console::log($pattern);

			if (!preg_match($pattern, $uri, $matches)) {
				continue;
			}

			$params_matched = true;
			foreach ($params_post as $param_name => $params_value) {
				if (is_numeric($param_name)) {
					$param_name = $params_value;
					$request_value = $this->request->post($param_name);
					$params_matched &= ($request_value !== null);
				} else {
					$request_value = $this->request->post($param_name);
					if (is_array($params_value)) {
						$params_matched = false;
					} else {
						$params_matched &= ($request_value === (string) $params_value);
					}
				}
				if (!$params_matched) {
					break;
				}
			}

			if (!$params_matched) {
				continue;
			}

			foreach ($params_get as $param_name => $params_value) {
				if (is_numeric($param_name)) {
					$param_name = $params_value;
					$request_value = $this->request->get($param_name);
					$params_matched &= ($request_value !== null);
				} else {
					$request_value = $this->request->get($param_name);
					if (is_array($params_value)) {
						$params_matched = false;
					} else {
						$params_matched &= ($request_value === (string) $params_value);
					}
				}
				if (!$params_matched) {
					break;
				}
			}

			if ($params_matched) {
				$arguments = [];
				foreach ($args_names as $arg_name => $arg_index) {
					// $arguments[$arg_name] = $matches[$arg_index];
					$arguments[] = $matches[$arg_index];
				}

				// console::log([$route_class, $route_method, $arguments]);

				return [$route_plugin, $route_controller, $route_method, $arguments];
			}
		}

		return ['', '', '', []];
	}

	public $db = null;

	protected $path;
	protected $start_time;

	protected $daemon;
	protected $debug;
	protected $base;

	public $baseUri;
	public $layout;

	public $uri;
	public $relativeUri;

	public $request;

	private $logger;

	// public $scripts = '';
	public $content = '';
	// public $bodyEnd = '';

	public function head() {
		return '';
	}

	public function content() {
		return $this->content;
	}

	public function bodyBegin() {
		return '';
	}

	public function bodyEnd() {
		ob_start();
		if ($this->debug) {
			// event
			$profilerName = $this->config['profiler'] ?? '';
			if ($profilerName) {
				$profiler = $profilerName::instance(); // this is unsafe better do plugin::getInstance($profilerName);
				$profiler->renderProfiler();
			} else {
				debug::dumpLog();
			}
		}
		return ob_get_clean();
	}

	public function getElapsedTime() {
		return microtime(true) - $this->start_time;
	}

	public function init($params) {
		$connection_params = $this->config['db'] ?? null;
		if ($connection_params) {
			$this->db = new db($connection_params);
			$this->db->connect();
		}

		[
			'path' => $this->path,
			'start_time' => $this->start_time
		] = $params;

		$this->daemon = $this->config['daemon'] ?? false;
		$this->debug = $this->config['debug'] ?? false;
		$this->base = $this->config['base'] ?? '/'.trimLeft(trimRight($this->path, '/').'/','/');
		$this->baseUri = $this->config['baseUri'] ?? '/';
		$this->layout = $this->config['layout'] ?? 'main.php';

		// $this->baseUri = '/test';
		// $this->baseUri = '/';
		if ($this->daemon) {
			$this->logger = new logger(false); // this must do debugger plugin
		} else {
			session::start();

			// request::init();
			$this->request = request::init(); // new httpRequest();

			$uri = trimRight(parse_url(environment::get('REQUEST_URI'), PHP_URL_PATH), '/');
			$base_uri = trimLeft($this->baseUri, '/');
			if ($base_uri) {
				$relative_uri = '/'.preg_replace('#^/'.preg_quote($base_uri).'\b/?#', '', $uri);
			} else {
				$relative_uri = '/'.trimLeft($uri, '/');
			}

			$this->uri = $uri;
			$this->relativeUri = $relative_uri;

			[$route_plugin, $route_controller, $route_method, $arguments] = $this->route($relative_uri);
			if ($route_plugin) { // $route_class) {
				$plugin = $route_plugin::instance(); // plugin

				if ($route_controller === null) {
					$route_class = $route_plugin;
					$route_object = $plugin;
				} else if (is_string($route_controller)) {
					$route_class = $route_controller;
					$route_object = new $route_controller($plugin);
				} else {
					$route_class = get_class($route_controller);
					$route_object = $route_controller;
				}

				/* if (is_string($route_class)) {
					$route_object = $route_class::instance();
				} else {
					$route_object = $route_class;
				} */

				if (!method_exists($route_object, $route_method)) {
					throw new Exception(debug::_('APP_ROUTE_METHOD_NOT_EXIST', $route_class, $route_method), E_WARNING);
				}
				// $route_object->$route_method($arguments);
				$this->content = call_user_func_array([$route_object, $route_method], $arguments);
				if ($this->content === null) {
					trigger_error(debug::_('APP_ROUTE_METHOD_RETURN_MISSING', $route_class, $route_method), E_USER_WARNING);
					$this->content = '';
				}
				// $this-> = [$route_object, $route_method, $arguments];
			} else {
				/* console::log('page404');
				exit; */
				throw new Exception(debug::_('APP_INIT_ROUTE_PAGE_NOT_FOUND', $uri), E_WARNING);
			}
		}
	}

	public function dispatch($socket, $msg) {
		$msg = json_decode($msg);
		// $socket;

		// $msg->channel;
		// $msg->event;
		// $data = $msg->data;

		$this->logger->start();

		/* if ($msg->channel === 'test') {
			$userSession = session::getInstance($msg->sessionId);
			$session = $userSession->channel('global');
			
			if ($msg->event === 'store.session') {
			
				$session->value = $msg->data; // $_SESSION['global_a'] = $msg->data;
				
			} else if ($msg->event === 'get.session') {
			
				// $msg->data;
				
				$socket->send(json_encode([
					'channel' => $msg->channel,
					'event' => 'updates.session',
					'data' => [
						'value' => $session->value ?? '', // $_SESSION['global_a']
						// 'session_id' => $userSession->getId() // if you want to test correctness of session_id()
					]
				], JSON_UNESCAPED_UNICODE));
				
			}
		} */

		ob_start();
		$this->logger->dumpLog();
		$log = ob_get_clean();

		$this->logger->stop();

		$socket->send(json_encode([
			'channel' => 'debug',
			'event' => 'updates.messages',
			'data' => $log
		], JSON_UNESCAPED_UNICODE));


		
		// echo behavior for testing
		
		/* $data = $msg->data;
		$data->text = 'echo: '.$data->text;
		$connection->send(json_encode([
			'channel' => $msg->channel,
			'event' => $msg->event,
			'data' => $data
		], JSON_UNESCAPED_UNICODE)); */
	}

	public function redirect($uri) {
		/* if (is_ajax) {
			trigger_error(debug::_('APP_REDIRECT_NOT_ALLOWED_IN_AJAX'), E_WARNING);
			return;
		} */
		if ($this->debug) {
			debug::saveLog();
		}
		header('Location: '.$uri);
		exit;
	}

	public function run($params) {
		$this->init($params);
		if (!$this->daemon) {
			$this->render();
		}
	}

	public function render() {
		$layout_path = $this->path.'/layouts/'.$this->layout;
		if (!is_file($layout_path)) {
			throw new Exception( debug::_('APP_RENDER_LAYOUT_FILE_NOT_FOUND', $layout_path), E_WARNING);
		}
		include($layout_path);
		return true;
	}

}