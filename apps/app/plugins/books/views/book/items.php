<?php

use apps\app\widgets\test as widgetTest;

?>
<div class="widget-test">
	<?= widgetTest::instance()->render() ?>
</div>

<div class="books">
<?php foreach ($books as $book):?>
	<div class="book">
		<div class="book-background">
			<a href="/books/<?= $book->id ?>/update" class="id">#<?= $book->id ?></a>
			<div class="title"><?= $book->title ?></div>
			<div class="description"><?= $book->description ?></div>
			<div class="icon"><?= $book->icon ?></div>
		</div>
	</div>

<?php endforeach;?>
</div>
