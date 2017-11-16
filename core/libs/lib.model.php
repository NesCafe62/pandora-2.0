<?php
namespace core\libs;

use console;

use core;
use debug;
use Exception;
use PDOException;
use PDO;

// клксс query как обертка для них от предоставляет синтаксис ->where()->all() 

// book::find()->one();

// book::findOne();

// book::find()->where(['id' => 42]);

// book::find()->all();

/* book::find([
	'where' => ['raw' => ['year(date_add(now, interval ? day) ) > 2008', 33] ],
	'order by' => 'create_date',
	'group by' => 'category_id',
	'limit' => 10,
])->all(); */





// вернусь к делу ближе
// сейчас скопирую пример запроса

/* public static function query_sections_subsections() {
	return [
		'debug' => true, // профилирование запроса, вывод в ортладочную консоль сгенерированного sql (отформатированного с отступами) и его времени выполнения
		'table' => 'forum_section',
		'fields' => [
			'id: section_id',
			'title: section_title',
			'position: section_position',
			'icon: section_icon',
		],
		'join' => [
			'table' => 'forum_subsection',
			'on' => 'id = section_id', // автоматически генерируется forum_section.id = forum_subsection.section_id
			'fields' => [
				'id: subsection_id',
				'title',
				'description',
				'position: subsection_position',
			],
			'join' => [
				[
					'table' => 'forum_theme: thm1',
					'on' => 'id = subsection_id',
					'fields' => [
						'id: theme_id',
						'title: theme_title',
					],
					'join' => [
						[
							'table' => 'forum_theme: thm2',
							'on' => [ // склейка по условию and внутри on (or тоже поддерживается)
								'forum_subsection.id' => 'subsection_id',
								'last_message_id <' => 'last_message_id',
							]
						],
						[
							'table' => 'forum_message: msg1',
							'on' => 'last_message_id = id',
							'fields' => [
								'id: message_id',
								'user_id',
								'created_at',
								'content',
							],
							'join' => [
								'table' => 'users',
								'on' => 'user_id = id',
								'fields' => [
									'login: username',
								],
								'join' => [
									'table' => 'user_profile',
									'on' => 'id = id',
									'fields' => [
										'avatar',
									]
								]

							],
						]
					]
				],
				[
					'table' => 'forum_theme: thm3',
					'on' => 'id = subsection_id',
					'fields' => 'count(distinct thm3.id): themes_count', // двоеточие это AS
					'join' => [
						'table' => 'forum_message:msg2', // forum_message as msg2
						'on' => 'id = theme_id',
						'fields' => 'count(msg2.id): messages_count'
					]
				]
			]
		],
		'order by' => 'forum_section.position ASC, forum_section.id ASC, forum_subsection.position ASC',
		'group by' => 'forum_subsection.id, forum_section.id, thm1.id, msg1.id, users.login, user_profile.avatar',
		'where' => ['thm2.id' => null]
	];
} // <<< специально по жирнее выбрал ))) дав стаых версиях древовидный
// и по идее в новом такой же в промежутке когда от модели он в queryGenerator передаваться будет
// там наверно не особо мы оптимально его писали, нам была важна скорость разработки, подшлифовать потом всегда можно

public static function query_subsection_themes() {
		return[
			'table' => 'forum_subsection',
			'fields' => [
				'id:subsection_id',
				'title',
				'description',
				'position: subsection_position',
			],
			'join' => [
				'table' => 'forum_theme',
				'on' => 'id = subsection_id',
				'fields' => [
					'id: theme_id',
					'title',
				]
			],
			'where' => ['forum_subsection.id' => ':id']
		];
}

public static function query_messages() {
	return [
		'table' => 'forum_message',
		'fields' => [
			'id',
			'user_id',
			'content',
			'created_at',
		],
		'join' => [
			'table' => 'users',
			'on' => 'user_id = id',
			'fields'=> [
				'id:user_id',
				'login: username',
			],
			'join' => [
				'table' => 'user_profile',
				'on' => 'id = id',
				'fields' => [
					'avatar',
				],
			],

		],
		'where' => ['theme_id'=> ':theme_id'],
		'order by' => 'created_at ASC',
	];
} */

// нет это просто код из класса ForumModel. на beavers форум делали http://beavers.games/forum
// в идеале нам надо было создать плагин core/forum с минимальным набором функционала и положить его в комплект (устанавливаемых) плагинов к фреймворку
// а потом от него отнаследоваться и допилить тем что отличаться будет (но времени не было на такое)

// ок

// вообще этот код - это просто SQL запрос обычный ? или это часть ядра твоего фреймворка

// я отошел

class query {

	private $db; // ссылка на соединение с базой

	private $itemClass; // имя класса для создания экземпляра при извлечении результата запроса

	private $options = [];

	public function __construct($db, $options, $itemClass = null) {
		$this->db = $db;
		$this->itemClass = $itemClass;
		if (empty($options['table'])) {
			throw new Exception(debug::_('QUERY_CONSTRUCT_OPTION_NOT_SET', 'table'), E_WARNING);
		}
		if (empty($options['fields'])) {
			$options['fields'] = '*'; // <<
		}
		$this->options = $options;
	}

	public function one() {
		[$querySql, $params] = $this->db->generateQuery(extend([
			'query' => 'select'
		], $this->options));
		[$result, $prepare] = $this->db->query($querySql, $params);
		if (!$result) {
			if ($result === false) {
				return false;
			} else {
				return null;
			}
		}
		if ($this->itemClass) {
			$prepare->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, $this->itemClass);
			$result = $prepare->fetch();
			if (!$result) {
				$result = null;
			} else {
				$result->setSaved();
				$result->afterFind();
			}
		} else {
			$result = $prepare->fetch(PDO::FETCH_OBJ);
		}
		return $result;
	}

	public function all() {
		[$querySql, $params] = $this->db->generateQuery(extend([
			'query' => 'select'
		], $this->options));
		[$result, $prepare] = $this->db->query($querySql, $params);
		if (!$result) {
			if ($result === false) {
				return false;
			} else {
				return [];
			}
		}
		if (!class_exists($this->itemClass)) {
			throw new Exception(debug::_('QUERY_ALL_ITEM_CLASS_NOT_FOUND'), E_WARNING);
		}
		if ($this->itemClass) {
			$result = $prepare->fetchAll(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, $this->itemClass);
		} else {
			$result = $prepare->fetchAll(PDO::FETCH_OBJ);
		}
		foreach ($result as $item) {
			$item->setSaved();
			$item->afterFind();
		}
		return $result;
	}

	/* public function join($join) {
		;
	} */

	public function where($where) {
		$this->options['where'] = $where;
		return $this;
	}

	public function orderBy($orderFields) {
		$this->options['order by'] = $orderFields;
		return $this;
	}

	public function limit($limit) {
		$this->options['limit'] = $limit;
		return $this;
	}

}

/* class modelForm {



} */


function stringFormatArgs($string, $params = []) {
	return preg_replace_callback('#\{\$([^}]+)\}#', function($matches) use ($params) {
		$param_name = $matches[1];
		return $params[$param_name] ?? 'PARAM_'.strtoupper($param_name);
	}, $string);
}

class model {

	protected static $table = '';

	protected $fields = [];

	private static $keyFields;
	private static $tableFields;

	private static $initializedModels = [];


	private $unsavedFields = [];

	protected static $autoCreate = false;

	// protected $hasRecord = false;

	protected $formName = '';

	protected static $autoincField = 'id';

	public static function getFields() {
		return [];
	}

	public static function getKeyFields() {
		return ['id'];
	}

	private $isValid = false;
	private $validated = false;

	private $isDeleted = false;

	private $isUpdate = false;

	private $validateMessages = [];

	public function __set($key, $value) {
		$self = get_called_class();
		// console::log($key);
		if (!in_array($key, self::$tableFields[$self])) {
			// throw new Exception(debug::_('MODEL_SET_FIELD_NOT_DECLARED', $key), E_WARNING);
			trigger_error(debug::_('MODEL_SET_FIELD_NOT_DECLARED', $self, $key), E_USER_WARNING);
		}
		if ($this->isUpdate) { // hasRecord
			if (in_array($key, self::$keyFields[$self])) {
				// throw new Exception(debug::_('MODEL_SET_PRIMARY_KEY_NOT_ACCEPTED'), E_WARNING);
				trigger_error(debug::_('MODEL_SET_PRIMARY_KEY_NOT_ACCEPTED', $self), E_USER_WARNING);
			}
			$this->unsavedFields[$key] = true;
		}
		$this->validated = false;
		$this->fields[$key] = $value;
	}

	public function __get($key) {
		return $this->fields[$key] ?? null;
	}

	public function __isset($key) {
		return isset($this->fields[$key]);
	}

	private static $protectedFields = [
		'fields', 'unsavedFields', 'isUpdate', 'isDeleted', 'formName', 'isValid', 'validated', 'validateMessages' // hasRecord
	];

	private static function init() {
		$self = get_called_class();
		if (!isset(self::$tableFields[$self])) {
			$keyFields = $self::getKeyFields();
			self::$keyFields[$self] = $keyFields;
			self::$tableFields[$self] = array_merge($keyFields, $self::getFields());
		}
		if ($self::$autoCreate) {
			$self::createTable();
		}
		self::$initializedModels[$self] = true;
	}

	private function setValue($field_name, $value) {
		$self = get_called_class();
		if (in_array($field_name, self::$tableFields[$self])) {
			$this->__set($field_name, $value);
		} else if (in_array($field_name, self::$protectedFields)) {
			throw new Exception(debug::_('MODEL_SET_VALUE_FIELD_RESTRICTED', $field_name), E_WARNING);
		} else {
			$this->$field_name = $value;
		}
	}

	private function getValue($field_name) {
		$self = get_called_class();
		if (in_array($field_name, self::$tableFields[$self])) {
			return $this->fields[$field_name] ?? null;
		} else if (in_array($field_name, self::$protectedFields)) {
			throw new Exception(debug::_('MODEL_GET_VALUE_FIELD_RESTRICTED', $field_name), E_WARNING);
		} else {
			return $this->$field_name;
		}
	}

	public function setSaved() {
		$this->isUpdate = true; // hasRecord
		$this->unsavedFields = [];
	}

	public function getValues() {
		return $this->fields;
	}

	public function isUpdate() {
		return $this->isUpdate;
	}

	/* public function __unset($key) {
		unset($this->fields[$key]);
	} */

	public static function getTable() {
		$self = get_called_class();
		if ($self::$table == '') {
			trigger_error(debug::_('MODEL_GET_TABLE_VALUE_IS_EMPTY', $self), E_USER_WARNING);
		}
		return $self::$table;
	}

	public function getValidateMessages() {
		return $this->validateMessages;
	}

	public function getValidateMessage($field) {
		return $this->validateMessages[$field] ?? '';
	}

	public function setValidateMessage($field, $message) {
		if ($message) {
			$this->validateMessages[$field] = $message;
		}
	}

	public static function find($options = []) { // ['limit' => 10, 'order by' => '', 'where' => []]
		$db = core::app()->db;
		$self = get_called_class();

		if (!isset(self::$initializedModels[$self])) {
			$self::init();
		}

		// $table = $self::$table;
		$table = self::getTable();
		if (!$table) {
			return false;
		}
		$tableFields = array_merge($self::getKeyFields(), $self::getFields());

		return new query($db, extend([
			'table' => $table,
			'fields' => $tableFields
		], $options), $self);
	}

	public function afterFind() { }

	public static function findOne($where) {
		$db = core::app()->db;
		$self = get_called_class();

		if (!isset(self::$initializedModels[$self])) {
			$self::init();
		}

		// $table = $self::$table;
		$table = self::getTable();
		if (!$table) {
			return false;
		}
		$tableFields = array_merge($self::getKeyFields(), $self::getFields());

		return (new query($db, [
			'table' => $table,
			'fields' => $tableFields,
			'where' => $where
		], $self))->one();
	}

	public function __construct($fields = []) {
		$self = get_called_class();
		if ($self == __CLASS__) {
			throw new Exception(debug::_('MODEL_DIRECT_CONSTRUCTION_DISABLED'), E_WARNING);
		}
		if (!isset(self::$initializedModels[$self])) {
			$self::init();
		}
		if ($fields) {
		    $this->fields = $fields;
        }
	}

	// $user = new modelUser(['id' => 2, 'name' => 'user2']); // isNew = true;

	// $user = new modelUser([
	//		'name' => 'user'
	// ]); // isNew = true;

	// $user = modelUser::find()->one(); // isNew = false;


	public static function tableExists($table) {
		$db = core::app()->db;

		$quote = self::getQuote($db);

		// [$result, $prepare] = $db->query('SHOW TABLES LIKE ?', ['table' => $table]);
		[$result, $prepare] = $db->query('SELECT 1 FROM '.$quote.$table.$quote.' LIMIT 1', ['ignoreError' => true]);

		// console::log($result);
		// console::log($prepare->fetchAll(PDO::FETCH_OBJ));

		return $result !== false;
	}

	private static function getQuote($db) {
		$connectionParams = $db->getConnectionParams();
		return queryGenerator::getQuote($connectionParams['type']);
	}

	public static function createTable() {
		$self = get_called_class();

		$table = self::getTable();
		if (!$table) {
			return false;
		}

		if (self::tableExists($table)) {
			// table already exists
			return true;
		}

		if (!method_exists($self, 'getCreateFields')) {
			trigger_error(debug::_('MODEL_CREATE_TABLE_METHOD_NOT_EXIST', $self.'::getCreateFields'), E_USER_WARNING);
			return false;
		}

		$create_fields = $self::getCreateFields();
		$constraints = [];
		if (method_exists($self, 'getConstraints')) {
			$constraints = $self::getConstraints();
		}

		$db = core::app()->db;

		$connectionParams = $db->getConnectionParams();

		$connection_type = $connectionParams['type'];

		$quote = queryGenerator::getQuote($connection_type);

		// ['int', 11, 'autoinc' => true],
		// ['varchar', 32],
		// ['varchar', 255],
		// ['varchar', 12, 'default' => ''],
		// ['varchar', 255, null],
		// ['varchar', 255, 'null' => false],

		$sqlConstraints = [];

		$primary_fields = [];

		foreach ($constraints as $constraint) {
			if (!isset($constraint[0])) {
				trigger_error(debug::_('MODEL_CREATE_TABLE_CONSTRAINT_TYPE_MISSING', $self.'::getConstraints'), E_USER_WARNING);
				return false;
			}
			$constraint_type = $constraint[0];

			if (!isset($constraint[1])) {
				trigger_error(debug::_('MODEL_CREATE_TABLE_CONSTRAINT_FIELD_MISSING', $self.'::getConstraints', $constraint_type), E_USER_WARNING);
				return false;
			}

			$constraint_field = '';
			$constraint_fields = [];

			if ($constraint_type == 'foreign') {
				$constraint_field = $constraint[1];
			} else {
				$constraint_fields = $constraint[1];
				if (!is_array($constraint_fields)) {
					$constraint_fields = [$constraint_fields];
				}
			}

			switch ($constraint_type) { // constraint type
				case 'primary':
					$primary_fields = $constraint_fields;
					break;
					// $constraint_name = $constraint['name'] ?? 'pk_'.$table.'_'.implode('_',$constraint_fields);
					// $sql_constraint = 'PRIMARY KEY '.$quote.$constraint_name.$quote.' ('.implode(', ',$constraint_fields).')';
					// break;
				case 'unique':
					$constraint_name = $constraint['name'] ?? 'unique_'.$table.'_'.implode('_',$constraint_fields);
					$sql_constraint = 'UNIQUE '.$quote.$constraint_name.$quote.' ('.implode(', ',$constraint_fields).')';
					break;
				case 'foreign':
					if (!isset($constraint[2])) {
						trigger_error(debug::_('MODEL_CREATE_TABLE_CONSTRAINT_REFERENCE_MISSING', $self.'::getConstraints', $constraint_field), E_USER_WARNING);
						return false;
					}
					if (strpos($constraint[2], '.') === false) {
						trigger_error(debug::_('MODEL_CREATE_TABLE_CONSTRAINT_REFERENCE_WRONG_FORMAT', $self.'::getConstraints', $constraint_field, $constraint[2]), E_USER_WARNING);
						return false;
					}
					$ref = explode('.',$constraint[2]);
					$reference_field = array_pop($ref);
					$reference_table = implode('.',$ref);
					$constraint_name = $constraint['name'] ?? 'fk_'.$table.'-'.$constraint_field.'_'.$reference_table.'-'.$reference_field;
					$sql_constraint = 'FOREIGN KEY '.$quote.$constraint_name.$quote.' ('.$constraint_field.')'."\n";
					$sql_constraint .= '    REFERENCES '.$reference_table.' ('.$reference_field.')';

					$on_delete = strtoupper($constraint['onDelete'] ?? 'NO ACTION');
					$on_update = strtoupper($constraint['onUpdate'] ?? 'NO ACTION');

					$sql_constraint .= '    ON DELETE '.$on_delete;
					$sql_constraint .= '    ON UPDATE '.$on_update;
					break;
				default:
					trigger_error(debug::_('MODEL_CREATE_TABLE_CONSTRAINT_TYPE_UNKNOWN', $self.'::getConstraints', $constraint_field), E_USER_WARNING);
					return false;
			}

			if ($constraint_type != 'primary') {
				$sqlConstraints[] = '    '.$sql_constraint;
			}
		}


		$sqlCreateFields = [];

		foreach ($create_fields as $field => $params) {
			if (!isset($params[0])) {
				trigger_error(debug::_('MODEL_CREATE_TABLE_FIELD_TYPE_MISSING', $self.'::getCreateFields', $field), E_USER_WARNING);
				return false;
			}
			$type = $params[0];
			$length = $params[1] ?? '';

			if (isset($params['null'])) {
				$nullable = $params['null'];
			} else {
				$nullable = false;
				if (isset($params[3]) && $params[3] === null) {
					$nullable = true;
				}
			}

			$default = '';
			$has_default = false;
			if (isset($params['default'])) {
				$default = $params['default'];
				$has_default = true;
			}

			$auto_increment = $params['autoinc'] ?? false;

			$fieldParams = '';

			if (($connection_type == 'sqlite' || $connection_type == 'pgsql') && $type == 'int') {
				$type = 'integer';
				$length = '';
			}

			if ($length) {
				$type .= '('.$length.')';
			}

			if (in_array($field, $primary_fields)) {
				$fieldParams .= ' PRIMARY KEY';
			}

			if ($auto_increment) {
				if ($connection_type == 'sqlite') {
					$fieldParams .= ' AUTOINCREMENT'; // PRIMARY KEY
				} else {
					$fieldParams .= ' AUTO_INCREMENT';
				}
			}

			if (!$nullable) {
				$fieldParams .= ' NOT NULL';
			}

			if ($has_default) {
				$fieldParams .= ' DEFAULT '.queryGenerator::sqlValue($default);
			}

			$sqlCreateFields[] = '    '.$field.' '.$type.$fieldParams;
		}

		// ['primary', 'id']
		// ['unique', ['id', 'name']]
		// ['foreign', 'category_id', 'category.id', 'onDelete' => 'cascade', 'onUpdate' => 'no action']

		// PRIMARY KEY (contact_id, group_id),
		// UNIQUE (contact_id, group_id),

		$sqlCreateFields = array_merge($sqlCreateFields, $sqlConstraints);

		$sqlTable = queryGenerator::sqlTable($table, $connectionParams['schema'], $connectionParams['tablePrefix']);

		$table_options = '';
 		// $table_options = ' ENGINE=MyISAM DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci';

		$query_create_table = 'CREATE TABLE '.$sqlTable.' ('."\n".implode(','."\n", $sqlCreateFields)."\n".')'.$table_options;

		/* $query_create_table = "CREATE TABLE connection (
    id integer NOT NULL,
    name varchar(32) NOT NULL,
    host varchar(255) NOT NULL,
    port varchar(12) NOT NULL,
    dbtype varchar(12) NOT NULL,
    dbname varchar(255) NOT NULL,
    pass varchar(255) NOT NULL,
	PRIMARY KEY AUTOINCREMENT (id)
)"; */

		[$res, $query] = $db->query($query_create_table);

		return $res;
	}

	public function validate() {
		if (!$this->validated) {
			$self = get_called_class();
			$rules = $self::rules();

			$valid = true;

			$validate_messages = [];
			foreach ($rules as $field_name => $field_rules) {
				$value = $this->getValue($field_name) ?? null;
				$field_valid = true;
				if (!is_array($field_rules)) {
					$field_rules = [$field_rules];
				}
				$validate_message = '';
				// $rule_params = [];
				foreach ($field_rules as $rule_name => $rule_params) {
					if (is_numeric($rule_name)) {
						if (is_array($rule_params)) {
							throw new Exception(debug::_('MODEL_VALIDATE_RULE_WRONG_FORMAT', $field_name, $rule_name), E_WARNING);
						}
						$rule_name = $rule_params;
					}
					if (is_scalar($rule_params)) {
						$rule_params = ['param' => $rule_params];
					}

					$rule_params['field_name'] = $field_name;

					[$rule_valid, $validate_message] = rules::validate($rule_name, $value, $rule_params); // try catch
					$field_valid = $field_valid && $rule_valid;
					if (!$field_valid) {
						$validate_messages[$field_name] = stringFormatArgs($validate_message, array_merge($rule_params, [ // language consts instead of messages in the future
							// 'field_name' => $field_name,
							'value' => $value
						]));
						break;
					}
					$this->setValue($field_name, $value);
				}
				$valid = $valid && $field_valid;
			}
			$this->validateMessages = $validate_messages;

			$this->validated = true;
			$this->isValid = $valid;

			$this->afterValidate();
		}
		return $this->isValid;
	}

	public function load($form_name = '') { // $validate = true) {
		if ($this->isDeleted) {
			trigger_error(debug::_('MODEL_LOAD_OBJECT_IS_DELETED'), E_USER_WARNING);
			return false;
		}

		$self = get_called_class();
		$rules = $self::rules();

		$form_name = $form_name ?? $this->formName;

		$loaded = false;
		foreach ($rules as $field_name => $field_rules) {
			$value = request::post($field_name, $form_name);

			if ($value === null) {
				continue;
			}

			/* foreach ($field_rules as $rule_name => $rule_params) {
				if (is_numeric($rule_name)) {
					if (is_array($rule_params)) {
						throw new Exception(debug::_('MODEL_VALIDATE_RULE_WRONG_FORMAT', $field_name, $rule_name), E_WARNING);
					}
					$rule_name = $rule_params;
				} */
				/* switch ($rule_name) {
					case 'file':
						$value = request::file($field_name, $form_name);
						break;
					case 'files':
						$value = request::files($field_name, $form_name);
						break;
				} */
			// }


			// $this->fields[$field_name] = $value;
			// $this->__set($field_name, $value);
			$this->setValue($field_name, $value);
			// console::log($value);
			// console::log($field_name);
			$loaded = true;
		}

		/* if ($loaded && $validate) {
			return $this->validate();
		} */

		 return $loaded;
	}

	public function loadSave() {
		return $this->load() && $this->save();
	}

	public static function rules() {
		return [];
	}

	protected function beforeSave() { }

	protected function afterSave() { }

	protected function afterSaveError() { }

	protected function beforeDelete() { }

	protected function afterDelete() { }

	protected function afterDeleteError() { }

	// protected function beforeLoad() { }

	// protected function afterLoad() { }

	// protected function beforeValidate() { }

	protected function afterValidate() { }

	public function save() {
		if ($this->isDeleted) {
			trigger_error(debug::_('MODEL_SAVE_OBJECT_IS_DELETED'), E_USER_WARNING);
			return false;
		}

		if (!$this->validated) {
			if (!$this->validate()) {
				return false;
			}
		} else if (!$this->isValid) {
			trigger_error(debug::_('MODEL_SAVE_VALIDATION_RESULT_FALSE'), E_USER_WARNING);
			return false;
		}

		$self = get_called_class();
		// $table = $self::$table;
		$table = self::getTable();
		if (!$table) {
			return false;
		}

		if ($this->beforeSave() === false) {
			trigger_error(debug::_('MODEL_SAVE_BEFORE_SAVE_RESULT_FALSE'), E_USER_WARNING);
			return false;
		}

		$db = core::app()->db;

		if (!$this->isUpdate) { // hasRecord
			[$querySql, $queryParams] = $db->generateQuery([
				'query' => 'insert',
				'table' => $table,
				'fields' => $this->fields
			]);
			[$result, $prepared] = $db->query($querySql, $queryParams);

			if (!$result) {
				trigger_error(debug::_('MODEL_SAVE_DATABASE_INSERT_RESULT_FALSE'), E_USER_WARNING);
				$this->afterSaveError();
				return false;
			}

			$autoincField = $self::$autoincField;
			if ($autoincField) {
				$this->fields[$autoincField] = $db->lastInsertId($table, $autoincField);
			}

		} else {
			$unsavedFields = [];
			if ($this->unsavedFields) {
				$unsavedFields = array_intersect_key($this->fields, $this->unsavedFields);
			}

			if ($unsavedFields) {
				$where = [];
				foreach (self::$keyFields[$self] as $keyField) {
					if (!isset($this->fields[$keyField])) {
						$this->afterSaveError();
						throw new Exception(debug::_('MODEL_SAVE_KEY_FIELD_NOT_FOUND_IN_FIELDS'), E_WARNING);
					}
					$where[$keyField] = $this->fields[$keyField];
				}

				[$querySql, $queryParams] = $db->generateQuery([
					'query' => 'update', // replace
					'table' => $table,
					'fields' => $unsavedFields,
					'where' => $where
				]);
				[$result, $prepared] = $db->query($querySql, $queryParams);

				if ($result === false) {
					trigger_error(debug::_('MODEL_SAVE_DATABASE_UPDATE_RESULT_FALSE'), E_USER_WARNING);
					$this->afterSaveError();
					return false;
				}
			}

		}

		$this->setSaved();

		$this->afterSave();

		return true;
	}

	public function delete() {
		if ($this->isDeleted) {
			trigger_error(debug::_('MODEL_DELETED_OBJECT_IS_DELETED'), E_USER_WARNING);
			return false;
		}
		if (!$this->isUpdate) { // !hasRecord
			trigger_error(debug::_('MODEL_DELETE_OBJECT_NOT_LOADED'), E_USER_WARNING);
			return false;
		}

		$self = get_called_class();

		$table = self::getTable();
		if (!$table) {
			return false;
		}

		if ($this->beforeDelete() === false) {
			trigger_error(debug::_('MODEL_DELETE_BEFORE_DELETE_RESULT_FALSE'), E_USER_WARNING);
			return false;
		}

		$db = core::app()->db;

		$where = [];
		foreach (self::$keyFields[$self] as $keyField) {
			if (!isset($this->fields[$keyField])) {
				$this->afterDeleteError();
				throw new Exception(debug::_('MODEL_DELETE_KEY_FIELD_NOT_FOUND_IN_FIELDS'), E_WARNING);
			}
			$where[$keyField] = $this->fields[$keyField];
		}

		[$querySql, $queryParams] = $db->generateQuery([
			'query' => 'delete',
			'table' => $table,
			'where' => $where
		]);
		[$result, $prepared] = $db->query($querySql, $queryParams);

		if ($result === false) {
			trigger_error(debug::_('MODEL_DELETE_DATABASE_DELETE_RESULT_FALSE'), E_USER_WARNING);
			$this->afterDeleteError();
			return false;
		}

		$this->isDeleted = true;

		$this->afterDelete();

		return true;
	}


}
