<?php

/**
 * Abstract DB clas.
 *
 * @abstract
 */
abstract class DB {


	/**
	 * Default db username
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $username = null;

	/**
	 * Default db password
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $password = null;

	/**
	 * charset
	 *
	 * (default value: 'utf8mb4')
	 *
	 * @var string
	 * @access protected
	 */
	protected $charset = 'utf8mb4';

	/**
	 * Database supports $charset
	 *
	 * @var bool
	 */
	public $set_names = false;

	/**
	 * pdo
	 *
	 * (default value: null)
	 *
	 * @var PDO
	 * @access protected
	 */
	protected $pdo = null;

	/**
	 * SSL attributes
	 *
	 * @var array|false
	 */
	public $ssl = false;

	/**
	 * Database name - needed for check
	 *
	 * (default value: '')
	 *
	 * @var string
	 * @access public
	 */
	public $dbname 	= '';		// needed for DB check

	/**
	 * hostname
	 *
	 * (default value: 'localhost')
	 *
	 * @var string
	 * @access protected
	 */
	protected $host 	= 'localhost';

	/**
	 * Default port number
	 *
	 * (default value: '3306')
	 *
	 * @var string
	 * @access protected
	 */
	protected $port 	= '3306';

	/**
	 * Cache file to store all results from queries to
	 *
	 *  structure:
	 *
	 *      [table][index] = (object) $content
	 *
	 *
	 * (default value: array())
	 *
	 * @var array
	 * @access public
	 */
	public $cache = array();

	/**
	 * Enable MySQL CTE query support
	 *
	 * @var bool|null
	 */
	private $ctes_enabled = null;

	/**
	 * Guard against XSS attacks by html escaping strings by default
	 *
	 * @var boolean
	 */
	public $html_escape_enabled = true;

	/**
	 * List of tables and columns to exclude from html_escaping
	 *
	 * @var array
	 */
	private $html_escape_exceptions = [];

	/**
	 * Instal flag
	 * @var bool
	 * @access protected
	 */
	public $install = false;

	/**
	 * Debugging flag
	 *
	 * (default value: false)
	 *
	 * @var bool
	 * @access protected
	 */
	protected $debug = false;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @param mixed $username (default: null)
	 * @param mixed $password (default: null)
	 * @param mixed $charset (default: null)
	 * @param mixed $ssl (default: null)
	 * @return void
	 */
	public function __construct($username = null, $password = null, $charset = null, $ssl = null) {
		if (isset($username)) $this->username = $username;
		if (isset($password)) $this->password = $password;
		if (isset($charset))  $this->charset = $charset;
		# ssl
		if ($ssl) {
			$this->ssl = $ssl;
		}
		$this->load_schema_exceptions();
	}

	/**
	 * Process /db/SCHEMA and load columns commented with "__no_html_escape__"
	 *
	 * @return void
	 */
	private function load_schema_exceptions() {
		$fh = fopen(dirname(__FILE__) . '/../../db/SCHEMA.sql', 'r');
		if ($fh === false) {
			return;
		}

		$lines = explode("\n", str_replace("\r\n", "\n", fread($fh, 100000)));
		fclose($fh);

		$current_table = "";

		foreach ($lines as $line) {
			if (strpos($line, 'CREATE TABLE') === 0) {
				$tbl_name = explode('`', $line);
				if (sizeof($tbl_name) >= 3) {
					$current_table = $tbl_name[1];
				}
				continue;
			}
			if (!is_string($current_table)) {
				continue;
			}
			if (strpos($line, ') ENGINE=') ===  0) {
				$current_table = null;
				continue;
			}
			if (strpos($line, '__no_html_escape__') !== false) {
				$col_name = explode('`', $line);
				if (sizeof($col_name) >= 3) {
					$this->html_escape_exceptions[$current_table][$col_name[1]] = 1;
				}
			}
		}
	}

	/**
	 * convert a date object/string ready for use in sql
	 *
	 * @access public
	 * @static
	 * @param mixed $date (default: null)
	 * @return string|false
	 */
	public static function toDate($date = null) {
		if (is_int($date)) {
			return date('Y-m-d H:i:s', $date);
		} elseif (is_string($date)) {
			return date('Y-m-d H:i:s', strtotime($date));
		} else {
			return date('Y-m-d H:i:s');
		}
	}

	/**
	 * Connect to the database
	 * Call whenever a connection is needed to be made
	 *
	 * @access public
	 * @return void
	 */
	public function connect() {
		$dsn = $this->makeDsn();

		try {
			# ssl?
			if ($this->ssl) {
				$this->pdo = new \PDO($dsn, $this->username, $this->password, $this->ssl);
			}
			else {
				$this->pdo = new \PDO($dsn, $this->username, $this->password);
			}

			$this->setErrMode(\PDO::ERRMODE_EXCEPTION);

		} catch (\PDOException $e) {
			throw new Exception ("Could not connect to database! ".$e->getMessage());
		}

		try {
			$this->pdo->query('SET NAMES \'' . $this->charset . '\';');
			$this->set_names = true;
		} catch (Exception $e) {
			$this->set_names = false;
		}
	}

	/**
	 * Set PDO error mode
	 * @param mixed $mode
	 */
	public function setErrMode($mode = \PDO::ERRMODE_EXCEPTION) {
		$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, $mode);
	}

	/**
	 * Set PDO stringify fetches, issue #4043
	 *
	 * @param boolean $stringify
	 * @return bool
	 */
	public function setStringifyFetches($stringify = true) {
		return $this->pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, $stringify);
	}

	/**
	 * makeDsn function.
	 *
	 * @access protected
	 * @return string
	 */
	protected function makeDsn() {
		return ':charset=' . $this->charset;
	}

	/**
	 * resets connection.
	 *
	 * @access public
	 * @return void
	 */
	public function resetConn() {
		unset($this->pdo);
		$this->cache = [];
		$this->install = false;
	}

	/**
	 * logs queries to file
	 *
	 * @access private
	 * @param mixed $query
	 * @param array $values
	 * @return void
	 */
	private function log_query ($query, $values = false) {
		if($this->debug) {

			$myFile = "/tmp/queries.txt";
			$fh = fopen($myFile, 'a') or die("can't open file");
			// query
			fwrite($fh, $query->queryString);
			// values
			if(is_array($values)) {
            fwrite($fh, " Params: ".implode(", ", $values));
			}
			// break
            fwrite($fh, "\n");
			fclose($fh);
		}
	}

	/**
	 * Remove outer quotes from a string
	 *
	 * @access public
	 * @static
	 * @param mixed $str
	 * @return string
	 */
	public static function unquote_outer($str) {
		$len = strlen($str);

		if ($len>1) {
			if ($str[0] == "'" && $str[$len-1] == "'") {
				return substr($str, 1, -1);
			} elseif ($str[0] == "'") {
				return substr($str, 1);
			} elseif ($str[$len-1] == "'") {
				return substr($str, 0, -1);
			}
		} elseif ($len>0) {
			if ($str[0] == "'") {
				return '';
			}
		}

		return $str;
	}

	/**
	 * Are we currently connected to the database
	 *
	 * @access public
	 * @return bool
	 */
	public function isConnected() {
		return (@$this->pdo !== null);
	}

	/**
	 * MySQL CTE support check
	 *
	 * @access public
	 * @return bool
	 */
	public function is_cte_enabled() {
		// Check cached result
		if (is_bool($this->ctes_enabled))
			return $this->ctes_enabled;

		$db = Config::ValueOf("db");
		$ctes_enabled = filter_var(@$db['use_cte'], FILTER_VALIDATE_INT, ['options'=>['default' => 1, 'min_range' => 0, 'max_range' => 2]]);

		if ($ctes_enabled===0) {	            // Disable CTE Support
			$this->ctes_enabled = false;
		} elseif($ctes_enabled===2) {        // Force enable CTE support
			$this->ctes_enabled = true;
		} else {
			try {                           // (default) Autodetect CTE support
				@$this->runQuery('WITH RECURSIVE cte_test(n) AS (SELECT 1 UNION ALL SELECT n+1 FROM cte_test WHERE n < 3) SELECT n FROM cte_test;');
				$this->ctes_enabled = true;
			} catch(Exception $e) {
				$this->ctes_enabled = false;
			}
		}

		return $this->ctes_enabled;
	}

	/**
	 * Returns last insert ID
	 *
	 * @access public
	 * @return string|false
	 */
	public function lastInsertId() {
		return $this->pdo->lastInsertId();
	}

	/**
	 * Run a statement on the database
	 * Note: no objects are fetched
	 *
	 * @access public
	 * @param mixed $query
	 * @param array $values (default: array())
	 * @param integer|null &$rowCount (default: null)
	 * @return bool
	 */
	public function runQuery($query, $values = array(), &$rowCount = null) {
		if (!$this->isConnected()) $this->connect();

		$result = null;

		$statement = $this->pdo->prepare($query);

		//debug
		$this->log_query($statement, $values);

		if (is_object($statement)) {
			$result = $statement->execute((array)$values); //this array cast allows single values to be used as the parameter
			$rowCount = $statement->rowCount();
		}
		return $result;
	}

	/**
	 * Emulate a SQL CTE query using temporary tables
	 *
	 * @param   string  $tableName        Database table name
	 * @param   string  $schema           Temporary table schema e.g (int(11))
	 * @param   string  $anchor_query     CTE Anchor query (may contain ?)
	 * @param   array   $anchor_args      CTE Anchor args
	 * @param   string  $recursive_query  Recursive query, should reference temporary table cte_last
	 * @param   string  $results_query    Results query
	 *
	 * @return  mixed
	 */
	public function emulate_cte_query($tableName, $schema, $anchor_query, $anchor_args, $recursive_query, $results_query, $cleanup=true) {
		$results = false;

		/**
		 * Reset engine type if set in config.php (MEMORY or InnoDB)
		 */
		$db = Config::ValueOf('db');
		$tmptable_engine_type = (@$db['tmptable_engine_type']=="InnoDB") ? "InnoDB" : "MEMORY";

		try {
			// Emulate SQL CTE query using temporary tables.
			//  - cte_query, holds accumulated results
			//  - cte_0,     temporary results storage (can't reference a temporary table name multiple times in the same query)
			//  - cte_last,  results of the last iteration.

			$query = "DROP TABLE IF EXISTS cte_query, cte_0, cte_1, cte_last;" .
					"CREATE TEMPORARY TABLE cte_query $schema ENGINE = $tmptable_engine_type;" .
					"CREATE TEMPORARY TABLE cte_0     $schema ENGINE = $tmptable_engine_type;" .
					"CREATE TEMPORARY TABLE cte_last  $schema ENGINE = $tmptable_engine_type;";
			$this->runQuery($query);

			// Run Anchor query then the recursive query until there are no more results
			$level = 1;
			do {
				// reset args for recursive query
				$anchor_args = $level==1 ? $anchor_args : [];

				$query = "INSERT INTO cte_0 ".($level++==1 ? $anchor_query : $recursive_query).";" .
						"TRUNCATE TABLE cte_last;" .
						"INSERT IGNORE INTO cte_last  SELECT * FROM cte_0;" .
						"TRUNCATE TABLE cte_0;" .
						"INSERT IGNORE INTO cte_query SELECT * FROM cte_last;";
				$result = $this->runQuery($query, $anchor_args, $rowCount);

				if ($level>256) { throw new Exception(_('Recursion limit reached.')); }
			} while ($result == 1 && $rowCount > 0);

			// Run $result_query using cte temporary table results
			$results = $this->getObjectsQuery($tableName, $results_query);

		} catch (Exception $e) {
			if ($cleanup)
				$this->runQuery("DROP TABLE IF EXISTS cte_query, cte_0, cte_last;");
			throw $e;
		}

		// Cleanup and return results
		if ($cleanup)
			$this->runQuery("DROP TABLE IF EXISTS cte_query, cte_0, cte_last;");
		return $results;
	}

	/**
	 * Allow a value to be escaped, ready for insertion as a mysql parameter
	 * Note: for usage as a value (rather than prepared statements), you MUST manually quote around.
	 *
	 * @access public
	 * @param mixed $str
	 * @return string
	 */
	public function escape($str) {
		$str = (string) $str;
		if (is_blank($str)) return "";

		if (!$this->isConnected()) $this->connect();

		// SQL Injection - strip backquote character
		$str = str_replace('`', '', $str);
		return $this->unquote_outer($this->pdo->quote($str));
	}

	/**
	 * Get a quick number of objects in a table
	 *
	 * @access public
	 * @param mixed $tableName
	 * @return mixed
	 */
	public function numObjects($tableName) {
		if (!$this->isConnected()) $this->connect();

		$tableName = $this->escape($tableName);
		$statement = $this->pdo->prepare('SELECT COUNT(*) as `num` FROM `'.$tableName.'`;');

		//debug
		$this->log_query ($statement);
		$statement->execute();

		return $statement->fetchColumn();
    }

	/**
	 * Get a quick number of objects in a table for filtered field
	 *
	 * @access public
	 * @param mixed $tableName
	 * @param mixed $method
	 * @param boolean $like (default: false)
	 * @param mixed $value
	 * @return mixed
	 */
	public function numObjectsFilter($tableName, $method, $value, $like = false) {
		if (!$this->isConnected()) $this->connect();

		$like === true ? $operator = "LIKE" : $operator = "=";

		$tableName = $this->escape($tableName);
		$statement = $this->pdo->prepare('SELECT COUNT(*) as `num` FROM `'.$tableName.'` where `'.$method.'` '.$operator.' ?;');

		//debug
		$this->log_query ($statement, (array) $value);
		$statement->execute(array($value));

		return $statement->fetchColumn();
	}

	/**
	 * Update an object in a table with values given
	 *
	 * Note: the id of the object is assumed to be in.
	 *
	 * @access public
	 * @param mixed $tableName
	 * @param mixed $obj
	 * @param string $primarykey (default: 'id')
	 * @param mixed $primarykey2 (default: null)
	 * @return bool
	 */
	public function updateObject($tableName, $obj, $primarykey = 'id', $primarykey2 = null) {
		if (!$this->isConnected()) $this->connect();

		$obj = (array)$obj;

		//we cannot update an object without an id specified so quit
		if (!isset($obj[$primarykey])) {
			throw new Exception('Missing primary key');
		}

		$tableName = $this->escape($tableName);

		//get the objects id from the provided object and knock it off from the object so we don't try to update it
		$objId[] = $obj[$primarykey];
		unset($obj[$primarykey]);

		//secondary primary key?
		if(!is_null($primarykey2)) {
		$objId[] = $obj[$primarykey2];
		unset($obj[$primarykey2]);
		}

		//TODO: validate given object parameters with that of the table (this validates parameters names)

		//formulate an update statement based on the object parameters
		$objParams = array_keys($obj);

		$preparedParamArr = array();
		foreach ($objParams as $objParam) {
			$preparedParamArr[] = '`' . $this->escape($objParam) . '`=?';
		}

		// exit on no parameters
		if(sizeof($preparedParamArr)==0) {
			throw new Exception('No values to update');
			return false;
		}

		$preparedParamStr = implode(',', $preparedParamArr);

		//primary key 2?
		if(!is_null($primarykey2))
		$statement = $this->pdo->prepare('UPDATE `' . $tableName . '` SET ' . $preparedParamStr . ' WHERE `' . $primarykey . '`=? AND `' . $primarykey2 . '`=?;');
		else
		$statement = $this->pdo->prepare('UPDATE `' . $tableName . '` SET ' . $preparedParamStr . ' WHERE `' . $primarykey . '`=?;');

		//merge the parameters and values
		$paramValues = array_merge(array_values($obj), $objId);

		//debug
		$this->log_query ($statement, $paramValues);
		//run the update on the object
		return $statement->execute($paramValues);
	}

	/**
	 * Update multiple objects at once.
	 *
	 * @access public
	 * @param string $tableName
	 * @param array $ids
	 * @param array $values
	 * @return bool
	 */
	public function updateMultipleObjects($tableName, $ids, $values) {
		$tableName = $this->escape($tableName);
		//set ids
		$num = count($ids);
		$idParts = array_fill(0, $num, '`id`=?');
		//set values
		$objParams = array_keys($values);
		$preparedParamArr = array();
		foreach ($objParams as $objParam) {
			$preparedParamArr[] = '`' . $this->escape($objParam) . '`=?';
		}
		//set values
		$all_values = array_merge(array_values($values),$ids);
		//execute
		return $this->runQuery('UPDATE `'.$tableName.'` SET '.implode(',', $preparedParamArr).'  WHERE '.implode(' OR ', $idParts), $all_values);
	}

	/**
	 * Insert an object into a table
	 * Note: an id field is ignored if specified.
	 *
	 * @access public
	 * @param string $tableName
	 * @param object|array $obj
	 * @param bool $raw (default: false)
	 * @param bool $replace (default: false)
	 * @param bool $ignoreId (default: true)
	 * @return mixed
	 */
	public function insertObject($tableName, $obj, $raw = false, $replace = false, $ignoreId = true) {
		if (!$this->isConnected()) $this->connect();

		$obj = (array)$obj;

		$tableName = $this->escape($tableName);

		if (!$raw && array_key_exists('id', $obj) && $ignoreId) {
			unset($obj['id']);
		}

		if (count($obj)<1) {
			return true;
		}

		//formulate an update statement based on the object parameters
		$objValues = array_values($obj);

		$preparedParamsArr = array();
		foreach ($obj as $key => $value) {
			$preparedParamsArr[] = '`' . $this->escape($key) . '`';
		}

		$preparedParamsStr = implode(', ', $preparedParamsArr);
		$preparedValuesStr = implode(', ', array_fill(0, count($objValues), '?'));

		if ($replace) {
			$statement = $this->pdo->prepare('REPLACE INTO `' . $tableName . '` (' . $preparedParamsStr . ') VALUES (' . $preparedValuesStr . ');');
		} else {
			$statement = $this->pdo->prepare('INSERT INTO `' . $tableName . '` (' . $preparedParamsStr . ') VALUES (' . $preparedValuesStr . ');');
		}

		//run the update on the object
		if (!$statement->execute($objValues)) {
			$errObj = $statement->errorInfo();

			//return false;
			throw new Exception($errObj[2]);
		}

		return $this->pdo->lastInsertId();
	}


	/**
	 * Check if an object exists.
	 *
	 * @access public
	 * @param string $tableName
	 * @param string $query (default: null)
	 * @param array $values (default: array())
	 * @param mixed $id (default: null)
	 * @return bool
	 */
	public function objectExists($tableName, $query = null, $values = array(), $id = null) {
		return is_object($this->getObject($tableName, $id));
	}


	/**
	 * Anti stored-XSS: Safe by default strategy
	 *
	 * Call htmlentities() on all string data returned from the database to ensure it is safe to pass to print().
	 * Areas of code that require unsafe HTML symbols will be updated to explicitly call html_entity_decode().
	 *
	 * @param string $tableName
	 * @param mixed $data
	 * @return mixed
	 */
	private function html_escape_strings($tableName, &$data) {
		// Disabled globally?
		if (!$this->html_escape_enabled) {
			return $data;
		}

		if (is_array($data)) {
			foreach ($data as $i => $v) {
				if (is_array($v) || is_object($v)) {
					$data[$i] = $this->html_escape_strings($tableName, $v);
				}
			}
			return $data;
		}

		if (is_object($data)) {
			foreach ($data as $k => $v) {
				if ($tableName === "no_html_escape" || !is_string($v)) {
					continue;
				}
				if (isset($this->html_escape_exceptions[$tableName]) && isset($this->html_escape_exceptions[$tableName][$k])) {
					continue;
				}
				$data->{$k} = htmlentities($v, ENT_QUOTES, 'UTF-8');
			}
			return $data;
		}

		if (is_string($data)) {
			return htmlentities($data, ENT_QUOTES, 'UTF-8');
		}

		return $data;
	}

	/**
	 * Get a filtered list of objects from the database.
	 *
	 * @access public
	 * @param string $tableName
	 * @param string $sortField (default: 'id')
	 * @param bool $sortAsc (default: true)
	 * @param mixed $numRecords (default: null)
	 * @param int $offset (default: 0)
	 * @param string $class (default: 'stdClass')
	 * @return array
	 */
	public function getObjects($tableName, $sortField = 'id', $sortAsc = true, $numRecords = null, $offset = 0, $class = 'stdClass') {
		if (!$this->isConnected()) $this->connect();

		$sortStr = '';
		if (!$sortAsc) {
			$sortStr = 'DESC';
		}

		// change sort fields for vlans and vrfs. ugly :/
	    if ($tableName=='vlans' && $sortField=='id') { $sortField = "vlanId"; }
	    if ($tableName=='vrf' && $sortField=='id') { $sortField = "vrfId"; }

		//we should escape all of the params that we need to
		$tableName = $this->escape($tableName);
		$sortField = $this->escape($sortField);

		if ($numRecords === null) {
			//get all (no limit)
			$statement = $this->pdo->query('SELECT * FROM `'.$tableName.'` ORDER BY `'.$sortField.'` '.$sortStr.';');
		} else {
			//get a limited range of objects
			$statement = $this->pdo->query('SELECT * FROM `'.$tableName.'` ORDER BY `'.$sortField.'` '.$sortStr.' LIMIT '.$numRecords.' OFFSET '.$offset.';');
		}

		$results = array();

		if (is_object($statement)) {
			$results = $statement->fetchAll($class == 'stdClass' ? PDO::FETCH_CLASS : PDO::FETCH_NUM);
		}

		return $this->html_escape_strings($tableName, $results);
	}

	/**
	 * Get all objects matching values
	 *
	 * @access public
	 * @param string $tableName
	 * @param mixed $query (default: null)
	 * @param array $values (default: array())
	 * @param string $class (default: 'stdClass')
	 * @return array
	 */
	public function getObjectsQuery($tableName, $query = null, $values = array(), $class = 'stdClass') {
		if (!$this->isConnected()) $this->connect();

		$statement = $this->pdo->prepare($query);

		//debug
		$this->log_query ($statement, $values);
		$statement->execute((array)$values);

		$results = array();

		if (is_object($statement)) {
			$results = $statement->fetchAll($class == 'stdClass' ? PDO::FETCH_CLASS : PDO::FETCH_NUM);
		}

		return $this->html_escape_strings($tableName, $results);
	}

	/**
	 * Get all objects groped by $groupField, array of (id,count(*)) pairs
	 *
	 * @param  string $tableName
	 * @param  string $groupField
	 * @return array
	 */
	public function getGroupBy($tableName, $groupField = 'id') {
		if (!$this->isConnected()) $this->connect();

		$statement = $this->pdo->prepare("SELECT `$groupField`,COUNT(*) FROM `$tableName` GROUP BY `$groupField`");

		//debug
		$this->log_query ($statement, array());
		$statement->execute();

		$results = array();

		if (is_object($statement)) {
			$results = $statement->fetchAll(PDO::FETCH_KEY_PAIR);
		}

		return $this->html_escape_strings($tableName, $results);
	}

	/**
	 * Get a single object from the database
	 *
	 * @access public
	 * @param mixed $tableName
	 * @param mixed $id (default: null)
	 * @param string $class (default: 'stdClass')
	 * @return object|null
	 */
	public function getObject($tableName, $id = null, $class = 'stdClass') {
		if (!$this->isConnected()) $this->connect();
		$id = intval($id);

		//has a custom query been provided?
		$tableName = $this->escape($tableName);

		//prepare a statement to get a single object from the database
		if ($id !== null) {
			$statement = $this->pdo->prepare('SELECT * FROM `'.$tableName.'` WHERE `id`=? LIMIT 1;');
			$statement->bindParam(1, $id, \PDO::PARAM_INT);
		} else {
			$statement = $this->pdo->prepare('SELECT * FROM `'.$tableName.'` LIMIT 1;');
		}

		//debug
		$this->log_query ($statement, array($id));
		$statement->execute();

		//we can then extract the single object (if we have a result)
		$resultObj = $statement->fetchObject($class);

		if ($resultObj === false) {
			return null;
		} else {
			return $this->html_escape_strings($tableName, $resultObj);
		}
	}

	/**
	 * Fetches single object from provided query
	 *
	 * @access public
	 * @param string $tableName
	 * @param mixed $query (default: null)
	 * @param array $values (default: array())
	 * @param string $class (default: 'stdClass')
	 * @return object|null
	 */
	public function getObjectQuery($tableName, $query = null, $values = array(), $class = 'stdClass') {
		if (!$this->isConnected()) $this->connect();

		$statement = $this->pdo->prepare($query);
		//debug
		$this->log_query ($statement, $values);
		$statement->execute((array)$values);

		$resultObj = $statement->fetchObject($class);

		if ($resultObj === false) {
			return null;
		} else {
			return $this->html_escape_strings($tableName, $resultObj);
		}
	}

	/**
	 * Escape $result_fields parameter
	 *
	 * @access public
	 * @param string|array $result_fields
	 * @return string
	 */
	public function escape_result_fields($result_fields) {
		if (empty($result_fields)) return "*";

		if (is_array($result_fields)) {
			foreach ($result_fields as $i => $f) $result_fields[$i] = "`$f`";
			$result_fields = implode(',', $result_fields);
		}
		return $result_fields;
	}

	/**
	 * Searches for object in database
	 *
	 * @access public
	 * @param string $table
	 * @param mixed $field
	 * @param mixed $value
	 * @param string $sortField (default: 'id')
	 * @param bool $sortAsc (default: true)
	 * @param bool $like (default: false)
	 * @param bool $negate (default: false)
	 * @param string|array $result_fields (default: "*")
	 * @return array
	 */
	public function findObjects($table, $field, $value, $sortField = 'id', $sortAsc = true, $like = false, $negate = false, $result_fields = "*") {
		$table = $this->escape($table);
		$field = $this->escape($field);
		$sortField = $this->escape($sortField);
		$like === true ? $operator = "LIKE" : $operator = "=";
		$negate === true ? $negate_operator = "NOT " : $negate_operator = "";

		$result_fields = $this->escape_result_fields($result_fields);

		// change sort fields for vlans and vrfs. ugly :/
	    if ($table=='vlans' && $sortField=='id') { $sortField = "vlanId"; }
	    if ($table=='vrf' && $sortField=='id') { $sortField = "vrfId"; }

	    // subnets
	    if ($table=='subnets' && $sortField=='subnet') {
	        return $this->getObjectsQuery($table, 'SELECT '.$result_fields.' FROM `' . $table . '` WHERE `'. $field .'`'.$negate_operator. $operator .'? ORDER BY LPAD(`subnet`,39,0) ' . ($sortAsc ? '' : 'DESC') . ';', array($value));
	    } else {
	        return $this->getObjectsQuery($table, 'SELECT '.$result_fields.' FROM `' . $table . '` WHERE `'. $field .'`'.$negate_operator. $operator .'? ORDER BY `'.$sortField.'` ' . ($sortAsc ? '' : 'DESC') . ';', array($value));
	    }
	}

	/**
	 * Searches for single object.
	 *
	 * @access public
	 * @param string $table
	 * @param mixed $field
	 * @param mixed $value
	 * @return object|null
	 */
	public function findObject($table, $field, $value) {
		$table = $this->escape($table);
		$field = $this->escape($field);

		return $this->getObjectQuery($table, 'SELECT * FROM `' . $table . '` WHERE `' . $field . '` = ? LIMIT 1;', array($value));
	}

	/**
	* Delete an object from the database
	*
	* @param string $tableName
	* @param int $id
	* @return bool
	*/
	public function deleteObject($tableName, $id) {
		$tableName = $this->escape($tableName);

		return $this->runQuery('DELETE FROM `'.$tableName.'` WHERE `id`=?;', array($id));
	}

	/**
	* Delete a list of objects from the database
	*
	* @param string $tableName
	* @param array $ids
	* @return bool
	*/
	public function deleteObjects($tableName, $ids) {
		$tableName = $this->escape($tableName);
		$num = count($ids);
		$idParts = array_fill(0, $num, '`id`=?');

		return $this->runQuery('DELETE FROM `'.$tableName.'` WHERE ' . implode(' OR ', $idParts), $ids);
	}

	/**
	 * Delete a list of objects from the database based on identifier
	 *
	 * @method deleteObjects
	 * @param  string $tableName
	 * @param  string $identifier
	 * @param  mixed $id
	 * @return bool
	 */
	public function deleteObjectsByIdentifier($tableName, $identifier = "id", $id = 0) {
		$tableName = $this->escape($tableName);
		$identifier = $this->escape($identifier);

		return $this->runQuery('DELETE FROM `'.$tableName.'` WHERE `'.$identifier.'` = ?', $id);
	}

	/**
	 * Delete specified row
	 *
	 * @access public
	 * @param string $tableName
	 * @param string $field
	 * @param string $value
	 * @param string $field2
	 * @param string $value2
	 * @return bool
	 */
	public function deleteRow($tableName, $field, $value, $field2=null, $value2 = null) {
		$tableName = $this->escape($tableName);
		$field = $this->escape($field);
		$field2 = $this->escape($field2);

		//multiple
		if(!empty($field2))
		return $this->runQuery('DELETE FROM `'.$tableName.'` WHERE `'.$field.'`=? and `'.$field2.'`=?;', array($value, $value2));
		else
		return $this->runQuery('DELETE FROM `'.$tableName.'` WHERE `'.$field.'`=?;', array($value));
	}

	/**
	 * truncate specified table
	 *
	 * @access public
	 * @param {string} $tableName
	 * @return bool
	 */
	public function emptyTable($tableName) {
		//escape table name
		$tableName = $this->escape($tableName);
		//execute
		return $this->runQuery('TRUNCATE TABLE `'.$tableName.'`;');
	}

	/**
	 * Begin SQL Transaction
	 *
	 * @access public
	 * @return bool
	 */
	public function beginTransaction() {
		return $this->pdo->beginTransaction();
	}

	/**
	 * Commit SQL Transaction
	 *
	 * @access public
	 * @return bool
	 */
	public function commit() {
		if (!$this->pdo->inTransaction())
			return false;
		return $this->pdo->commit();
	}

	/**
	 * Commit SQL Transaction
	 *
	 * @access public
	 * @return bool
	 */
	public function rollBack() {
		if (!$this->pdo->inTransaction())
			return false;
		return $this->pdo->rollBack();
	}
}


/**
*
*	PDO class wrapper
*		Database class
*
*/
class Database_PDO extends DB {


	/**
	 * SSL options for db connection
	 *
	 * (default value: array ())
	 *
	 * @var array
	 * @access protected
	 */
	protected $pdo_ssl_opts = array ();

	/**
	 * flag if installation is happening!
	 *
	 * (default value: false)
	 *
	 * @var bool
	 * @access public
	 */
	public $install = false;




	/**
	 * __construct function.
	 *
	 * @access public
	 * @param mixed $host (default: null)
	 * @param mixed $port (default: null)
	 * @param mixed $dbname (default: null)
	 * @param mixed $username (default: null)
	 * @param mixed $password (default: null)
	 * @param mixed $charset (default: null)
	 */
	public function __construct($username=null, $password=null, $host=null, $port=null, $dbname=null, $charset=null) {
		# set parameters
		$this->set_db_params ();
		# rewrite user/pass if requested - for installation
		$username==null ? : $this->username = $username;
		$password==null ? : $this->password = $password;
		$host==null 	? : $this->host = $host;
		$port==null 	? : $this->port = $port;
		$dbname==null 	? : $this->dbname = $dbname;

		# construct
		parent::__construct($this->username, $this->password, $this->charset, $this->ssl);
	}


	/**
	 * get database parameters from config.php
	 *
	 * @access private
	 * @return void
	 */
	private function set_db_params () {
		# use config file
		$db = Config::ValueOf('db');

		# set
		$this->host 	= $db['host'];
		$this->port 	= $db['port'];
		$this->username = $db['user'];
		$this->password = $db['pass'];
		$this->dbname 	= $db['name'];

		$this->ssl = false;
		if (@$db['ssl']===true) {

			$this->pdo_ssl_opts = array (
				'ssl_key'    => PDO::MYSQL_ATTR_SSL_KEY,
				'ssl_cert'   => PDO::MYSQL_ATTR_SSL_CERT,
				'ssl_ca'     => PDO::MYSQL_ATTR_SSL_CA,
				'ssl_cipher' => PDO::MYSQL_ATTR_SSL_CIPHER,
				'ssl_capath' => PDO::MYSQL_ATTR_SSL_CAPATH
			);

			$this->ssl = array();

			if ($db['ssl_verify']===false) {
				$this->ssl[1014] = false;	// PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT=1014 exists as of PHP 7.0.18 and PHP 7.1.4.
			}

			foreach ($this->pdo_ssl_opts as $key => $pdoopt) {
				if ($db[$key]) {
					$this->ssl[$pdoopt] = $db[$key];
				}
			}

		}

	}

	/**
	 * connect function.
	 *
	 * @access public
	 * @return void
	 */
	public function connect() {
		parent::connect();
		//@$this->pdo->query('SET NAMES \'' . $this->charset . '\';');
	}

	/**
	 * makeDsn function
	 *
	 * @access protected
	 * @return string
	 */
	protected function makeDsn() {
		# for installation
		if($this->install)	{ return 'mysql:host=' . $this->host . ';port=' . $this->port . ';charset=' . $this->charset; }
		else				{ return 'mysql:host=' . $this->host . ';port=' . $this->port . ';dbname=' . $this->dbname . ';charset=' . $this->charset; }
	}

	/**
	 * more generic static useful methods
	 *
	 * @access public
	 * @return array
	 */
	public function getColumnInfo() {
		$columns = $this->getObjectsQuery("no_html_escape",
			"SELECT `table_name`, `column_name`, `column_default`, `is_nullable`, `data_type`,`column_key`, `extra`
			FROM `columns`
			WHERE `table_schema`='" . $this->dbname . "';
		");

		$columnsByTable = array();

		if (!is_array($columns))
			return $columnsByTable;

		foreach ($columns as $column) {
			if (!isset($columnsByTable[$column->table_name])) {
				$columnsByTable[$column->table_name] = array();
			}

			$columnsByTable[$column->table_name][$column->column_name] = $column;
		}

		return $columnsByTable;
	}

	/**
	 * Returns field info.
	 *
	 * @access public
	 * @param bool $tableName (default: false)
	 * @param bool $field (default: false)
	 * @return object|null
	 */
	public function getFieldInfo ($tableName = false, $field = false) {
    	//escape
    	$tableName = $this->escape($tableName);
    	$field = $this->escape($field);
    	// fetch and return
    	return $this->getObjectQuery("no_html_escape", "SHOW FIELDS FROM `$tableName` where Field = ?", array($field));
	}

	/**
	 * getForeignKeyInfo function.
	 *
	 * @access public
	 * @return array
	 */
	public function getForeignKeyInfo() {
		$foreignLinks = $this->getObjectsQuery("no_html_escape",
			"SELECT i.`table_name`, k.`column_name`, i.`constraint_type`, i.`constraint_name`, k.`referenced_table_name`, k.`referenced_column_name`
			FROM `table_constraints` i
			LEFT JOIN `key_column_usage` k ON i.`constraint_name` = k.`constraint_name`
			WHERE i.`constraint_type` = 'FOREIGN KEY' AND i.`table_schema`='" . $this->dbname . "';
			");

		$foreignLinksByTable = array();
		$foreignLinksByRefTable = array();

		if (!is_array($foreignLinks))
			return array($foreignLinksByTable, $foreignLinksByRefTable);

		foreach ($foreignLinks as $foreignLink) {
			if (!isset($foreignLinksByTable[$foreignLink->table_name])) {
				$foreignLinksByTable[$foreignLink->table_name] = array();
			}

			if (!isset($foreignLinksByRefTable[$foreignLink->referenced_table_name])) {
				$foreignLinksByRefTable[$foreignLink->referenced_table_name] = array();
			}

			$foreignLinksByTable[$foreignLink->table_name][$foreignLink->column_name] = $foreignLink;
			$foreignLinksByRefTable[$foreignLink->referenced_table_name][$foreignLink->table_name] = $foreignLink;
		}

		return array($foreignLinksByTable, $foreignLinksByRefTable);
	}
}
