<?php namespace apps\app\plugins\books\controllers;

use apps\app\plugins\books\models\book;
use core\libs\controller;
use core\widgets\form;

class bookController extends controller {

	public function routeItems() {
		$books = book::findAll();
		return $this->render('items', ['books' => $books]);
	}

	public function routeItem($book_id) {
		$book = book::findOne(['id' => $book_id]);
		return $this->render('item', ['book' => $book]);
	}

	public function routeAdd() {
		$book = new book();
		if ($book->load && $book->save()) {
			$this->redirect('/books/'.$book->id);
		}
		$form = new form($book);
		return $this->render('form', ['form' => $form, 'uriBack' => '/books']);
	}

	public function routeUpdate($book_id) {
		$book = book::findOne(['id' => $book_id]);
		if ($book->load() && $book->save()) {
			$this->redirect('/books/'.$book->id);
		}
		$form = new form($book);
		return $this->render('form', ['form' => $form, 'uriBack' => '/books']);
	}

	public function routeDelete() {
		$book_id = request::post('id');
		if ($book_id) {
			$book = book::findOne(['id' => $book_id]);
			if ($book) {
				$book->delete();
			}
		}
		$this->redirect('/books');
	}

}