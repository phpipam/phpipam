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
	 * If Jdon validation error occurs it will be saved here
	 *
	 * (default value: false)
	 *
	 * @var bool
	 * @access public
	 */
	public $json_error = false;

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
	 * @return bool|object
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
	 * @return bool|object
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
    			$this->cache_write ($table, $res->{$method}, $res);
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
        if ($notification_users!==false) {
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
					define(SETTINGS, json_encode($settings));
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
                    $this->cache[$table][$identifier][$id]->ip = $this->transform_address ($object->{$ip_check}, "dotted");
                }
            }
        }
    }

    /**
     * Check if caching is not needed
     *
     * @access protected
     * @param mixed $table
     * @return bool
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
     * @return bool|mixed
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
     * @return mixed
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
     * @return bool|array
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
	 * @return int|mixed
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
	 * Create URL for base
	 *
	 * @access public
	 * @return mixed
	 */
	public function createURL () {
		# reset url for base
		if($_SERVER['SERVER_PORT'] == "443") 		{ $url = "https://$_SERVER[HTTP_HOST]"; }
		// reverse proxy doing SSL offloading
        elseif(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')  { $url = "https://$_SERVER[HTTP_X_FORWARDED_HOST]"; }
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
	 * @return mixed
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
		        "move"
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
	    return preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/",$email) ? true : false;
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
        // for older php versions make sure that function "json_last_error_msg" exist and create it if not
        if (!function_exists('json_last_error_msg')) {
            function json_last_error_msg() {
                static $ERRORS = array(
                    JSON_ERROR_NONE => 'No error',
                    JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
                    JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
                    JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
                    JSON_ERROR_SYNTAX => 'Syntax error',
                    JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
                );

                $error = json_last_error();
                return isset($ERRORS[$error]) ? $ERRORS[$error] : 'Unknown error';
            }
        }

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
	 * Transforms int to ipv6
	 *
	 * @access public
	 * @param mixed $ipv6long
	 * @return mixed
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
		// return def
		if (isset($error[$error_int]))	{ return $error[$error_int]; }
		else							{ return "JSON_ERROR_UNKNOWN"; }
	}

	/**
	 * Fetches latlng from googlemaps by provided address
	 *
	 * @access public
	 * @param mixed $address
	 * @return array
	 */
	public function get_latlng_from_address ($address) {
        // replace spaces
        $address = str_replace(' ','+',$address);
        // get grocode
        $geocode=file_get_contents('https://maps.google.com/maps/api/geocode/json?address='.$address.'&sensor=false');
        $output= json_decode($geocode);
        // return result
        return array("lat"=>$output->results[0]->geometry->location->lat, "lng"=>$output->results[0]->geometry->location->lng);
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
     * @param mixed $action
     * @param mixed $timepicker_index
     * @return array
     */
    public function create_custom_field_input ($field, $object, $action, $timepicker_index) {
        # make sure it is array
        $field = (array) $field;
        $object = (object) $object;
        $html = array();

        # replace spaces with |
        $field['nameNew'] = str_replace(" ", "___", $field['name']);

        # required
        $required = $field['Null']=="NO" ? "*" : "";

        # set default value if adding new object
        if ($action=="add")	{ $object->{$field['name']} = $field['Default']; }


        //set, enum
        if(substr($field['type'], 0,3) == "set" || substr($field['type'], 0,4) == "enum") {
        	//parse values
        	$tmp = substr($field['type'], 0,3)=="set" ? explode(",", str_replace(array("set(", ")", "'"), "", $field['type'])) : explode(",", str_replace(array("enum(", ")", "'"), "", $field['type']));
        	//null
        	if($field['Null']!="NO") { array_unshift($tmp, ""); }

        	$html[] = "<select name='$field[nameNew]' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$field[Comment]'>";
        	foreach($tmp as $v) {
            $html[] = $v==$object->{$field['name']} ? "<option value='$v' selected='selected'>$v</option>" : "<option value='$v'>$v</option>";
        	}
        	$html[] = "</select>";
        }
        //date and time picker
        elseif($field['type'] == "date" || $field['type'] == "datetime") {
        	// just for first
        	if($timepicker_index==0) {
        		$html[] =  '<link rel="stylesheet" type="text/css" href="css/1.2/bootstrap/bootstrap-datetimepicker.min.css">';
        		$html[] =  '<script type="text/javascript" src="js/1.2/bootstrap-datetimepicker.min.js"></script>';
        		$html[] =  '<script type="text/javascript">';
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
        	if(!isset($object->{$field['name']}))	{ $html[] = ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'] .'" maxlength="'.$size.'" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n"; }
        	else								    { $html[] = ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'] .'" maxlength="'.$size.'" value="'. $object->{$field['name']}. '" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n"; }
        }
        //boolean
        elseif($field['type'] == "tinyint(1)") {
        	$html[] =  "<select name='$field[nameNew]' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$field[Comment]'>";
        	$tmp = array(0=>"No",1=>"Yes");
        	//null
        	if($field['Null']!="NO") { $tmp[2] = ""; }

        	foreach($tmp as $k=>$v) {
        		if(strlen($object->{$field['name']})==0 && $k==2)	{ $html[] = "<option value='$k' selected='selected'>"._($v)."</option>"; }
        		elseif($k==$object->{$field['name']})				{ $html[] = "<option value='$k' selected='selected'>"._($v)."</option>"; }
        		else											    { $html[] = "<option value='$k'>"._($v)."</option>"; }
        	}
        	$html[] = "</select>";
        }
        //text
        elseif($field['type'] == "text") {
        	$html[] = ' <textarea class="form-control input-sm" name="'. $field['nameNew'] .'" placeholder="'. $field['name'] .'" rowspan=3 rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. $object->{$field['name']}. '</textarea>'. "\n";
        }
        //default - input field
        else {
        	$html[] = ' <input type="text" class="ip_addr form-control input-sm" name="'. $field['nameNew'] .'" placeholder="'. $field['name'] .'" value="'. $object->{$field['name']}. '" size="30" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n";
        }

        # result
        return array(
            "required" => $required,
            "field" => implode("\n", $html),
            "timepicker_index" => $timepicker_index
        );
	}

	/**
	 * Creates image link to rack.
	 *
	 * @access public
	 * @param bool $rackId (default: false)
	 * @param bool $deviceId (default: false)
	 * @return mixed|bool
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
    	$title[] = $this->settings->siteTitle;

    	// page
    	if (isset($get['page'])) {
        	// dashboard
        	if ($get['page']=="dashboard") {
            	return $this->settings->siteTitle." Dashboard";
        	}
        	// install, upgrade
        	elseif ($get['page']=="temp_share" || $get['page']=="request_ip" || $get['page']=="opensearch") {
            	$title[] = ucwords($get['page']);
        	}
        	// sections, subnets
        	elseif ($get['page']=="subnets" || $get['page']=="folder") {
            	// subnets
            	$title[] = "Subnets";

            	// section
            	if (isset($get['section'])) {
                 	$se = $this->fetch_object ("sections", "id", $get['section']);
                	if($se!==false) {
                    	$title[] = $se->name;
                	}
            	}
            	// subnet
            	if (isset($get['subnetId'])) {
                 	$sn = $this->fetch_object ("subnets", "id", $get['subnetId']);
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
                    $ip = $this->fetch_object ("ipaddresses", "id", $get['ipaddrid']);
                    if($ip!==false) {
                        $title[] = $this->transform_address($ip->ip_addr, "dotted");
                    }
            	}
        	}
        	// tools, admin
        	elseif ($get['page']=="tools" || $get['page']=="administration") {
            	$title[] = ucwords($get['page']);
            	// subpage
            	if (isset($get['section'])) {
                	$title[] = ucwords($get['section']);
            	}
            	if (isset($get['subnetId'])) {
                	// vland domain
                	if($get['section']=="vlan") {
                     	$se = $this->fetch_object ("vlanDomains", "id", $get['subnetId']);
                    	if($se!==false) {
                        	$title[] = $se->name." domain";
                    	}
                	}
                	else {
                    	$title[] = ucwords($get['subnetId']);
                    }
            	}
        	}
        	else {
            	$title[] = ucwords($get['page']);
            }
    	}
        // return title
    	return implode(" / ", $title);
	}

}
?>
