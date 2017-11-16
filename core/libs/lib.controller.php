<?php
namespace core\libs;

use Exception;

use console;
use debug;

class controller {

	protected $plugin;

	protected $app;

	private $viewPath = '';

	public function post($var) {
		return $this->plugin->request->post($var);
	}

	public function get($var) {
		return $this->plugin->request->get($var);
	}

	public function __construct($plugin) {
		if (!$plugin) {
			throw new Exception(debug::_('CONTROLLER_CONSTRUCT_PLUGIN_NOT_SET'), E_WARNING);
		}
		$class_name = get_called_class();
		preg_match('#[^\\\\]*$#', $class_name, $matches);
		$controller_name = $matches[0];
		$this->viewPath = str_replace('Controller', '', $controller_name).'/';
		$this->plugin = $plugin;
		$this->app = $this->plugin->app;
		if (!isset(self::$instances[$class_name])) {
			self::$instances[$class_name] = $this;
		}
	}

	protected static $instances = [];

	public static function instance() {
		$controller_name = get_called_class();
		if (!isset(self::$instances[$controller_name])) {
			preg_match('#^(.*)\\\\controllers#', $controller_name, $matches);
			$plugin_name = $matches[1];
			new $controller_name($plugin_name::instance());
			//trigger_error(debug::_('CONTROLLER_INSTANCE_WAS_NOT_CREATED', $controller_name), E_WARNING);
			//return null;
		}
		return self::$instances[$controller_name];
	}

	public function redirect($uri) {
		$this->plugin->redirect($uri);
	}

	public function render($view, $params = [], $buffering = true) {
		$params['controller'] = $this;
		return $this->plugin->render($this->viewPath.$view, $params, $buffering);
	}

}
