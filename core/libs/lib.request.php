<?php
namespace core\libs;

use console;
use core;

class httpRequest {

	public function post($var, $form_name = '') {
		if ($form_name) {
			return $_POST[$form_name][$var] ?? null;
		}
		return $_POST[$var] ?? null;
	}

	public function file($var, $form_name = '') {
		if ($form_name) {
			return $_FILES[$form_name][$var] ?? null;
		}
		return $_FILES[$var] ?? null;
	}

	// public function files($var, $form_name = '') {

	public function get($var, $form_name = '') {
		if ($form_name) {
			return $_GET[$form_name][$var] ?? null;
		}
		return $_GET[$var] ?? null;
	}

}

class request {

	public static function init() {
		return new httpRequest();
	}

	public static function post($var, $form_name = '') {
		return core::app()->request->post($var, $form_name);
	}

	public static function file($var, $form_name = '') {
		return core::app()->request->file($var, $form_name);
	}

	// public static function files($var, $form_name = '') {

	public static function get($var, $form_name = '') {
		return core::app()->request->get($var, $form_name);
	}

}

// $controller->post() = plugin->request->post()  plugin->request <= app->request
//
// request::post(); = core::app()->request->post()
// request::get();