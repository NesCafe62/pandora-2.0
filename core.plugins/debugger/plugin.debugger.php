<?php
namespace core\plugins;

use core\libs\plugin;

use \debug;

class debugger extends plugin {

	private static function formatSeconds($value) {
		if ($value < 1) {
			return round($value*1000, 1).' ms';
		} else {
			return round($value,4).' s';
		}
	}

	private static $errorChannel = [
		E_STRICT => 'notice',
		E_DEPRECATED => 'notice',
		E_USER_DEPRECATED => 'notice',
		E_NOTICE => 'notice',
		E_USER_NOTICE => 'console',
		E_WARNING => 'warning',
		E_USER_WARNING => 'warning',
		E_PARSE => 'error',
		E_USER_ERROR => 'error',
		E_RECOVERABLE_ERROR => 'error',
		E_CORE_ERROR => 'error',
		E_COMPILE_ERROR => 'error',
		E_CORE_WARNING => 'warning',
		E_COMPILE_WARNING => 'warning',
		E_ERROR => 'error'
	];

	public function beforeAppRender() {
		$this->app->style('/core.plugins/debugger/css/debugger.css');
	}

	public function renderProfiler() {
		$messages = array_map(function($message) {
			$message = (object) $message;
			$message->typeLabel = ucfirst($message->typeLabel ?? debug::getErrorTypeName($message->type));
			$message->label = $message->label ?? '';
			$message->channel = $message->channel ?? self::$errorChannel[$message->type] ?? 'console';
			$message->line = 'строка <span>'.$message->line.'</span>';
			return $message;
		}, debug::getLog());
		$timePageGenerated = self::formatSeconds($this->app->getElapsedTime());
		echo $this->render('profiler', ['messages' => $messages, 'timePageGenerated' => $timePageGenerated]);
	}

}