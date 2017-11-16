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
		$this->path = self::getWidgetPath($class_name);
		preg_match('#[^\\\\]*$#', $class_name, $matches);
		$widget_name = $matches[0];
		$this->viewPath = $this->path.'views/';
		$this->view = $widget_name;
		if (!isset(self::$instances[$class_name])) {
			self::$instances[$class_name] = $this;
		}
	}

	private static function getWidgetPath($class_name) {
		return str_replace(['core\\widgets','\\'], ['core.widgets','/'], $class_name).'/';
	}

	public function render($_params = [], $viewName = '') {
		$widget_class = get_called_class();

		$viewName = ($viewName) ? $viewName : $this->view;
		$buffering = $params['buffering'] ?? true;
		$viewPath = $this->viewPath;
		$viewFilename = $viewPath.$viewName.'.php';

		while (!is_file($viewFilename)) {
			$widget_class = get_parent_class($widget_class);
			if ($widget_class == 'core\\libs\\widget') {
				trigger_error(debug::_('WIDGET_RENDER_VIEW_NOT_FOUND', $viewFilename), E_USER_0WARNING);
				return '';
			}
			$viewPath = self::getWidgetPath($widget_class).'views/';
			$viewFilename = $viewPath.$viewName.'.php';
		}

		// $widget_name = get_parent_class($widget_name);

		/* if (!is_file($viewFilename)) {
			throw new Exception(debug::_('WIDGET_RENDER_VIEW_NOT_FOUND', $viewFilename), E_WARNING);
		} */

		$app = core::app();
		$this->app = $app;
		$this->request = $app->request;
		if ($_params) {
			extract($_params, EXTR_SKIP);
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