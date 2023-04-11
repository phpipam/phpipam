<?php

/**
 * phpIPAM class with common functions, used in all other classes
 *
 * @author: Miha Petkovsek <miha.petkovsek@gmail.com>
 */
class Common_functions  {

	/**
     * from api flag
     *
     * (default value: false)
     *
     * @var bool
     */
    public $api = false;

	/**
	 * settings
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access public
	 */
	public $settings = null;

	/**
	 * If Jdon validation error occurs it will be saved here
	 *
	 * (default value: false)
	 *
	 * @var bool
	 * @access public
	 */
	public $json_error = false;

    /**
     * Default font
     *
     * (default value: "<font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:12px)
     *
     * @var string
     * @access public
     */
    public $mail_font_style = "<font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:12px;color:#333;'>";

    /**
     * Default font
     *
     * (default value: "<font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:12px)
     *
     * @var string
     * @access public
     */
    public $mail_font_style_light = "<font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:11px;color:#777;'>";

    /**
     * Default font for links
     *
     * (default value: "<font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:12px)
     *
     * @var string
     * @access public
     */
    public $mail_font_style_href = "<font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:12px;color:#a0ce4e;'>";

	/**
	 * Database
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Database;

	/**
	 * Result
	 *
	 * @var mixed
	 * @access public
	 */
	public $Result;

	/**
	 * Log
	 *
	 * @var mixed
	 * @access public
	 */
	public $Log;

	/**
	 * Net_IPv4
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Net_IPv4;

	/**
	 * Net_IPv6
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Net_IPv6;

	/**
	 * (array) IP address types from Addresses object
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access public
	 */
	public $address_types = null;

	/**
	 * NET_DNS object
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $DNS2;

	/**
	 * debugging flag
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $debugging;

	/**
	 * Cache mac vendor objects
	 * @var array|null
	 */
	private $mac_address_vendors = null;



	/**
	 * __construct function
	 *
	 * @access public
	 */
	public function __construct () {
		# debugging
		$this->set_debugging( Config::ValueOf('debugging') );
	}

	/**
	 *	@version handling
	 *	--------------------------------
	 */

	 /**
	 * Compare dotted version numbers 1.21.0 <=> 1.4.10
	 *
	 * @access public
	 * @param string $verA
	 * @param mixed $verB
	 * @return int
	 */
	public function cmp_version_strings($verA, $verB) {
		$a = explode('.', $verA);
		$b = explode('.', $verB);

		if ($a[0] != $b[0]) return $a[0] < $b[0] ? -1 : 1;			// 1.x.y is less than 2.x.y
		if (strcmp($a[1], $b[1]) != 0) return strcmp($a[1], $b[1]);	// 1.21.y is less than 1.3.y
		if ($a[2] != $b[2]) return $a[2] < $b[2] ? -1 : 1;			// 1.4.9 is less than 1.4.10
		return 0;
	}








	/**
	 *	@general fetch methods
	 *	--------------------------------
	 */


	/**
	 * Fetch all objects from specified table in database
	 *
	 * @access public
	 * @param mixed $table
	 * @param mixed $sortField (default:id)
	 * @param mixed bool (default:true)
	 * @return false|array
	 */
	public function fetch_all_objects ($table=null, $sortField="id", $sortAsc=true) {
		# null table
		if(is_null($table)||strlen($table)==0) return false;

		$cached_item = $this->cache_check("fetch_all_objects", "t=$table f=$sortField o=$sortAsc");
		if(is_object($cached_item)) return $cached_item->result;

		# fetch
		try { $res = $this->Database->getObjects($table, $sortField, $sortAsc); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# save
		if (is_array($res)) {
			foreach ($res as $r) {
				$this->cache_write ($table, $r);
			}
		}
		# result
		$result = (is_array($res) && sizeof($res)>0) ? $res : false;
		$this->cache_write ("fetch_all_objects", (object) ["id"=>"t=$table f=$sortField o=$sortAsc", "result" => $result]);
		return $result;
	}

	/**
	 * Fetches specified object specified table in database
	 *
	 * @access public
	 * @param mixed $table
	 * @param mixed $method
	 * @param mixed $value
	 * @return false|object
	 */
	public function fetch_object ($table, $method, $value) {
		// checks
		if(!is_string($table)) return false;
		if(strlen($table)==0)  return false;
		if(is_null($method))   return false;
		if(is_null($value))    return false;
		if($value===0)         return false;

		# check cache
		$cached_item = $this->cache_check($table, $value);
		if(is_object($cached_item))
			return $cached_item;

		# null method
		$method = is_null($method) ? "id" : $this->Database->escape($method);

		try { $res = $this->Database->getObjectQuery("SELECT * from `$table` where `$method` = ? limit 1;", array($value)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}

		# save to cache array
		$this->cache_write ($table, $res);

		return is_object($res) ? $res : false;
	}

	/**
	 * Fetches multiple objects in specified table in database
	 *
	 *	doesnt cache
	 *
	 * @access public
	 * @param mixed $table
	 * @param mixed $field
	 * @param mixed $value
	 * @param string $sortField (default: 'id')
	 * @param bool $sortAsc (default: true)
	 * @param bool $like (default: false)
	 * @param array|mixed $result_fields (default: *)
	 * @return bool|array
	 */
	public function fetch_multiple_objects ($table, $field, $value, $sortField = 'id', $sortAsc = true, $like = false, $result_fields = "*") {
		# null table
		if(is_null($table)||strlen($table)==0) return false;
		else {
			try { $res = $this->Database->findObjects($table, $field, $value, $sortField, $sortAsc, $like, false, $result_fields); }
			catch (Exception $e) {
				$this->Result->show("danger", _("Error: ").$e->getMessage());
				return false;
			}
			# save to cache
			if ($result_fields==="*" && is_array($res)) { // Only cache objects containing all fields
				foreach ($res as $r) {
					$this->cache_write ($table, $r);
				}
			}
			# result
			return (is_array($res) && sizeof($res)>0) ? $res : false;
		}
	}

	/**
	 * Count objects in database.
	 *
	 * @access public
	 * @param mixed $table
	 * @param mixed $field
	 * @param mixed $val (default: null)
	 * @param bool $like (default: false)
	 * @return int
	 */
	public function count_database_objects ($table, $field, $val=null, $like = false) {
		# if null
		try { $cnt = $this->Database->numObjectsFilter($table, $field, $val, $like); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		return $cnt;
	}

	/**
	 * Count all objects in database.
	 *
	 * @param  string $table
	 * @param  string $field
	 * @return array|false
	 */
	public function count_all_database_objects ($table, $field) {
		try { $cnt = $this->Database->getGroupBy($table, $field); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		return $cnt;
	}

	/**
	 * Returns table schema information
	 *
	 * @param  string $tableName
	 * @return array
	 */
	public function getTableSchemaByField($tableName) {
		$results = [];

		if (!is_string($tableName)) return $results;

		$tableName = $this->Database->escape($tableName);

		$cached_item = $this->cache_check("getTableSchemaByField", "t=$tableName");
		if(is_object($cached_item)) return $cached_item->result;

		try {
			$query = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?;";
			$schema = $this->Database->getObjectsQuery($query, [$this->Database->dbname, $tableName]);
		} catch (\Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return $results;
		}

		if (is_array($schema)) {
			foreach ($schema as $col) {
				$results[$col->COLUMN_NAME] = $col;
			}
		}
		$this->cache_write("getTableSchemaByField", (object) ["id"=>"t=$tableName", "result" => $results]);
		return $results;
	}

	/**
	 * Get all admins that are set to receive changelog
	 *
	 * @access public
	 * @param bool|mixed $subnetId
	 * @return bool|array
	 */
	public function changelog_mail_get_recipients ($subnetId = false) {
    	// fetch all users with mailNotify
        $notification_users = $this->fetch_multiple_objects ("users", "mailChangelog", "Yes", "id", true);
        // recipients array
        $recipients = array();
        // any ?
        if (is_array($notification_users)) {
        	if(sizeof($notification_users)>0) {
	         	// if subnetId is set check who has permissions
	        	if (isset($subnetId)) {
	             	foreach ($notification_users as $u) {
	                	// inti object
	                	$Subnets = new Subnets ($this->Database);
	                	//check permissions
	                	$subnet_permission = $Subnets->check_permission($u, $subnetId);
	                	// if 3 than add
	                	if ($subnet_permission==3) {
	                    	$recipients[] = $u;
	                	}
	            	}
	        	}
	        	else {
	            	foreach ($notification_users as $u) {
	                	if($u->role=="Administrator") {
	                    	$recipients[] = $u;
	                	}
	            	}
	        	}
	        }
        	return sizeof($recipients)>0 ? $recipients : false;
        }
        else {
            return false;
        }
	}




	/**
	 * fetches settings from database
	 *
	 * @access private
	 * @return mixed
	 */
	public function get_settings () {
		if (is_object($this->settings))
			return $this->settings;

		// fetch_object results are cached in $Database->cache.
		$settings = $this->fetch_object("settings", "id", 1);

		if (!is_object($settings))
			return false;

		#save
		$this->settings = $settings;

		return $this->settings;
	}


    /**
     * Write result to cache.
     *
     * @access protected
     * @param string $table
     * @param mixed $object
     * @return void
     */
    protected function cache_write ($table, $object) {
        if (!is_object($object))
            return;

        // Exclude exceptions from caching
        if ($this->cache_check_exceptions($table))
            return;

        // get and check id property
        $identifier = $this->cache_set_identifier ($table);

        if (!property_exists($object, $identifier))
            return;

        $id = $object->{$identifier};

        // already set
        if (isset($this->Database->cache[$table][$identifier][$id]))
            return;

        // add ip ?
        $ip_check = $this->cache_check_add_ip($table);
        if ($ip_check!==false) {
            $object->ip = $this->transform_address ($object->{$ip_check}, "dotted");
        }

        // save
        $this->Database->cache[$table][$identifier][$id] = clone $object;
    }

    /**
     * Check if caching is not needed
     *
     * @access protected
     * @param mixed $table
     * @return bool
     */
    protected function cache_check_exceptions ($table) {
        $exceptions = [
            "firewallZoneSubnet"=>1,
            "circuitsLogicalMapping" =>1,
            "php_sessions"=>1];

        // check
        return isset($exceptions[$table]) ? true : false;
    }

    /**
     * Check if ip is to be added to result
     *
     * @access protected
     * @param mixed $table
     * @return bool|mixed
     */
    protected function cache_check_add_ip ($table) {
        $ip_tables = ["subnets"=>"subnet", "ipaddresses"=>"ip_addr"];

        // check
        return array_key_exists ($table, $ip_tables) ? $ip_tables[$table] : false;
    }

    /**
     * Set identifier for table - exceptions.
     *
     * @access protected
     * @param string $table
     * @return string
     */
    protected function cache_set_identifier ($table) {
        // Tables with different primary keys
        $mapings = [
            'userGroups'=>'g_id',
            'lang'=>'l_id',
            'vlans'=>'vlanId',
            'vrf'=>'vrfId',
            'changelog'=>'cid',
            'widgets'=>'wid',
            'deviceTypes'=>'tid',
            'nominatim_cache'=>'sha256'];

        return isset($mapings[$table]) ? $mapings[$table] : 'id';
    }

    /**
     * Checks if object alreay exists in cache..
     *
     * @access protected
     * @param mixed $table
     * @param mixed $id
     * @return bool|array
     */
    protected function cache_check ($table, $id) {
        // get identifier
        $identifier = $this->cache_set_identifier ($table);

        // check if cache is already set, otherwise return false
        if (isset($this->Database->cache[$table][$identifier][$id]))
            return clone $this->Database->cache[$table][$identifier][$id];

        return false;
    }

	/**
	 * Sets debugging
	 *
	 * @access public
	 * @param bool $debug (default: false)
	 * @return void
	 */
	public function set_debugging ($debug = false) {
		$this->debugging = $debug==true ? true : false;
	}

	/**
	 * Gets debugging
	 *
	 * @access public
	 * @return bool
	 */
	public function get_debugging () {
		return $this->debugging;
	}

	/**
	 * Initializes PEAR Net IPv4 object
	 *
	 * @access public
	 * @return void
	 */
	public function initialize_pear_net_IPv4 () {
		//initialize NET object
		if(!is_object($this->Net_IPv4)) {
			require_once( dirname(__FILE__) . '/../../functions/PEAR/Net/IPv4.php' );
			//initialize object
			$this->Net_IPv4 = new Net_IPv4();
		}
	}

	/**
	 * Initializes PEAR Net IPv6 object
	 *
	 * @access public
	 * @return void
	 */
	public function initialize_pear_net_IPv6 () {
		//initialize NET object
		if(!is_object($this->Net_IPv6)) {
			require_once( dirname(__FILE__) . '/../../functions/PEAR/Net/IPv6.php' );
			//initialize object
			$this->Net_IPv6 = new Net_IPv6();
		}
	}

	/**
	 * Initializes PEAR Net IPv6 object
	 *
	 * @access public
	 * @return void
	 */
	public function initialize_pear_net_DNS2 () {
		//initialize NET object
		if(!is_object($this->DNS2)) {
			require_once( dirname(__FILE__) . '/../../functions/PEAR/Net/DNS2.php' );
			//initialize object
			$this->DNS2 = new Net_DNS2_Resolver(array("timeout"=>2));
		}
	}

	/**
	 * Strip tags from array or field to protect from XSS
	 *
	 * @access public
	 * @param array|string $input
	 * @return array|string
	 */
	public function strip_input_tags ($input) {
		if(is_array($input)) {
			foreach($input as $k=>$v) {
				if(is_array($v)) {
					$input[$k] = $this->strip_input_tags($v);
					continue;
				}
				$input[$k] = is_null($v) ? NULL : strip_tags($v);
			}
			# stripped array
			return $input;
		}

		// not array
		return is_null($input) ? NULL : strip_tags($input);
	}

	/**
	 * Remove <script>, <iframe> and JS HTML event attributes from HTML to protect from XSS
	 *
	 * @param   string  $html
	 * @return  string
	 */
	public function noxss_html($html) {
		if (!is_string($html) || strlen($html)==0)
			return "";

		// Convert encoding to UTF-8
		$html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

		// Throw loadHTML() parsing errors
		$err_mode = libxml_use_internal_errors(false);

		try {
			$dom = new \DOMDocument();

			if ($dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOBLANKS | LIBXML_NOWARNING | LIBXML_NOERROR) === false)
				return "";

			$banned_elements = ['script', 'iframe', 'embed'];
			$remove_elements = [];

			$elements = $dom->getElementsByTagName('*');

			if (is_object($elements) && $elements->length>0) {
				foreach($elements as $e) {
					if (in_array($e->nodeName, $banned_elements)) {
						$remove_elements[] = $e;
						continue;
					}

					if (!$e->hasAttributes())
						continue;

					// remove on* HTML event attributes
					foreach ($e->attributes as $attr) {
						if (substr($attr->nodeName,0,2) == "on")
							$e->removeAttribute($attr->nodeName);
					}
				}

				// Remove banned elements
				foreach($remove_elements as $e)
					$e->parentNode->removeChild($e);

				// Return sanitised HTML
				$html = $dom->saveHTML();
			}
		} catch (Exception $e) {
			$html = "";
		}

		// restore error mode
		libxml_use_internal_errors($err_mode);

		return is_string($html) ? $html : "";
	}

	/**
	 * Changes empty array fields to specified character
	 *
	 * @access public
	 * @param array|object $fields
	 * @param string $char (default: "/")
	 * @return array
	 */
	public function reformat_empty_array_fields ($fields, $char = "/") {
    	$out = array();
    	// loop
		foreach($fields as $k=>$v) {
    		if(is_array($v)) {
        		$out[$k] = $v;
    		}
    		else {
    			if(is_null($v) || strlen($v)==0) {
    				$out[$k] = 	$char;
    			} else {
    				$out[$k] = $v;
    			}
    		}
		}
		# result
		return $out;
	}

	/**
	 * Removes empty array fields
	 *
	 * @access public
	 * @param array $fields
	 * @return array
	 */
	public function remove_empty_array_fields ($fields) {
    	// init
    	$out = array();
    	// loop
    	if(is_array($fields)) {
			foreach($fields as $k=>$v) {
				if(is_null($v) || strlen($v)==0) {
				}
				else {
					$out[$k] = $v;
				}
			}
		}
		# result
		return $out;
	}

	/**
	 * Trim whitespace form array objects
	 *
	 * @method trim_array_objects
	 * @param  string|array $fields
	 * @return string|array
	 */
	public function trim_array_objects ($fields) {
		if(is_array($fields)) {
	    	// init
	    	$out = array();
	    	// loop
			foreach($fields as $k=>$v) {
				$out[$k] = trim($v);
			}
		}
		else {
			$out = trim($fields);
		}
		# result
		return $out;
	}

	/**
	 * Strip XSS on value print
	 *
	 * @method strip_xss
	 *
	 * @param  string $input
	 *
	 * @return string
	 */
	public function strip_xss ($input) {
		return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Detect the encoding used for a string and convert to UTF-8
	 *
	 * @method convert_encoding_to_UTF8
	 * @param  string $string
	 * @return string
	 */
	public function convert_encoding_to_UTF8($string) {
		//convert encoding if necessary
		return mb_convert_encoding($string, 'UTF-8', mb_detect_encoding($string, 'ASCII, UTF-8, ISO-8859-1, auto', true));
	}

	/**
	 * Function to verify checkbox if 0 length
	 *
	 * @access public
	 * @param mixed $field
	 * @return int|mixed
	 */
	public function verify_checkbox ($field) {
		return (!isset($field) || strlen($field)==0) ? 0 : escape_input($field);
	}

	/**
	 * identify ip address type - ipv4 or ipv6
	 *
	 * @access public
	 * @param mixed $address
	 * @return mixed IP version
	 */
	public function identify_address ($address) {
		# dotted representation
		if (strpos($address, ':') !== false) return 'IPv6';
		if (strpos($address, '.') !== false) return 'IPv4';
		# numeric representation
		if (is_numeric($address)) {
			if($address <= 4294967295) return 'IPv4'; // 4294967295 = '255.255.255.255'
			return 'IPv6';
		} else {
			# decimal representation
			if(strlen($address) < 12) return 'IPv4';
			return 'IPv6';
		}
	}

	/**
	 * Alias of identify_address_format function
	 *
	 * @access public
	 * @param mixed $address
	 * @return mixed
	 */
	public function get_ip_version ($address) {
		return $this->identify_address ($address);
	}

	/**
	 * Transforms array to log format
	 *
	 * @access public
	 * @param mixed $logs
	 * @param bool $changelog
	 * @return mixed
	 */
	public function array_to_log ($logs, $changelog = false) {
		$result = "";

		if(!is_array($logs))
			return $result;

		foreach($logs as $key=>$req) {
			# ignore __ and PHPSESSID
			if( substr($key,0,2)=='__' || substr($key,0,9)=='PHPSESSID' || substr($key,0,4)=='pass' || $key=='plainpass' || $key=='values')
				continue;

			// NOTE The colon character ":" is reserved as it used in array_to_log for implode/explode.
			// Replace colon (U+003A) with alternative characters.
			// Using JSON encode/decode would be more appropiate but we need to maintain backwards compatibility with historical changelog/logs data in the database.
			if ($req == "mac")
				$req = strtr($req, ':', '-'); # Mac-addresses, replace Colon U+003A with hyphen U+002D

			if (strpos($req, ':')!==false)
				$req = strtr($req, ':', '.'); # Default, replace Colon U+003A with Full Stop U+002E.

			$result .= ($changelog===true) ? "[$key]: $req<br>" : " ". $key . ": " . $req . "<br>";
		}
		return $result;
	}

	/**
	 * Transforms seconds to hms
	 *
	 * @access public
	 * @param mixed $sec
	 * @param bool $padHours (default: false)
	 * @return mixed
	 */
	public function sec2hms($sec, $padHours = false) {
	    // holds formatted string
	    $hms = "";

	    // get the number of hours
	    $hours = intval(intval($sec) / 3600);

	    // add to $hms, with a leading 0 if asked for
	    $hms .= ($padHours)
	          ? str_pad($hours, 2, "0", STR_PAD_LEFT). ':'
	          : $hours. ':';

	    // get the seconds
	    $minutes = intval(($sec / 60) % 60);

	    // then add to $hms (with a leading 0 if needed)
	    $hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ':';

	    // seconds
	    $seconds = intval($sec % 60);

	    // add to $hms, again with a leading 0 if needed
	    $hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);

	    // return hms
	    return $hms;
	}

	/**
	 * Shortens text to max chars
	 *
	 * @access public
	 * @param mixed $text
	 * @param int $chars (default: 25)
	 * @return mixed
	 */
	public function shorten_text($text, $chars = 25) {
		// minimum length = 8
		if ($chars < 8) $chars = 8;
		// count input text size
		$origLen = mb_strlen($text);
		// cut unwanted chars
		if ($origLen > $chars) {
			$text = mb_substr($text, 0, $chars-3) . '...';
		}
		return $text;
	}

	/**
	 * Reformats MAC address to requested format
	 *
	 * @access public
	 * @param mixed $mac
	 * @param string $format (default: 1)
	 *      1 : 00:66:23:33:55:66
	 *      2 : 00-66-23-33-55-66
	 *      3 : 0066.2333.5566
	 *      4 : 006623335566
	 * @return mixed
	 */
	public function reformat_mac_address ($mac, $format = 1) {
    	// strip al tags first
    	$mac = strtolower(str_replace(array(":",".","-"), "", $mac));
    	// format 4
    	if ($format==4) {
        	return $mac;
    	}
    	// format 3
    	if ($format==3) {
        	$mac = str_split($mac, 4);
        	$mac = implode(".", $mac);
    	}
    	// format 2
    	elseif ($format==2) {
        	$mac = str_split($mac, 2);
        	$mac = implode("-", $mac);
    	}
    	// format 1
    	else {
        	$mac = str_split($mac, 2);
        	$mac = implode(":", $mac);
    	}
    	// return
    	return $mac;
	}

	/**
	 * Return port number used to access the site
	 *
	 * @access  private
	 * @return  int
	 */
	private function httpPort() {
		// If only HTTP_X_FORWARDED_PROTO='https' is set assume port=443. Override if required by setting HTTP_X_FORWARDED_PORT
		if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && !isset($_SERVER['HTTP_X_FORWARDED_PORT'])) {
			return ($_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ? 443 : 80;
		}
		elseif (isset($_SERVER['HTTP_X_FORWARDED_PORT'])) {
			return $_SERVER['HTTP_X_FORWARDED_PORT'];
		}
		elseif (isset($_SERVER['SERVER_PORT'])) {
			return $_SERVER['SERVER_PORT'];
		}
		else {
			return 80;
		}
	}

	/**
	* Returns true if site is accessed with https
	*
	* @access public
	* @return bool
	*/
	public function isHttps () {
		if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
			return ($_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https');
		}
		elseif (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
			return true;
		}
		elseif(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
			return true;
		}
		elseif($this->httpPort() == 443) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Create URL for base
	 *
	 * @access public
	 * @return string
	 */
	public function createURL () {
		$proto = $this->isHttps() ? 'https' : 'http';

		if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
			$url = $_SERVER['HTTP_X_FORWARDED_HOST'];
		}
		elseif (isset($_SERVER['HTTP_HOST'])) {
			$url = $_SERVER['HTTP_HOST'];
		}
		elseif (isset($_SERVER['SERVER_NAME'])) {
			$url = $_SERVER['SERVER_NAME'];
		}
		else {
			$url = "localhost";
		}
		$host = parse_url("$proto://$url", PHP_URL_HOST) ?: "localhost";

		$port = $this->httpPort();

		if (($proto == "http" && $port == 80) || ($proto == "https" && $port == 443)) {
			return "$proto://$host";
		} else {
			return "$proto://$host:$port";
		}
	}

	/**
	 * Creates links from text fields if link is present
	 *
	 *	source: https://css-tricks.com/snippets/php/find-urls-in-text-make-links/
	 *
	 * @access public
	 * @param mixed $field_type
	 * @param mixed $text
	 * @return mixed
	 */
	public function create_links ($text, $field_type = "varchar") {
		// create links only for varchar fields
		if (strpos($field_type, "varchar")!==false) {
			// regular expression
			$reg_exUrl = "#((http|https|ftp|ftps|telnet|ssh|rdp)://\S+[^\s.,>)\];'\"!?])#";

			// Check if there is a url in the text, make the urls hyper links
			$text = preg_replace($reg_exUrl, "<a href='$0' target='_blank'>$0</a>", $text);
		}
		// return text
		return $text;
	}

	/**
	 * Sets valid actions
	 *
	 * @access private
	 * @return string[]
	 */
	private function get_valid_actions () {
		return array(
		        "add",
		        "all-add",
		        "edit",
		        "all-edit",
		        "delete",
		        "truncate",
		        "split",
		        "resize",
		        "move",
		        "remove",
		        "assign"
		      );
	}

	/**
	 * Validate posted action on scripts
	 *
	 * @access public
	 * @param mixed $action
	 * @param bool $popup
	 * @return mixed|bool
	 */
	public function validate_action ($action, $popup = false) {
		# get valid actions
		$valid_actions = $this->get_valid_actions ();
		# check
		in_array($action, $valid_actions) ?: $this->Result->show("danger", _("Invalid action!"), true, $popup);
	}

	/**
	 * Validates email address.
	 *
	 * @access public
	 * @param mixed $email
	 * @return bool
	 */
	public function validate_email($email) {
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	/**
	 * Validate hostname
	 *
	 * @access public
	 * @param mixed $hostname
	 * @param bool $permit_root_domain
	 * @return bool|mixed
	 */
	public function validate_hostname($hostname, $permit_root_domain=true) {
    	// first validate hostname
    	$valid =  (preg_match("/^([a-z_\d](-*[a-z_\d])*)(\.([a-z_\d](-*[a-z_\d])*))*$/i", $hostname) 	//valid chars check
	            && preg_match("/^.{1,253}$/", $hostname) 										//overall length check
	            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $hostname)   ); 				//length of each label
	    // if it fails return immediately
	    if (!$valid) {
    	    return $valid;
	    }
	    // than validate root_domain if requested
	    elseif ($permit_root_domain)    {
    	    return $valid;
	    }
	    else {
    	    if(strpos($hostname, ".")!==false)  { return $valid; }
    	    else                                { return false; }
	    }
	}

	/**
	 * Validates IP address
	 *
	 * @access public
	 * @param mixed $ip
	 * @return bool
	 */
	public function validate_ip ($ip) {
    	if(filter_var($ip, FILTER_VALIDATE_IP)===false) { return false; }
    	else                                            { return true; }
	}

	/**
	 * Validates MAC address
	 *
	 * @access public
	 * @param mixed $mac
	 * @return bool
	 */
	public function validate_mac ($mac) {
    	// first put it to common format (1)
    	$mac = $this->reformat_mac_address ($mac);
    	// we permit empty
        if (strlen($mac)==0)                                                            { return true; }
    	elseif (preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $mac) != 1)   { return false; }
    	else                                                                            { return true; }
	}

    /**
     * Validates json from provided string.
     *
     * @access public
     * @param mixed $string
     * @return mixed
     */
    public function validate_json_string($string) {
        // try to decode
        json_decode($string);
        // check for error
        $parse_result = json_last_error_msg();
        // save possible error
        if($parse_result!=="No error") {
            $this->json_error = $parse_result;
        }
        // return true / false
        return (json_last_error() == JSON_ERROR_NONE);
    }

	/**
	 * Validate a postal code.
	 *
	 * https://gist.github.com/mpezzi/1171590
	 *
	 * @param $value
	 * @param $country
     * @return bool
	 */
	public function validate_postcode ($value = "", $country = 'united kingdom') {
		// to lower
		$country = strtolower($country);
		// set regexes
		$country_regex = array(
			'united kingdom' => '/\\A\\b[A-Z]{1,2}[0-9][A-Z0-9]? [0-9][ABD-HJLNP-UW-Z]{2}\\b\\z/i',
			'england'        => '/\\A\\b[A-Z]{1,2}[0-9][A-Z0-9]? [0-9][ABD-HJLNP-UW-Z]{2}\\b\\z/i',
			'canada'         => '/\\A\\b[ABCEGHJKLMNPRSTVXY][0-9][A-Z][ ]?[0-9][A-Z][0-9]\\b\\z/i',
			'italy'          => '/^[0-9]{5}$/i',
			'deutschland'    => '/^[0-9]{5}$/i',
			'germany'        => '/^[0-9]{5}$/i',
			'belgium'        => '/^[1-9]{1}[0-9]{3}$/i',
			'united states'  => '/\\A\\b[0-9]{5}(?:-[0-9]{4})?\\b\\z/i',
			'default'        => '/^[0-9]/i',
		);

		// check for country
		if ( isset($country_regex[$country]) ) {
			return preg_match($country_regex[$country], $value);
		}
		// default
		return preg_match($country_regex['default'], $value);
	}

	/**
	 * Transforms ipv6 to nt
	 *
	 * @access public
	 * @param mixed $ipv6
	 * @return mixed
	 */
	public function ip2long6 ($ipv6) {
		if($ipv6 == ".255.255.255") {
			return false;
		}
	    $ip_n = inet_pton($ipv6);
	    $bits = 15; // 16 x 8 bit = 128bit
	    $ipv6long = "";

	    while ($bits >= 0)
	    {
	        $bin = sprintf("%08b",(ord($ip_n[$bits])));
	        $ipv6long = $bin.$ipv6long;
	        $bits--;
	    }
	    return gmp_strval(gmp_init($ipv6long,2),10);
	}

	/**
	 * Transforms int to ipv4
	 *
	 * @access private
	 * @param mixed $ipv4long
	 * @return mixed
	 */
	private function long2ip4($ipv4long) {
		if (PHP_INT_SIZE==4) {
			// As of php7.1 long2ip() no longer accepts strings.
			// Convert unsigned int IPv4 to signed integer.
			$ipv4long = (int) ($ipv4long + 0);
		}
		return long2ip($ipv4long);
	}

	/**
	 * Transforms int to ipv6
	 *
	 * @access private
	 * @param mixed $ipv6long
	 * @return mixed
	 */
	private function long2ip6($ipv6long) {
		$hex = sprintf('%032s', gmp_strval(gmp_init($ipv6long, 10), 16));
		$ipv6 = implode(':', str_split($hex, 4));
		// compress result
		return inet_ntop(inet_pton($ipv6));
	}

	/**
	 * Identifies IP address format
	 *
	 *	0 = decimal
	 *	1 = dotted
	 *
	 * @access public
	 * @param mixed $address
	 * @return mixed decimal or dotted
	 */
	public function identify_address_format ($address) {
		return is_numeric($address) ? "decimal" : "dotted";
	}

	/**
	 * Transforms IP address to required format
	 *
	 *	format can be decimal (1678323323) or dotted (10.10.0.0)
	 *
	 * @access public
	 * @param mixed $address
	 * @param string $format (default: "dotted")
	 * @return mixed requested format
	 */
	public function transform_address ($address, $format = "dotted") {
		# no change
		if($this->identify_address_format ($address) == $format)		{ return $address; }
		else {
			if($this->identify_address_format ($address) == "dotted")	{ return $this->transform_to_decimal ($address); }
			else														{ return $this->transform_to_dotted ($address); }
		}
	}

	/**
	 * Transform IP address from decimal to dotted (167903488 -> 10.2.1.0)
	 *
	 * @access public
	 * @param mixed $address
	 * @return mixed dotted format
	 */
	public function transform_to_dotted ($address) {
	    if ($this->identify_address ($address) == "IPv4" ) 				{ return($this->long2ip4($address)); }
	    else 								 			  				{ return($this->long2ip6($address)); }
	}

	/**
	 * Transform IP address from dotted to decimal (10.2.1.0 -> 167903488)
	 *
	 * @access public
	 * @param mixed $address
	 * @return int IP address
	 */
	public function transform_to_decimal ($address) {
	    if ($this->identify_address ($address) == "IPv4" ) 				{ return( sprintf("%u", ip2long($address)) ); }
	    else 								 							{ return($this->ip2long6($address)); }
	}

	/**
	 * Returns array of address types
	 *
	 * @access protected
	 * @return void
	 */
	protected function get_addresses_types () {
		# from cache
		if($this->address_types == null) {
			# fetch
			$types = $this->fetch_all_objects ("ipTags", "id");
			if (!is_array($types))
				return;

			# save to array
			$types_out = array();
			foreach($types as $t) {
				$types_out[$t->id] = (array) $t;
			}
			# save to cache
			$this->address_types = $types_out;
		}
	}

	/**
	 * Translates address type from index (int) to type
	 *
	 *	e.g.: 0 > offline
	 *
	 * @access protected
	 * @param mixed $index
	 * @return mixed
	 */
	protected function translate_address_type ($index) {
		return isset($this->address_types[$index]["type"]) ? $this->address_types[$index]["type"] : "Used";
	}

	/**
	 * Returns text representation of json errors
	 *
	 * @access public
	 * @param mixed $error_int
	 * @return mixed
	 */
	public function json_error_decode ($error_int) {
    	// init
    	$error = array();
		// error definitions
		$error[0] = "JSON_ERROR_NONE";
		$error[1] = "JSON_ERROR_DEPTH";
		$error[2] = "JSON_ERROR_STATE_MISMATCH";
		$error[3] = "JSON_ERROR_CTRL_CHAR";
		$error[4] = "JSON_ERROR_SYNTAX";
		$error[5] = "JSON_ERROR_UTF8";
		$error[6] = "JSON_ERROR_RECURSION";
		$error[7] = "JSON_ERROR_INF_OR_NAN";
		$error[8] = "JSON_ERROR_UNSUPPORTED_TYPE";
		$error[9] = "JSON_ERROR_INVALID_PROPERTY_NAME";
		$error[10] = "JSON_ERROR_UTF16";
		// return def
		if (isset($error[$error_int]))	{ return $error[$error_int]; }
		else							{ return "JSON_ERROR_UNKNOWN"; }
	}

	/**
	 * Download URL via CURL
	 * @param  string $url
	 * @param  array|boolean $headers (default:false)
	 * @param  integer $timeout (default:30)
	 */
	public function curl_fetch_url($url, $headers=false, $timeout=30) {
		$result = ['result'=>false, 'result_code'=>503, 'error_msg'=>''];

		try {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_MAXREDIRS, 4);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FAILONERROR, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
			if (is_array($headers))
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

			// configure proxy settings
			if (Config::ValueOf('proxy_enabled') == true) {
				curl_setopt($curl, CURLOPT_PROXY, Config::ValueOf('proxy_server'));
				curl_setopt($curl, CURLOPT_PROXYPORT, Config::ValueOf('proxy_port'));
				if (Config::ValueOf('proxy_use_auth') == true) {
				curl_setopt($curl, CURLOPT_PROXYUSERPWD, Config::ValueOf('proxy_user').':'.Config::ValueOf('proxy_pass'));
				}
			}

			$result['result']      = curl_exec($curl);
			$result['result_code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			$result['error_msg']   = curl_error($curl);

			// close
			curl_close ($curl);

		} catch (Exception $e) {
			$result['error_msg'] = $e->getMessage();
		}

		return $result;
	}

    /**
     * Updates location to latlng from address
     *
     * @access public
     * @param mixed $id
     * @param mixed $lat
     * @param mixed $lng
     * @return bool
     */
    public function update_latlng ($id, $lat, $lng) {
		# execute
		try { $this->Database->updateObject("locations", array("id"=>$id, "lat"=>$lat, "long"=>$lng), "id"); }
		catch (Exception $e) {
			return false;
		}
		return true;
    }

    /**
     * Creates form input field for custom fields.
     *
     * @access public
     * @param mixed $field
     * @param mixed $object
     * @param mixed $timepicker_index
     * @param bool $disabled
     * @param string $set_delimiter
     * @param string $nameSuffix
     * @return array
     */
    public function create_custom_field_input ($field, $object, $timepicker_index, $disabled = false, $set_delimiter = "", $nameSuffix = "") {
        # make sure it is array
		$field  = (array) $field;
		$object = (object) $object;

        // disabled
        $disabled_text = $disabled ? "readonly" : "";
        // replace spaces with |
        $field['nameNew'] = str_replace(" ", "___", $field['name']);
        // required
        $required = $field['Null']=="NO" ? "*" : "";
		// set default value if adding new object
		if (!property_exists($object, $field['name'])) {
			$object->{$field['name']} = $field['Default'];
		}

        //set, enum
        if(substr($field['type'], 0,3) == "set" || substr($field['type'], 0,4) == "enum") {
        	$html = $this->create_custom_field_input_set_enum ($field, $object, $disabled_text, $set_delimiter, $nameSuffix);
        }
        //date and time picker
        elseif($field['type'] == "date" || $field['type'] == "datetime") {
        	$html = $this->create_custom_field_input_date ($field, $object, $timepicker_index, $disabled_text, $nameSuffix);
        }
        //boolean
        elseif($field['type'] == "tinyint(1)") {
        	$html = $this->create_custom_field_input_boolean ($field, $object, $disabled_text, $nameSuffix);
        }
        //text
        elseif($field['type'] == "text") {
        	$html = $this->create_custom_field_input_textarea ($field, $object, $disabled_text, $nameSuffix);
        }
		//default - input field
		else {
            $html = $this->create_custom_field_input_input ($field, $object, $disabled_text, $nameSuffix);
		}

        # result
        return array(
			"required"         => $required,
			"field"            => implode("\n", $html),
			"timepicker_index" => $timepicker_index
        );
	}

    /**
     * Creates form input field for set and enum values
     *
     * @access private
     * @param mixed $field
     * @param mixed $object
     * @param string $disabled_text
     * @param string $set_delimiter
     * @param string $nameSuffix
     * @return array
     */
    private function create_custom_field_input_set_enum ($field, $object, $disabled_text, $set_delimiter = "", $nameSuffix = "") {
		$html = array();
    	//parse values
    	$field['type'] = trim(substr($field['type'],0,-1));
    	$tmp = substr($field['type'], 0,3)=="set" ? explode(",", str_replace(array("set(", "'"), "", $field['type'])) : explode(",", str_replace(array("enum(", "'"), "", $field['type']));
    	//null
    	if($field['Null']!="NO") { array_unshift($tmp, ""); }

    	$html[] = "<select name='$field[nameNew]$nameSuffix' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$field[Comment]' $disabled_text>";
    	foreach($tmp as $v) {
    		// set selected
			$selected = $v==$object->{$field['name']} ? "selected='selected'" : "";
			// parse delimiter
			if(strlen($set_delimiter)==0) {
				// save
		        $html[] = "<option value='$v' $selected>$v</option>";
			}
			else {
				// explode by delimiter
				$tmp2 = explode ($set_delimiter, $v);
	    		// reset selected
				$selected = $tmp2[0]==$object->{$field['name']} ? "selected='selected'" : "";
				// save
		        $html[] = "<option value='$tmp2[0]' $selected>$tmp2[1]</option>";
			}

    	}
    	$html[] = "</select>";

    	// result
    	return $html;
	}

    /**
     * Creates form input field for date fields.
     *
     * @access private
     * @param mixed $field
     * @param mixed $object
     * @param mixed $timepicker_index
     * @param string $disabled_text
     * @param string $nameSuffix
     * @return array
     */
    private function create_custom_field_input_date ($field, $object, &$timepicker_index, $disabled_text, $nameSuffix = "") {
   		$html = array ();
    	// just for first
    	if($timepicker_index==0) {
    		$html[] =  '<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap-datetimepicker.min.css?v='.SCRIPT_PREFIX.'">';
    		$html[] =  '<script src="js/bootstrap-datetimepicker.min.js?v='.SCRIPT_PREFIX.'"></script>';
    		$html[] =  '<script>';
    		$html[] =  '$(document).ready(function() {';
    		//date only
    		$html[] =  '	$(".datepicker").datetimepicker( {pickDate: true, pickTime: false, pickSeconds: false });';
    		//date + time
    		$html[] =  '	$(".datetimepicker").datetimepicker( { pickDate: true, pickTime: true } );';
    		$html[] =  '})';
    		$html[] =  '</script>';
    	}
    	$timepicker_index++;

    	//set size
    	if($field['type'] == "date")	{ $size = 10; $class='datepicker';		$format = "yyyy-MM-dd"; }
    	else							{ $size = 19; $class='datetimepicker';	$format = "yyyy-MM-dd"; }

    	//field
    	if(!isset($object->{$field['name']}))	{ $html[] = ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'].$nameSuffix .'" maxlength="'.$size.'" rel="tooltip" data-placement="right" title="'.$field['Comment'].'" '.$disabled_text.'>'. "\n"; }
    	else								    { $html[] = ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'].$nameSuffix .'" maxlength="'.$size.'" value="'. $this->strip_xss($object->{$field['name']}). '" rel="tooltip" data-placement="right" title="'.$field['Comment'].'" '.$disabled_text.'>'. "\n"; }

    	// result
		return $html;
	}

    /**
     * Creates form input field for boolean fields.
     *
     * @access private
     * @param mixed $field
     * @param mixed $object
     * @param string $disabled_text
     * @param string $nameSuffix
     * @return array
     */
    private function create_custom_field_input_boolean ($field, $object, $disabled_text, $nameSuffix = "") {
    	$html = array ();
    	$html[] =  "<select name='$field[nameNew]$nameSuffix' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$field[Comment]' $disabled_text>";
    	$tmp = array(0=>"No",1=>"Yes");
    	//null
    	if($field['Null']!="NO") { $tmp[2] = ""; }

    	foreach($tmp as $k=>$v) {
    		if(strlen($object->{$field['name']})==0 && $k==2)	{ $html[] = "<option value='$k' selected='selected'>"._($v)."</option>"; }
    		elseif($k==$object->{$field['name']})				{ $html[] = "<option value='$k' selected='selected'>"._($v)."</option>"; }
    		else											    { $html[] = "<option value='$k'>"._($v)."</option>"; }
    	}
    	$html[] = "</select>";
    	// result
    	return $html;
	}

    /**
     * Creates form input field for text fields.
     *
     * @access private
     * @param mixed $field
     * @param mixed $object
     * @param string $disabled_text
     * @param string $nameSuffix
     * @return array
     */
    private function create_custom_field_input_textarea ($field, $object, $disabled_text, $nameSuffix = "") {
    	$html = array ();
    	$html[] = ' <textarea class="form-control input-sm" name="'. $field['nameNew'].$nameSuffix .'" placeholder="'. $this->print_custom_field_name ($field['name']) .'" rowspan=3 rel="tooltip" data-placement="right" title="'.$field['Comment'].'" '.$disabled_text.'>'. $object->{$field['name']}. '</textarea>'. "\n";
    	// result
    	return $html;
	}

    /**
     * Creates form input field for date fields.
     *
     * @access private
     * @param mixed $field
     * @param mixed $object
     * @param string $disabled_text
     * @param string $nameSuffix
     * @return array
     */
    private function create_custom_field_input_input ($field, $object, $disabled_text, $nameSuffix = "") {
        $html = array ();
        // max length
        $maxlength = 100;
        if(strpos($field['type'],"varchar")!==false) {
            $maxlength = str_replace(array("varchar","(",")"),"", $field['type']);
        }
        if(strpos($field['type'],"int")!==false) {
            $maxlength = str_replace(array("int","(",")"),"", $field['type']);
        }
        // print
		$html[] = ' <input type="text" class="form-control input-sm" name="'. $field['nameNew'].$nameSuffix .'" placeholder="'. $this->print_custom_field_name ($field['name']) .'" value="'. $this->strip_xss($object->{$field['name']}). '" size="30" rel="tooltip" data-placement="right" maxlength="'.$maxlength.'" title="'.$field['Comment'].'" '.$disabled_text.'>'. "\n";
    	// result
    	return $html;
	}

	/**
	 * Prints custom field
	 *
	 * @method print_custom_field
	 *
	 * @param  string $type
	 * @param  string $value
	 * @param  string $delimiter
	 *
	 * @return void
	 */
	public function print_custom_field ($type, $value, $delimiter = false, $replacement = false) {
		// escape
		$value = str_replace("'", "&#39;", $value);
		// create links
		$value = $this->create_links ($value, $type);

		// delimiter ?
		if($delimiter !== false && $replacement !== false) {
			$value = str_replace($delimiter, $replacement, $value);
		}

		//booleans
		if($type=="tinyint(1)")	{
			if($value == "1")			{ print _("Yes"); }
			elseif(strlen($value)==0) 	{ print "/"; }
			else						{ print _("No"); }
		}
		//text
		elseif($type=="text") {
			if(strlen($value)>0)	{ print "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", $value)."'>"; }
			else					{ print ""; }
		}
		else {
			print $value;
		}
	}

	/**
	 * Print custom field name, strip out custom_ prefix
	 *
	 * @method print_custom_field_name
	 *
	 * @param  string $name
	 *
	 * @return string
	 */
	public function print_custom_field_name ($name) {
		return strpos($name, "custom_")===0 ? substr($name, 7) : $name;
	}

	/**
	 * Creates image link to rack.
	 *
	 * @method create_rack_link
	 *
	 * @param  bool|int $rackId
	 * @param  bool|int $deviceId
	 * @param  bool $is_back
	 *
	 * @return [type]
	 */
	public function create_rack_link ($rackId = false, $deviceId = false, $is_back = false) {
    	if($rackId===false) {
        	    return false;
    	}
    	else {
        	//device ?
        	if ($deviceId!==false) {
            	return $this->createURL ().BASE."app/tools/racks/draw_rack.php?rackId=$rackId&deviceId=$deviceId&is_back=$is_back";
        	}
        	else {
            	return $this->createURL ().BASE."app/tools/racks/draw_rack.php?rackId=$rackId&is_back=$is_back";
        	}
    	}
	}

	/**
	 * Get MAC address vendor details
	 *
	 * https://www.macvendorlookup.com/vendormacs-xml-download
	 *
	 * @method get_mac_address_vendor
	 * @param  mixed $mac
	 * @return string
	 */
	public function get_mac_address_vendor_details ($mac) {
		// set default arrays
		$matches = array();
		// validate mac
		if(strlen($mac)<4)				{ return ""; }
		if(!$this->validate_mac ($mac))	{ return ""; }
		// reformat mac address
		$mac = strtoupper($this->reformat_mac_address ($mac, 1));
		$mac_partial = explode(":", $mac);
		// get mac XML database

		if (is_null($this->mac_address_vendors)) {
			//populate mac vendors array
			$this->mac_address_vendors = array();

			$data = file_get_contents(dirname(__FILE__)."/../vendormacs.xml");

			if (preg_match_all('/\<VendorMapping\smac_prefix="([0-9a-fA-F]{2})[:-]([0-9a-fA-F]{2})[:-]([0-9a-fA-F]{2})"\svendor_name="(.*)"\/\>/', $data, $matches, PREG_SET_ORDER)) {
				if (is_array($matches)) {
					foreach ($matches as $match) {
						$mac_vendor = strtoupper($match[1] . ':' . $match[2] . ':' . $match[3]);
						$this->mac_address_vendors[$mac_vendor] = $match[4];
					}
				}
			}
		}

		$mac_vendor = strtoupper($mac_partial[0] . ':' . $mac_partial[1] . ':' . $mac_partial[2]);

		if (isset($this->mac_address_vendors[$mac_vendor])) {
			return $this->mac_address_vendors[$mac_vendor];
		} else {
			return "";
		}
	}

	/**
	 * Read user supplied permissions ($_POST) and calculate deltas from old_permissions
	 *
	 * @access public
	 * @param  array $post_permissions
	 * @param  array $old_permissions
	 * @return array
	 */
	public function get_permission_changes ($post_permissions, $old_permissions) {
		$new_permissions = array();
		$removed_permissions = array();
		$changed_permissions = array();

		# set new posted permissions
		foreach($post_permissions as $key=>$val) {
			if(substr($key, 0,5) == "group") {
				if($val != "0") $new_permissions[substr($key,5)] = $val;
			}
		}

		// calculate diff
		if(is_array($old_permissions)) {
			foreach ($old_permissions as $k1=>$p1) {
				// if there is not permisison in new that remove old
				// if change than save
				if (!array_key_exists($k1, $new_permissions)) {
					$removed_permissions[$k1] = 0;
				} elseif ($old_permissions[$k1]!==$new_permissions[$k1]) {
					$changed_permissions[$k1] = $new_permissions[$k1];
				}
			}
		} else {
			$old_permissions = array();  // fix for adding
		}
		// add also new groups if available
		if(is_array($new_permissions)) {
			foreach ($new_permissions as $k1=>$p1) {
				if(!array_key_exists($k1, $old_permissions)) {
					$changed_permissions[$k1] = $new_permissions[$k1];
				}
			}
		}

		return array($removed_permissions, $changed_permissions, $new_permissions);
	}

	/**
	 * Parse subnet permissions to user readable format
	 *
	 * @access public
	 * @param mixed $permissions
	 * @return string
	 */
	public function parse_permissions ($permissions) {
		switch($permissions) {
			case 0: 	$r = _("No access");	break;
			case 1: 	$r = _("Read");			break;
			case 2: 	$r = _("Write");		break;
			case 3: 	$r = _("Admin");		break;
			default:	$r = _("error");
		}
		return $r;
	}






	/**
	 *	@breadcrumbs functions
	 * ------------------------
	 */

	/**
	 * print_breadcrumbs function.
	 *
	 * @access public
	 * @param mixed $Section
	 * @param mixed $Subnet
	 * @param mixed $req
	 * @param mixed $Address (default: null)
	 * @return void
	 */
	public function print_breadcrumbs ($Section, $Subnet, $req, $Address=null) {
		# subnets
		if($req['page'] == "subnets")		{ $this->print_subnet_breadcrumbs ($Subnet, $req, $Address); }
		# folders
		elseif($req['page'] == "folder")	{ $this->print_folder_breadcrumbs ($Section, $Subnet, $req); }
		# tools
		elseif ($req['page'] == "tools") 	{ $this->print_tools_breadcrumbs ($req); }
	}

	/**
	 * Print address breadcrumbs
	 *
	 * @access private
	 * @param mixed $Subnet
	 * @param mixed $req
	 * @param mixed $Address
	 * @return void
	 */
	private function print_subnet_breadcrumbs ($Subnet, $req, $Address) {
		if(isset($req['subnetId'])) {
			# get all parents
			$parents = $Subnet->fetch_parents_recursive ($req['subnetId']);

			print "<ul class='breadcrumb'>";
			# remove root - 0
			//array_shift($parents);

			# section details
			$section = (array) $this->fetch_object ("sections", "id", $req['section']);

			# section name
			print "	<li><a href='".create_link("subnets",$section['id'])."'>$section[name]</a> <span class='divider'></span></li>";

			# all parents
			foreach($parents as $parent) {
				$subnet = (array) $Subnet->fetch_subnet("id",$parent);
				if($subnet['isFolder']==1) {
					print "	<li><a href='".create_link("folder",$section['id'],$parent)."'><i class='icon-folder-open icon-gray'></i> $subnet[description]</a> <span class='divider'></span></li>";
				} else {
					print "	<li><a href='".create_link("subnets",$section['id'],$parent)."'>$subnet[description] ($subnet[ip]/$subnet[mask])</a> <span class='divider'></span></li>";
				}
			}
			# parent subnet
			$subnet = (array) $Subnet->fetch_subnet("id",$req['subnetId']);
			# ip set
			if(isset($req['ipaddrid'])) {
				$ip = (array) $Address->fetch_address ("id", $req['ipaddrid']);
				print "	<li><a href='".create_link("subnets",$section['id'],$subnet['id'])."'>$subnet[description] ($subnet[ip]/$subnet[mask])</a> <span class='divider'></span></li>";
				print "	<li class='active'>$ip[ip]</li>";			//IP address
			}
			else {
				print "	<li class='active'>$subnet[description] ($subnet[ip]/$subnet[mask])</li>";		//active subnet

			}
			print "</ul>";
		}
	}

	/**
	 * Print folder breadcrumbs
	 *
	 * @access private
	 * @param obj $Section
	 * @param obj $Subnet
	 * @param mixed $req
	 * @return void
	 */
	private function print_folder_breadcrumbs ($Section, $Subnet, $req) {
		if(isset($req['subnetId'])) {
			# get all parents
			$parents = $Subnet->fetch_parents_recursive ($req['subnetId']);
			print "<ul class='breadcrumb'>";
			# remove root - 0
			array_shift($parents);

			# section details
			$section = (array) $Section->fetch_section(null, $req['section']);

			# section name
			print "	<li><a href='".create_link("subnets",$section['id'])."'>$section[name]</a> <span class='divider'></span></li>";

			# all parents
			foreach($parents as $parent) {
				$parent = (array) $parent;
				$subnet = (array) $Subnet->fetch_subnet(null,$parent[0]);
				if ($subnet['isFolder']=="1")
				print "	<li><a href='".create_link("folder",$section['id'],$parent[0])."'><i class='icon-folder-open icon-gray'></i> $subnet[description]</a> <span class='divider'></span></li>";
				else
				print "	<li><a href='".create_link("subnets",$section['id'],$parent[0])."'><i class='icon-folder-open icon-gray'></i> $subnet[description]</a> <span class='divider'></span></li>";
			}
			# parent subnet
			$subnet = (array) $Subnet->fetch_subnet(null,$req['subnetId']);
			print "	<li>$subnet[description]</li>";																		# active subnet
			print "</ul>";
		}
	}

	/**
	 * Prints tools breadcrumbs
	 *
	 * @access public
	 * @param mixed $req
	 * @return void
	 */
	private function print_tools_breadcrumbs ($req) {
		print "<ul class='breadcrumb'>";
		print "	<li><a href='".create_link("tools")."'>"._('Tools')."</a> <span class='divider'></span></li>";
		if(!isset($req['subnetId'])) {
		    print "	<li class='active'>$req[section]</li>";
		}
		else {
		    print "	<li class='active'><a href='".create_link("tools", $req['section'])."'>$req[section]</a> <span class='divider'></span></li>";

		    # pstn
		    if ($_GET['section']=="pstn-prefixes") {
    			# get all parents
    			$Tools = new Tools ($this->Database);
    			$parents = $Tools->fetch_prefix_parents_recursive ($req['subnetId']);
    			# all parents
    			foreach($parents as $parent) {
    				$prefix = $this->fetch_object("pstnPrefixes", "id", $parent[0]);
    				print "	<li><a href='".create_link("tools",$req['section'],$parent[0])."'><i class='icon-folder-open icon-gray'></i> $prefix->name</a> <span class='divider'></span></li>";
    			}

		    }
		    $prefix = $this->fetch_object("pstnPrefixes", "id", $req['subnetId']);
		    print "	<li class='active'>$prefix->name</li>";
		}
		print "</ul>";
	}

	/**
	 * Prints site title
	 *
	 * @access public
	 * @param mixed $get
	 * @return void
	 */
	public function get_site_title ($get) {
    	// remove html tags
    	$get = $this->strip_input_tags ($get);
    	// init
    	$title = array ();
    	$title[] = $this->settings->siteTitle;

    	// page
    	if (isset($get['page'])) {
        	// dashboard
        	if ($get['page']=="dashboard") {
            	return $this->settings->siteTitle." "._("Dashboard");
        	}
        	// install, upgrade
        	elseif ($get['page']=="temp_share" || $get['page']=="request_ip" || $get['page']=="opensearch") {
            	$title[] = ucwords(escape_input($get['page']));
        	}
        	// sections, subnets
        	elseif ($get['page']=="subnets" || $get['page']=="folder") {
            	// subnets
            	$title[] = _("Subnets");

            	// section
            	if (isset($get['section'])) {
                 	$se = $this->fetch_object ("sections", "id", escape_input($get['section']));
                	if($se!==false) {
                    	$title[] = $se->name;
                	}
            	}
            	// subnet
            	if (isset($get['subnetId'])) {
                 	$sn = $this->fetch_object ("subnets", "id", escape_input($get['subnetId']));
                	if($sn!==false) {
                    	if($sn->isFolder) {
                        	$title[] = $sn->description;
                    	}
                    	else {
                        	$sn->description = strlen($sn->description)>0 ? " (".$sn->description.")" : "";
                        	$title[] = $this->transform_address($sn->subnet, "dotted")."/".$sn->mask.$sn->description;
                        }
                	}
            	}
            	// ip address
            	if (isset($get['ipaddrid'])) {
                    $ip = $this->fetch_object ("ipaddresses", "id", escape_input($get['ipaddrid']));
                    if($ip!==false) {
                        $title[] = $this->transform_address($ip->ip_addr, "dotted");
                    }
            	}
        	}
        	// tools, admin
        	elseif ($get['page']=="tools" || $get['page']=="administration") {
            	$title[] = ucwords(escape_input($get['page']));
            	// subpage
            	if (isset($get['section'])) {
                	$title[] = ucwords(escape_input($get['section']));
            	}
            	if (isset($get['subnetId'])) {
                	// vland domain
                	if($get['section']=="vlan") {
                     	$se = $this->fetch_object ("vlanDomains", "id", escape_input($get['subnetId']));
                    	if($se!==false) {
                        	$title[] = $se->name." domain";
                    	}
                	}
                	else {
                    	$title[] = ucwords(escape_input($get['subnetId']));
                    }
            	}
        	}
        	else {
            	$title[] = ucwords(escape_input($get['page']));
            }
    	}
        // return title
    	return implode(" / ", $title);
	}




	/**
	 * Print action wrapper
	 *
	 * Provided items can have following items:
	 *     type: link, divider, header
	 *     text: text to print
	 *     href: ''
	 *     class: classes to be added to item
	 *     dataparams: params to be added (e.g. data-deviceid='0')
	 *     icon: name for icon
	 *     visible: where it should be visible
	 *
	 *
	 * @method print_actions
	 * @param  string $type
	 * @param  array $items [array of items]
	 * @param  bool $left_align
	 * @param  bool $print_text
	 * @return [type]
	 */
	public function print_actions ($compress = "1", $items = [], $left_align = false, $print_text = false) {
	    if (sizeof($items)>0) {
	        return $compress=="1" ? $this->print_actions_dropdown($items, $left_align, $print_text) : $this->print_actions_buttons ($items);
	    }
	    else {
	        return "";
	    }
	}

	/**
	 * Prints action dropdown
	 *
	 * @method print_actions_buttons
	 * @param  array $items [array of items]
	 * @param  bool $left_align
	 * @param  bool $print_text
	 * @return string
	 */
	private function print_actions_dropdown ($items = [], $left_align = false, $print_text = false) {
	    // init
	    $html   = [];
	    // alignment
	    $alignment = $left_align ? "dropdown-menu-left" : "dropdown-menu-right";
	    // text
	    $action_text = $print_text ? " <i class='fa fa-cogs'></i> "._("Actions")." " : " <i class='fa fa-cogs'></i> ";

	    $html[] = "<div class='dropdown'>";
	    $html[] = "  <button class='btn btn-xs btn-default dropdown-toggle ' type='button' id='dropdownMenu' data-toggle='dropdown' aria-haspopup='true' aria-expanded='true' rel='tooltip' title='"._("Actions")."'> ".$action_text." <span class='caret'></span></button>";
	    $html[] = "  <ul class='dropdown-menu $alignment' aria-labelledby='dropdownMenu'>";

	    // loop items
	    foreach ($items as $i) {
			$i = array_merge(['class'=>null, 'dataparams'=>null], $i);

	        // visible
	        if (isset($i['visible'])) {
	            if ($i['visible']!="dropdown") {
	                continue;
	            }
	        }
	        // title
	        if ($i['type']=="header") {
	            $html[] = "   <li class='dropdown-header'>".($i['text'])."</li>";

	        }
	        // separator
	        elseif ($i['type']=="divider") {
	            $html[] = "   <li role='separator' class='divider'></li>";
	        }
	        // item
	        else {
	            $html[] = "   <li><a class='$i[class]' href='$i[href]' $i[dataparams]><i class='fa fa-$i[icon]'></i> ".$i['text']."</a></li>";
	        }
	    }
	    // remove last divider if present
	    if (strpos(end($html),"divider")!==false) {
	        array_pop($html);
	    }
	    // end
	    $html[] = " </ul>";
	    $html[] = "</div>";
	    // result
	    return implode("\n", $html);
	}


	/**
	 * Prints icons btn-group
	 *
	 * @method print_actions_buttons
	 * @param  array $items [array of items]
	 * @return string
	 */
	private function print_actions_buttons ($items = []) {
	    // init
	    $html   = [];
	    // structure
	    $html[] = " <div class='btn-group'>";
	    // irems
	    foreach ($items as $i) {
	        // visible
	        if (isset($i['visible'])) {
	            if ($i['visible']!="buttons") {
	                continue;
	            }
	        }
	        // save only links
	        if($i['type']=="link") {
	            $html[] = " <a href='$i[href]' class='btn btn-xs btn-default $i[class]' $i[dataparams] rel='tooltip' title='".$i['text']."'><i class='fa fa-$i[icon]'></i></a>";
	        }
	    }
	    // end
	    $html[] =  " </div>";
	    // result
	    return implode("\n", $html);
	}
}
