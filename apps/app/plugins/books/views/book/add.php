<form method="post" action="<?= $app->uri ?>">

	<?php

	$form->input('title', ['label' => 'Заголовок']);

	$form->input('description', ['label' => 'Описание']);

	$form->input('icon', ['label' => 'Изображение']);

	$form->buttonSubmit('Добавить');
	$form->buttonLink('Отмена', $uri_back);

	//PDO::FETCH_KEY_PAIR

//
//	one('label')   value('label')
//
//	all(['id' => 'label'])   column(['id' => 'label'])   column('label')

//	$items = Category::find()->select('label')->all() // ->indexBy('id')->column();

	// $form->dropDownList($items);
	?>

	<?php /* <label>title</label>
	<input type="text" name="title"/>
	<div class="error"></div>

	<label>description</label>
	<input type="text" name="description"/>


	<label>icon</label>
	<input type="text" name="icon"/>
	<button type="submit">Добавить</button> */ ?>
</form>