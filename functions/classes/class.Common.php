<?php

/**
 * phpIPAM class with common functions, used in all other classes
 *
 * @author: Miha Petkovsek <miha.petkovsek@gmail.com>
 */
class Common_functions  {

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
	 * cache_check_exceptions
	 *
	 * (default value: array())
	 *
	 * @var array
	 * @access private
	 */
	private $cache_check_exceptions = array();

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
	 * @return void
	 */
	public function fetch_all_objects ($table=null, $sortField="id", $sortAsc=true) {
		# null table
		if(is_null($table)||strlen($table)==0) return false;
		# fetch
		try { $res = $this->Database->getObjects($table, $sortField, $sortAsc); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# save
		if (sizeof($res)>0) {
    		foreach ($res as $r) {
        		$this->cache_write ($table, $r->id, $r);
    		}
		}
		# result
		return sizeof($res)>0 ? $res : false;
	}

	/**
	 * Fetches specified object specified table in database
	 *
	 * @access public
	 * @param mixed $table
	 * @param mixed $method (default: null)
	 * @param mixed $value
	 * @return void
	 */
	public function fetch_object ($table=null, $method=null, $value) {
		# null table
		if(is_null($table)||strlen($table)==0) return false;

		// checks
		if(is_null($table))		return false;
		if(strlen($table)==0)   return false;
		if(is_null($method))	return false;
		if(is_null($value))		return false;
		if($value===0)		    return false;

		# null method
		$method = is_null($method) ? "id" : $this->Database->escape($method);

		# check cache
		$cached_item = $this->cache_check($table, $value);
		if($cached_item!==false) {
			return $cached_item;
		}
		else {
			try { $res = $this->Database->getObjectQuery("SELECT * from `$table` where `$method` = ? limit 1;", array($value)); }
			catch (Exception $e) {
				$this->Result->show("danger", _("Error: ").$e->getMessage());
				return false;
			}
			# save to cache array
			if(sizeof($res)>0) {
    			// set identifier
    			$method = $this->cache_set_identifier ($table);
    			// save
    			$this->cache_write ($table, $res->$method, $res);
				return $res;
			}
			else {
				return false;
			}
		}
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
	 * @return void
	 */
	public function fetch_multiple_objects ($table, $field, $value, $sortField = 'id', $sortAsc = true, $like = false) {
		# null table
		if(is_null($table)||strlen($table)==0) return false;
		else {
			try { $res = $this->Database->findObjects($table, $field, $value, $sortField, $sortAsc, $like); }
			catch (Exception $e) {
				$this->Result->show("danger", _("Error: ").$e->getMessage());
				return false;
			}
			# save to cach
			if (sizeof($res)>0) {
    			foreach ($res as $r) {
        			$this->cache_write ($table, $r->id, $r);
    			}
			}
			# result
			return sizeof($res)>0 ? $res : false;
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
	 * @return void
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
	 * fetches settings from database
	 *
	 * @access private
	 * @return void
	 */
	public function get_settings () {
		# constant defined
		if (defined('SETTINGS')) {
			if ($this->settings === null || $this->settings === false) {
				$this->settings = json_decode(SETTINGS);
			}
		}
		else {
			# cache check
			if($this->settings === null) {
				try { $settings = $this->Database->getObject("settings", 1); }
				catch (Exception $e) { $this->Result->show("danger", _("Database error: ").$e->getMessage()); }
				# save
				if ($settings!==false)	 {
					$this->settings = $settings;
				}
			}
		}
	}

	/**
	 * get_settings alias
	 *
	 * @access public
	 * @return void
	 */
	public function settings () {
		return $this->get_settings();
	}


    /**
     * Write result to cache.
     *
     * @access protected
     * @param mixed $table
     * @param mixed $id
     * @param mixed $object
     * @return void
     */
    protected function cache_write ($table, $id, $object) {
        // get method
        $identifier = $this->cache_set_identifier ($table);
        // check if cache is already set, otherwise save
        if ($this->cache_check_exceptions!==false) {
            if (!isset($this->cache[$table][$identifier][$id])) {
                $this->cache[$table][$identifier][$id] = (object) $object;
                // add ip ?
                $ip_check = $this->cache_check_add_ip($table);
                if ($ip_check!==false) {
                    $this->cache[$table][$identifier][$id]->ip = $this->transform_address ($object->$ip_check, "dotted");
                }
            }
        }
    }

    /**
     * Check if caching is not needed
     *
     * @access protected
     * @param mixed $table
     * @return void
     */
    protected function cache_check_exceptions ($table) {
        // define
        $exceptions = array("deviceTypes");
        // check
        return in_array($table, $exceptions) ? true : false;
    }

    /**
     * Cehck if ip is to be added to result
     *
     * @access protected
     * @param mixed $table
     * @return void
     */
    protected function cache_check_add_ip ($table) {
        // define
        $ip_tables = array("subnets"=>"subnet", "ipaddresses"=>"ip_addr");
        // check
        return array_key_exists ($table, $ip_tables) ? $ip_tables[$table] : false;
    }

    /**
     * Set identifier for table - exceptions.
     *
     * @access protected
     * @param mixed $table
     * @return void
     */
    protected function cache_set_identifier ($table) {
        // vlan and subnets have different identifiers
        if ($table=="vlans")        { return "vlanId"; }
        elseif ($table=="vrf")      { return "vrfId"; }
        else                        { return "id"; }
    }

    /**
     * Checks if object alreay exists in cache..
     *
     * @access protected
     * @param mixed $table
     * @param mixed $id
     * @return void
     */
    protected function cache_check ($table, $id) {
        // get method
        $method = $this->cache_set_identifier ($table);
        // check if cache is already set, otherwise return false
        if (isset($this->cache[$table][$method][$id]))  { return (object) $this->cache[$table][$method][$id]; }
        else                                            { return false; }
    }




	/**
	 * Sets debugging
	 *
	 * @access private
	 * @return void
	 */
	public function set_debugging () {
		include( dirname(__FILE__) . '/../../config.php' );
		$this->debugging = $debugging ? true : false;
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
			$this->DNS2 = new Net_DNS2_Resolver();
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
    			$input[$k] = strip_tags($v);
            }
		}
		else {
			$input = strip_tags($input);
		}
		# stripped
		return $input;
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
			if(is_null($v) || strlen($v)==0) {
				$out[$k] = 	$char;
			} else {
				$out[$k] = $v;
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
		foreach($fields as $k=>$v) {
			if(is_null($v) || strlen($v)==0) {
			}
			else {
				$out[$k] = $v;
			}
		}
		# result
		return $out;
	}

	/**
	 * Function to verify checkbox if 0 length
	 *
	 * @access public
	 * @param mixed $field
	 * @return void
	 */
	public function verify_checkbox ($field) {
		return @$field==""||strlen(@$field)==0 ? 0 : $field;
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
	    if (strpos($address, ":")) 		{ return 'IPv6'; }
	    elseif (strpos($address, ".")) 	{ return 'IPv4'; }
	    # decimal representation
	    else  {
	        # IPv4 address
	        if(strlen($address) < 12) 	{ return 'IPv4'; }
	        # IPv6 address
	    	else 						{ return 'IPv6'; }
	    }
	}

	/**
	 * Alias of identify_address_format function
	 *
	 * @access public
	 * @param mixed $address
	 * @return void
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
	 * @return void
	 */
	public function array_to_log ($logs, $changelog = false) {
		$result = "";
		# reformat
		if(is_array($logs)) {
			// changelog
			if ($changelog===true) {
			    foreach($logs as $key=>$req) {
			    	# ignore __ and PHPSESSID
			    	if( (substr($key,0,2) == '__') || (substr($key,0,9) == 'PHPSESSID') || (substr($key,0,4) == 'pass') || $key=='plainpass' ) {}
			    	else 																  { $result .= "[$key]: $req<br>"; }
				}

			}
			else {
			    foreach($logs as $key=>$req) {
			    	# ignore __ and PHPSESSID
			    	if( (substr($key,0,2) == '__') || (substr($key,0,9) == 'PHPSESSID') || (substr($key,0,4) == 'pass') || $key=='plainpass' ) {}
			    	else 																  { $result .= " ". $key . ": " . $req . "<br>"; }
				}
			}
		}
		return $result;
	}

	/**
	 * Transforms seconds to hms
	 *
	 * @access public
	 * @param mixed $sec
	 * @param bool $padHours (default: false)
	 * @return void
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
	 * @return void
	 */
	public function shorten_text($text, $chars = 25) {
		//count input text size
		$startLen = strlen($text);
		//cut onwanted chars
	    $text = substr($text,0,$chars);
		//count output text size
		$endLen = strlen($text);

		//append dots if it was cut
		if($endLen != $startLen) {
			$text = $text."...";
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
	 * @return void
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
	 * Create URL for base
	 *
	 * @access public
	 * @return void
	 */
	public function createURL () {
		# reset url for base
		if($_SERVER['SERVER_PORT'] == "443") 		{ $url = "https://$_SERVER[HTTP_HOST]"; }
		// reverse proxy doing SSL offloading
		elseif(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') 	{ $url = "https://$_SERVER[SERVER_NAME]"; }
		elseif(isset($_SERVER['HTTP_X_SECURE_REQUEST'])  && $_SERVER['HTTP_X_SECURE_REQUEST'] == 'true') 	{ $url = "https://$_SERVER[SERVER_NAME]"; }
		// custom port
		elseif($_SERVER['SERVER_PORT']!="80")  		{ $url = "http://$_SERVER[SERVER_NAME]:$_SERVER[SERVER_PORT]"; }
		// normal http
		else								 		{ $url = "http://$_SERVER[HTTP_HOST]"; }

		//result
		return $url;
	}

	/**
	 * Creates links from text fields if link is present
	 *
	 *	source: https://css-tricks.com/snippets/php/find-urls-in-text-make-links/
	 *
	 * @access public
	 * @param mixed $field_type
	 * @param mixed $text
	 * @return void
	 */
	public function create_links ($text, $field_type = "varchar") {
        // create links only for varchar fields
        if (strpos($field_type, "varchar")!==false) {
    		// regular expression
    		$reg_exUrl = "#(http|https|ftp|ftps|telnet|ssh)://\S+[^\s.,>)\];'\"!?]#";

    		// Check if there is a url in the text
    		if(preg_match($reg_exUrl, $text, $url)) {
    	       // make the urls hyper links
    	       $text = preg_replace($reg_exUrl, "<a href='{$url[0]}' target='_blank'>{$url[0]}</a> ", $text);
    		}
        }
        // return text
        return $text;
	}

	/**
	 * Validates email address.
	 *
	 * @access public
	 * @param mixed $email
	 * @return void
	 */
	public function validate_email($email) {
	    return preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/",$email) ? true : false;
	}

	/**
	 * Validate hostname
	 *
	 * @access public
	 * @param mixed $hostname
	 * @param bool $permit_root_domain
	 * @return void
	 */
	public function validate_hostname($hostname, $permit_root_domain=true) {
    	// first validate hostname
    	$valid =  (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $hostname) 	//valid chars check
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
	 * @return void
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
	 * @return void
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
	 * Transforms ipv6 to nt
	 *
	 * @access public
	 * @param mixed $ipv6
	 * @return void
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
	 * Transforms int to ipv6
	 *
	 * @access public
	 * @param mixed $ipv6long
	 * @return void
	 */
	public function long2ip6($ipv6long) {
	    $bin = gmp_strval(gmp_init($ipv6long,10),2);
	    $ipv6 = "";

	    if (strlen($bin) < 128) {
	        $pad = 128 - strlen($bin);
	        for ($i = 1; $i <= $pad; $i++) {
	            $bin = "0".$bin;
	        }
	    }

	    $bits = 0;
	    while ($bits <= 7)
	    {
	        $bin_part = substr($bin,($bits*16),16);
	        $ipv6 .= dechex(bindec($bin_part)).":";
	        $bits++;
	    }
	    // compress result
	    return inet_ntop(inet_pton(substr($ipv6,0,-1)));
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
	    if ($this->identify_address ($address) == "IPv4" ) 				{ return(long2ip($address)); }
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
	 * Returns text representation of json errors
	 *
	 * @access public
	 * @param mixed $error_int
	 * @return void
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
		// return def
		if (isset($error[$error_int]))	{ return $error[$error_int]; }
		else							{ return "JSON_ERROR_UNKNOWN"; }
	}

	/**
	 * Creates image link to rack.
	 *
	 * @access public
	 * @param bool $rackId (default: false)
	 * @param bool $deviceId (default: false)
	 * @return void
	 */
	public function create_rack_link ($rackId = false, $deviceId = false) {
    	if($rackId===false) {
        	    return false;
    	}
    	else {
        	//device ?
        	if ($deviceId!==false) {
            	return $this->createURL ().BASE."app/tools/racks/draw_rack.php?rackId=$rackId&deviceId=$deviceId";
        	}
        	else {
            	return $this->createURL ().BASE."app/tools/racks/draw_rack.php?rackId=$rackId";
        	}
    	}
	}









	/**
	 *	@breadcrumbs functions
	 * ------------------------
	 */

	/**
	 *	print breadcrumbs
	 */
	public function print_breadcrumbs ($Section, $Subnet, $req, $Address=null) {
		# subnets
		if($req['page'] == "subnets")		{ $this->print_subnet_breadcrumbs ($Section, $Subnet, $req, $Address); }
		# folders
		elseif($req['page'] == "folder")	{ $this->print_folder_breadcrumbs ($Section, $Subnet, $req); }
		# tools
		elseif ($req['page'] == "tools") 	{ $this->print_tools_breadcrumbs ($req); }
	}

	/**
	 * Print address breadcrumbs
	 *
	 * @access private
	 * @param obj $Section
	 * @param obj $Subnet
	 * @param mixed $req
	 * @param obj $Address
	 * @return void
	 */
	private function print_subnet_breadcrumbs ($Section, $Subnet, $req, $Address) {
		if(isset($req['subnetId'])) {
			# get all parents
			$parents = $Subnet->fetch_parents_recursive ($req['subnetId']);
			print "<ul class='breadcrumb'>";
			# remove root - 0
			array_shift($parents);

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
		if(isset($req['tpage'])) {
			print "<ul class='breadcrumb'>";
			print "	<li><a href='".create_link("tools")."'>"._('Tools')."</a> <span class='divider'></span></li>";
			print "	<li class='active'>$req[tpage]></li>";
			print "</ul>";
		}
	}
}
?>
