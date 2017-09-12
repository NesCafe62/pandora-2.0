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

	public function renderProfiler() {
		echo '<div class="debugger">';
			debug::dumpLog();
			// $messages = debug::getLog();
			$page_generated = self::formatSeconds($this->app->getElapsedTime());
			echo '<div class="page-time">page generated: '.$page_generated.'</div>';
		echo '</div>';
	}

}