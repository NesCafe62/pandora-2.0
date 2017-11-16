<?php
namespace core\libs;

use console;

use debug;
use Exception;
use PDO;
use PDOException;

class db {

	private $connection = null;

	private $connectionParams = [];

	public const CURRENT_TIMESTAMP = '#_CURRENT_TIMESTAMP';


	private static function connectMysql(&$params) {
		$params = extend($params, [
			'port' => '',
			'encoding' => 'utf8',
			'schema' => '',
			'tablePrefix' => '',
		]);

		[
			'database' => $database,
			'host' => $host,
			'port' => $port,
			'user' => $user,
			'pass' => $pass,
			'encoding' => $encoding
		] = $params;

		$connection_string = 'mysql:host='.$host.';dbname='.$database;
		if ($port) {
			$connection_string .= ';port='.$port;
		}
		$options = [
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES `'.$encoding.'`, sql_mode = (SELECT REPLACE(@@sql_mode,\'ONLY_FULL_GROUP_BY\',\'\'));'
		];
		return new PDO($connection_string, $user, $pass, $options);
	}

	private static function connectSqlite(&$params) {
		$params = extend($params, [
			'encoding' => 'utf8',
			'schema' => '',
			'tablePrefix' => '',
		]);

		['database' => $database] = $params;

		$connection_string = 'sqlite:'.$database;
		return new PDO($connection_string);
	}

	private static function connectPgsql(&$params) {
		$params = extend($params, [
			'port' => '',
			'encoding' => 'utf8',
			'schema' => '',
			'tablePrefix' => '',
		]);

		[
			'database' => $database,
			'schema' => $schema,
			'host' => $host,
			'port' => $port,
			'user' => $user,
			'pass' => $pass,
			'encoding' => $encoding
		] = $params;

		$connection_string = 'pgsql:host='.$host.';dbname='.$database;
		if ($port) {
			$connection_string .= ';port='.$port;
		}
		/* if ($encoding) {
			$connection_string .= ';charset='.$encoding;
		} */
		$options = [
			PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL
		];
		$connection = new PDO($connection_string, $user, $pass, $options);
		if ($schema) {
			$connection->exec('SET search_path = '.$schema);
		}
		return $connection;
	}

	public function __construct($connection_params) {
		$this->connectionParams = $connection_params;
	}

	public function connect() {
		$this->connectionParams['type'] = $this->connectionParams['type'] ?? 'mysql';
		$type = $this->connectionParams['type'];

		$connectionMethod = 'connect'.ucfirst($type);

		if (!method_exists($this, $connectionMethod)) {
			throw new Exception(debug::_('DB_CONNECTION_UNKNOWN_TYPE', $type), E_WARNING);
		}

		$connection = self::$connectionMethod($this->connectionParams);
		$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$this->connection = $connection;

		// return true;
	}

	public function getConnectionParams() {
		return $this->connectionParams;
	}

	public function generateQuery($options) { // ['query' => 'select', 'table', 'fields', 'where', 'limit', 'order by', 'group by', 'having']
		$options['connectionType'] = $this->connectionParams['type'];
		$options['schema'] = $options['schema'] ?? $this->connectionParams['schema']; //  ?? ''
		$options['tablePrefix'] = $options['tablePrefix'] ?? $this->connectionParams['tablePrefix']; //  ?? ''
		return queryGenerator::generate($options);
	}

	public function lastInsertId($table = '', $keyField = 'id') {
		if ($this->connectionParams['type'] === 'pgsql') {
			$sequence_name = $table.'_'.$keyField.'_seq';
		} else {
			$sequence_name = null;
		}
		$lastInsertId = $this->connection->lastInsertId($sequence_name);
		return $lastInsertId;
	}

	public function query($query, $params = []) {
		// $err = '';
		$result = false;
		$preparedQuery = null;
		$ignoreError = $params['ignoreError'] ?? false;
		try {
			console::log(['query' => $query, 'params' => $params]);
			$preparedQuery = $this->connection->prepare($query);
			$result = $preparedQuery->execute($params);
		} catch (PDOException $e) {
			// $err = $e->getMessage();
			if (!$ignoreError) {
				trigger_error(debug::_('DATABASE_QUERY_EXECUTE_ERROR', $e->getMessage()), E_USER_WARNING);
			}
		}

	//	if ($err) {
			// $result = false;
	//		debug::log($err);
	//	}

		return [$result, $preparedQuery];
	}

}

class queryGenerator {

	private static $param = 0;

	private static function resetParams() {
		self::$param = 0;
	}

	public static function sqlParam() {
		self::$param++;
		return ':param'.self::$param;
	}

	public static function getQuote($connectionType) {
		// return ($connectionType == 'mysql') ? '`' : '"';
		switch ($connectionType) {
			case 'mysql':
				return '`';
			case 'pgsql':
				return '"';
			case 'sqlite':
				return '`';
			default:
				return '`';
		}
	}

	public static function sqlValue($value) {
		if ($value === null) {
			return 'NULL';
		} else if (is_numeric($value)) {
			return $value.'';
		} else if (substr($value, 0, 2) == '#_') {
			return deleteLeft($value, 2);
		} else {
			return '"'.$value.'"';
		}
	}

	public static function sqlLimit($limit) {
		if (is_array($limit) && count($limit) >= 2) {
			$offset = (int) $limit[0];
			$count = (int) $limit[1];
			if ($offset < 0) {
				trigger_error(debug::_('QUERY_GENERATOR_QUERY_LIMIT_NEGATIVE_OFFSET', $count, $offset), E_NOTICE);
				$offset = 0;
			}
		} else {
			if (is_array($limit)) {
				trigger_error(debug::_('QUERY_GENERATOR_QUERY_LIMIT_WRONG_FORMAT', '['.implode(',',$limit).']'), E_NOTICE);
			}
			if ($limit <= 0) {
				return '';
			}
			$count = (int) $limit;
			$offset = 0;
		}
		return $count.(($offset) ? ' OFFSET '.$offset : '');
	}

	public static function sqlTable($table, $schema, $tablePrefix) {
		if ($tablePrefix) {
			$table = $tablePrefix.$table;
		}
		if ($schema) {
			$table = $schema.'.'.$table;
		}
		return $table;
	}

	public static function sqlWhere($where, $level = 0) {

		$params = [];

		if (!empty($where['raw'])) {

			$condOp = 'RAW';

			$sqlWhere = $where['raw'];
			unset($where['raw']);
			$params = $where;

			/* foreach ($where as $param => $val) {
				$params[ltrim($param, ':')] = $val;
			} */

		} else {

			$condOp = (!empty($where['_op'])) ? strtoupper($where['_op']) : 'AND';
			$condNot = false;

			unset($where['_op']);

			if ($condOp === 'NOT') {
				$condNot = true;
				$condOp = 'AND';
			}

			$conditions = [];

			foreach ($where as $field => $val) {
				if (is_numeric($field)) {
					if (is_array($val)) {
						[$subWhere, $subParams, $subCondOp] = self::sqlWhere($val, $level+1);

						$params = array_merge($params, $subParams);

						if ($subCondOp === $condOp) {
							if ($subWhere) {
								$conditions[] = $subWhere;
							}
						} else {
							$sub_offset = "\t".'    ';
							if ($subWhere[0] === '(') {
								$subWhere = "\n\t".$subWhere;
							}
							$subWhere = str_replace("\t", $sub_offset, $subWhere);
							$subWhere = '('.$subWhere. "\n\t".')';
							if ($subCondOp === 'NOT') {
								$subWhere = 'NOT '.$subWhere;
							}
							$conditions[] = $subWhere;
						}
					} else {
						$conditions[] = $val;
					}
				} else {

					$op = '=';
					$field = strtolower(trim($field));
					foreach (['!=','<=','>=','=','>','<',' like'] as $_op) {
						if (endsWith($field, $_op)) {
							$field = rtrim(trimRight($field, $_op));
							$op = $_op;
							break;
						}
					}

					if (is_array($val)) {
						// "in" sql-statement
						$s = [];
						if (count($val) > 0) {
							foreach ($val as $v) {
								$param = self::sqlParam();
								$s[] = $param;
								$params[$param] = $v;
							}
						} else {
							$s[] = "'_EMPTY_'";
						}
						$condition = $field.' IN ('.implode(', ',$s).')';
					} else if ($val === null) {
						if ($op === '=') {
							$condition = $field.' IS NULL';
						} else {
							$condition = $field.' IS NOT NULL';
						}
					} else {
						if ( ($val != '') && ($val[0] === ':') ) {
							$param = $val; // ltrim($val, ':');
						} else {
							$param = self::sqlParam();
							$params[$param] = $val;
						}
						$condition = $field.' '.$op.' '.$param;
					}

					$conditions[] = "\n\t".$condition;
				}
			}

			$sqlWhere = implode(' '.$condOp.' ', $conditions);

			if ($level === 0) {
				$offset = '    ';
				if ($condNot) {
					$sqlWhere = 'NOT ('.$sqlWhere. "\n".$offset.')';
					$offset .= '    ';
				}
				$sqlWhere = str_replace("\t", $offset, $sqlWhere);
			}
		}

		return [$sqlWhere, $params, $condOp];
	}

	public static function querySelect($options) {
		$params = [];

		if (empty($options['table'])) {
			throw new Exception(debug::_('QUERY_GENERATE_OPTION_NOT_SET', 'table'), E_WARNING);
		}

		self::resetParams();

		[
			'table' => $table,
			'fields' => $fields, // join
			'where' => $where,
			'group by' => $groupBy,
			'having' => $having,
			'order by' => $orderBy,
			'limit' => $limit
		] = extend($options, [
			'fields' => '*',
			'where' => '',
			'group by' => '',
			'having' => '',
			'order by' => '',
			'limit' => ''
		]);

		/* console::log([
			'table' => $table,
			'fields' => $fields,
			'where' => $where,
			'group by' => $groupBy,
			'having' => $having,
			'order by' => $orderBy,
			'limit' => $limit
		]); */

		// ------------
		$sqlTables = self::sqlTable($table, $options['schema'], $options['tablePrefix']);

		// ------------
		if (!is_array($fields)) {
			// $fields = explode(',',$fields);
			// $fields = preg_split(',\s*',$fields);
			preg_match_all('/([^,\'"(]|\'[^\']*\'|"[^"]*"|\([^)]*\))+/', $fields, $matches);
			$fields = $matches[0];
		}

		$sqlFields = implode(', ', array_unique($fields));

		// ------------
		$querySql = 'SELECT '.$sqlFields." \n".'FROM '.$sqlTables;

		// ------------
		$sqlWhere = '';
		if ($where) {
			[$sqlWhere, $params] = self::sqlWhere($where);
			$params = array_merge($params, $params);
		}

		// ------------
		$sqlGroupBy = $groupBy;
		if ($groupBy && is_array($groupBy)) {
			$sqlGroupBy = implode(', ',$groupBy);
		}

		// ------------
		$sqlHaving = ''; // $having

		// ------------
		$sqlOrderBy = $orderBy;
		if ($orderBy && is_array($orderBy)) {
			$sqlOrderBy = implode(', ',$orderBy);
		}

		// ------------
		$sqlLimit = self::sqlLimit($limit); // limit

		foreach ([
			'WHERE' => $sqlWhere,
			'GROUP BY' => $sqlGroupBy,
			'HAVING' => $sqlHaving,
			'ORDER BY' => $sqlOrderBy,
			'LIMIT' => $sqlLimit
		] as $param => $value) {
			if ($value) {
				$querySql .= " \n".$param.' '.$value;
			}
		}

		return [$querySql, $params];
	}

	// public static function queryDelete($options) { }

	public static function queryUpdate($options) {
		$params = [];

		if (empty($options['table'])) {
			throw new Exception(debug::_('QUERY_GENERATE_OPTION_NOT_SET', 'table'), E_WARNING);
		}
		if (empty($options['fields'])) {
			throw new Exception(debug::_('QUERY_GENERATE_OPTION_NOT_SET', 'fields'), E_WARNING);
		}
		if (empty($options['where'])) {
			throw new Exception(debug::_('QUERY_GENERATE_OPTION_NOT_SET', 'where'), E_WARNING);
		}

		self::resetParams();

		[
			'table' => $table,
			'fields' => $fields, // join
			'where' => $where
		] = $options;

		/* console::log([
			'table' => $table,
			'fields' => $fields,
			'where' => $where
		]); */

		// ------------
		$sqlTables = self::sqlTable($table, $options['schema'], $options['tablePrefix']);

		// ------------
		$quote = self::getQuote($options['connectionType']);

		$sqlFields = [];
		$values = [];
		foreach ($fields as $fieldName => $value) {
			if (!is_array($value)) {
				$param = self::sqlParam();
				$values[$param] = $value;
				$expression = $param;
			} else { // raw expression
				$expression = array_shift($value);
				$expression_params = [];
				$expression = preg_replace_callback('/\?/', function($matches) use (&$expression_params) {
					$param = self::sqlParam();
					$expression_params[] = $param;
					return $param;
				}, $expression);

				$i = 0;
				foreach ($expression_params as $param) {
					if (!isset($value[$i])) {
						throw new Exception(debug::_('QUERY_GENERATE_RAW_EXPRESSION_NOT_ENOUGH_ARGUMENTS', $fieldName, $i), E_WARNING);
					}
					$values[$param] = $value[$i];
					$i++;
				}
			}
			$sqlFields[] = $quote.$fieldName.$quote.' = '.$expression;
		}
		$params = array_merge($params, $values);

		// ------------
		$sqlWhere = '';
		if ($where) {
			[$sqlWhere, $whereParams] = self::sqlWhere($where);
			$params = array_merge($params, $whereParams);
		}

		// ------------
		$querySql = 'UPDATE '.$sqlTables." \n".'SET '.implode(', ',$sqlFields).' '." \n".'WHERE '.$sqlWhere;

		return [$querySql, $params];
	}

	public static function queryInsert($options, $modeRewrite = false) {
		$params = [];

		if (empty($options['table'])) {
			throw new Exception(debug::_('QUERY_GENERATE_OPTION_NOT_SET', 'table'), E_WARNING);
		}
		if (empty($options['fields'])) {
			throw new Exception(debug::_('QUERY_GENERATE_OPTION_NOT_SET', 'table'), E_WARNING);
		}

		self::resetParams();

		[
			'table' => $table,
			'fields' => $fields
		] = $options;

		/* console::log([
			'table' => $table,
			'fields' => $fields
		]); */

		// ------------
		$sqlTables = self::sqlTable($table, $options['schema'], $options['tablePrefix']);

		// ------------
		// $sqlFields = implode(', ', $fields);

		$quote = self::getQuote($options['connectionType']);

		$sqlFields = [];
		$sqlValues = [];
		foreach ($fields as $fieldName => $value) {
			$param = self::sqlParam();
			$sqlFields[] = $quote.$fieldName.$quote;
			$sqlValues[] = $param;
			$values[$param] = $value;
		}
		$params = array_merge($params, $values);


		// ------------
		$querySql = (($modeRewrite) ? 'REWRITE' : 'INSERT').' INTO '.$sqlTables." \n".'('.implode(', ',$sqlFields).') '." \n".'VALUES ('.implode(',',$sqlValues).')';

		return [$querySql, $params];
	}

	public static function queryRewrite($options) {
		return self::queryInsert($options, true);
	}

	public static function generate($options) {
		$options['connectionType'] = $options['connectionType'] ?? 'mysql';

		$queryMethod = 'query'.ucfirst($options['query'] ?? 'select');

		if (!method_exists(__CLASS__, $queryMethod)) {
			throw new Exception(debug::_('QUERY_GENERATE_UNKNOWN_QUERY_TYPE', $options['query']), E_WARNING);
		}

		return self::$queryMethod($options);
	}

}