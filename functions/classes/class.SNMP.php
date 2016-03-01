<?php

/**
 *	phpIPAM SNMP class to manage SNMP-related dunctions
 *
 *      http://php.net/manual/en/class.snmp.php
 *
 */

class phpipamSNMP extends Common_functions {

	/**
	 * properties
	 */
	public $settings = false;				// settings
	public $last_result = false;            // save last snmp result

    private $snmp_session = false;          // session
    private $snmp_host = false;             // host (ip)
    private $snmp_hostname = false;         // hostname
	private $snmp_version = 1;              // snmp version (1,2)
	private $snmp_community = 'public';     // community
	private $snmp_port = '161';             // snmp UDP port
	private $snmp_timeout = '500';          // timeout in ms

    public $snmp_groups = array("info", "arp", "route");
	public  $snmp_queries = false;          // array ob objects of SNMP methods
	public  $snmp_query_groups = array();  // array of methods


	/**
	 * properties - objects
	 */
	protected $Result;						//for Result printing
	protected $Database;					//for Database connection



	/**
	 * __construct function.
	 *
	 * @access public
	 * @param Database_PDO $Database
	 * @param bool $device (default: false)
	 * @return void
	 */
	public function __construct (Database_PDO $Database, $device = false) {
		# initialize Result
		$this->Result = new Result ();
		# initialize object
		$this->Database = $Database;
		# if device provided execute
		$this->set_snmp_device ($device);
		# set snmp methods from database
		$this->set_snmp_queries ();
	}





	/**
	 * Sets snmp device details
	 *
	 * @access public
	 * @param int $device (default: false)
	 * @return void
	 */
	public function set_snmp_device ($device = false) {
    	# if false exit
    	if ($device === false)          { return false; }

    	# cast as object
    	$device = (object) $device;

        # host
        $this->set_snmp_host ($device->ip_addr);
        # hostname = za debugging
        $this->set_snmp_hostname ($device->hostname);
    	# set community
    	$this->set_snmp_community ($device->snmp_community);
    	# set version
    	$this->set_snmp_version ($device->snmp_version);
    	# set port
    	$this->set_snmp_port ($device->snmp_port);
    	# set timeout
    	$this->set_snmp_timeout ($device->snmp_timeout);
	}

	/**
	 * Sets snmp host to query
	 *
	 * @access private
	 * @param mixed $ip
	 * @return void
	 */
	private function set_snmp_host ($ip) {
    	if ($this->validate_ip ($ip)) {
        	$this->snmp_host = $ip;
    	}
    	else {
        	$this->Result->show("danger", "Invalid device IP address", true, true, false, true);
    	}
	}

	/**
	 * Sets snmp hostname for debugging
	 *
	 * @access private
	 * @param mixed $ip
	 * @return void
	 */
	private function set_snmp_hostname ($hostname) {
    	if (strlen($hostname)>0) {
        	$this->snmp_hostname = $hostname;
    	}
	}

	/**
	 * Sets SNMP community
	 *
	 * @access private
	 * @param mixed $community
	 * @return void
	 */
	private function set_snmp_community ($community) {
    	if (strlen($community)>0) {
        	$this->snmp_community = $community;
        }
	}

	/**
	 * Sets SNMP version
	 *
	 * @access private
	 * @param int $version (default: 1)
	 * @return void
	 */
	private function set_snmp_version ($version = 1) {
    	if ($version==1 || $version==2) {
    	    $this->snmp_version = $version;
    	}
	}

	/**
	 * Sets snmp port
	 *
	 * @access private
	 * @param mixed $port
	 * @return void
	 */
	private function set_snmp_port ($port) {
    	if (is_numeric($port)) {
        	$this->snmp_port = $port;
        }
	}

	/**
	 * Sets snmp timeout
	 *
	 * @access private
	 * @param mixed $timeout
	 * @return void
	 */
	private function set_snmp_timeout ($timeout) {
    	if (is_numeric($timeout)) {
        	$this->snmp_timeout = $timeout;
        }
	}

	/**
	 * Fetches all available SNMP methods from database
	 *
	 * @access private
	 * @return void
	 */
	private function set_snmp_queries () {
		# fetch
		try { $res = $this->Database->getObjects("snmp", "id"); }
		catch (Exception $e) {
    		throw new Exception ($e->getMessage());
			return false;
		}
		# result
		if(sizeof($res)>0) {
    		// reindex and save
    		foreach ($res as $r) {
        		$out[$r->name] = $r;
    		}
    		$this->snmp_queries = $out;

            // set groups also
            $this->set_snmp_query_groups ();
		}
	}

	/**
	 * Reindexes snmp methods to group array
	 *
	 *  [arp] = array ($m1, $m2, ...);
	 *
	 * @access private
	 * @return void
	 */
	private function set_snmp_query_groups () {
    	if ($this->snmp_queries!==false) {
        	foreach ($this->snmp_queries as $m) {
            	$this->snmp_query_groups[$m->method][] = $m;
        	}
    	}
	}







	/**
	 *	@device preparation
	 *	--------------------------------
	 */

    /**
     * Sets all devices that have enabled specific method.
     *
     * @access public
     * @param mixed $method
     * @return void
     */
    public function set_method_devices ($method) {
        // invalid method
        if (!in_array($method, $this->snmp_groups)) {
            throw new Exception ("Invalid SNMP group");
        }
        else {
            # fetch devices that have apropriate snmp queries
            $devices = $this->Database->getObjects("devices", "id");
            if ($devices!==false) {
                foreach ($devices as $d) {
                    //parse methods available for device
                    $methods = explode(";", $d->snmp_queries);

                    // go through queries
                    foreach ($this->snmp_query_groups[$method] as $mg) {
                        if (in_array($mg->id, $methods)) {
                            $devices_used[] = $d;
                            break;
                        }
                    }
                }
            }
            # result
            return isset($devices_used) ? $devices_used : false;
        }
    }





	/**
	 *	@fetch SNMP methods
	 *	--------------------------------
	 */

    /**
     * Sets new SNMP session
     *
     * @access private
     * @return void
     */
    private function init_snmp_connection () {
        // init connection
        if ($this->snmp_version=="1")       { $this->snmp_session = new SNMP(SNMP::VERSION_1, $this->snmp_host, $this->snmp_community, $this->snmp_timeout); }
        elseif ($this->snmp_version=="2")   { $this->snmp_session = new SNMP(SNMP::VERSION_2c, $this->snmp_host, $this->snmp_community, $this->snmp_timeout); }
        elseif ($this->snmp_version=="3")   { $this->snmp_session = new SNMP(SNMP::VERSION_3, $this->snmp_host, $this->snmp_community, $this->snmp_timeout); }
        else                                { throw new Exception ("Invalid SNMP version"); }
        // set parameters
        $this->snmp_session->oid_output_format = SNMP_OID_OUTPUT_NUMERIC;
    }

    /**
     * Checks for snmp error and throws exception
     *
     * @access private
     * @return void
     */
    private function check_snmp_error () {
        if ($this->snmp_session->getErrno ()!="0")    {  throw new Exception ("<strong>$this->snmp_hostname</strong>: ".$this->snmp_session->getError ()); }
    }

    /**
     * Saves last snmp result
     *
     * @access private
     * @param mixed $result
     * @return void
     */
    private function save_last_result ($result) {
        $this->last_result = $result;
    }

    /**
     * Fetch es system info
     *
     * @access public
     * @param obj $device
     * @return void
     */
    public function get_sysinfo ($device = false) {
        // init
        $this->init_snmp_connection ();
        // try
        try {
            $sysdescr = $this->snmp_session->get( $this->snmp_queries['SNMPv2-MIB::sysDescr.1']->oid );
        }
		catch (Exception $e) {
    		throw new Exception ($e->getMessage());
			return false;
		}
        // check for errors
        $this->check_snmp_error ();
        // close
        $this->snmp_session->close();
        // save result
        $this->save_last_result ($sysdescr);
        // return
        return $this->last_result;
    }

    /**
     * Fetch ARP table from deivce.
     *
     * @access public
     * @param obj $device
     * @param bool $oid_id (default: false)
     * @return void
     */
    public function get_arp_table ($device , $oid_id=false) {
        // init
        $this->init_snmp_connection ();
        // set methods
        $methods = explode(";", $device->snmp_queries);
        // if oid is set remove all other
        if ($oid_id!==false) { $methods = array($oid_id); }

        // fetch
        foreach ($this->snmp_query_groups["arp"] as $m) {
            if (in_array($m->id, $methods)) {
                try {
                    // set indexes
                    $this->set_routing_table_indexes ($this->snmp_queries[$m->name]->oid);
                    // fetch
                    $res1 = $this->snmp_session->walk( $this->snmp_queries[$m->name]->oid.$this->ifindex );
                    $res2 = $this->snmp_session->walk( $this->snmp_queries[$m->name]->oid.$this->maskindex );
        		} catch (Exception $e) {
            		throw new Exception ($e->getMessage());
        		}
                // check for errors
                $this->check_snmp_error ();
                // parse result
                $n=0;
                foreach ($res1 as $r) {
                    $res[$n]['ip']  = trim(substr($r, strpos($r, ":")+2));
                    $n++;
                }
                $n=0;
                foreach ($res2 as $r) {
                    //make sure MAC has all 0
                    $mac = explode(":", trim(substr($r, strpos($r, ":")+2)));
                    foreach ($mac as $km=>$mc) {
                        if (strlen($mc)==1) {
                            $mac[$km] = str_pad($mc, 2, "0", STR_PAD_LEFT);
                        }
                    }
                    $res[$n]['mac'] = implode(":", $mac);
                    // validate mac
                    if ($this->validate_mac($res[$n]['mac'])===false) { $res[$n]['mac'] = ""; }
                    $n++;
                }
                // save for return
                $return = $res;
            }
        }
        // close
        $this->snmp_session->close();
        // return response
        return isset($return) ? $return : false;
    }

    /**
     * Fetch routung table from device.
     *
     * @access public
     * @param mixed $device
     * @param bool $oid_id (default: false)
     * @return void
     */
    public function get_routing_table ($device, $oid_id=false) {
        // init
        $this->init_snmp_connection ();
        // set methods
        $methods = explode(";", $device->snmp_queries);
        // if oid is set remove all other
        if ($oid_id!==false) { $methods = array($oid_id); }

        // fetch
        foreach ($this->snmp_query_groups["route"] as $m) {
            if (in_array($m->id, $methods)) {
                try {
                    // set indexes
                    $this->set_routing_table_indexes ($this->snmp_queries[$m->name]->oid);
                    // fetch
                    $res1 = $this->snmp_session->walk( $this->snmp_queries[$m->name]->oid.$this->ifindex );
                    $res2 = $this->snmp_session->walk( $this->snmp_queries[$m->name]->oid.$this->maskindex );
        		} catch (Exception $e) {
            		throw new Exception ("<strong>$device->hostname</strong>: ".$e->getMessage(). "<br> oid: ".$this->snmp_queries[$m->name]->oid.".1");
        		}
                // check for errors
                $this->check_snmp_error ();
                // parse result
                $n=0;
                foreach ($res1 as $r) {
                    $res[$n]['subnet']  = trim(substr($r, strpos($r, ":")+2));
                    $n++;
                }
                $n=0;
                foreach ($res2 as $r) {
                    $res[$n]['mask']  = trim(substr($r, strpos($r, ":")+2));
                    $n++;
                }
                // save for return
                $return = $res;
            }
        }
        // close
        $this->snmp_session->close();
        // return response
        return isset($return) ? $return : false;
    }

    /**
     * Sets indexes for different queries
     *
     * @access private
     * @param mixed $oid
     * @return void
     */
    private function set_routing_table_indexes ($oid) {
        // interfaces
        if($oid==".1.3.6.1.2.1.4.20.1") {
            $this->ifindex   = ".1";
            $this->maskindex = ".3";
        }
        // arp table
        elseif ($oid==".1.3.6.1.2.1.4.22.1") {
            $this->ifindex   = ".3";
            $this->maskindex = ".2";
        }
        // default
        else {
            $this->ifindex   = ".1";
            $this->maskindex = ".2";
        }
    }

}