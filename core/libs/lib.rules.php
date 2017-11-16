<?php
namespace core\libs;

use Exception;
use debug;

use console;

class rules {

	private static $rules = [];

	public static function ruleInt(&$value, $params) {
		$valid = preg_match('/^[+-]?[0-9]+$/', $value);
		$message = '';
		if (!$valid) {
			$message = 'Введите целое число';
		} else {
			$value = (int) $value;
		}
		return [$valid, $message];
	}

	public static function ruleFile(&$value, $params) {
		$value = request::file($params['field_name']);
		$valid = true;
		$message = '';
		return [$valid, $message];
	}

	public static function ruleEmail($value, $params) {
		$valid = preg_match('/^[\w\.-_]+@[\w\.-_]+\.[\w\.]+$/', $value);
		$message = '';
		if (!$valid) {
			$message = 'Введите адрес электронной почты в правильном формате, напрмиер: kelThuzad@undead.com';
		}
		return [$valid, $message];
	}

	public static function ruleRegexp($value, $params) {
		if (!isset($params['param'])) {
			throw new Exception(debug::_('RULES_RULE_REGEXP_PATTERN_NOT_SET'), E_WARNING);
		}
		$valid = preg_match($params['param'], $value);
		$message = '';
		if (!$valid) {
			$message = 'Неверный вормат поля "{$field_name}"';
		}
		return [$valid, $message];
	}

	public static function ruleFloat(&$value, $params) {
		$valid = preg_match('/^[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?$/', $value);
		$message = '';
		if (!$valid) {
			$message = 'Введите целое или дробное число';
		} else {
			$value = (float) $value;
		}
		return [$valid, $message];
	}

	public static function ruleTrim(&$value, $params) {
		$value = trim($value);
		return [true, ''];
	}

	public static function ruleRequired($value, $params) {
		$valid = ($value != '');
		$message = '';
		if (!$valid) {
			$message = 'Заполните поле "{$field_name}"';
		}
		return [$valid, $message];
	}

	public static function add($rule, $rulesCallback) {
		$rule = 'rule'.ucfirst($rule);
		if (method_exists(__CLASS__, $rule)) {
			throw new Exception(debug::_('RULES_ADD_DEFAULT_RULE_CAN_NOT_OVERRIDE'), E_WARNING);
		}
		if (!is_function($rulesCallback)) {
			throw new Exception(debug::_('RULES_ADD_CALLBACK_MUST_BE_FUNCTION'), E_WARNING);
		}
		self::$rules[$rule] = $rulesCallback;
	}

	public static function validate($rule, &$value, $params) {
		$rule = 'rule'.ucfirst($rule);
		if (method_exists(__CLASS__, $rule)) {
			$validator = [__CLASS__, $rule];
		} else {
			$validator = self::$rules[$rule] ?? null;
			if (!$validator) {
				throw new Exception(debug::_('RULES_VALIDATE_RULE_NOT_REGISTERED', $rule), E_WARNING);
			}
		}
		return call_user_func_array($validator, [&$value, $params]);
	}

}