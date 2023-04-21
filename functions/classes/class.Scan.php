<?php

/**
 *	phpIPAM SCAN and PING class
 */

class Scan extends Common_functions {

	/**
	 * (array of objects) to store addresses, address ID is array index
	 *
	 * @var mixed
	 * @access public
	 */
	public $addresses;

	/**
	 * php executable file
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access public
	 */
	public $php_exec = null;

	/**
	 * default icmp type
	 *
	 * (default value: "ping")
	 *
	 * @var string
	 * @access public
	 */
	public $icmp_type = "ping";

	/**
	 * Sets OS type
	 *
	 * @var string
	 */
	public $os_type = "default";

	/**
	 * icmp timeout
	 *
	 * (default value: 1)
	 *
	 * @var int
	 * @access protected
	 */
	protected $icmp_timeout = 1;

	/**
	 * icmp retries
	 *
	 * (default value: 1)
	 *
	 * @var int
	 * @access protected
	 */
	protected $icmp_count = 1;

	/**
	 *  exit or return icmp status
	 *
	 * (default value: false)
	 *
	 * @var bool
	 * @access protected
	 */
	protected $icmp_exit = false;

	/**
	 * Database
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Database;

	/**
	 * Subnets
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Subnets;

	/**
	 * Addresses
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Addresses;






	/**
	 * __construct function
	 *
	 * @access public
	 * @param Database_PDO $database
	 * @param mixed $settings (default: null)
	 */
	public function __construct (Database_PDO $database, $settings = null) {
		parent::__construct();

		# Save database object
		$this->Database = $database;
		# initialize Result
		$this->Result = new Result ();

		# fetch settings
		$settings = is_null($this->settings) ? $this->get_settings() : (object) $this->settings;

		$this->ping_type   = $settings->scanPingType;
		$this->ping_path   = $settings->scanPingPath;
		$this->fping_path  = $settings->scanFPingPath;

		# set type
		$this->reset_scan_method ($this->ping_type);
		# set OS type
		$this->set_os_type ();
		# set php exec
		$this->set_php_exec ();
		# Log object
		$this->Log = new Logging ($this->Database, $settings);

		if ($this->icmp_type != "none" && $errmsg = php_feature_missing(null, ['exec']))
			$this->Result->show("danger", $errmsg, true);
	}

	/**
	 * Returns all possible ping types / apps
	 *
	 * @access public
	 * @return array
	 */
	public function ping_fetch_types () {
		return ["none", "ping", "pear", "fping"];
	}

	/**
	 * This functin resets the scan method, for cron scripts
	 *
	 * @access public
	 * @param mixed $method
	 * @return void
	 */
	public function reset_scan_method ($method) {
		// fetch possible methods
		$possible = $this->ping_fetch_types ();
		//check
		if(!in_array($method, $possible)) {
			//die or print?
			if($this->icmp_exit)				{ die(json_encode(array("status"=>1, "error"=>_("Invalid scan method.")))); }
			else								{ $this->Result->show("danger", _("Invalid scan method."), true); }
		}
		//ok
		else {
			$this->icmp_type = $method;
		}
	}

	/**
	 * Sets php exec
	 *
	 * @access private
	 * @return void
	 */
	private function set_php_exec () {
		// Invoked via CLI, use current php-cli binary if known (>php5.3)
		if ( php_sapi_name() === "cli" && defined('PHP_BINARY') ) {
			$this->php_exec = PHP_BINARY;
			return;
		}

		// Invoked via HTML (or php5.3)
		$php_cli_binary = Config::ValueOf('php_cli_binary');

		// Check for user specified php-cli binary (Multiple php versions installed)
		if ( !empty($php_cli_binary) ) {
			$this->php_exec = $php_cli_binary;
			return;
		}

		// Default: Use system default php version symlinked to php in PHP_BINDIR
		$this->php_exec = $this->os_type=="Windows" ? PHP_BINARY : PHP_BINDIR."/php";
	}

	/**
	 * Sets OS type
	 *
	 * @method set_os_type
	 */
	private function set_os_type () {
		if	(PHP_OS == "FreeBSD" || PHP_OS == "NetBSD")                         { $this->os_type = "FreeBSD"; }
		elseif(PHP_OS == "Linux" || PHP_OS == "OpenBSD")                        { $this->os_type = "Linux"; }
		elseif(PHP_OS == "WIN32" || PHP_OS == "Windows" || PHP_OS == "WINNT")	{ $this->os_type = "Windows"; }
	}

	/**
	 * Set weather the code should exit or return
	 *
	 *	Default is return
	 *
	 * @access public
	 * @param bool $exit (default: false)
	 * @return void
	 */
	public function ping_set_exit ($exit = false) {
		$this->icmp_exit = $exit;
	}









	/**
	 *	@ping @icmp methods
	 *	--------------------------------
	 */

	/**
	 * Function that pings address and checks if it responds
	 *
	 *	any script can be used by extension, important are results
	 *
	 *	0 = Alive
	 *	1 = Offline
	 *	2 = Offline
	 *
	 *	all other codes can be explained in ping_exit_explain method
	 *
	 * @access public
	 * @param mixed $address
	 * @param int $count (default: 1)
	 * @param int $timeout (default: 1)
	 * @param bool $exit (default: false)
	 * @return void
	 */
	public function ping_address ($address, $count=1, $timeout = 1) {
		#set parameters
		$this->icmp_timeout = $timeout;
		$this->icmp_count = $count;

		# escape address
		$address = escapeshellarg($address);

		# make sure it is in right format
		$address = $this->transform_address ($address, "dotted");
		# set method name variable
		$ping_method = "ping_address_method_".$this->icmp_type;
		# ping with selected method
		return $this->{$ping_method} ($address);
	}

	/**
	 * Null Ping method - return "Scanning disabled" (1001)
	 */
	protected function ping_address_method_none($address) {
		if($this->icmp_exit) {
			exit(1001);
		} else {
			return 1001;
		}
	}

	/**
	 * Ping selected address and return response
	 *
	 *	timeout value: for miliseconds multiplyy by 1000
	 *
	 * @access protected
	 * @param ip $address
	 * @return void
	 */
	protected function ping_address_method_ping ($address) {
		# if ipv6 append 6
		$ping_path = ($this->identify_address ($address)=="IPv6") ? $this->ping_path."6" : $this->ping_path;

		# verify ping path
		$this->ping_verify_path ($ping_path);

		# set ping command based on OS type
		if ($this->os_type == "FreeBSD")    { $cmd = $ping_path." -c $this->icmp_count -W ".($this->icmp_timeout*1000)." $address 1>/dev/null 2>&1"; }
		elseif($this->os_type == "Linux")   { $cmd = $ping_path." -c $this->icmp_count -W $this->icmp_timeout $address 1>/dev/null 2>&1"; }
		elseif($this->os_type == "Windows")	{ $cmd = $ping_path." -n $this->icmp_count -w ".($this->icmp_timeout*1000)." $address"; }
		else								{ $cmd = $ping_path." -c $this->icmp_count -n $address 1>/dev/null 2>&1"; }

        # for IPv6 remove wait
        if ($this->identify_address ($address)=="IPv6") {
            $cmd = explode(" ", $cmd);
            unset($cmd[3], $cmd[4]);
            $cmd = implode(" ", $cmd);
        }

		# execute command, return $retval
	    exec($cmd, $output, $retval);

		# return result for web or cmd
		if($this->icmp_exit)	{ exit  ($retval); }
		else					{ return $retval; }
	}

	/**
	 * Ping selected address with PEAR ping package
	 *
	 * @access protected
	 * @param ip $address
	 * @return void
	 */
	protected function ping_address_method_pear ($address) {
		# we need pear ping package
		require_once(dirname(__FILE__) . '/../../functions/PEAR/Net/Ping.php');
		$ping = Net_Ping::factory();

		# ipv6 not supported
		if (!is_object($ping) || $this->identify_address ($address)=="IPv6") {
    		//return result for web or cmd
    		if($this->icmp_exit) 	{ exit	(255); }
    		else	  				{ return 255; }
		}

		# Check for PEAR_Error
		if ($ping instanceof PEAR_Error) {
			//return result for web or cmd
			if($this->icmp_exit)    { exit ($ping->code); }
			else                    { return $ping->code; }
		}

		# check for errors
		if($ping->pear->isError($ping)) {
			if($this->icmp_exit)	{ exit  ($ping->getMessage()); }
			else					{ return $ping->getMessage(); }
		}
		else {
			//set count and timeout
			$ping->setArgs(array('count' => $this->icmp_timeout, 'timeout' => $this->icmp_timeout));
			//execute
			$ping_response = $ping->ping($address);
			//check response for error
			if($ping->pear->isError($ping_response)) {
				$result['code'] = 2;
			}
			else {
				//all good
				if($ping_response->_transmitted == $ping_response->_received) {
					$result['code'] = 0;
					$this->rtt = "RTT: ". strstr($ping_response->_round_trip['avg'], ".", true);
				}
				//ping loss
				elseif($ping_response->_received == 0) {
					$result['code'] = 1;
				}
				//failed
				else {
					$result['code'] = 3;
				}
			}
		}

		//return result for web or cmd
		if($this->icmp_exit) 	{ exit	($result['code']); }
		else	  				{ return $result['code']; }
	}

	/**
	 * Ping selected address with fping function
	 *
	 *	Exit status is:
	 *		0 if all the hosts are reachable,
	 *		1 if some hosts were unreachable,
	 *		2 if any IP addresses were not found,
	 *		3 for invalid command line arguments,
	 *		4 for a system call failure.
	 *
	 *	fping cannot be run from web, it needs root privileges to be able to open raw socket :/
	 *
	 * @access public
	 * @param mixed $subnet 	//CIDR
	 * @return void
	 */
	public function ping_address_method_fping ($address) {
		$this->ping_verify_path ($this->fping_path);

		# set command
		$type = ($this->identify_address ($address)=="IPv6") ? '--ipv6' : '--ipv4';
		$cmd = $this->fping_path." $type -c $this->icmp_count -t ".($this->icmp_timeout*1000)." $address";
		# execute command, return $retval
	    exec($cmd, $output, $retval);

	    # save result
	    if($retval==0) {
	    	$this->save_fping_rtt ($output[0]);
		}

		# return result for web or cmd
		if($this->icmp_exit)	{ exit  ($retval); }
		else					{ return $retval; }
	}

	/**
	 * Saves RTT for fping
	 *
	 * @access private
	 * @param mixed $line
	 * @return void
	 */
	private function save_fping_rtt ($line) {
		// 173.192.112.30 : xmt/rcv/%loss = 1/1/0%, min/avg/max = 160/160/160
 		$tmp = explode(" ",$line);

 		# save rtt
		@$this->rtt	= "RTT: ".str_replace("(", "", $tmp[7]);
	}

	/**
	 * Ping selected address with fping function
	 *
	 *	Exit status is:
	 *		0 if all the hosts are reachable,
	 *		1 if some hosts were unreachable,
	 *		2 if any IP addresses were not found,
	 *		3 for invalid command line arguments,
	 *		4 for a system call failure.
	 *
	 *	fping cannot be run from web, it needs root privileges to be able to open raw socket :/
	 *
	 * @access public
	 * @param mixed $subnet 	//CIDR
	 * @return void
	 */
	public function ping_address_method_fping_subnet ($subnet_cidr, $return_result = false) {
		$this->ping_verify_path ($this->fping_path);
		$out = array();
		# set command
		$cmd = $this->fping_path . ' -c ' . $this->icmp_count . ' -t ' . ($this->icmp_timeout*1000) . ' -Ag ' . $subnet_cidr;
		# execute command, return $retval
	    exec($cmd, $output, $retval);

	    # save result
	    if(sizeof($output)>0) {
	    	foreach($output as $line) {
				if (!preg_match("/(timed out|100% loss)/", $line)) {
					$tmp = explode(" ",$line);
					$out[] = $tmp[0];
				}
	    	}
	    }

	    # save to var
	    $this->fping_result = $out;

	    # return result?
	    if($return_result)		{ return $out; }

		# return result for web or cmd
		if($this->icmp_exit)	{ exit  ($retval); }
		else					{ return $retval; }
	}

	/**
	 * Verifies that ping file exists
	 *
	 * @access private
	 * @param mixed $path
	 * @return void
	 */
	private function ping_verify_path ($path) {
		// Windows
		if($this->os_type=="Windows") {
			if(!file_exists('"'.$path.'"')) {
				if($this->icmp_exit)	{ exit  ($this->ping_exit_explain(1000)); }
				else					{ return $this->Result->show("danger", _($this->ping_exit_explain(1000)), true);  }
			}
		}
		else {
			if(!file_exists($path)) {
				if($this->icmp_exit)	{ exit  ($this->ping_exit_explain(1000)); }
				else					{ return $this->Result->show("danger", _($this->ping_exit_explain(1000)), true);  }
			}
		}
	}

	/**
	 * Explains invalid error codes
	 *
	 * @access public
	 * @param mixed $code
	 * @return void
	 */
	public function ping_exit_explain ($code) {
		# fetch explain codes
		$explain_codes = $this->ping_set_exit_code_explains ();

		# return code
		return isset($explain_codes[$code]) ? $explain_codes[$code] : false;
	}

	/**
	 * This function sets ping exit code and message mappings
	 *
	 *	http://www.freebsd.org/cgi/man.cgi?query=sysexits&apropos=0&sektion=0&manpath=FreeBSD+4.3-RELEASE&arch=default&format=ascii
	 *
	 *	extend if needed for future scripts
	 *
	 * @access public
	 * @return void
	 */
	public function ping_set_exit_code_explains () {
		$explain_codes[0]  = "SUCCESS";
		$explain_codes[1]  = "OFFLINE";
		$explain_codes[2]  = "ERROR";
		$explain_codes[3]  = "UNKNOWN ERROR";
		$explain_codes[64] = "EX_USAGE";
		$explain_codes[65] = "EX_DATAERR";
		$explain_codes[68] = "EX_NOHOST";
		$explain_codes[70] = "EX_SOFTWARE";
		$explain_codes[71] = "EX_OSERR";
		$explain_codes[72] = "EX_OSFILE";
		$explain_codes[73] = "EX_CANTCREAT";
		$explain_codes[74] = "EX_IOERR";
		$explain_codes[75] = "EX_TEMPFAIL";
		$explain_codes[77] = "EX_NOPERM";
		$explain_codes[255] = "EX_NOT_SUPPORTED";
		$explain_codes[1000] = _("Invalid ping path");
		$explain_codes[1001] = _("Scanning disabled");
		# return codes
		return $explain_codes;
	}

	/**
	 * Update lastseen field for specific IP address
	 *
	 * @access public
	 * @param int $id
	 * @param datetime $datetime
	 * @return void
	 */
	public function ping_update_lastseen ($id, $datetime = null) {
    	# set datetime
    	$datetime = is_null($datetime) ? date("Y-m-d H:i:s") : $datetime;
		# execute
		try { $this->Database->updateObject("ipaddresses", array("id"=>$id, "lastSeen"=>$datetime), "id"); }
		catch (Exception $e) {
			!$this->debugging ? : $this->Result->show("danger", $e->getMessage(), false);
			# log
			!$this->debugging ? : $this->Log->write (_("Status update"), _('Failed to update address status.'), 0 );
		}
	}

	/**
	 * Update last check time for agent
	 *
	 * @access public
	 * @param int $id
	 * @param datetime $date
	 * @return void
	 */
	public function ping_update_scanagent_checktime ($id, $date = false) {
    	# set time
    	if ($date === false)    { $date = date("Y-m-d H:i:s"); }
    	else                    { $date = $date; }
		# execute
		try { $this->Database->updateObject("scanAgents", array("id"=>$id, "last_access"=>date("Y-m-d H:i:s")), "id"); }
		catch (Exception $e) {
		}
	}

	/**
	 * Updates last time that subnet was scanned
	 *
	 * @method update_subnet_scantime
	 * @param  int $subnet_id
	 * @param  false|datetime $datetime
	 * @return void
	 */
	public function update_subnet_scantime ($subnet_id, $datetime = false) {
		// set date
		$datetime = $datetime===false ? date("Y-m-d H:i:s") : $datetime;
		// update
		try { $this->Database->updateObject("subnets", array("id"=>$subnet_id, "lastScan"=>$datetime), "id"); }
		catch (Exception $e) {}
	}

	/**
	 * Updates last time discovery check was run on subnet
	 *
	 * @method update_subnet_discoverytime
	 * @param  int $subnet_id
	 * @param  false|datetime $datetime
	 * @return void
	 */
	public function update_subnet_discoverytime ($subnet_id, $datetime = false) {
		// set date
		$datetime = $datetime===false ? date("Y-m-d H:i:s") : $datetime;
		// update
		try { $this->Database->updateObject("subnets", array("id"=>$subnet_id, "lastDiscovery"=>$datetime), "id"); }
		catch (Exception $e) {}
	}

	/**
	 * Updates address tag if state changes
	 *
	 * @method update_address_tag
	 * @param  int$address_id
	 * @param  int $tag_id (default: 2)
	 * @param  int $old_tag_id (default: null)
	 * @param  string $last_seen_date (default: false)
	 * @return bool
	 */
	public function update_address_tag ($address_id, $tag_id = 2, $old_tag_id = null, $last_seen_date = false) {
		if (is_numeric($address_id)) {
			// don't update statuses for never seen addresses !
			if ($last_seen_date!==false && !is_null($last_seen_date) && strlen($last_seen_date)>2 && $last_seen_date!="0000-00-00 00:00:00" && $last_seen_date!="1970-01-01 00:00:01" && $last_seen_date!="1970-01-01 01:00:00") {
				// dont update reserved to offline
				if (!($tag_id==1 && $old_tag_id==3)) {
					try { $this->Database->updateObject("ipaddresses", array("id"=>$address_id, "state"=>$tag_id), "id"); }
					catch (Exception $e) {
						return false;
					}
				}
				else {
					return false;
				}
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
		// ok
		return true;
	}

	/**
	 * Opens socket connection on specified TCP ports, if at least one is available host is alive
	 *
	 * @access public
	 * @param mixed $address
	 * @param mixed $port
	 * @return void
	 */
	public function telnet_address ($address, $port) {
		# set all ports
		$ports = explode(",", str_replace(";",",",$port));
		# default response is dead
		$retval = 1;
		//try each port untill one is alive
		foreach($ports as $p) {
			// open socket
			$conn = @fsockopen($address, $p, $errno, $errstr, $this->icmp_timeout);
			//failed
			if (!$conn) {}
			//success
			else 		{
				$retval = 0;	//set return as port if alive
				fclose($conn);
				break;			//end foreach if success
			}
		}
	    # exit with result
	    exit($retval);
	}









	/**
	 *	@prepare addresses methods
	 *	--------------------------------
	 */

	/**
	 * Returns all addresses to be scanned or updated
	 *
	 * @access public
	 * @param mixed $type		//discovery, update
	 * @param mixed $subnet
	 * @param bool $type
	 * @return void
	 */
	public function prepare_addresses_to_scan ($type, $subnet, $die = true) {
		# discover new addresses
		if($type=="discovery") 	{ return is_numeric($subnet) ? $this->prepare_addresses_to_discover_subnetId ($subnet, $die) : $this->prepare_addresses_to_discover_subnet ($subnet, $die); }
		# update addresses statuses
		elseif($type=="update") { return $this->prepare_addresses_to_update ($subnet); }
		# fail
		else 					{ die(json_encode(array("status"=>1, "error"=>_("Invalid scan type provided.")))); }
	}

	/**
	 * Returns array of all addresses to be scanned inside subnet defined with subnetId
	 *
	 * @access public
	 * @param mixed $subnetId
	 * @return void
	 */
	public function prepare_addresses_to_discover_subnetId ($subnetId, $die) {
		# initialize classes
		$Subnets   = new Subnets ($this->Database);

		//subnet ID is provided, fetch subnet
		$subnet = $Subnets->fetch_subnet(null, $subnetId);
		if($subnet===false)	{
			 if ($die)											{ die(json_encode(array("status"=>1, "error"=>_("Invalid subnet ID provided.")))); }
			 else												{ return array(); }
		}

		// we should support only up to 4094 hosts!
		if($Subnets->max_hosts ($subnet)>4096 && php_sapi_name()!="cli")
		if ($die)												{ die(json_encode(array("status"=>1, "error"=>_("Scanning from GUI is only available for subnets up to /20 or 4096 hosts!")))); }
		else													{ return array(); }

		# set array of addresses to scan, exclude existing!
		$ip = $Subnets->get_all_possible_subnet_addresses ($subnet);

		# remove existing
		$ip = $this->remove_existing_subnet_addresses ($ip, $subnetId);

		//none to scan?
		if(sizeof($ip)==0)	{
			if ($die)											{ die(json_encode(array("status"=>1, "error"=>"Didn't find any address to scan!"))); }
			else												{ return array(); }
		}

		//return
		return $ip;
	}

	/**
	 * Removes existing addresses from
	 *
	 * @access private
	 * @param mixed $ip				//array of ip addresses in decimal format
	 * @param mixed $subnetId		//id of subnet
	 * @return array
	 */
	private function remove_existing_subnet_addresses ($ip, $subnetId) {
		# first fetch all addresses
		$Addresses = new Addresses ($this->Database);
		// get all existing IP addresses in subnet
		$addresses  = $Addresses->fetch_subnet_addresses($subnetId);
		// if some exist remove them
		if(is_array($addresses) && is_array($ip) && sizeof($ip)>0) {
			foreach($addresses as $a) {
				$key = array_search($a->ip_addr, $ip);
				if($key !== false) {
					unset($ip[$key]);
				}
			}
			//reindex array for pinging
			$ip = array_values(@$ip);
		}
		//return
		return is_array(@$ip) ? $ip : array();
	}

	/**
	 * Returns array of all addresses in subnet
	 *
	 * @access public
	 * @param mixed $subnet
	 * @return void
	 */
	public function prepare_addresses_to_discover_subnet ($subnet) {
		# initialize classes
		$Subnets   = new Subnets ($this->Database);

		# result
		$ip = $Subnets->get_all_possible_subnet_addresses ($subnet);
		//none to scan?
		if(sizeof($ip)==0)									{ die(json_encode(array("status"=>1, "error"=>_("Didn't find any address to scan!")))); }
		//result
		return $ip;
	}

	/**
	 * Returns array of all addresses to be scanned
	 *
	 * @access public
	 * @param mixed $subnetId
	 * @return void
	 */
	public function prepare_addresses_to_update ($subnetId) {
		# first fetch all addresses
		$Addresses = new Addresses ($this->Database);
		// get all existing IP addresses in subnet
		$subnet_addresses = $Addresses->fetch_subnet_addresses($subnetId);
		//create array
		if(is_array($subnet_addresses) && sizeof($subnet_addresses)>0) {
			foreach($subnet_addresses as $a) {
				$scan_addresses[$a->id] = $a->ip_addr;
			}
			//reindex
			$scan_addresses = array_values(@$scan_addresses);
			//return
			return $scan_addresses;
		}
		else {
			return array();
		}
	}
}


/**
 *	@scan helper functions
 * ------------------------
 */

/**
 *	Ping address helper for CLI threading
 *
 *	used for:
 *		- icmp status update (web > ping, pear)
 *		- icmp subnet discovery (web > ping, pear)
 *		- icmp status update (cli)
 *		- icmp discovery (cli)
 */
function ping_address ($address) {
//	$Database = new Database_PDO;
//	$Scan = new Scan ($Database);
 	global $Scan;
	//scan
	return $Scan->ping_address ($address);
}

/**
 *	Telnet address helper for CLI threading
 */
function telnet_address ($address, $port) {
	global $Scan;
	//scan
	return $Scan->telnet_address ($address, $port);
}

/**
 *	fping subnet helper for fping threading, all methods
 */
function fping_subnet ($subnet_cidr, $return = true) {
	global $Scan;
	//scan
	return $Scan->ping_address_method_fping_subnet ($subnet_cidr, $return);
}
