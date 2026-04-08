<?php

/**
 *	phpIPAM DNS class to manage DNS-related functions
 *
 */

class DNS extends Common_functions {

	/**
	 * type of record to fetch
	 *
	 * (default value: "A")
	 *
	 * @var string
	 * @access private
	 */
	private $type = "A";

	/**
	 * Database
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Database;

	/**
	 * DNS type - local (no DNS set for subnet) or remote
	 *
	 * @var string
	 */
	public $dns_type = "local";

	/**
	 * Multiple result flag
	 *
	 * @var bool
	 */
	private $multiple = false;

	/**
	 * Flag if local DNS in /etc/resolv.conf cannot be accessed
	 *
	 * @var bool
	 */
	public $local_failed = false;

	/**
	 * Array of DNS servers to use
	 *
	 * @var array
	 */
	public $ns = array();

	/**
	 * Array of dead NS
	 *
	 * @var array
	 */
	public $dead_ns = array();

	/**
	 * Print error if DNS is not accessible
	 *
	 * @var bool
	 */
	public $print_error = false;

	/**
	 * Resolve error if DNS is not accessible
	 *
	 * @var mixed
	 */
	private $resolve_error;




	/**
	 * __construct function.
	 *
	 * @access public
	 * @param Database_PDO $Database
	 * @param mixed $settings (default: null)
	 */

	/**
	 * __construct function
	 *
	 * @method __construct
	 * @param  Database_PDO $Database
	 * @param  object|array $settings
	 * @param  bool         $print_error
	 */
	public function __construct (Database_PDO $Database, $settings=null, $print_error = false) {
		# initialize Result
		$this->Result = new Result ();
		# initialize object
		$this->Database = $Database;
		// settings
		$this->settings = !is_null($settings) ? (object) $settings : $this->get_settings ();
		// initialize resolver
		$this->initialize_pear_net_DNS2 ();
		// set print error flg
		$this->print_error = $print_error;
	}

	/**
	 * Set flag to return multiple records if found
	 *
	 * @method set_multiple
	 * @param  bool $multiple
	 */
	public function set_multiple ($multiple = false) {
		if(is_bool($multiple)) {
			$this->multiple = $multiple;
		}
	}


	/**
	 * Sets array of nameservers to use
	 *
	 * @access private
	 * @param mixed $nsid (default: null)
	 * @return void
	 */
	private function set_nameservers ($nsid = null) {
		// null or invalid
		if(is_null($nsid) || !is_numeric($nsid) || $nsid==0) {
			// set type
			$this->dns_type = "local";
			return false;
		}
		// ok
		else {
			// set type to remote
			$this->dns_type = "remote";
			// fetch nameservers
			$nameservers = $this->fetch_object ("nameservers", "id", $nsid);
			// error
			if ($nameservers===false) {
				return false;
			}
			// ok
			else {
				if (is_blank($nameservers->namesrv1)) {
					return false;
				}
				else {
					// to array
					$nsarray = pf_explode(";", $nameservers->namesrv1);
					// check against dead NSes
					foreach ($nsarray as $k=>$nsserv) {
						$nsserv = trim($nsserv);
						if(in_array($nsserv, $this->dead_ns)) {
							unset($nsarray[$k]);
						}
					}
					// save active
					if(sizeof($nsarray)>0) {
						$this->ns = $nsarray;
					}
					else {
						$this->ns = array();
						return false;
					}
				}
			}
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
		if (!is_blank($hostname) && !is_blank($address)) {
											{ return array("class"=>"", "address"=>$address, "name"=>$hostname); }
		}
		// if settings permits to check or override is set
		elseif($this->settings->enableDNSresolving == 1 || $override===true) {
			// ignore if remote DNS failed
			if ($this->dns_type=="remote" && sizeof($this->ns)==0) {
				return array("class"=>"", 		"address"=>$address, "name"=>$hostname);
			}
			// if address is set fetch A record
			elseif ($address!==false && !is_blank($address)) {
				// set resolve type
				$this->type = "PTR";

				// resolve
				$resolved = $this->resolve_address_net_dns ($address);
				// false ?
				if ($resolved===false)		{ return array("class"=>"", 		"address"=>$address, "name"=>$hostname); }
				else						{ return array("class"=>"resolved", "address"=>$address, "name"=>$resolved); }
			}
			// if hostname is set fetch PTR record
			elseif($hostname!==false && !is_blank($hostname)) {
				// set resolve type
				$this->type = "A";

				// resolve
				$resolved = $this->resolve_address_net_dns ($hostname);
				// false ?
				if ($resolved===false)		{ return array("class"=>"",			"address"=>$address, "name"=>$hostname); }
				else						{ return array("class"=>"resolved", "address"=>$resolved, "name"=>$hostname); }
			}
		}
		// don't check
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
		if (sizeof($this->ns)>0) {
			// check each , if dead remove it !
			foreach ($this->ns as $ns) {
				if (!in_array($ns, $this->dead_ns)) {
					$this->DNS2->setServers (array($ns));

					// try to get record
					try {
					    $result = $this->DNS2->query($address, $this->type);
					} catch(Net_DNS2_Exception $e) {
						// log error
						$this->resolve_error = $e->getMessage();
						// if server inaccessible remove it from array of ns
						if(strpos($this->resolve_error, "timeout")!==false) {
							$this->dead_ns[] = $ns;
							if($this->print_error) {
								$this->Result->show("warning", _("DNS error")." ($ns): ".$this->resolve_error);
							}
							$this->dead_ns = array_unique($this->dead_ns);
						}
					}
				}
			}
		}
		// no NS, default query from /etc/hosts
		elseif($this->local_failed===false) {
			// try to get record
			try {
			    $result = $this->DNS2->query($address, $this->type);
			} catch(Net_DNS2_Exception $e) {
				// log error
				$this->resolve_error = $e->getMessage();
				// if server inaccessible remove it from array of ns
				if(strpos($this->resolve_error, "timeout")!==false) {
					//·set·flag
					$this->local_failed = true;
					// check which DNS caused problems
					$dns_exception_list = $this->DNS2->last_exception_list;
					// loop
					foreach ($dns_exception_list as $ns=>$val) {
						if($this->print_error) {
							$this->Result->show("warning", _("DNS error")." ($ns): ".$this->resolve_error);
						}
					}
				}
				return false;
			}
		}

		// return response
		if (isset($result->answer)) {
			// set what to search
			$search = $this->type=="PTR" ? "ptrdname" : "address";

			foreach($result->answer as $mxrr) {
				if ($mxrr->{$search}) {
					if ($this->multiple) {
						$res_m[] = $mxrr->{$search};
					}
					else {
						return $mxrr->{$search};
					}
				}
			}
			// multiple return
			if($this->multiple) {
				return $res_m;
			}
		}
		else {
			return false;
		}
	}
}
