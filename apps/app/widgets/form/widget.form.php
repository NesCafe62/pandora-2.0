<?php
namespace apps\app\widgets;

use core\libs\widget;

class form extends widget {

	protected $model = null;

	public function __construct($model = null) {
		$this->model = $model;
	}

	public function fieldInput($name, $value) {
		$value = $value ?? '';
		echo '<input type="text" name="'.$name.'" value="'.$value.'"/>';
	}

	public function field($type, $name, $params = []) {
		$label = $params['label'] ?? '';
		if ($this->model) {
			$value = $this->model->$name ?? '';
		} else {
			$value = '';
		}

		if ($label) {
			echo '<label>';
				echo '<div class="label">'.$label.'</div>';
		}

		if ($type == 'input') {
			$this->fieldInput($name, $value);
		}

		if ($label) {
			echo '</label>';
		}
	}

	// ['template' => '<div>{INPUT}{ERROR}</div>']
	public function input($name, $params = []) {
		$this->field('input', $name, $params);
	}

	public function buttonSubmit($label) {
		echo '<button type="submit">'.$label.'</button>';
	}

	public function buttonLink($label, $uri) {
		$uri = $uri ?? '#';
		echo '<button type="button" onclick="window.location.href=\''.$uri.'\'">'.$label.'</button>';
	}

	public function button($type, $label, $params) {
		if ($type == 'submit') {
			$this->buttonSubmit($label);
		} else if ($type == 'link') {
			$this->buttonLink($label, $params['uri'] ?? null);
		} else {
			echo '<button type="button">'.$label.'</button>';
		}
	}

}