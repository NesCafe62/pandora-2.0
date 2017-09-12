<?php
namespace core\libs;

class environment {

	public static function get($param_name, $default = '') {
		return $_SERVER[$param_name] ?? $default;
	}

}