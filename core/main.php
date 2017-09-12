<?php

use core\libs\{app, session};

class console {

	public static function log($var, $label = '') {
		/* echo '<pre>';
			var_dump($var);
		echo '</pre>';
		echo '<br/>'; */
		ob_start();
		var_dump($var);
		$dump = ob_get_clean();
		if ($label) {
			$dump = '<b>'.$label.':</b> '.$dump;
		}
		[$file, $line] = debug::getCalledMethod(1);
		debug::addLog([
			'type' => debug::E_CONSOLE,
			'message' => '<pre>'.$dump.'</pre>',
			'file' => $file,
			'line' => $line
		]);
	}

}

class debug {

	const E_CONSOLE = E_USER_NOTICE;

	private static $errorTypes = [
		E_STRICT => 'strict',
		E_DEPRECATED => 'deprecated',
		E_USER_DEPRECATED => 'deprecated',
		E_NOTICE => 'notice',
		E_USER_NOTICE => 'console', // 'notice',
		E_WARNING => 'warning',
		E_USER_WARNING => 'warning',
		E_PARSE => 'fatal error', // 'parse error'
		E_USER_ERROR => 'error',
		E_RECOVERABLE_ERROR => 'recoverable error',
		E_CORE_ERROR => 'core error',
		E_COMPILE_ERROR => 'fatal error', // 'compile error'
		E_CORE_WARNING => 'core warning',
		E_COMPILE_WARNING => 'compile warning',
		E_ERROR => 'fatal error'
	];

	public static function _out_error($e) {
		$msg = $e['message'] ?? '';
		$file = $e['file'] ?? '';
		$line = $e['line'] ?? '';

		$msg_type = self::$errorTypes[$e['type']] ?? 'error';

		// if (isset($e['label']) && ($e['label'] !== '')) {
			// $msg = '<span class="label">' . $e['label'] . '</span> ' . $msg;
		// }

		if (!empty($e['label'])) {
			$msg = '<span class="label">'.$e['label'].'</span> '.$msg;
		}

		/* $html = '<b>'.ucfirst($msg_type).'</b>: '.$msg;
		if ($file !== '') {
			$html .= ' in <b>'.$file.'</b>';
		}
		if ($line !== '') {
			$html .= ' on line <b>'.$line.'</b>';
		} */

		$html = '<b>'.ucfirst($msg_type).'</b>&nbsp;&nbsp;&nbsp;&nbsp; ';
		if ($file !== '') {
			$html .= '<b>'.$file.'</b>';
		}
		if ($line !== '') {
			$html .= '&nbsp;&nbsp; on line&nbsp; <b>'.$line.'</b>';
		}
		$html .= ': '.$msg;

		return $html;
	}

	public static function _($err, string ...$params) {
		$var = 'ERR_'.$err;
		foreach ($params as &$param) {
			$param = "'".$param."'";
		}
		array_unshift($params, $var);
		$res = implode(' ',$params);
		return $res;
	}

	public static function getCalledMethod($level = 2) {
		$traces = debug_backtrace();
		$file = null;
		$line = null;
		if (isset($traces[$level])) {
			$m = $traces[$level];
			$file = '/'.trimLeft($m['file'], core::root().'/');
			$line = $m['line'];
		}
		return [$file, $line];
	}

	private static $loggers = [];

	public static function addLogger($logger) {
		self::$loggers[] = $logger;
	}

	public static function removeLogger($logger) {
		foreach (self::$loggers as $i => $logger_item) {
			if ($logger_item === $logger) {
				unset(self::$loggers[$i]);
				break;
			}
		}
	}

	public static function addLog($message) {
		foreach (self::$loggers as $logger) {
			$logger->addLog($message);
		}
	}

	public static function getLog() {
		return self::$logger->getLog();
	}

	public static function dumpLog() {
		self::$logger->dumpLog();
	}

	public static function errorHandler($type, $message, $file, $line) {
		/* echo self::_out_error([
			'type' => $type,
			'message' => $message,
			'file' => $file,
			'line' => $line
		]).'<br/>'; */
		self::addLog([
			'type' => $type,
			'message' => $message,
			'file' => $file,
			'line' => $line
		]);
	}

	public static function exceptionHandler($e) {
		self::errorHandler($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
		/* echo self::_out_error([
			'type' => $e->getCode(),
			'message' => $e->getMessage(),
			'file' => $e->getFile(),
			'line' => $e->getLine()
		]).'<br/>'; */
	}

	// private static $debug = true;

	private static $logger;

	private static $session;

	public static function saveLog() {
		self::$session->messages = self::getLog();
	}

	public static function init(/*$debug */) {
		// self::$debug = $debug;
		self::$logger = new logger();
		set_error_handler(['debug','errorHandler']);
		set_exception_handler(['debug','exceptionHandler']);

		require_once(__DIR__.'/libs/lib.session.php');
		self::$session = session::getChannel('debug');
		$messages = self::$session->messages ?? [];
		if ($messages) {
			unset(self::$session->messages);
			self::$logger->addMessages($messages, false);
		}
	}

}

class logger {

	private $messagesLog = [];

	private $params;

	public function __construct($auto_start = true, $params = []) {
		$this->params = $params;
		if ($auto_start) {
			$this->start();
		}
	}

	public function start() {
		debug::addLogger($this);
	}

	public function stop($flush = true) {
		if ($flush) {
			$this->messagesLog = [];
		}
		debug::removeLogger($this);
	}

	public function addLog($message) {
		$this->messagesLog[] = $message;
	}

	public function addMessages($messages, $append = true) {
		if ($messages) {
			if ($append) {
				$this->messagesLog = array_merge($this->messagesLog, $messages);
			} else {
				$this->messagesLog = array_merge($messages, $this->messagesLog);
			}
		}
	}

	public function dumpLog() {
		foreach ($this->messagesLog as $message) {
			echo debug::_out_error($message).'<br/>';
		}
	}

	public function getLog() {
		return $this->messagesLog;
	}

}

// $logger = new logger();

// console::log('2143');

// $dump = $logger->dumpLog();

// $logger->getLog();

class core {

	private static function autoload($class_name) {
		$path = str_replace('\\', '/', $class_name);

		if (preg_match('#\\bplugins/([^/]+)$#', $path, $matches)) {
			$plugin_name = $matches[1];
		} else {
			$plugin_name = '';
		}

		if (preg_match('#\\bwidgets/([^/]+)$#', $path, $matches)) {
			$widget_name = $matches[1];
		} else {
			$widget_name = '';
		}

		$controller_name = '';
		if (!$plugin_name) {
			$path = preg_replace_callback('#([^/]+)Controller$#', function($matches) use (&$controller_name) {
				$controller_name = strtolower($matches[1]);
				return 'controller.'.$controller_name;
			}, $path);
		}

		$path = str_replace([
			'libs/',
			// 'apps/',
			'core/plugins/',
			'plugins/',
			'widgets/'
		], [
			'libs/lib.',
			// ($plugin_name || $controller_name) ? 'apps/' : 'apps/app.',
			'core.plugins/',
			($plugin_name) ? 'plugins/'.$plugin_name.'/plugin.' : 'plugins/',
			'widgets/'.$widget_name.'/widget.'
		], $path).'.php';

		if (is_file($path)) {
			require_once($path);
			if (class_exists($class_name, false)) {
				return true;
			}
		}
		return false;
	}

	private static $path_root = '';

	public static function root() {
		return self::$path_root;
	}

	// application instance
	private static $app = null;

	public static function app() {
		return self::$app;
	}

	public static function shutdownHandler($e) {
		// $e = error_get_last();
		if ($e) {
			/* while (ob_get_level() > 0) {
				ob_end_clean();
			} */
			debug::dumpLog();
			exit;
		}
	}

	public static function run($config) {
		// time start
		$start_time = microtime(true);

		// autoload
		spl_autoload_register(['core','autoload']);

		// init error handlers
		debug::init(); // $config['debug'] ?? true);
		// register_shutdown_function(['core','shutdownHandler']);

		try {
			// errors level
			error_reporting(E_ALL);
			ini_set('display_errors','1');

			// encoding
			mb_internal_encoding('UTF-8');

			// include functions lib
			include(__DIR__.'/libs/lib.functions.php');


			self::$path_root = trimRight(__DIR__, '/core');

			// if (!isset($config['path'])) {
				// $config['path'] = remove_left(getcwd(), self::$path_root.'/');
			// }

			// $config['path'] = $config['path'] ?? trimLeft(getcwd(), self::$path_root.'/');
			$app_path = trimLeft(getcwd(), self::$path_root.'/');

			// var_dump(self::$path_root);
			// exit;

			// set root directory
			chdir(self::$path_root);

			// create application instance
			$app = new app($config);
			self::$app = $app;

			// console::log(self::$app->getConfig());
			// console::log(self::$app->layout);

			// console::log($app_path);

			// initialise application
			$app->run([
				'path' => $app_path,
				'start_time' => $start_time
			]);
		} catch (Throwable $e) {
			debug::exceptionHandler($e);
			self::shutdownHandler($e);
		}

		// time end
	}

}