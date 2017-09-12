<?php
namespace core\libs;

use console;

use core;
use debug;
use Exception;
use PDOException;
use PDO;

class query {

	// $result = $sth->fetchAll(PDO::FETCH_CLASS, "fruit");

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
			return null;
		}
		if ($this->itemClass) {
			$prepare->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, $this->itemClass);
			$result = $prepare->fetch();
			if (!$result) {
				$result = null;
			} else {
				$result->setSaved();
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
			return null;
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

class modelForm {



}


function stringFormatArgs($string, $params = []) {
	return preg_replace_callback('#\{\$([^}]+)\}#', function($matches) use ($params) {
		$param_name = $matches[1];
		return $params[$param_name] ?? 'PARAM_'.strtoupper($param_name);
	}, $string);
}

class model {

	protected static $table = '';

	protected $fields = [];

	protected static $keyFields;
	protected static $tableFields;

	private $unsavedFields = [];

	protected $hasRecord = false;

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

	private $validateMessages = [];

	public function __set($key, $value) {
		$self = get_called_class();
		if (!in_array($key, $self::$tableFields)) {
			throw new Exception(debug::_('MODEL_SET_FIELD_NOT_DECLARED', $key), E_WARNING);
		}
		if ($this->hasRecord) {
			if (in_array($key, $self::$keyFields)) {
				throw new Exception(debug::_('MODEL_SET_PRIMARY_KEY_NOT_ACCEPTED'), E_WARNING);
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
		'fields', 'unsavedFields', 'hasRecord', 'formName', 'isValid', 'validated', 'validateMessages'
	];

	private static function init() {
		$self = get_called_class();
		if (!isset($self::$tableFields)) {
			$self::$keyFields = $self::getKeyFields();
			$self::$tableFields = array_merge($self::$keyFields, $self::getFields());
		}
	}

	private function setValue($field_name, $value) {
		$self = get_called_class();
		if (in_array($field_name, $self::$tableFields)) {
			$this->__set($field_name, $value);
		} else if (!in_array($field_name, self::$protectedFields)) {
			$this->$field_name = $value;
		} else {
			throw new Exception(debug::_('MODEL_SET_VALUE_FIELD_RESTRICTED', $field_name), E_WARNING);
		}
	}

	private function getValue($field_name) {
		$self = get_called_class();
		if (in_array($field_name, $self::$tableFields)) {
			return $this->fields[$field_name] ?? null;
		} else if (!in_array($field_name, self::$protectedFields)) {
			return $this->$field_name;
		} else {
			throw new Exception(debug::_('MODEL_GET_VALUE_FIELD_RESTRICTED', $field_name), E_WARNING);
		}
	}

	public function setSaved() {
		$this->hasRecord = true;
		$this->unsavedFields = [];
	}

	public function getValues() {
		return $this->fields;
	}

	/* public function __unset($key) {
		unset($this->fields[$key]);
	} */

	public static function getTable() {
		$self = get_called_class();
		return $self::$table;
	}

	public function getValidateMessages() {
		return $this->validateMessages;
	}

	public static function find($options = []) { // ['limit' => 10, 'order by' => '', 'where' => []]
		$db = core::app()->db;
		$self = get_called_class();

		$table = $self::$table;
		$tableFields = array_merge($self::getKeyFields(), $self::getFields());

		return new query($db, extend([
			'table' => $table,
			'fields' => $tableFields
		], $options), $self);
	}

	public function afterFind() {
	}

	public static function findOne($where) {
		$db = core::app()->db;
		$self = get_called_class();

		$table = $self::$table;
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
		// { to-do:  move to static init()
		$self::init();
		// }
		if ($fields) {
		    $this->fields = $fields;
        }
	}

	// $user = new modelUser(['id' => 2, 'name' => 'user2']); // isNew = true;

	// $user = new modelUser([
	//		'name' => 'user'
	// ]); // isNew = true;

	// $user = modelUser::find()->one(); // isNew = false;


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
					[$rule_valid, $validate_message] = rules::validate($rule_name, $value, $rule_params); // try catch
					$field_valid = $field_valid && $rule_valid;
					if (!$field_valid) {
						$validate_messages[$field_name] = stringFormatArgs($validate_message, array_merge($rule_params, [ // language consts instead of messages in the future
							'field_name' => $field_name,
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
		}
		return $this->isValid;
	}

	public function load($form_name = '') { // $validate = true) {
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

	public function save() {
		if (!$this->validated) {
			$this->validate();
		}

		if (!$this->isValid) {
			trigger_error(debug::_('MODEL_SAVE_VALIDATION_RESULT_FALSE'), E_WARNING);
			return false;
		}

		$db = core::app()->db;

		$self = get_called_class();
		$table = $self::$table;

		if (!$this->hasRecord) {
			[$querySql, $queryParams] = $db->generateQuery([
				'query' => 'insert',
				'table' => $table,
				'fields' => $this->fields
			]);
			[$result, $prepared] = $db->query($querySql, $queryParams);

			$autoincField = $self::$autoincField;
			if ($autoincField) {
				$this->fields[$autoincField] = $db->lastInsertId($table, $autoincField);
			}

			$this->setSaved();

			return $result;

		} else {
			$unsavedFields = [];
			if ($this->unsavedFields) {
				$unsavedFields = array_intersect_key($this->fields, $this->unsavedFields);
			}

			if (!$unsavedFields) {
				return true;
			}

			$where = [];
			foreach ($self::$keyFields as $keyField) {
				if (!isset($this->fields[$keyField])) {
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

			$this->setSaved();

			return $result;
		}
	}

	public function delete() {
		;
	}


}

/* echo <<<HE
    
    classA{
    
    }
    classB{
    
    }
    
    classUser{
    
    public func rules(){
        return[];
    }
    
    
    public static func restorePass($token){
        
        
        
    }
    
    }
    
    
HE; */






