<?php
/**
*
*	PDO class wrapper
*		Database class
*
*/
class Database extends DatabaseBaseClass {

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
		require(dirname(__FILE__) . '/../../config.php');
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