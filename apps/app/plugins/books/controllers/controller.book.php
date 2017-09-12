<?php
namespace apps\app\plugins\books\controllers;

use apps\app\plugins\books\models\book;
use console;
use core\libs\controller;
// use core\libs\form;
use apps\app\widgets\form;

/* tasks

# query column, value functions

# widgets

*/



/* class forumController extends controller {

	public function viewForum($category_id = null) {
		$forumThemes = forumThemes::getThemes($category_id);

		return $this->render('items', ['forumThemes' => $forumThemes]);
	}

} */

// echo forumController::instance()->viewForum($category_id);


class bookController extends controller {

	public function routeItems() {
		$books = book::find()->orderBy(['id DESC'])->all();

		return $this->render('items', ['books' => $books]);
	}

	public function routeItem($book_id) {
		$book = book::findOne(['id' => $book_id]);

		return $this->render('item', ['book' => $book]);
	}

	public function routeAdd() {
		$book = new book();

		if ($book->load() && $book->save()) {
			console::log('Успешно!');
		}
		// console::log($book->getValidateMessages());

		$form = new form($book);
		return $this->render('add', ['book' => $book, 'form' => $form, 'uri_back' => '/books']);
	}

	public function routeUpdate($id) {
		$book = book::findOne(['id' => $id]);

		if ($book->load() && $book->save()) {
			console::log('success');
			$this->redirect('/books/'.$id);
		}
		// console::log($book->getValidateMessages());

		$form = new form($book);
		return $this->render('update', ['book' => $book, 'form' => $form, 'uri_back' => '/books']);
	}

	public function routeDelete() {

	}

}