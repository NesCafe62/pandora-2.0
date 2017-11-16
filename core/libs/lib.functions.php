<?php

// returns true if $substr is at the beginning of $str
function startsWith($str, $substr) {
	return (strpos($str, $substr) === 0);
}

// returns true if $substr is at the end of $str
function endsWith($str, $substr) {
	return (strrpos($str, $substr) === strlen($str) - strlen($substr));
}

// removes $substr from the beginning of $str and returns result
function trimLeft($str, $substr) {
	if ($substr !== '') {
		if (startsWith($str, $substr)) $str = substr($str, strlen($substr));
		if ($str === false) $str = '';
	}
	return $str;
}

// removes $substr from the end of $str and returns result
function trimRight($str, $substr) {
	if ($substr !== '') {
		if (endsWith($str, $substr)) $str = substr($str, 0, strlen($str) - strlen($substr));
		if ($str === false) $str = '';
	}
	return $str;
}

function deleteLeft($str, $len) {
	return substr($str, $len);
}

function deleteRight($str, $len) {
	return substr($str, 0, strlen($str) - $len);
}

function cutLeft($str, $len) {
	return substr($str, 0, $len);
}

function cutRight($str, $len) {
	return substr($str, -$len);
}

function extend($src, $dst) {
	$is_obj = is_object($src);
	if (is_object($dst)) {
		$dst = (array) $dst;
	}
	foreach ($dst as $key => $val) {
		if (!$is_obj) {
			if (!isset($src[$key])) {
				$src[$key] = $val;
			}
		} else {
			if (!isset($src->$key)) {
				$src->$key = $val;
			}
		}
	}
	return $src;
}

function is_function($func) {
	return is_object($func) && $func instanceof Closure;
}