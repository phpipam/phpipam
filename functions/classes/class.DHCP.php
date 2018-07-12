<?php


/**
 * DHCP wrapper class.
 *
 *  This class servers as a wrapper for different DHCP servers
 *
 *  Curently supported are:
 *      - kea (http://kea.isc.org/wiki)
 *
 *
 *  DHCP servers will have to support common classes defined in this class
 *
 *
 *  get_    : methods to fetch data
 *          * get_config (returns config file in array form)
 *
 *  write_  : methods to save data
 *
 */
class DHCP extends Common_functions {

    /**
     * List of supported DHCP servers, needed for validation
     *
     * (default value: array("kea"))
     *
     * @var string
     * @access private
     */
    private $dhcp_server_types = array("kea");

    /**
     * Selected DHCP server from $dhcp_server_types to use with phpipam.
     *
     * (default value: null)
     *
     * @var mixed
     * @access private
     */
    private $dhcp_selected_type = null;

    /**
     * DHCP type specific settings, that will be passed to subclass
     *
     *  for example for kea array will be passed with location of config file (e.g. array("file"=>"/etc/kea/kea.conf"))
     *
     * (default value: array())
     *
     * @var array
     * @access private
     */
    private $dhcp_settings = array();

    /**
     * DHCP_server holder class
     *
     *  To store child DHCP object to
     *
     * (default value: false)
     *
     * @var bool
     * @access private
     */
    private $DHCP_server = false;

    /**
     * Result holder
     *
     *  Fore result printing in case of errors
     *
     * @var mixed
     * @access public
     */
    public $Result;






    /**
     * __construct function.
     *
     * @access public
     * @param mixed $server_type
     * @param array $dhcp_settings (default: array())
     * @return void
     */
    public function __construct($server_type, $dhcp_settings = array()) {
        // init Result class
        $this->Result = new Result ();

        // validate and set server type
        if(!in_array($server_type, $this->dhcp_server_types)) {
            $this->Result->show("danger",_("Invalid server type $server_type"), true);
        }
        else {
            $this->dhcp_selected_type = $server_type;
        }

        // save settings
        $this->dhcp_settings = (array) $dhcp_settings;

        // init class
        $this->init_dhcp_server_class ();
    }

    /**
     * Inits specific DHCP server and passes parameters
     *
     * @access private
     * @return void
     */
    private function init_dhcp_server_class () {
        // validate class file
        $this->verify_class_file ();
        // set class to call
        $dhcp_class = "DHCP_".$this->dhcp_selected_type;
        // init
		try {
		    $this->DHCP_server = new $dhcp_class ($this->dhcp_settings);
		}
		catch(Exception $e) {
			$this->Result->show("danger", $e->getMessage(), true);
		}
    }

    /**
     * Makes sure php class for selected DHCP type is found
     *
     * @access private
     * @return void
     */
    private function verify_class_file () {
        if(!file_exists(dirname(__FILE__)."/class.DHCP.".$this->dhcp_selected_type.".php")) {
            $this->Result->show("danger", _("Missing class file")." /functions/classes/class.DHCP.".$this->dhcp_selected_type.".php", true);
        }
        else {
            include(dirname(__FILE__)."/class.DHCP.".$this->dhcp_selected_type.".php");
        }
    }

    /**
     * Checks if DHCP subclass has specified method.
     *
     * @access private
     * @param mixed $method
     * @return void
     */
    private function validate_dhcp_type_method ($method) {
        if (!method_exists($this->DHCP_server, $method)) {
            $this->Result->show("danger", _("Method `$method` does not exist in class `class.DHCP.".$this->dhcp_selected_type.".php`"), true);
        }
    }






    /* @read methods --------------- */

    /**
     * Returns raw config file to display under settings.
     *
     * @access public
     * @return void
     */
    public function read_config_raw () {
        return $this->DHCP_server->config_raw;
    }

    /**
     * Returns config file for DNS server
     *
     *  Format must be array with following keys:
     *      - `Dhcp4`
     *          - `option-data` > array of default options with name and data keys (e.g. array(array("name"=>"domain-name", "data"=>"domain.local")))
     *          - `subnet4` (details are provided in documentation for read_subnets method below)
     *      - `Dhcp6`
     *          - `option-data` > array of default options with name and data keys (e.g. array(array("name"=>"domain-name", "data"=>"domain.local")))
     *          - `subnet6` (details are provided in documentation for read_subnets method below)
     *
     * @access public
     * @return void
     */
    public function read_config () {
        return $this->DHCP_server->config;
    }

    /**
     * Returns available subnets for DHCP with their parameters
     *
     *  We first check if ipv4_used and ipv6_used flag is set on child class
     *
     *  Returns:
     *      - if IP version is not used return false
     *      - if No subnets of type are available empty array will be returned
     *      - else return array of subnets
     *
     *  Array of subnets must contain following keys:
     *
     *      - `subnet` (e.g. 10.10.10.0/24)
     *      - `id` of subnet (to match with leases and reservations) (e.g. 2)
     *      - `pools` - array of pools with key pool (e.g. "pools"=>array(array("pool"=>"10.10.10.3-10.10.10.10"), array("pool"=>"10.10.10.11-10.10.10.20")))
     *      - `option-data` - array of options for this subnet (e.g. "option-data" = array(array("name"=>"router", "data"=>"10.10.10.1"), array("name"=>"name-servers", "data"=>"10.10.10.1, 10.10.10.2")))
     *
     *  Default values will be added to `option-data` from config file if it exists (if (isset($config['Dhcp4']['option-data'])))
     *
     * @access public
     * @param string $type (default: "IPv4")
     * @return void
     */
    public function read_subnets ($type = "IPv4") {
        // check if version used
        $flag = strtolower($type)."_used";
        if ($this->DHCP_server->{$flag}==false)    { return false; }

        // return subnets
        if($type=="IPv6")     { return $this->DHCP_server->subnets6; }
        else                  { return $this->DHCP_server->subnets4; }
    }

    /**
     * Returns array of active leases
     *
     *  We first check if ipv4_used and ipv6_used flag is set on child class
     *
     *  Returns:
     *      - if IP version is not used return false
     *      - if No leases of type are available empty array will be returned
     *      - else return array of leases
     *
     *  Array of leases must return following object to be displayed in phpipam:
     *      - `address` - lease IP address (.g. 10.10.10.43)
     *      - `hwaddr` - client MAC address, no delimiter or ./: (64006a88270b or 64:00:6a:88:27:0b or 6400.6a88.270b)
     *      - `subnet_id` - subnet identifier to link with subnet (int)
     *      - `client identifier` - string
     *      - `valid_lifetime` - for how long lease is valid
     *      - `expire` - date when lease expires (date Y-m-d H:i:s)
     *      - `state` - lease state (varchar)
     *      - `hostname` - client hostname (varchar)
     *
     *
     * @access public
     * @param string $type (default: "IPv4")
     * @return void
     */
    public function read_leases ($type = "IPv4") {
        // validate method
        $this->validate_dhcp_type_method ("get_leases");

        // check if version used
        $flag = strtolower($type)."_used";
        if ($this->DHCP_server->{$flag}==false)    { return false; }

        // get leases
        try {
            $this->DHCP_server->get_leases ($type);
        }
        catch (Exception $e) {
             $this->Result->show("danger", $e->getMessage(), false);
        }
        // return leases
        if($type=="IPv6")   { return $this->DHCP_server->leases6; }
        else                { return $this->DHCP_server->leases4; }
    }

    /**
     * Returns array of host reservations
     *
     *  We first check if ipv4_used and ipv6_used flag is set on child class
     *
     *  Returns:
     *      - if IP version is not used return false
     *      - if No reservations of type are available empty array will be returned
     *      - else return array of reservations
     *
     *  Array of reservations must return following object to be displayed in phpipam:
     *
     *      - `ip-address` - reserved IP address (.g. 10.10.10.43)
     *      - `hw-address` - reserved
     *      - `hostname` - MAC address, no delimiter or ./: (64006a88270b or 64:00:6a:88:27:0b or 6400.6a88.270b)
     *      - `location` - where lease is stored
     *      - `options` - array of options for client in key=>val format options = array("name-servers"=>"10.10.10.1");
     *      - `classes` - array of classes to assign to client
     *
     * @access public
     * @param string $type (default: "IPv4")
     * @return void
     */
    public function read_reservations ($type = "IPv4") {
        // validate method
        $this->validate_dhcp_type_method ("get_reservations");

        // check if version used
        $flag = strtolower($type)."_used";
        if ($this->DHCP_server->{$flag}==false)    { return false; }

        // get leases
        $this->DHCP_server->get_reservations ($type);
        // return leases
        if($type=="IPv6")   { return $this->DHCP_server->reservations6; }
        else                { return $this->DHCP_server->reservations4; }
    }








    /* @write methods --------------- */

}

?>