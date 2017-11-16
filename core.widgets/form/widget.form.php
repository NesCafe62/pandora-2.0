<?php
namespace core\widgets;

use core\libs\widget;
use Exception;

use console;

class form extends widget {

	protected $model = null;

	public $isUpdate = false;

	public function __construct($model = null) {
		parent::__construct();
		$this->model = $model;
		if ($model) {
			$this->isUpdate = $model->isUpdate();
		}
	}

	public function message() {
		$msg = $this->model->getMessage();
		if (!$msg) {
			return;
		}
		[$type, $message] = $msg;
		echo '<div class="form-message type-'.$type.'">'.$message.'</div>';
	}

	public function field($type, $name, $params = []) {

		$label = $params['label'] ?? '';
		$noWrap = $params['noWrap'] ?? '';
		if ($this->model) {
			$value = $this->model->$name ?? '';
			$validateMessage = $this->model->getValidateMessage($name);
		} else {
			$value = '';
			$validateMessage = '';
		}

		$fieldView = 'field.'.$type;

		$fieldHtml = $this->render([
			'name' => $name,
			'value' => $value,
			'params' => $params
		], $fieldView);

		echo $this->render([
			'label' => $label,
			'name' => $name,
			'isEmpty' => ($value == ''),
			'noWrap' => $noWrap,
			'fieldType' => $type,
			'fieldHtml' => $fieldHtml,
			'validateMessage' => $validateMessage
		], 'field');
	}

	// ['template' => '<div>{INPUT}{ERROR}</div>']
	public function input($name, $params = []) {
		$this->field('input', $name, $params);
	}

	public function textarea($name, $params = []) {
		$this->field('textarea', $name, $params);
	}

	public function select($name, $params = []) {
		$this->field('select', $name, $params);
	}

	public function groupSelect($name, $params = []) {
		$this->field('groupSelect', $name, $params);
	}

	public function checkbox($name, $params = []) {
		$this->field('checkbox', $name, $params);
	}

	public function file($name, $params = []) {
		$this->field('file', $name, $params);
	}

	public function password($name, $params = []) {
		$this->field('password', $name, $params);
	}

	public function hidden($name, $params = []) {
		$params['noWrap'] = true;
		$this->field('hidden', $name, $params);
	}

	public function buttonSubmit($label, $params = []) {
		$class = $params['class'] ?? '';
		echo '<button type="submit"'.($class ? ' class="'.$class.'"' : '').'>'.$label.'</button>';
		// echo $this->render([], 'button.submit');
	}

	public function buttonLink($label, $uri, $params = []) {
		$uri = $uri ?? '#';
		$class = $params['class'] ?? '';
		echo '<button type="button"'.($class ? ' class="'.$class.'"' : '').' onclick="window.location.href=\''.$uri.'\'">'.$label.'</button>';
		// echo $this->render([], 'button.link');
	}

	public function button($type, $label, $params) {
		if ($type == 'submit') {
			$this->buttonSubmit($label, $params);
		} else if ($type == 'link') {
			$this->buttonLink($label, $params['uri'] ?? null, $params);
		} else {
			echo '<button type="button">'.$label.'</button>';
		}
	}

}