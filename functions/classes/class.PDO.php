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
	 * (default value: 'utf8')
	 *
	 * @var string
	 * @access protected
	 */
	protected $charset = 'utf8';

	/**
	 * pdo
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $pdo = null;

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
	 * hosnamr
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
	}

	/**
	 * convert a date object/string ready for use in sql
	 *
	 * @access public
	 * @static
	 * @param mixed $date (default: null)
	 * @return void
	 */
	public static function toDate($date = null) {
		if (is_int($date)) {
			return date('Y-m-d H:i:s', $date);
		} else if (is_string($date)) {
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

			$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		} catch (\PDOException $e) {
			throw new Exception ("Could not connect to database! ".$e->getMessage());
		}

		@$this->pdo->query('SET NAMES \'' . $this->charset . '\';');
	}

	/**
	 * makeDsn function.
	 *
	 * @access protected
	 * @return void
	 */
	protected function makeDsn() {
		return ':charset=' . $this->charset;
	}

	/**
	 * resets conection.
	 *
	 * @access public
	 * @return void
	 */
	public function resetConn() {
		unset($this->pdo);
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
	 * @return void
	 */
	public static function unquote_outer($str) {
		$len = strlen($str);

		if ($len>1) {
			if ($str[0] == "'" && $str[$len-1] == "'") {
				return substr($str, 1, -1);
			} else if ($str[0] == "'") {
				return substr($str, 1);
			} else if ($str[$len-1] == "'") {
				return substr($str, 0, -1);
			}
		} else if ($len>0) {
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
	 * @return void
	 */
	public function isConnected() {
		return ($this->pdo !== null);
	}

	/**
	 * Returns last insert ID
	 *
	 * @access public
	 * @return void
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
	 * @return void
	 */
	public function runQuery($query, $values = array()) {
		if (!$this->isConnected()) $this->connect();

		$statement = $this->pdo->prepare($query);
		//debuq
		$this->log_query ($statement, $values);
		return $statement->execute((array)$values); //this array cast allows single values to be used as the parameter
	}

	/**
	 * Allow a value to be escaped, ready for insertion as a mysql parameter
	 * Note: for usage as a value (rather than prepared statements), you MUST manually quote around.
	 *
	 * @access public
	 * @param mixed $str
	 * @return void
	 */
	public function escape($str) {
		if (!$this->isConnected()) $this->connect();

		return $this->unquote_outer($this->pdo->quote((string)$str));
	}

	/**
	 * Get a quick number of objects in a table
	 *
	 * @access public
	 * @param mixed $tableName
	 * @return void
	 */
	public function numObjects($tableName) {
		if (!$this->isConnected()) $this->connect();

		$tableName = $this->escape($tableName);
		$statement = $this->pdo->prepare('SELECT COUNT(*) as `num` FROM `'.$tableName.'`;');

		//debuq
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
	 * @return void
	 */
	public function numObjectsFilter($tableName, $method, $value, $like = false) {
		if (!$this->isConnected()) $this->connect();

		$like === true ? $operator = "LIKE" : $operator = "=";

		$tableName = $this->escape($tableName);
		$statement = $this->pdo->prepare('SELECT COUNT(*) as `num` FROM `'.$tableName.'` where `'.$method.'` '.$operator.' ?;');

		//debuq
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
	 * @return void
	 */
	public function updateObject($tableName, $obj, $primarykey = 'id', $primarykey2 = null) {
		if (!$this->isConnected()) $this->connect();

		$obj = (array)$obj;

		//we cannot update an object without an id specified so quit
		if (!isset($obj[$primarykey])) {
			throw new Exception('Missing primary key');
			return false;
		}

		$tableName = $this->escape($tableName);

		//get the objects id from the provided object and knock it off from the object so we dont try to update it
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

		$preparedParamStr = implode(',', $preparedParamArr);

		//primary key 2?
		if(!is_null($primarykey2))
		$statement = $this->pdo->prepare('UPDATE `' . $tableName . '` SET ' . $preparedParamStr . ' WHERE `' . $primarykey . '`=? AND `' . $primarykey2 . '`=?;');
		else
		$statement = $this->pdo->prepare('UPDATE `' . $tableName . '` SET ' . $preparedParamStr . ' WHERE `' . $primarykey . '`=?;');

		//merge the parameters and values
		$paramValues = array_merge(array_values($obj), $objId);

		//debuq
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
	 * @return void
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
	 * @return void
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
	 * @return void
	 */
	public function objectExists($tableName, $query = null, $values = array(), $id = null) {
		return is_object($this->getObject($tableName, $id));
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
	 * @return void
	 */
	public function getObjects($tableName, $sortField = 'id', $sortAsc = true, $numRecords = null, $offset = 0, $class = 'stdClass') {
		if (!$this->isConnected()) $this->connect();

		$sortStr = '';
		if (!$sortAsc) {
			$sortStr = 'DESC';
		}

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
			while ($newObj = $statement->fetchObject($class)) {
				$results[] = $newObj;
			}
		}

		return $results;
	}


	/**
	 * use this function to conserve memory and read rows one by one rather than reading all of them
	 *
	 * @access public
	 * @param mixed $query (default: null)
	 * @param array $values (default: array())
	 * @param mixed $callback (default: null)
	 * @return void
	 */
	public function getObjectsQueryIncremental($query = null, $values = array(), $callback = null) {
		if (!$this->isConnected()) $this->connect();

		$statement = $this->pdo->prepare($query);

		//debuq
		$this->log_query ($statement, $values);
		$statement->execute((array)$values);

		if (is_object($statement)) {
			if ($callback) {
				while ($newObj = $statement->fetchObject('stdClass')) {
					if ($callback($newObj)===false) {
						return false;
					}
				}
			}
		}

		return true;
	}


	/**
	 * Get all objects matching values
	 *
	 * @access public
	 * @param mixed $query (default: null)
	 * @param array $values (default: array())
	 * @param string $class (default: 'stdClass')
	 * @return void
	 */
	public function getObjectsQuery($query = null, $values = array(), $class = 'stdClass') {
		if (!$this->isConnected()) $this->connect();

		$statement = $this->pdo->prepare($query);

		//debug
		$this->log_query ($statement, $values);
		$statement->execute((array)$values);

		$results = array();

		if (is_object($statement)) {
			while ($newObj = $statement->fetchObject($class)) {
				$results[] = $newObj;
			}
		}

		return $results;
	}

	/**
	 * Get a single object from the database
	 *
	 * @access public
	 * @param mixed $tableName
	 * @param mixed $id (default: null)
	 * @param string $class (default: 'stdClass')
	 * @return void
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

		//debuq
		$this->log_query ($statement, array($id));
		$statement->execute();

		//we can then extract the single object (if we have a result)
		$resultObj = $statement->fetchObject($class);

		if ($resultObj === false) {
			return null;
		} else {
			return $resultObj;
		}
	}

	/**
	 * Fetches single object from provided query
	 *
	 * @access public
	 * @param mixed $query (default: null)
	 * @param array $values (default: array())
	 * @param string $class (default: 'stdClass')
	 * @return void
	 */
	public function getObjectQuery($query = null, $values = array(), $class = 'stdClass') {
		if (!$this->isConnected()) $this->connect();

		$statement = $this->pdo->prepare($query);
		//debuq
		$this->log_query ($statement, $values);
		$statement->execute((array)$values);

		$resultObj = $statement->fetchObject($class);

		if ($resultObj === false) {
			return null;
		} else {
			return $resultObj;
		}
	}

	/**
	 * Get single value
	 *
	 * @access public
	 * @param mixed $query (default: null)
	 * @param array $values (default: array())
	 * @param string $class (default: 'stdClass')
	 * @return void
	 */
	public function getValueQuery($query = null, $values = array(), $class = 'stdClass') {
		$obj = $this->getObjectQuery($query, $values, $class);

		if (is_object($obj)) {
			$obj = (array)$obj;
			return reset($obj);
		} else {
			return null;
		}
	}

	/**
	 * Searches for object in database
	 *
	 * @access public
	 * @param mixed $table
	 * @param mixed $field
	 * @param mixed $value
	 * @param string $sortField (default: 'id')
	 * @param bool $sortAsc (default: true)
	 * @param bool $like (default: false)
	 * @param bool $negate (default: false)
	 * @return void
	 */
	public function findObjects($table, $field, $value, $sortField = 'id', $sortAsc = true, $like = false, $negate = false, $result_fields = "*") {
		$table = $this->escape($table);
		$field = $this->escape($field);
		$sortField = $this->escape($sortField);
		$like === true ? $operator = "LIKE" : $operator = "=";
		$negate === true ? $negate_operator = "NOT " : $negate_operator = "";

		// set fields
		if($result_fields!="*") {
    		$result_fields_arr = array();
    		foreach ($result_fields as $f) {
        		$result_fields_arr[] = "`$f`";
    		}
    		// implode
    		$result_fields = implode(",", $result_fields);
		}

        // subnets
        if ($table=="subnets" && $sortField=="subnet_int") {
    		return $this->getObjectsQuery('SELECT '.$result_fields.',subnet*1 as subnet_int FROM `' . $table . '` WHERE `'. $field .'`'.$negate_operator. $operator .'? ORDER BY `'.$sortField.'` ' . ($sortAsc ? '' : 'DESC') . ';', array($value));
        }
        else {
    		return $this->getObjectsQuery('SELECT '.$result_fields.' FROM `' . $table . '` WHERE `'. $field .'`'.$negate_operator. $operator .'? ORDER BY `'.$sortField.'` ' . ($sortAsc ? '' : 'DESC') . ';', array($value));
        }
	}

	/**
	 * Searches for single object.
	 *
	 * @access public
	 * @param mixed $table
	 * @param mixed $field
	 * @param mixed $value
	 * @return void
	 */
	public function findObject($table, $field, $value) {
		$table = $this->escape($table);
		$field = $this->escape($field);

		return $this->getObjectQuery('SELECT * FROM `' . $table . '` WHERE `' . $field . '` = ? LIMIT 1;', array($value));
	}

	/**
	 * Get list of items.
	 *
	 * @access public
	 * @param mixed $query (default: null)
	 * @param array $values (default: array())
	 * @param string $class (default: 'stdClass')
	 * @return void
	 */
	public function getList($query = null, $values = array(), $class = 'stdClass') {
		$objs = $this->getObjectsQuery($query, $values, $class);

		$list = array();
		foreach ($objs as $obj) {
			$columns = array_values((array)$obj);
			$list[] = $columns[0];
		}

		return $list;
	}

	/**
	* Delete an object from the database
	*
	* @param {string} table name
	* @param {int} object id
	* @return {boolean} success
	*/
	public function deleteObject($tableName, $id) {
		$tableName = $this->escape($tableName);

		return $this->runQuery('DELETE FROM `'.$tableName.'` WHERE `id`=?;', array($id));
	}

	/**
	* Delete a list of objects from the database
	*
	* @param {string} table name
	* @param {array} list of ids
	* @return {boolean} success
	*/
	public function deleteObjects($tableName, $ids) {
		$tableName = $this->escape($tableName);
		$num = count($ids);
		$idParts = array_fill(0, $num, '`id`=?');

		return $this->runQuery('DELETE FROM `'.$tableName.'` WHERE ' . implode(' OR ', $idParts), $ids);
	}

	/**
	 * Delete specified row
	 *
	 * @access public
	 * @param {string} $tableName
	 * @param {string $field
	 * @param {string $value
	 * @return void
	 */
	public function deleteRow($tableName, $field, $value, $field2=null, $value2 = null) {
		$tableName = $this->escape($tableName);
		$field = $this->escape($field);

		//multiple
		if(!is_null($field2))
		return $this->runQuery('DELETE FROM `'.$tableName.'` WHERE `'.$field.'`=? and `'.$field2.'`=?;', array($value, $value2));
		else
		return $this->runQuery('DELETE FROM `'.$tableName.'` WHERE `'.$field.'`=?;', array($value));
	}

	/**
	 * truncate specified table
	 *
	 * @access public
	 * @param {string} $tableName
	 * @return void
	 */
	public function emptyTable($tableName) {
		//escape talbe name
		$tableName = $this->escape($tableName);
		//execute
		return $this->runQuery('TRUNCATE TABLE `'.$tableName.'`;');
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
	 * flag if installation is happenig!
	 *
	 * (default value: false)
	 *
	 * @var bool
	 * @access public
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
		require( dirname(__FILE__) . '/../../config.php' );
		# set
		$this->host 	= $db['host'];
		$this->port 	= $db['port'];
		$this->username = $db['user'];
		$this->password = $db['pass'];
		$this->dbname 	= $db['name'];

		$this->ssl = false;
		if ($db['ssl']===true) {

			$this->pdo_ssl_opts = array (
				'ssl_key'    => PDO::MYSQL_ATTR_SSL_KEY,
				'ssl_cert'   => PDO::MYSQL_ATTR_SSL_CERT,
				'ssl_ca'     => PDO::MYSQL_ATTR_SSL_CA,
				'ssl_cipher' => PDO::MYSQL_ATTR_SSL_CIPHER,
				'ssl_capath' => PDO::MYSQL_ATTR_SSL_CAPATH
			);

			$this->ssl = array();

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
	 * @return void
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
	 * @return void
	 */
	public function getColumnInfo() {
		$columns = $this->getObjectsQuery("
			SELECT `table_name`, `column_name`, `column_default`, `is_nullable`, `data_type`,`column_key`, `extra`
			FROM `columns`
			WHERE `table_schema`='" . $this->dbname . "';
		");

		$columnsByTable = array();
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
	 * @return void|object
	 */
	public function getFieldInfo ($tableName = false, $field = false) {
    	//escape
    	$tableName = $this->escape($tableName);
    	$field = $this->escape($field);
    	// fetch and return
    	return $this->getObjectQuery("SHOW FIELDS FROM `$tableName` where Field = ?", array($field));

	}

	/**
	 * getForeignKeyInfo function.
	 *
	 * @access public
	 * @return void
	 */
	public function getForeignKeyInfo() {
		$foreignLinks = $this->getObjectsQuery("
			SELECT i.`table_name`, k.`column_name`, i.`constraint_type`, i.`constraint_name`, k.`referenced_table_name`, k.`referenced_column_name`
			FROM `table_constraints` i
			LEFT JOIN `key_column_usage` k ON i.`constraint_name` = k.`constraint_name`
			WHERE i.`constraint_type` = 'FOREIGN KEY' AND i.`table_schema`='" . $this->dbname . "';
		");

		$foreignLinksByTable = array();
		$foreignLinksByRefTable = array();
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



?>
