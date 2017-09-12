<?php

return [
	'bookController' => [
		/* '/books' => 'routeItems',
	// 	'/books :post{action=delete}' => 'routeDelete',
			or
		'/books' => ['routeDelete', 'action' => 'delete'],
		'/books/$book_id' => 'routeItem',
		'/books/$book_id/edit' => 'routeEdit',
		'/books/add' => 'routeAdd',
		'/books/update :post{id}' => 'routeUpdate', */

		'routeItems' => '/books',
		'routeItem' => '/books/$book_id',
		'routeAdd' => '/books/add',
		'routeUpdate' => '/books/$book_id/update', // 'post' => ['action' => 'update']
		'routeDelete' => ['/books', 'post' => ['action' => 'delete'] ],
	]
];

// permissions.php
/* return [
	'bookController' => [
		'routeItems' => 'books.items',
		'routeDelete' => 'books.delete',
		'routeItem' => 'books.view',
		'routeAdd' => 'books.add',
		'routeUpdate' => 'books.update',
	]
]; */