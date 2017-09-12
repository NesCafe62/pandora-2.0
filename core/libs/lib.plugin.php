<?php
namespace core\libs;

use console;

use core;
use Exception;
use debug;

class plugin {

	protected $app = null;

	private $path = '';
	private $viewPath = '';

	public $request;

	public function __construct($app) {
		$plugin_name = get_called_class();
		if (isset(self::$instances[$plugin_name])) {
			throw new Exception(debug::_('PLUGIN_CONSTRUCT_INSTANCE_ALREADY_EXIST'), E_WARNING);
		}
		$this->app = $app;
		$this->request = $app->request;
		$this->path = str_replace('\\', '/', $plugin_name).'/'; // preg_replace('#/[^/]*$#', '/',
		$this->viewPath = $this->path.'views/';
		self::$instances[$plugin_name] = $this; // careful multiple instances can be created.. and old value will be overwritten (should we check and throw Exception or just not to overwrite instance?)
	}

	protected static $instances = [];

	protected $session = null;

	public function session($session_id = null) {
		$plugin_name = get_called_class();
		if (!$this->session) {
			$session = session::getInstance($session_id);
			$this->session = $session->channel($plugin_name);
		}
		return $this->session;
	}

	public static function instance() {
		$plugin_name = get_called_class();
		if (!isset(self::$instances[$plugin_name])) {
			$app = core::app();
			new $plugin_name($app);
		}
		return self::$instances[$plugin_name];
	}

	public function redirect($uri) {
		$this->app->redirect($uri);
	}

	public function render($view, $params, $buffering = true) {
		$viewFilename = $this->viewPath.$view.'.php';
		if (!is_file($viewFilename)) {
			throw new Exception(debug::_('PLUGIN_RENDER_VIEW_NOT_FOUND', $viewFilename), E_WARNING);
		}

		$app = $this->app;
		if ($params) {
			extract($params, EXTR_SKIP);
		}

		if ($buffering) {
			ob_start();
		}
		include($viewFilename);
		if ($buffering) {
			return ob_get_clean();
		}
	}

}