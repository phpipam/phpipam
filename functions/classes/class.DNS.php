<?php

/**
 *	phpIPAM DNS class to manage DNS-related dunctions
 *
 */

class DNS extends Common_functions {

	/**
	 * private variables
	 */
	public $settings = false;				// settings
	private $type = "A";					// type of record to fetch

	/**
	 * object holders
	 */
	protected $Result;						//for Result printing
	protected $Database;					//for Database connection



	/**
	 * __construct method
	 *
	 * @access public
	 * @return void
	 */
	public function __construct (Database_PDO $Database, $settings=null) {
		# initialize Result
		$this->Result = new Result ();
		# initialize object
		$this->Database = $Database;
		// settings
		$this->settings = !is_null($settings) ? (object) $settings : $this->get_settings ();
		// initialize resolver
		$this->initialize_pear_net_DNS2 ();
	}


	/**
	 * Sets array of nameservers to use
	 *
	 * @access private
	 * @param mixed $nsid (default: null)
	 * @return void
	 */
	private function set_nameservers ($nsid = null) {
		// null ?
		if (is_null($nsid))								{ return false; }
		// not numeric
		elseif (!is_numeric($nsid))						{ return false; }
		// ok
		else {
			// fetch nameservers
			$nameservers =  $this->fetch_object ("nameservers", "id", $nsid);
			// error
			if ($nameservers===false)					{ return false; }
			// ok
			else {
				if (strlen($nameservers->namesrv1)==0)	{ return false; }
				else {
					$this->ns = explode(";", $nameservers->namesrv1);
				}
			}
		}
	}

	/**
	 * Fetches object from database
	 *
	 * @access private
	 * @param mixed $table (default: null)
	 * @param mixed $field (default: null)
	 * @param mixed $value (default: null)
	 * @return void
	 */
	private function fetch_object ($table = null, $field = null, $value = null) {
		// checks
		if(is_null($table))		return false;
		if(is_null($field))		return false;
		if(is_null($value))		return false;
		if($value=="0")			return false;
		// escape
		$table = $this->Database->escape($table);
		$field = $this->Database->escape($field);

		// fetch
		try { $res = $this->Database->getObjectQuery("SELECT * from `$table` where `$field` = ? limit 1;", array($value)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# save to cache array
		if(sizeof($res)==0) { return false; }
		else {
			return $res;
		}
	}

 	/**
 	 * Resolves hostname from IP or IP from hostname
 	 *
 	 * @access public
 	 * @param mixed $address  (default: false)	- IP address
 	 * @param mixed $hostname (default: false)	- hostname
 	 * @param bool $override  (default: false)	- checks even if not permitted in settings
 	 * @param int $nsid 	  (default: 0)		- nameserver ID
 	 *
 	 * @return array ("class"=>"", "address"=>$address, "name"=>$hostname);
 	 */
 	public function resolve_address ($address = false, $hostname = false, $override = false, $nsid = 0) {
		// set nameserver
		$this->set_nameservers ($nsid);
		// make sure it is dotted format
		$address = $this->transform_address ($address, "dotted");

		// if both are set ignore
		if (strlen($hostname)>1 && strlen($address)>0) {
											{ return array("class"=>"", "address"=>$address, "name"=>$hostname); }
		}
		// if settings permits to check or override is set
		elseif($this->settings->enableDNSresolving == 1 || $override===true) {
			// if address is set fetch A record
			if ($address!==false && strlen($address)>0) {
				// set resolve type
				$this->type = "PTR";

				// resolve
				$resolved = $this->resolve_address_net_dns ($address);
				// false ?
				if ($resolved===false)		{ return array("class"=>"", 		"address"=>$address, "name"=>$hostname); }
				else						{ return array("class"=>"resolved", "address"=>$address, "name"=>$resolved); }
			}
			// if hostname is set fetch PTR record
			elseif($hostname!==false && strlen($hostname)>0) {
				// set resolve type
				$this->type = "A";

				// resolve
				$resolved = $this->resolve_address_net_dns ($hostname);
				// false ?
				if ($resolved===false)		{ return array("class"=>"",			"address"=>$address, "name"=>$hostname); }
				else						{ return array("class"=>"resolved", "address"=>$resolved, "name"=>$hostname); }
			}
		}
		// dont check
		else 								{ return array("class"=>"",			"address"=>$address, "name"=>$hostname); }
	}

	/**
	 * Resolve address using NET_DNS2
	 *
	 * @access public
	 * @param mixed $address
	 * @return void
	 */
	public function resolve_address_net_dns ($address) {
		// set nameservers
		if (isset($this->ns))
		$this->DNS2->setServers ($this->ns);

		// try to get record
		try {
		    $result = $this->DNS2->query($address, $this->type);
		} catch(Net_DNS2_Exception $e) {
			// log error
			$this->resolve_error = $e->getMessage();
			return false;
		}

		// return response
		if (isset($result->answer)) {
			// set what to search
			$search = $this->type=="PTR" ? "ptrdname" : "address";

			foreach($result->answer as $mxrr) {
				if ($mxrr->$search) {
					return $mxrr->$search;
				}
			}
		}
		else {
			return false;
		}
	}

}