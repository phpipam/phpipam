<?php


/**
 * DHCP_kea class to work with isc-dhcp server
 *
 *  It will be called form class.DHCP.php wrapper it kea is selected as DHCP type
 *
 *  http://kea.isc.org/wiki
 *
 *
 */
class DHCP_kea extends Common_functions {

    /**
     * Location of kea config file
     *
     * (default value: false)
     *
     * @var bool
     * @access private
     */
    private $kea_config_file = "/etc/kea/kea.conf";

    /**
     * Settings to be provided to process kea files
     *
     * (default value: array())
     *
     * @var array
     * @access private
     */
    private $kea_settings = array();

    /**
     * Raw config file
     *
     * (default value: "")
     *
     * @var string
     * @access public
     */
    public $config_raw = "";

    /**
     * Parsed config file
     *
     * (default value: false)
     *
     * @var array|bool
     * @access public
     */
    public $config = false;

    /**
     * Falg if ipv4 is used
     *
     * (default value: false)
     *
     * @var bool
     * @access public
     */
    public $ipv4_used = false;

    /**
     * Flag if ipv6 is used
     *
     * (default value: false)
     *
     * @var bool
     * @access public
     */
    public $ipv6_used = false;

    /**
     * Array to store DHCP subnets, parsed from config file
     *
     *  Format:
     *      $subnets[] = array (pools=>array());
     *
     * (default value: array())
     *
     * @var array
     * @access public
     */
    public $subnets4 = array();

    /**
     * Array to store DHCP subnets, parsed from config file
     *
     *  Format:
     *      $subnets[] = array (pools=>array());
     *
     * (default value: array())
     *
     * @var array
     * @access public
     */
    public $subnets6 = array();

    /**
     * set available lease database types
     *
     * (default value: array("memfile", "mysql", "postgresql"))
     *
     * @var array
     * @access public
     */
    public $lease_types = array("memfile", "mysql", "postgresql");

    /**
     * List of active leases
     *
     * (default value: array())
     *
     * @var array
     * @access public
     */
    public $leases4 = array();

    /**
     * List of active leases
     *
     * (default value: array())
     *
     * @var array
     * @access public
     */
    public $leases6 = array();

    /**
     * Available reservation methods
     *
     * (default value: array("mysql"))
     *
     * @var array
     * @access public
     */
    public $reservation_types = array("file", "mysql");

    /**
     * Definition of hosts reservations
     *
     * (default value: array())
     *
     * @var array
     * @access public
     */
    public $reservations4 = array();
    public $reservations6 = array();

    /**
     * Database object for leases and hosts
     *
     * (default value: false)
     *
     * @var Database_PDO
     * @access protected
     */
    protected $Database_kea = false;



    /**
     * __construct function.
     *
     * @access public
     * @param array $kea_settings (default: array())
     * @return void
     */
    public function __construct($kea_settings = array()) {
        // save settings
        if (is_array($kea_settings))            { $this->kea_settings = $kea_settings; }
        else                                    { throw new exception ("Invalid kea settings"); }

        // set file
        if(isset($this->kea_settings['file']))  { $this->kea_config_file = $this->kea_settings['file']; }

        // parse config file on startup
        $this->parse_config ();
        // parse and save subnets
        $this->parse_subnets ();
    }

    /**
     * Opens database connection if needed for leases and hosts
     *
     * @access private
     * @param mixed $username
     * @param mixed $password
     * @param mixed $host
     * @param mixed $port
     * @param mixed $dbname
     * @param mixed $charset
     * @return void
     */
    private function init_database_conection ($username, $password, $host, $port, $dbname) {
        // open
        $this->Database_kea = new Database_PDO ($username, $password, $host, $port, $dbname);
    }






    /**
     * This function parses config file and returns it as array.
     *
     * @access private
     * @return void
     */
    private function parse_config () {
        // get file to array
        if(file_exists($this->kea_config_file)) {
            $config = file($this->kea_config_file);
            // save
            $this->config_raw = implode("\n",array_filter($config));
        }
        else {
            throw new exception ("Cannot access config file ".$this->kea_config_file);
        }

        // loop and remove comments (contains #) and replace multilpe spaces
        $out   = array();
        foreach ($config as $k=>$f) {
            if (strpos($f, "#")!==false || is_blank($f)) {}
            else {
                if(!is_blank($f)) {
                    $out[] = $f;
                }
            }
        }

        // join to line
        $config = implode("", $out);

		// validate json
		if ($this->validate_json_string ($config)===false) {
    		throw new exception ("JSON config file error: $this->json_error");
		}

        // save config
        $this->config = pf_json_decode($config, true);
        // save IPv4 / IPv6 flags
        if(isset($this->config['Dhcp4']))   { $this->ipv4_used = true; }
        if(isset($this->config['Dhcp6']))   { $this->ipv6_used = true; }
    }

    /**
     * Saves subnets definition to $subnets object
     *
     * @access private
     * @return void
     */
    private function parse_subnets () {
        // save to subnets4 object
        $this->subnets4 = @$this->config['Dhcp4']['subnet4'];
        // save to subnets6 object
        $this->subnets6 = @$this->config['Dhcp6']['subnet6'];
    }









    /* @leases --------------- */

    /**
     * Saves leases to $leases object as array.
     *
     * @access public
     * @param string $type (default: "IPv4")
     * @return void
     */
    public function get_leases ($type = "IPv4") {
        // first check where they are stored - mysql, postgres or file
        if ($type=="IPv4") {
            $lease_database = $this->config['Dhcp4']['lease-database'];
        }
        else {
            $lease_database = $this->config['Dhcp6']['lease-database'];
        }

        // set lease type
        $lease_database_type = $lease_database['type'];

        // validate database type
        if (!in_array($lease_database_type, $this->lease_types)) {
            throw new exception ("Invalid lease database type");
        }

        // get leases
        $lease_type = "get_leases_".$lease_database_type;
        $this->{$lease_type} ($lease_database, $type);
    }

    /**
     * Fetches leases from memfile.
     *
     *  First line is structure
     *      address,hwaddr,client_id,valid_lifetime,expire,subnet_id,fqdn_fwd,fqdn_rev,hostname,state
     *
     * @access private
     * @param mixed $lease_database
     * @param string $type (default: "IPv4")
     * @return void
     */
    private function get_leases_memfile ($lease_database, $type) {
        // read file to array
        $leases_from_file = @file($lease_database['name']);
        // first item are titles
        unset($leases_from_file[0]);
        // if leases are present format to array
        if (sizeof($leases_from_file)>0 && $leases_from_file!==false) {
            // init array
            $leases_parsed = array();
            // loop and save leases
            foreach ($leases_from_file as $l) {
                if(strlen($l)>1) {
                    // to array
                    $l = pf_explode(",", $l);

                    // set state
                    switch ($l[9]) {
                        case 0:
                            $l[9] = "default";
                            break;
                        case 1:
                            $l[9] = "declined";
                            break;
                        case 2:
                            $l[9] = "expired-reclaimed";
                            break;
                    }
                    // save only active
                    if ($l[4] > time() ) {
                        $leases_parsed[] = array(
                        					"address" => $l[0],
                        					"hwaddr" => $l[1],
                        					"client_id" => $l[2],
                        					"valid_lifetime" => $l[3],
                        					"expire" => date("Y-m-d H:i:s", $l[4]),
                        					"subnet_id" => $l[5],
                        					"fqdn_fwd" => $l[6],
                        					"fqdn_rev" => $l[7],
                        					"hostname" => $l[8],
                        					"state" => $l[9]
                        				);
                    }
                }
            }
        }
        else {
            throw new exception("Cannot read leases file ".$lease_database['name']);
        }

        // save result
        if ($type=="IPv4")  { $this->leases4 = $leases_parsed; }
        else                { $this->leases6 = $leases_parsed; }
    }

    /**
     * Fetches leases from mysql database.
     *
     * @access private
     * @param mixed $lease_database
     * @param string $type (default: "IPv4")
     * @return void
     */
    private function get_leases_mysql ($lease_database, $type) {
        // if host not specified assume localhost
        if (is_blank($lease_database['host'])) { $lease_database['host'] = "localhost"; }
        // open DB connection
        $this->init_database_conection ($lease_database['user'], $lease_database['password'], $lease_database['host'], 3306, $lease_database['name']);
        // set query
        if($type=="IPv4") {
            $query  = "select ";
            $query .= "INET_NTOA(address) as `address`, hex(hwaddr) as hwaddr, hex(`client_id`) as client_id,`subnet_id`,`valid_lifetime`,`expire`,`name` as `state`,`fqdn_fwd`,`fqdn_rev`,`hostname` from `lease4` as a, ";
            $query .= "`lease_state` as s where a.`state` = s.`state`;";
        }
        else {
            throw new Exception("IPv6 leases not yet!");
        }
        // fetch leases
		try { $leases = $this->Database_kea->getObjectsQuery($query); }
		catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
		// save leases
		if (sizeof($leases)>0) {
    		// we need array
    		$result = array();
    		// loop
    		foreach ($leases as $k=>$l) {
        		$result[$k] = (array) $l;
    		}

    		// save
    		if($type=="IPv4") {
        		$this->leases4 = $result;
            }
            else {
        		$this->leases6 = $result;
            }
		}
    }

    /**
     * Fetches leases from postgres SQL.
     *
     * @access private
     * @param mixed $lease_database
     * @return void
     */
    private function get_leases_postgresql ($lease_database) {
        throw new exception ("PostgresSQL not supported");
    }









    /* @reservations --------------- */

    /**
     * Saves reservations to $reservations object as array.
     *
     *  Note:
     *      For IPv4 reservations KEA by default uses `reservations` item under subnet4 > reservations array.
     *      It can also use hosts-database in MySQL, if hosts-database is set
     *
     *
     *  For KEA v 1.0 only MySQL is supported. If needed later item can be added to $reservation_types and new method created
     *
     * @access public
     * @param string $type (default: "IPv4")
     * @return void
     */
    public function get_reservations ($type = "IPv4") {
        // first check where they are stored - mysql, postgres or file
        if($type=="IPv4") {
            if (isset($this->config['Dhcp4']['hosts-database'])) {
                $reservations_database = $this->config['Dhcp4']['hosts-database'];
            }
            else {
                $reservations_database = false;
            }
        }
        else {
            if (isset($this->config['Dhcp4']['hosts-database'])) {
                $reservations_database = $this->config['Dhcp6']['hosts-database'];
            }
            else {
                $reservations_database = false;
            }
        }


        // first check reservations under subnet > reservations, can be both
        $this->get_reservations_config_file ($type, $reservations_database);

        // if set in config check also database
        if ($reservations_database!==false) {
            // set lease type
            $reservations_database_type = $reservations_database['type'];

            // id database type is set and valid check it also
            if (!in_array($reservations_database_type, $this->reservation_types)) {
                throw new exception ("Invalid reservations database type");
            }
            else {
                // get leases
                $type_l = "get_reservations_".$reservations_database_type;
                $this->{$type_l} ($reservations_database, $type);
            }
        }
    }

    /**
     * Fetches leases from memfile.
     *
     *  https://kea.isc.org/wiki/HostReservationDesign
     *
     * @access private
     * @param mixed $type
     * @param array $reservations_database
     * @return void
     */
    private function get_reservations_config_file ($type, $reservations_database) {
        // read file
        if($type=="IPv4") {
            // check if set
            if (isset($this->config['Dhcp4']['subnet4'])) {
                foreach ($this->config['Dhcp4']['subnet4'] as $s) {
                    // set
                    if (isset($s['reservations'])) {
                        // save id
                        unset($s_id);
                        $s_id = isset($s['id']) ? $s['id'] : "";
                        // init array
                        $this->reservations4 = array();
                        $m=0;
                        // loop
                        foreach ($s['reservations'] as $r) {
                            $this->reservations4[$m] = array(
                                                    "location"       => "Config file",
                                                    "hw-address"     => $r['hw-address'],
                                                    "ip-address"     => $r['ip-address'],
                                                    "hostname"       => $r['hostname'],
                                                    "dhcp4_subnet_id"=> $s_id,
                                                    "subnet"         => $s['subnet']
                                                    );
                            // options
                            if(isset($r['options'])) {
                                $this->reservations4[$m]['options'] = array();
                                foreach ($r['options'] as $o) {
                                     $this->reservations4[$m]['options'][$o['name']] = $o['data'];
                                }
                            }
                            // classes
                            if(isset($r['client-classes'])) {
                                $this->reservations4[$m]['classes'] = array();
                                foreach ($r['client-classes'] as $c) {
                                     $this->reservations4[$m]['classes'][] = $c;
                                }
                            }

                            // reformat
                            $this->reservations4[$m] = $this->reformat_empty_array_fields ($this->reservations4[$m], "/");

                            // next index
                            $m++;
                        }
                    }
                }
            }
        }
        else {
            $this->reservations6 = file($reservations_database['name']);
        }
    }

    /**
     * Fetches leases from mysql database.
     *
     * @access private
     * @param mixed $reservations_database  //database details
     * @param mixed $type                   //ipv4 / ipv6
     * @return void
     */
    private function get_reservations_mysql ($reservations_database, $type) {
        // if host not specified assume localhost
        if (is_blank($reservations_database['host'])) { $reservations_database['host'] = "localhost"; }
        // open DB connection
        $this->init_database_conection ($reservations_database['user'], $reservations_database['password'], $reservations_database['host'], 3306, $reservations_database['name']);
        // set query
        if($type=="IPv4") {
            $query = "select 'MySQL' as 'location', `dhcp4_subnet_id`, `ipv4_address` as `ip-address`, HEX(`dhcp_identifier`) as `hw-address`, `hostname` from `hosts`;";
        }
        else {
            $query = "select * from `hosts`;";
        }
        // fetch leases
		try { $reservations = $this->Database_kea->getObjectsQuery($query); }
		catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
		// save leases
		if (sizeof($reservations)>0) {
    		// we need array
    		$result = array();
    		// loop
    		foreach ($reservations as $k=>$l) {
        		// check for subnet
        		if ($l->dhcp4_subnet_id!==0 && !is_blank($l->dhcp4_subnet_id)) {
            		if($type=="IPv4") {
                		foreach($this->subnets4 as $s) {
                    		if($s['id']==$l->dhcp4_subnet_id) {
                        		$l->subnet = $s['subnet'];
                    		}
                		}
            		}
            		else {
                		foreach($this->subnets6 as $s) {
                    		if($s['id']==$l->dhcp6_subnet_id) {
                        		$l->subnet = $s['subnet'];
                    		}
                		}
                    }
        		}

        		// save
        		if($type=="IPv4") {
            		$this->reservations4[] = (array) $l;
        		}
        		else {
            		$this->reservations6[] = (array) $l;
        		}
    		}
		}
    }







    public function read_statistics () {
        $sock = stream_socket_client('unix:///var/lib/kea/socket', $errno, $errstr);

        $cmd = array("command"=>"list-commands");

        fwrite($sock, json_encode($cmd)."\r\n");

        echo fread($sock, 4096)."\n";

        fclose($sock);
    }
}
