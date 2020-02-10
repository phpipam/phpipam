<?php

/**
 *	phpIPAM SNMP class to manage SNMP-related dunctions
 *
 *      http://php.net/manual/en/class.snmp.php
 *
 */

class phpipamSNMP extends Common_functions {

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
     * @var SNMP|bool
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
	 * Default snmp timeout in ms
	 *
	 * (default value: '1000')
	 *
	 * @var string
	 * @access private
	 */
	private $snmp_timeout = '1000';

	/**
	 * Default snmp retries
	 *
	 * (default value: '3')
	 *
	 * @var string
	 * @access private
	 */
	private $snmp_retries = '3';

    /**
    * Object containing SNMPv3 Security session parameters
    *
    * (default value: false)
    *
    * @var mixed
    * @access private
    */
    private $snmpv3_security = false;

	/**
	 * array of objects of SNMP methods
	 *
	 * (default value: false)
	 *
	 * @var mixed
	 * @access public
	 */
	public $snmp_queries = false;

	/**
	 * array of text to numerical oid mappings.
	 *
	 * (default value: false)
	 *
	 * @var mixed
	 * @access public
	 */
	public $snmp_oids = false;

	/**
	 * Device sysObjectID.
	 *
	 * (default value: "")
	 *
	 * @var string
	 * @access public
	 */
	public $snmp_sysObjectID = "";

	/**
	 * VLAN number for MAC address fetching
	 *
	 * (default value: 1)
	 *
	 * @var int
	 * @access public
	 */
	public $vlan_number = 1;




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
    	$this->snmp_queries['get_system_info']->oid = "SNMPv2-MIB::sysDescr";
    	$this->snmp_queries['get_system_info']->description = "Displays device system info";

    	// arp table
    	$this->snmp_queries['get_arp_table'] = new StdClass();
    	$this->snmp_queries['get_arp_table']->id  = 2;
    	$this->snmp_queries['get_arp_table']->oid = "IP-MIB::ipNetToMediaEntry";
    	$this->snmp_queries['get_arp_table']->description = "Fetches ARP table";

    	// mac address table
    	$this->snmp_queries['get_mac_table'] = new StdClass();
    	$this->snmp_queries['get_mac_table']->id  = 3;
    	$this->snmp_queries['get_mac_table']->oid = "BRIDGE-MIB::dot1dTpFdbEntry";
    	$this->snmp_queries['get_mac_table']->description = "Fetches MAC address table";

    	// interface ip addresses
    	$this->snmp_queries['get_interfaces_ip'] = new StdClass();
    	$this->snmp_queries['get_interfaces_ip']->id  = 4;
    	$this->snmp_queries['get_interfaces_ip']->oid = "IP-MIB::ipAddrEntry";
    	$this->snmp_queries['get_interfaces_ip']->description = "Fetches interface ip addresses";

    	// get_routing_table
    	$this->snmp_queries['get_routing_table'] = new StdClass();
    	$this->snmp_queries['get_routing_table']->id  = 5;
    	$this->snmp_queries['get_routing_table']->oid = "IP-FORWARD-MIB::ipCidrRouteEntry";
    	$this->snmp_queries['get_routing_table']->description = "Fetches routing table";

    	// get vlans
    	$this->snmp_queries['get_vlan_table'] = new StdClass();
    	$this->snmp_queries['get_vlan_table']->id  = 6;
    	$this->snmp_queries['get_vlan_table']->oid = "CISCO-VTP-MIB::vtpVlanName";
    	$this->snmp_queries['get_vlan_table']->description = "Fetches VLAN table";

    	// get vrfs
    	$this->snmp_queries['get_vrf_table'] = new StdClass();
    	$this->snmp_queries['get_vrf_table']->id  = 7;
    	$this->snmp_queries['get_vrf_table']->oid = "MPLS-VPN-MIB::mplsVpnVrfDescription";
    	$this->snmp_queries['get_vrf_table']->description = "Fetches VRF table";

    	// Text to numerical OID conversion table
    	$this->snmp_oids = [
    		'SNMPv2-MIB::sysDescr'                => '.1.3.6.1.2.1.1.1',
    		'SNMPv2-MIB::sysObjectID'             => '.1.3.6.1.2.1.1.2',

    		'IP-MIB::ipNetToMediaEntry'           => '.1.3.6.1.2.1.4.22.1',
    		'IP-MIB::ipNetToMediaIfIndex'         => '.1.3.6.1.2.1.4.22.1.1',
    		'IP-MIB::ipNetToMediaPhysAddress'     => '.1.3.6.1.2.1.4.22.1.2',
    		'IP-MIB::ipNetToMediaNetAddress'      => '.1.3.6.1.2.1.4.22.1.3',
    		'IP-MIB::ipAddrEntry'                 => '.1.3.6.1.2.1.4.20.1',
    		'IP-MIB::ipAdEntAddr'                 => '.1.3.6.1.2.1.4.20.1.1',
    		'IP-MIB::ipAdEntNetMask'              => '.1.3.6.1.2.1.4.20.1.3',

    		'IF-MIB::ifDescr'                     => '.1.3.6.1.2.1.2.2.1.2',
    		'IF-MIB::ifName'                      => '.1.3.6.1.2.1.31.1.1.1.1',
    		'IF-MIB::ifAlias'                     => '.1.3.6.1.2.1.31.1.1.1.18',

    		'BRIDGE-MIB::dot1dBasePortIfIndex'    => '.1.3.6.1.2.1.17.1.4.1.2',
    		'BRIDGE-MIB::dot1dTpFdbEntry'         => '.1.3.6.1.2.1.17.4.3.1',
    		'BRIDGE-MIB::dot1dTpFdbAddress'       => '.1.3.6.1.2.1.17.4.3.1.1',
    		'BRIDGE-MIB::dot1dTpFdbPort'          => '.1.3.6.1.2.1.17.4.3.1.2',

    		'IP-FORWARD-MIB::ipCidrRouteEntry'    => '.1.3.6.1.2.1.4.24.4.1',
    		'IP-FORWARD-MIB::ipCidrRouteDest'     => '.1.3.6.1.2.1.4.24.4.1.1',
    		'IP-FORWARD-MIB::ipCidrRouteMask'     => '.1.3.6.1.2.1.4.24.4.1.2',

    		'CISCO-VTP-MIB::vtpVlanName'          => '.1.3.6.1.4.1.9.9.46.1.3.1.1.4',

    		'MPLS-VPN-MIB::mplsVpnVrfDescription'        => '.1.3.6.1.3.118.1.2.2.1.2',
    		'MPLS-VPN-MIB::mplsVpnVrfRouteDistinguisher' => '.1.3.6.1.3.118.1.2.2.1.3'
    	];
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
	 * snmp_get
	 *
	 * @access private
	 * @param string $oid
	 * @param string $index (default: "")
	 * @return mixed
	 */
	private function snmp_get ($oid, $index = "") {
		return $this->snmp_poll('get', $oid, $index);
	}

	/**
	 * snmp_walk
	 *
	 * @access private
	 * @param string $oid
	 * @param string $index (default: "")
	 * @return mixed
	 */
	private function snmp_walk ($oid, $index = "") {
		return $this->snmp_poll('walk', $oid, $index);
	}

	/**
	 * snmp_poll
	 *
	 * @access private
	 * @param string $type
	 * @param string $oid
	 * @param string $index (default: "")
	 * @return mixed
	 */
	private function snmp_poll ($type, $oid, $index) {
		// Convert to numerical OIDs.
		$oid_num   = isset($this->snmp_oids[$oid]) ? $this->snmp_oids[$oid] : $oid;
		$query     = strlen($index) == 0 ? $oid     : $oid.'.'.$index;
		$query_num = strlen($index) == 0 ? $oid_num : $oid_num.'.'.$index;

		// try
		try {
			$res = $this->snmp_session->{$type} ($query_num);
		}
		catch (Exception $e) {
			throw new Exception ("<strong>$this->snmp_hostname</strong>: ".$e->getMessage(). "<br> oid: ".$query);
		}

		// check for errors
		if ($this->snmp_session->getErrno ()!=0)  {
			throw new Exception ("<strong>$this->snmp_hostname</strong>: ".$this->snmp_session->getError (). "<br> oid: ".$query);
		}

		return $res;
	}


	/**
	* @save current device details methods
	* ------------------------------------
	*/

	/**
	 * Sets snmp device details
	 *
	 * @access public
	 * @param array|object|bool $device (default: false)
	 * @param int $vlan_number (default: false)
	 * @return void
	 */
	public function set_snmp_device ($device = false, $vlan_number = false) {
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
    	$this->set_snmp_community ($device->snmp_community, $vlan_number);
    	# set version
    	$this->set_snmp_version ($device->snmp_version);
    	# set port
    	$this->set_snmp_port ($device->snmp_port);
    	# set timeout
    	$this->set_snmp_timeout ($device->snmp_timeout);
        # set SNMPv3 security
        $this->set_snmpv3_security ($device);
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
	 * @param mixed $vlan_number
	 * @return void
	 */
	private function set_snmp_community ($community, $vlan_number) {
    	if (strlen($community)>0) {
        	// vlan ?
        	if ($vlan_number!==false && is_numeric($vlan_number)) {
                $this->snmp_community = $community."@".$vlan_number;
                $this->vlan_number = $vlan_number;
        	}
        	else {
                $this->snmp_community = $community;
        	}
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
    	if ($version==1 || $version==2 || $version==3) {
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
		if (is_numeric($timeout) && $timeout > 0) {
			$this->snmp_timeout = $timeout < 10000 ? $timeout : 10000;
		} else {
			$this->snmp_timeout = 1000;
		}
	}

    /**
     * Sets SNMPv3 Security parameters
     *
     * @access private
     * @param mixed $timeout
     * @return void
     */
    private function set_snmpv3_security ($device) {
        # only for v3
        if($device->snmp_version == "3") {
            $this->snmpv3_security                  = new StdClass();
            $this->snmpv3_security->sec_level       = $device->snmp_v3_sec_level;
            $this->snmpv3_security->auth_proto      = $device->snmp_v3_auth_protocol;
            $this->snmpv3_security->auth_pass       = $device->snmp_v3_auth_pass;
            $this->snmpv3_security->priv_proto      = $device->snmp_v3_priv_protocol;
            $this->snmpv3_security->priv_pass       = $device->snmp_v3_priv_pass;
            $this->snmpv3_security->contextName     = $device->snmp_v3_ctx_name;
            $this->snmpv3_security->contextEngineID = $device->snmp_v3_ctx_engine_id;
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
            if ($this->snmp_version=="1")       { $this->snmp_session = new SNMP(SNMP::VERSION_1,  $this->snmp_host, $this->snmp_community, $this->snmp_timeout * 1000, $this->snmp_retries); }
            elseif ($this->snmp_version=="2")   { $this->snmp_session = new SNMP(SNMP::VERSION_2c, $this->snmp_host, $this->snmp_community, $this->snmp_timeout * 1000, $this->snmp_retries); }
            elseif ($this->snmp_version=="3")   { $this->snmp_session = new SNMP(SNMP::VERSION_3,  $this->snmp_host, $this->snmp_community, $this->snmp_timeout * 1000, $this->snmp_retries);
                                                  $this->snmp_session->setSecurity(
                                                                                   $this->snmpv3_security->sec_level,
                                                                                   $this->snmpv3_security->auth_proto,
                                                                                   $this->snmpv3_security->auth_pass,
                                                                                   $this->snmpv3_security->priv_proto,
                                                                                   $this->snmpv3_security->priv_pass,
                                                                                   $this->snmpv3_security->contextName,
                                                                                   $this->snmpv3_security->contextEngineID
                                                                                   );}
            else                                { throw new Exception ("Invalid SNMP version"); }
        }
        // set parameters
        $this->snmp_session->oid_output_format = SNMP_OID_OUTPUT_NUMERIC;

		// Fetch device sysObjectID.  TODO: Customise queries based on vendor sysObjectID (HP, FortiGate, ...)
		// $this->snmp_sysObjectID = $this->snmp_get( 'SNMPv2-MIB::sysObjectID', '0' );
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
        if (method_exists($this, $query))   { return $this->{$query} (); }
        else                                { throw new Exception ("Invalid query"); }
    }

    /**
     * Fetches system info
     *
     * @access private
     * @return void
     */
    private function get_system_info () {
        // init
        $this->connection_open ();

        // try
        $sysdescr = $this->snmp_get ( "SNMPv2-MIB::sysDescr", "0" );

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
        $res1 = $this->snmp_walk ( "IP-MIB::ipNetToMediaNetAddress" );      // ip
        $res2 = $this->snmp_walk ( "IP-MIB::ipNetToMediaPhysAddress" );     // mac
        $res3 = $this->snmp_walk ( "IP-MIB::ipNetToMediaIfIndex" );         // interface index

        // parse IP
        $n=0;
        foreach ($res1 as $r) {
            $res[$n]['ip']  = $this->parse_snmp_result_value ($r);
            $n++;
        }
        // parse MAC
        $n=0;
        foreach ($res2 as $r) {
            $res[$n]['mac'] = $this->format_snmp_mac_value ($r);
            // validate mac
            if ($this->validate_mac($res[$n]['mac'])===false) { $res[$n]['mac'] = ""; }
            $n++;
        };

        $interface_indexes = array();       // to avoid fetching if multiple times
        // fetch interface name
        $n=0;
        foreach ($res3 as $r) {
            $index = $this->parse_snmp_result_value ($r);
            // if already fetched
            if (array_key_exists($index, $interface_indexes)) {
                $res[$n]['port'] = $interface_indexes[$index];
            }
            else {
                try {
                    $res1 = $this->snmp_get ( "IF-MIB::ifName", $index );  // if description
                    $res2 = $this->snmp_get ( "IF-MIB::ifDescr", $index );     // if port

                    //parse and save
                    $res[$n]['port'] = $this->parse_snmp_result_value ($res1);
                    $res[$n]['portname'] = $this->parse_snmp_result_value ($res2);
                    $interface_indexes[$index] = $res[$n]['port'];
                }
                catch (Exception $e) {
                    $res[$n]['port'] = "";
                    $res[$n]['portname'] = "";
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
     * Fetch MAC address table from device for specified VLAN.
     *
     *
     *  First we fetch MAC address and bridgeport
     *  Than we fetch interface index from bridgeport index
     *  Than we fetch interface description
     *
     *
     * @access private
     * @return void
     */
    private function get_mac_table () {
        // init
        $this->connection_open ();

        // fetch
        $res1 = $this->snmp_walk ( "BRIDGE-MIB::dot1dTpFdbAddress" );    // mac
        $res2 = $this->snmp_walk ( "BRIDGE-MIB::dot1dTpFdbPort" );       // bridge port index

        // parse MAC
        $n=0;
        foreach ($res1 as $r) {
            $res[$n]['mac'] = $this->format_snmp_mac_value ($r);
            // validate mac
            if ($this->validate_mac($res[$n]['mac'])===false) { $res[$n]['mac'] = ""; }
            $n++;
        };

        // parse bridgeport index and fetch if description
        $n=0;
        foreach ($res2 as $r) {
            $res[$n]['bridgeportindex'] = $this->parse_snmp_result_value ($r);
            // fetch interface
            try {
                $res3 = $this->snmp_get ( "BRIDGE-MIB::dot1dBasePortIfIndex", $res[$n]['bridgeportindex'] );         // bridge port to interface index
                $res4 = $this->snmp_get ( "IF-MIB::ifDescr", $this->parse_snmp_result_value ($res3) );  // interface description
                $res5 = $this->snmp_get ( "IF-MIB::ifAlias", $this->parse_snmp_result_value ($res3) );

                //parse and save
                $res[$n]['vlan_number'] = $this->vlan_number;
                //$res[$n]['portindex'] = $this->parse_snmp_result_value ($res3);
                $res[$n]['port'] = $this->parse_snmp_result_value ($res4);
                $res[$n]['port_alias'] = $this->parse_snmp_result_value ($res5);
            }
            catch (Exception $e) {
                $res[$n]['port'] = "";
                $res[$n]['error'] = $e->getMessage();
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
        $res1 = $this->snmp_walk ( "IP-MIB::ipAdEntAddr" );
        $res2 = $this->snmp_walk ( "IP-MIB::ipAdEntNetMask" );

        // parse result
        $n=0;
        foreach ($res1 as $r) {
            $res[$n]['ip']  = $this->parse_snmp_result_value ($r);
            $n++;
        }
        $n=0;
        foreach ($res2 as $r) {
            $res[$n]['mac'] = $this->format_snmp_mac_value ($r);
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
        $res1 = $this->snmp_walk ( "IP-FORWARD-MIB::ipCidrRouteDest" );
        $res2 = $this->snmp_walk ( "IP-FORWARD-MIB::ipCidrRouteMask" );

        // parse result
        $n=0;
        foreach ($res1 as $r) {
            $res[$n]['subnet']  = $this->parse_snmp_result_value ($r);
            $n++;
        }
        $n=0;
        foreach ($res2 as $r) {
            $res[$n]['mask']  = $this->parse_snmp_result_value ($r);
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
        $res1 = $this->snmp_walk ( "CISCO-VTP-MIB::vtpVlanName", "1" );

        // parse result
        foreach ($res1 as $k=>$r) {
            // set number
            $k = str_replace($this->snmp_oids['CISCO-VTP-MIB::vtpVlanName'].'.1.', "", $k);
            $k = array_pop(explode(".", $k));
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
     * Decode mplsVpnVrfName oid to ASCII
     * @param  string $oid
     * @return string
     */
    private function decode_mplsVpnVrfName($oid) {
        // mplsVpnVrfName. When this object is used as an index to a table,
        // the first octet is the string length, and subsequent octets are
        // the ASCII codes of each character.
        // For example, “vpn1” is represented as 4.118.112.110.49.
        $a = array_values(array_filter(explode('.', $oid)));
        if (($a[0]+1) != sizeof($a))
            return $oid;

        $mplsVpnVrfName = "";

        foreach($a as $i=>$v) {
            if ($i == 0) continue;
            $mplsVpnVrfName .= chr($v);
        }
        return $mplsVpnVrfName;
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

        // fetch
        $res = [];
        $res1 = $this->snmp_walk ( "MPLS-VPN-MIB::mplsVpnVrfRouteDistinguisher" );
        $res2 = $this->snmp_walk ( "MPLS-VPN-MIB::mplsVpnVrfDescription" );

        // parse results
        foreach ($res1 as $k=>$r) {
            // set name
            $k = str_replace($this->snmp_oids['MPLS-VPN-MIB::mplsVpnVrfRouteDistinguisher'].'.', "", $k);
            $k = str_replace("\"", "", $k);
            $k = $this->decode_mplsVpnVrfName($k);
            // set rd
            $r  = $this->parse_snmp_result_value ($r);
            $res[$k]['rd'] = $r;
        }
        foreach ($res2 as $k=>$r) {
            // set name
            $k = str_replace($this->snmp_oids['MPLS-VPN-MIB::mplsVpnVrfDescription'].'.', "", $k);
            $k = str_replace("\"", "", $k);
            $k = $this->decode_mplsVpnVrfName($k);
            // set descr
            $r  = $this->parse_snmp_result_value ($r);
            $res[$k]['descr'] = $r;
        }

        // save result
        $this->save_last_result ($res);

        // return response
        return isset($res) ? $res : false;
    }

    /**
     * Standardise SNMP MACs  -> 0:1:fe   >> 00:01:fe
     *                        -> 0-1-fe   >> 00:01:fe
     *                        -> 00 01 fe >> 00:01:fe
     * @access private
     * @param string $mac
     * @return string
     */
    private function format_snmp_mac_value ($mac) {
        if (!is_string($mac))
            return '';

        $mac = trim($mac);

        $separators = [':', '-', ' '];

        $mac_parts = [];
        foreach ($separators as $separator) {
            if (strpos($mac, $separator)===false)
                continue;
            $mac_parts = explode($separator, $mac);
            break;
        }

        if (sizeof($mac_parts)!=6)
            return $mac;

        foreach ($mac_parts as $i=>$v) {
            $mac_parts[$i] = str_pad($v, 2, "0", STR_PAD_LEFT);
        }

        return implode(":", $mac_parts);
    }

    /**
     * Parses result - removes STRING:
     *
     * @access private
     * @param mixed $r
     * @return void
     */
    private function parse_snmp_result_value ($r) {
        $r = stripslashes($r);
        $r = trim(substr($r, strpos($r, ":")+2));
        // if the first char is a " then remove it
        if(substr_compare($r, '"', 0, 1)===0)  $r=substr($r, 1);
        // if the last char is a " then remove it
        if(substr_compare($r, '"', -1, 1)===0) $r=substr($r, 0,-1);
        return escape_input($r);
    }

}