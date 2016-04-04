<?php

/**
 *	phpIPAM SNMP class to manage SNMP-related dunctions
 *
 *      http://php.net/manual/en/class.snmp.php
 *
 */

class phpipamSNMP extends Common_functions {

	/**
	 * Settings
	 *
	 * (default value: false)
	 *
	 * @var bool
	 * @access public
	 */
	public $settings = false;

	/**
	 * Saves last result value
	 *
	 * (default value: false)
	 *
	 * @var bool
	 * @access public
	 */
	public $last_result = false;

    /**
     * snmp session
     *
     * (default value: false)
     *
     * @var bool
     * @access private
     */
    private $snmp_session = false;

    /**
     * snmp device host (ip address)
     *
     * (default value: false)
     *
     * @var bool
     * @access private
     */
    private $snmp_host = false;

    /**
     * Snmp device hostname
     *
     * (default value: false)
     *
     * @var bool
     * @access private
     */
    private $snmp_hostname = false;

	/**
	 * snmp version (1, 2, 3)
	 *
	 * (default value: 1)
	 *
	 * @var int
	 * @access private
	 */
	private $snmp_version = 1;

	/**
	 * Default snmp community
	 *
	 * (default value: 'public')
	 *
	 * @var string
	 * @access private
	 */
	private $snmp_community = 'public';

	/**
	 * Default snmp port
	 *
	 * (default value: '161')
	 *
	 * @var string
	 * @access private
	 */
	private $snmp_port = '161';

	/**
	 * Default snmp timeut in ms
	 *
	 * (default value: '500')
	 *
	 * @var string
	 * @access private
	 */
	private $snmp_timeout = '500';

	/**
	 * array ob objects of SNMP methods
	 *
	 * (default value: false)
	 *
	 * @var bool
	 * @access public
	 */
	public $snmp_queries = false;

	/**
	 * Result object - for result printing
	 *
	 * @var mixed
	 * @access public
	 */
	public $Result;



	/**
	 * __construct function.
	 *
	 * @access public
	 * @param Database_PDO $Database
	 * @param bool $device (default: false)
	 * @return void
	 */
	public function __construct () {
		# set snmp methods from database
		$this->set_snmp_queries ();
		# initialize Result
		$this->Result = new Result ();
	}

	/**
	 * Sets all supported SNMP queries
	 *
	 * @access public
	 * @return void
	 */
	public function set_snmp_queries () {
    	// system info
    	$this->snmp_queries['get_system_info'] = new StdClass();
    	$this->snmp_queries['get_system_info']->id  = 1;
    	$this->snmp_queries['get_system_info']->oid = ".1.3.6.1.2.1.1.1.0";
    	$this->snmp_queries['get_system_info']->description = "Displays device system info";

    	// arp table
    	$this->snmp_queries['get_arp_table'] = new StdClass();
    	$this->snmp_queries['get_arp_table']->id  = 2;
    	$this->snmp_queries['get_arp_table']->oid = ".1.3.6.1.2.1.4.22.1";
    	$this->snmp_queries['get_arp_table']->description = "Fetches ARP table";

    	// interface ip addresses
    	$this->snmp_queries['get_interfaces_ip'] = new StdClass();
    	$this->snmp_queries['get_interfaces_ip']->id  = 3;
    	$this->snmp_queries['get_interfaces_ip']->oid = ".1.3.6.1.2.1.4.20.1";
    	$this->snmp_queries['get_interfaces_ip']->description = "Fetches interface ip addresses";

    	// get_routing_table
    	$this->snmp_queries['get_routing_table'] = new StdClass();
    	$this->snmp_queries['get_routing_table']->id  = 4;
    	$this->snmp_queries['get_routing_table']->oid = ".1.3.6.1.2.1.4.24.4.1";
    	$this->snmp_queries['get_routing_table']->description = "Fetches routing table";

    	// get vlans
    	$this->snmp_queries['get_vlan_table'] = new StdClass();
    	$this->snmp_queries['get_vlan_table']->id  = 5;
    	$this->snmp_queries['get_vlan_table']->oid = ".1.3.6.1.4.1.9.9.46.1.3.1.1.4";
    	$this->snmp_queries['get_vlan_table']->description = "Fetches VLAN table";

    	// get vrfs
    	$this->snmp_queries['get_vrf_table'] = new StdClass();
    	$this->snmp_queries['get_vrf_table']->id  = 6;
//     	$this->snmp_queries['get_vrf_table']->oid = ".1.3.6.1.3.118.1.2.2.1";
    	$this->snmp_queries['get_vrf_table']->oid = "MPLS-VPN-MIB::mplsVpnVrfDescription";
    	$this->snmp_queries['get_vrf_table']->description = "Fetches VRF table";
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
	* @save current device details methods
	* ------------------------------------
	*/

	/**
	 * Sets snmp device details
	 *
	 * @access public
	 * @param int $device (default: false)
	 * @return void
	 */
	public function set_snmp_device ($device = false) {
    	# clear connection if it exists
    	$this->connection_close ();
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
	 *	@SNMP connection methods
	 *	--------------------------------
	 */

    /**
     * Sets new SNMP session
     *
     * @access private
     * @return void
     */
    private function connection_open () {
        // init connection
        if ($this->snmp_session === false) {
            if ($this->snmp_version=="1")       { $this->snmp_session = new SNMP(SNMP::VERSION_1,  $this->snmp_host, $this->snmp_community, $this->snmp_timeout); }
            elseif ($this->snmp_version=="2")   { $this->snmp_session = new SNMP(SNMP::VERSION_2c, $this->snmp_host, $this->snmp_community, $this->snmp_timeout); }
            elseif ($this->snmp_version=="3")   { $this->snmp_session = new SNMP(SNMP::VERSION_3,  $this->snmp_host, $this->snmp_community, $this->snmp_timeout); }
            else                                { throw new Exception ("Invalid SNMP version"); }
        }
        // set parameters
        $this->snmp_session->oid_output_format = SNMP_OID_OUTPUT_NUMERIC;
    }

    /**
     * Closes current snmp connection.
     *
     * @access public
     * @return void
     */
    public function connection_close () {
        // if object
        if (is_object($this->snmp_session))
        $this->snmp_session->close ();
        // to default
        $this->snmp_session = false;
    }

    /**
     * Checks for snmp error and throws exception
     *
     * @access private
     * @return void
     */
    private function connection_error_check () {
        if ($this->snmp_session->getErrno ()!="0")    {  throw new Exception ("<strong>$this->snmp_hostname</strong>: ".$this->snmp_session->getError ()); }
    }








	/**
	 *	@SNMP fetch methods
	 *	--------------------------------
	 */

    /**
     * Wrapper that executes snmp fetch / walk
     *
     * @access public
     * @param mixed $query
     * @return void
     */
    public function get_query ($query) {
        if (method_exists($this, $query))   { return $this->$query (); }
        else                                { throw new Exception ("Invalid query"); }
    }

    /**
     * Fetch es system info
     *
     * @access private
     * @return void
     */
    private function get_system_info () {
        // init
        $this->connection_open ();
        // try
        try {
            $sysdescr = $this->snmp_session->get( $this->snmp_queries['get_system_info']->oid );
        }
		catch (Exception $e) {
    		throw new Exception ($e->getMessage());
			return false;
		}
        // check for errors
        $this->connection_error_check ();
        // save result
        $this->save_last_result ($sysdescr);
        // return
        return $this->last_result;
    }

    /**
     * Fetch ARP table from device.
     *
     * @access private
     * @return void
     */
    private function get_arp_table () {
        // init
        $this->connection_open ();
        // fetch
        try {
            $res1 = $this->snmp_session->walk( $this->snmp_queries["get_arp_table"]->oid.".3" );    // ip
            $res2 = $this->snmp_session->walk( $this->snmp_queries["get_arp_table"]->oid.".2" );    // mac
            $res3 = $this->snmp_session->walk( $this->snmp_queries["get_arp_table"]->oid.".1" );    // interface index
		}
		catch (Exception $e) {
    		throw new Exception ($e->getMessage());
		}

        // check for errors
        $this->connection_error_check ();

        // parse IP
        $n=0;
        foreach ($res1 as $r) {
            $res[$n]['ip']  = trim(substr($r, strpos($r, ":")+2));
            $n++;
        }
        // parse MAC
        $n=0;
        foreach ($res2 as $r) {
            $res[$n]['mac'] = $this->fill_mac_nulls ($r);
            // validate mac
            if ($this->validate_mac($res[$n]['mac'])===false) { $res[$n]['mac'] = ""; }
            $n++;
        };

        $interface_indexes = array();       // to avoid fetching if multiple times
        // fetch interface name
        $n=0;
        foreach ($res3 as $r) {
            $index = trim(substr($r, strpos($r, ":")+2));
            // if already fetched
            if (array_key_exists($index, $interface_indexes)) {
                $res[$n]['port'] = $interface_indexes[$index];
            }
            else {
                try {
                    $res1 = $this->snmp_session->get( ".1.3.6.1.2.1.31.1.1.1.1.".$index );
                    //parse and save
                    $res[$n]['port'] = trim(substr($res1, strpos($res1, ":")+2));
                    $interface_indexes[$index] = $res[$n]['port'];
                }
                catch (Exception $e) {
                    $res[$n]['port'] = "";
                }
            }
            $n++;
        }

        // save result
        $this->save_last_result ($res);

        // return response
        return isset($res) ? $res : false;
    }


    /**
     * Fetch ARP table from device.
     *
     * @access private
     * @return void
     */
    private function get_interfaces_ip () {
        // init
        $this->connection_open ();
        // fetch
        try {
            $res1 = $this->snmp_session->walk( $this->snmp_queries["get_interfaces_ip"]->oid.".1" );
            $res2 = $this->snmp_session->walk( $this->snmp_queries["get_interfaces_ip"]->oid.".3" );
		}
		catch (Exception $e) {
    		throw new Exception ($e->getMessage());
		}

        // check for errors
        $this->connection_error_check ();

        // parse result
        $n=0;
        foreach ($res1 as $r) {
            $res[$n]['ip']  = trim(substr($r, strpos($r, ":")+2));
            $n++;
        }
        $n=0;
        foreach ($res2 as $r) {
            $res[$n]['mac'] = $this->fill_mac_nulls ($r);
            // validate mac
            if ($this->validate_mac($res[$n]['mac'])===false) { $res[$n]['mac'] = ""; }
            $n++;
        };

        // save result
        $this->save_last_result ($res);

        // return response
        return isset($res) ? $res : false;
    }

    /**
     * Fetch routung table from device.
     *
     * @access private
     * @return void
     */
    private function get_routing_table () {
        // init
        $this->connection_open ();
        // fetch
        try {
            $res1 = $this->snmp_session->walk( $this->snmp_queries["get_routing_table"]->oid.".1" );
            $res2 = $this->snmp_session->walk( $this->snmp_queries["get_routing_table"]->oid.".2" );
		}
		catch (Exception $e) {
    		throw new Exception ("<strong>$device->hostname</strong>: ".$e->getMessage(). "<br> oid: ".$this->snmp_queries["get_routing_table"]->oid);
		}

        // check for errors
        $this->connection_error_check ();

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

        // save result
        $this->save_last_result ($res);

        // return response
        return isset($res) ? $res : false;
    }

    /**
     * Fetch vlan table from device.
     *
     * @access private
     * @return void
     */
    private function get_vlan_table () {
        // init
        $this->connection_open ();
        // fetch
        try {
            $res1 = $this->snmp_session->walk( $this->snmp_queries["get_vlan_table"]->oid.".1" );
		}
		catch (Exception $e) {
    		throw new Exception ("<strong>$device->hostname</strong>: ".$e->getMessage(). "<br> oid: ".$this->snmp_queries["get_vlan_table"]->oid);
		}

        // check for errors
        $this->connection_error_check ();

        // parse result
        foreach ($res1 as $k=>$r) {
            // set number
            $k = str_replace($this->snmp_queries["get_vlan_table"]->oid.".1.", "", $k);
            // set value
            $r  = trim(str_replace("\"","",substr($r, strpos($r, ":")+2)));
            $res[$k] = $r;
        }

        // save result
        $this->save_last_result ($res);

        // return response
        return isset($res) ? $res : false;
    }

    /**
     * Fetch vrf table from device.
     *
     * @access private
     * @return void
     */
    private function get_vrf_table () {
        // init
        $this->connection_open ();
        // set parameters
        $this->snmp_session->oid_output_format = SNMP_OID_OUTPUT_MODULE;

        // fetch
        try {
            $res1 = $this->snmp_session->walk( $this->snmp_queries["get_vrf_table"]->oid );    // MPLS-VPN-MIB::mplsVpnVrfDescription."OAM" = STRING: 300:1
		}
		catch (Exception $e) {
    		throw new Exception ("<strong>$device->hostname</strong>: ".$e->getMessage(). "<br> oid: ".$this->snmp_queries["get_vrf_table"]->oid);
		}

        // check for errors
        $this->connection_error_check ();

        // parse result
        foreach ($res1 as $k=>$r) {
            // set name
            $k = str_replace($this->snmp_queries["get_vrf_table"]->oid.".", "", $k);
            $k = str_replace("\"", "", $k);
            // set rd
            $r  = trim(str_replace("\"","",substr($r, strpos($r, ":")+2)));
            $res[$k] = $r;
        }

        // save result
        $this->save_last_result ($res);

        // return response
        return isset($res) ? $res : false;
    }

	/**
	 * Fills mac with nulls -> 0:0:fe >> 00:00:fe
	 *
	 * @access private
	 * @param mixed $mac
	 * @return void
	 */
	private function fill_mac_nulls ($mac) {
        //make sure MAC has all 0
        $mac = explode(":", trim(substr($mac, strpos($mac, ":")+2)));
        foreach ($mac as $km=>$mc) {
            if (strlen($mc)==1) {
                $mac[$km] = str_pad($mc, 2, "0", STR_PAD_LEFT);
            }
        }
        // return
        return implode(":", $mac);
	}

}