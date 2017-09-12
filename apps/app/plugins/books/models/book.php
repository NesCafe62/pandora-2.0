<?php
namespace apps\app\plugins\books\models;

use core\libs\model;

// use core\libs\rules;
/* rules::add('test', function($value, $params) {
	;
}); */

class book extends model {

//	public  $id;
//	public  $title;
//	public  $description;
//	public  $icon;


	protected static $table = 'book';

	public static function getFields() {
		return [
			'title',
			'description',
			'icon'
		];
	}

	public static function rules() {
		return [
//			'id' => ['required'],
			'title' => ['required','trim'],
			'description' => ['required','trim'],
			'icon' => ['required','trim'],
//			'title' => ['required', 'string' => ['minLength' => 3] ],
//			'description' => [], // 'test'],
//			'icon' => ['required'], // 'file'=>['types' => ['jpg','png']]

//			'' => ['type' => 'select'],
//			'' => ['type' => 'hidden']
/*			['icon', 'required'],
			['icon', 'varchar'],

			['title', 'required'],
			['title', 'varchar'],

			['title', [['varchar','min' => 4, 'max' => 10],'required']],

			[['title', 'icon'], ['varchar', 'icon']],*/

		];
	}


}