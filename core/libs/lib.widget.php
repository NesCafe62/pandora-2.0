<?php
namespace core\libs;

use console;

use Exception;
use debug;
use core;

class widget {

	public $view;

	protected $path = '';

	protected $viewPath = '';

	public $app;

	public $request;

	public function __construct() {
		$class_name = get_called_class();
		preg_match('#[^\\\\]*$#', $class_name, $matches);
		$widget_name = $matches[0];
		$this->path = str_replace('\\', '/', $class_name).'/';
		$this->viewPath = $this->path.'views/';
		$this->view = $widget_name;
		if (!isset(self::$instances[$class_name])) {
			self::$instances[$class_name] = $this;
		}
	}

	public function render($params = [], $view = '') {
		$view = ($view) ? $view : $this->view;
		$buffering = $params['buffering'] ?? true;
		$viewFilename = $this->viewPath.$view.'.php';
		if (!is_file($viewFilename)) {
			throw new Exception(debug::_('WIDGET_RENDER_VIEW_NOT_FOUND', $viewFilename), E_WARNING);
		}

		$app = core::app();
		$this->app = $app;
		$this->request = $app->request;
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

	protected static $instances = [];

	public static function instance() {
		$widget_name = get_called_class();
		if (!isset(self::$instances[$widget_name])) {
			return new $widget_name();
			// trigger_error(debug::_('WIDGET_INSTANCE_WAS_NOT_CREATED'), E_USER_WARNING);
			// return null;
		}
		return self::$instances[$widget_name];
	}

}