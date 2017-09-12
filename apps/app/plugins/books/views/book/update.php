<form method="post" action="<?= $app->uri ?>">

	<?php


	?>
	<label>title</label>
	<input type="text" name="title" value="<?= $book->title ?>"/>
	<label>description</label>
	<input type="text" name="description" value="<?= $book->description ?>"/>
	<label>icon</label>
	<input type="text" name="icon" value="<?= $book->icon ?>"/>
	<input type="hidden" name="id" value="<?= $book->id ?>"/>
	<button type="submit">Сохранить</button>
	<button type="button" onclick="window.location.href='<?= $uri_back ?>'">Отмена</button>
</form>