<?php
namespace core\libs;

class html {

	public static function class($class) {
		if (!is_array($class)) {
			$class = explode(' ', trim($class));
		}
		if (!$class) {
			return '';
		}
		return ' class="'.implode(' ', $class).'"';
	}

	public static function attribs($attribs, $data = false) {
		if (!$attribs) {
			return '';
		}
		$attribsHtml = [];
		foreach ($attribs as $attrib => $value) {
			if ($data) {
				$attrib = 'data-'.$attrib;
			}
			$attribsHtml[] = $attrib.'='.$value;
		}
		return ' '.implode(' ', $attribsHtml);
	}

	public static function dump($var, $pre = true) {
		ob_start();
		var_dump($var);
		$dump = ob_get_clean();
		if ($pre) {
			$dump = '<pre>'.$dump.'</pre>';
		}
		return $dump;
	}

	public static function toJson($obj) {
		return str_replace('"','\'', json_encode($obj, JSON_UNESCAPED_UNICODE));
	}

}