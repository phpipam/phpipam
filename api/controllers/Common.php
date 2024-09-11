<?php

/**
 *  API Parameter class
 */
class API_params extends Params {

	/**
	 * Read array of arguments
	 *
	 * @param array $args
	 * @param bool  $strip_tags
	 * @return void
	 */
	public function read($args, $strip_tags = false) {
		if (!is_array($args))
			return;

		if (isset($args['controller']))
			$args['controller'] = strtolower($args['controller']);

		parent::read($args, $strip_tags);
	}
}

/**
 *	phpIPAM API class for common functions
 *
 *
 */
class Common_api_functions {


	/**
	 * controller_keys
	 *
	 * @var array
	 * @access protected
	 */
	protected $controller_keys;

	/**
	 * _params provided from request
	 *
	 * @var object
	 * @access public
	 */
	public $_params;

	/**
	 * Lock transaction to avoid duplicate entries or errors
	 *
	 * (default value: 0)
	 *
	 * @var bool
	 * @access public
	 */
	public $lock = 0;

	/**
	 * File to write lock to
	 *
	 * (default value: "_lock.txt")
	 *
	 * @var string
	 * @access public
	 */
	public $lock_file_name = "/tmp/phpipam_api_lock.txt";

    /**
     * File handler
     *
     * (default value: false)
     *
     * @var bool|resource
     * @access private
     */
    private $lock_file_handler = false;

    /**
     * Custom fields
     *
     * @var array
     * @access public
     */
    public $custom_fields;

	/**
	 * valid_keys
	 *
	 * @var array
	 * @access protected
	 */
	protected $valid_keys;

	/**
	 * custom_keys
	 *
	 * @var array
	 * @access protected
	 */
	protected $custom_keys;

	/**
	 * Keys to be removed
	 *
	 * @var array
	 * @access protected
	 */
	protected $remove_keys;

	/**
	 * keys
	 *
	 * @var array
	 * @access protected
	 */
	protected $keys;

	/**
	 * Database object
	 *
	 * @var Database_PDO
	 * @access protected
	 */
	protected $Database;

	/**
	 * Master Tools class
	 *
	 * @var Tools
	 * @access protected
	 */
	protected $Tools;

	/**
	 * Response class
	 *
	 * @var Responses
	 * @access protected
	 */
	protected $Response;

	/**
	 * Master subnets class
	 *
	 * @var Subnets
	 * @access protected
	 */
	protected $Subnets;

	/**
	 * Master Addresses object
	 *
	 * @var Addresses
	 * @access protected
	 */
	protected $Addresses;

	/**
	 * Master Sections object
	 *
	 * @var Sections
	 * @access protected
	 */
	protected $Sections;

	/**
	 * Master user class
	 *
	 * @var User
	 * @access protected
	 */
	protected $User;

	/**
	 * Master Admin object
	 *
	 * @var Admin
	 * @access protected
	 */
	protected $Admin;

	/**
	 * App object - will be passed by index.php
	 * to provide app details
	 *
	 * @var false|object
	 */
	public $app = false;




	/**
	 * Provide default REQUEST_METHODs
	 *
	 */

	private function NOT_IMPLEMENTED() {
		return array("code"=>501, "message"=>"Method not implemented");
	}

	public function OPTIONS () {
		return $this->NOT_IMPLEMENTED ();
	}

	public function GET () {
		return $this->NOT_IMPLEMENTED ();
	}

	public function POST () {
		return $this->NOT_IMPLEMENTED ();
	}

	public function PATCH () {
		return $this->NOT_IMPLEMENTED ();
	}

	public function DELETE () {
		return $this->NOT_IMPLEMENTED ();
	}

	/* Alias HEAD to GET */

	public function HEAD () {
		return $this->GET ();
	}

	/* Alias PUT to PATCH */

	public function PUT () {
		return $this->PATCH ();
	}




	/**
	 * Initializes new Object.
	 *
	 * @access protected
	 * @param mixed $Object_name		// object name
	 * @param mixed $Database	       // Database object
	 */
	protected function init_object ($Object_name, $Database) {
		// admin fix
		if($Object_name=="Admin")	    { $this->{$Object_name}	= new $Object_name ($Database, false); }
		// User fix
		elseif($Object_name=="User")	{ $this->{$Object_name}	= new $Object_name ($Database, true); $this->{$Object_name}->user = null; }
		// default
		else					        { $this->{$Object_name}	= new $Object_name ($Database); }
		// set exit method
		$this->{$Object_name}->Result->exit_method = "exception";
		// set API flag
		$this->{$Object_name}->api = true;
	}

	/**
	 * Sets valid keys for actions
	 *
	 * @access protected
	 * @param mixed $controller
	 * @return void
	 */
	protected function set_valid_keys ($controller) {
		# array of controller keys
		$this->controller_keys = array("app_id", "controller");

		# array of all valid keys - fetch from SCHEMA
		$this->valid_keys = $this->Tools->fetch_standard_fields ($controller);

		# add custom fields
		$custom_fields = $this->Tools->fetch_custom_fields($controller);
		if(sizeof($custom_fields)>0) {
			foreach($custom_fields as $cf) {
				$this->custom_keys[] = $cf['name'];
			}
		}

		# save custom fields
		$this->custom_fields = $custom_fields;

		# merge all
		$this->valid_keys = array_merge($this->controller_keys, $this->valid_keys);
		if(isset($this->custom_keys)) {
			$this->valid_keys = array_merge($this->valid_keys, $this->custom_keys);
		}

		# set items to remove
		$this->remove_keys = array("editDate");
		# remove update time
		foreach($this->valid_keys as $k=>$v) {
			if(in_array($v, $this->remove_keys)) {
				unset($this->valid_keys[$k]);
			}
		}
	}

	/**
	 * Prepares result, creates links if requested and transforms address/subnet to
	 *	decimal format
	 *
	 * @access protected
	 * @param mixed $result
	 * @param mixed $controller (default: null)
	 * @param bool $links (default: true)
	 * @param bool $transform_address (default: true)
	 * @return void
	 */
	protected function prepare_result ($result, $controller = null, $links = true, $transform_address = true) {
		// empty controller
		$controller = is_null($controller) ? $this->_params->controller : $controller;

		// links
		if($links) {
			// if parameter is set obey
			if(isset($this->_params->links)) {
				if($this->_params->links!="false")
								{ $result = $this->add_links ($result, $controller); }
			}
			// otherwise take defaults
			else {
				if($this->app->app_show_links==1)
								{ $result = $this->add_links ($result, $controller); }
			}
		}
		// filter
		if (isset($this->_params->filter_by)) {
								{ $result = $this->filter_result ($result); }
		}
		// transform address
		if($transform_address)	{ $result = $this->transform_address ($result); }

		// remove subnets and addresses if needed
		$result = $this->remove_folders ($result);
		$result = $this->remove_subnets ($result);

		// remap keys
		$result = $this->remap_keys ($result, $controller);

		// Reindex results to start at index 0.
		if (is_array($result)) { $result = array_values($result); }

		# return
		return $result;
	}

	/**
	 * Filters result
	 *
	 *	parameters: filter_by, filter_value
	 *
	 * @access protected
	 * @param array $result
	 * @return object[]
	 */
	protected function filter_result ($result = array ()) {
		// remap keys before applying filter
		$result = $this->remap_keys ($result, false);
		// validate
		$this->validate_filter_by ($result);

		// Filter single object
		if (is_object($result))
			$result = [$result];    // convert to array of objects

		if (!is_array($result))
			return false;           // Bad input

		// Filter array of objects
		$result2 = [];
		foreach($result as $r) {
			if (!property_exists($r, $this->_params->filter_by))
				continue;

			if ($this->_params->filter_match == 'partial') {
				// match partial string
				if (strpos($r->{$this->_params->filter_by}, $this->_params->filter_value) === false)
					continue;
			} elseif ($this->_params->filter_match == 'regex') {
				// match regular expression
				if (preg_match($this->_params->filter_value, $r->{$this->_params->filter_by}) !== 1)
					continue;
			} else {
				// match full string
				if ($r->{$this->_params->filter_by} != $this->_params->filter_value)
					continue;
			}

			$result2[] = $r;    // save match
		}

		if (empty($result2))
			$this->Response->throw_exception(404, _('No results (filter applied)'));

		# reindex filtered result
		return array_values($result2);
	}

	/**
	 * Validates filter_by
	 *
	 *  Takes first result, checks all keys against provided filter_by value
	 *
	 * @access protected
	 * @param mixed $result
	 * @return void
	 */
	protected function validate_filter_by ($result) {
		// validate filter
		if (is_array($result))	{ $result = $result[0]; }

		// validate filter_value
		if(!isset($this->_params->filter_value))
			$this->Response->throw_exception(400, _('Missing filter_value'));

		if (is_blank($this->_params->filter_value))
			$this->Response->throw_exception(400, _('Empty filter_value'));

		// validate filter_by is a valid property
		if (!is_object($result) || !property_exists($result, $this->_params->filter_by))
			$this->Response->throw_exception(400, _('Invalid filter_by'));

		// validate filter_match (default:'full')
		if (!isset($this->_params->filter_match))
			$this->_params->filter_match = 'full';

		if (!in_array($this->_params->filter_match, ['full', 'partial', 'regex']))
			$this->Response->throw_exception(400, _('Invalid filter_match'));

		if ($this->_params->filter_match == 'regex') {
			@preg_match($this->_params->filter_value, 'phpIPAM');
			if (($last_err = preg_last_error()) != PREG_NO_ERROR)
				$this->Response->throw_exception(400, _('Invalid regular expression')." (err=$last_err)");
		}
	}

	/**
	 * Creates links for GET requests
	 *
	 * @access private
	 * @param mixed $result
	 * @param mixed $controller
	 * @return void
	 */
	protected function add_links ($result, $controller=null) {
		// lower controller
		$controller = strtolower($controller);

		// multiple options
		if(is_array($result)) {
			foreach($result as $k=>$r) {
				// fix for Vlans and vrfs
				if($controller=="vlans")				{ $r->id = $r->vlanId; }
				if($controller=="tools/vlans")			{ $r->id = $r->vlanId; }
				if($controller=="vrfs")					{ $r->id = $r->vrfId; }
				if($this->_params->id=="deviceTypes")	{ $r->id = $r->tid; }

				$m=0;
				// custom links
				$custom_links = $this->define_links ($controller);
				if($custom_links!==false) {
					$links_arr = $this->define_links ($controller);
					if(is_array($links_arr)) {
						foreach($this->define_links ($controller) as $link=>$method) {
							// self only !
							if ($link=="self") {
							$result[$k]->links[$m] = new stdClass ();
							$result[$k]->links[$m]->rel  	= $link;
							$result[$k]->links[$m]->href 	= "/api/".$this->_params->app_id."/$controller/".$r->id."/";
							}
						}
					}
				}

				// remove id for vlans
				if($controller=="vlans")	{ unset($r->id); }
				if($controller=="vrfs")		{ unset($r->id); }
			}
		}
		// single item
		else {
				// fix for Vlans and Vrfs
				if($controller=="vlans")				{ $result->id = $result->vlanId; }
				if($controller=="tools/vlans")			{ $result->id = $result->vlanId; }
				if($controller=="vrfs")					{ $result->id = $result->vrfId; }
				if($this->_params->id=="deviceTypes")	{ $result->id = $result->tid; }

				$m=0;
				// custom links
				$custom_links = $this->define_links ($controller);
				if($custom_links!==false) {
					$links_arr = $this->define_links ($controller);
					if(is_array($links_arr)) {
						foreach($this->define_links ($controller) as $link=>$method) {
							$result->links[$m] = new stdClass ();
							$result->links[$m]->rel  	= $link;
							// self ?
							if ($link=="self")
							$result->links[$m]->href 	= "/api/".$this->_params->app_id."/$controller/".$result->id."/";
							else
							$result->links[$m]->href 	= "/api/".$this->_params->app_id."/$controller/".$result->id."/$link/";
							$result->links[$m]->methods = $method;
							// next
							$m++;
						}
					}
				}

				// remove id for vlans
				if($controller=="vlans")	{ unset($result->id); }
				if($controller=="vrfs")		{ unset($result->id); }
		}
		# return
		return $result;
	}

	/**
	 * Defines links for controller
	 *
	 * @access private
	 * @param mixed $controller
	 * @return array
	 */
	private function define_links ($controller) {
    	// init
    	$result = array();
		// sections
		if($controller=="sections") {
			$result["self"]			 	= array ("GET","POST","DELETE","PATCH");
			$result["subnets"]          = array ("GET");
			// return
			return $result;
		}
		// subnets
		elseif($controller=="subnets") {
			$result["self"]			 	= array ("GET","POST","DELETE","PATCH");
			$result["addresses"]        = array ("GET");
			$result["addresses/{ip}"]   = array ("GET");
			$result["usage"]            = array ("GET");
			$result["first_free"]       = array ("GET");
			$result["slaves"]           = array ("GET");
			$result["slaves_recursive"] = array ("GET");
			$result["truncate"]         = array ("DELETE");
			$result["permissions"]      = array ("DELETE", "PATCH");
			$result["resize"]           = array ("PATCH");
			$result["split"]            = array ("PATCH");
			// return
			return $result;
		}
		// addresses
		elseif($controller=="addresses") {
			$result["self"]				= array ("GET","POST","DELETE","PATCH");
			$result["ping"]				= array ("GET");
			// return
			return $result;
		}
		// tags
		elseif($controller=="addresses/tags") {
			$result["self"]				= array ("GET");
			$result["addresses"]		= array ("GET");
			// return
			return $result;
		}
		// tools - devices
		elseif($controller=="tools/devices") {
			$result["self"]			 	= array ("GET","POST","DELETE","PATCH");
			$result["addresses"]        = array ("GET");
			// return
			return $result;
		}
		// tools - devices
		elseif($controller=="tools/devicetypes") {
			$result["self"]			 	= array ("GET","POST","DELETE","PATCH");
			$result["devices"]        	= array ("GET");
			// return
			return $result;
		}
		// tools - tags
		elseif($controller=="tools/iptags") {
			$result["self"]			 	= array ("GET","POST","DELETE","PATCH");
			$result["addresses"]        = array ("GET");
			// return
			return $result;
		}
		// tools - tags
		elseif($controller=="tools/vlans") {
			$result["self"]			 	= array ("GET");
			$result["subnets"]          = array ("GET");
			// return
			return $result;
		}
		// tools - tags
		elseif($controller=="tools/vrf") {
			$result["self"]			 	= array ("GET");
			$result["subnets"]          = array ("GET");
			// return
			return $result;
		}
		// tags
		elseif($controller=="iptags") {
			$result["self"]				= array ("GET");
			$result["addresses"]		= array ("GET");
			// return
			return $result;
		}
		// tags
		elseif($controller=="devices") {
			$result["self"]				= array ("GET");
			$result["addresses"]		= array ("GET");
			// return
			return $result;
		}
		// vlan domains
		elseif($controller=="l2domains") {
			$result["self"]			 	= array ("GET","POST","DELETE","PATCH");
			$result["vlans"]          	= array ("GET");
			// return
			return $result;
		}
		// vlans
		elseif($controller=="vlans") {
			$result["self"]			 	= array ("GET","POST","DELETE","PATCH");
			$result["subnets"]          = array ("GET");
			// return
			return $result;
		}
		// vrfs
		elseif($controller=="vrfs") {
			$result["self"]			 	= array ("GET","POST","DELETE","PATCH");
			$result["subnets"]          = array ("GET");
			// return
			return $result;
		}

		// default
		return false;
	}

	/**
	 * Transforms IP address and subnet
	 *
	 * @access protected
	 * @param mixed $result
	 * @return void
	 */
	protected function transform_address ($result) {
		$result_is_object = false;

		if (is_object($result)) {
			$result_is_object = true;
			$result = [$result];
		}

		if (!is_array($result))
			return $result;

		foreach($result as $r) {
			$properties = ['subnet', 'ip_addr'];
			foreach($properties as $property) {
				if (property_exists($r, $property)) {
					// remove IP & transform property to dotted notation
					unset($r->ip);
					$r->{$property} = $this->Subnets->transform_address($r->{$property}, "dotted");
				}
			}
		}

		return $result_is_object===true ? $result[0] : $result;
	}

	/**
	 * Validates posted keys and returns proper inset values
	 *
	 * @access private
	 * @return array
	 */
	protected function validate_keys () {
    	// init values
    	$values = array();
    	// loop
		foreach($this->_params as $pk=>$pv) {
			if(!in_array($pk, $this->valid_keys)) 	{ $this->Response->throw_exception(400, 'Invalid request key '.$pk); }
			// set parameters
			else {
				if(!in_array($pk, $this->controller_keys)) {
					 $values[$pk] = $pv;
				}
			}
		}
		# remove editDate
		unset($values['editDate']);
		# return
		return $values;
	}

	/**
	 * Validates OPTIONS request
	 *
	 * @access protected
	 * @return void
	 */
	protected function validate_options_request () {
		foreach($this->_params as $key=>$val) {
			if(!in_array($key, array("app_id", "controller", "id"))) {
													{ $this->Response->throw_exception(400, 'Invalid request key parameter '.$key); }
			}
		}
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
    	// init common class
    	$Common = new Common_functions;
    	// check
    	return $Common->validate_mac ($mac);
	}

	/**
	 * Reformats MAC address to requested format
	 *
	 * @access public
	 * @param string $mac
	 * @param int $format (default: 1)
	 *      1 : 00:66:23:33:55:66
	 *      2 : 00-66-23-33-55-66
	 *      3 : 0066.2333.5566
	 *      4 : 006623335566
	 * @return string
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
	 * Returns array of possible permissions
	 *
	 * @access public
	 * @return array
	 */
	public function get_possible_permissions() {
		return array(
			"na" => 0,
			"ro" => 1,
			"rw" => 2,
			"rwa" => 3
		);
	}

	/**
	 * This method removes all folders if controller is subnets
	 *
	 * @access protected
	 * @param mixed $result
	 * @return mixed
	 */
	protected function remove_folders($result) {
		// must be subnets
		if ($this->_params->controller != "subnets") {
			return $result;
		}

		if (is_array($result)) {
			foreach ($result as $k => $r) {
				if (isset($r->isFolder) && $r->isFolder == "1") {
					unset($result[$k]);
				}
			}
		} else {
			if (isset($result->isFolder) && $result->isFolder == "1") {
				unset($result);
			}
		}

		if (empty($result)) {
			$this->Response->throw_exception(404, "No subnets found");
		}
		return $result;
	}

	/**
	 * This method removes all subnets if controller is subnets
	 *
	 * @access protected
	 * @param mixed $result
	 * @return mixed
	 */
	protected function remove_subnets($result) {
		// must be subnets
		if ($this->_params->controller != "folders") {
			return $result;
		}

		if (is_array($result)) {
			foreach ($result as $k => $r) {
				if (isset($r->isFolder) && $r->isFolder != "1") {
					unset($result[$k]);
				}
			}
		} else {
			if (isset($result->isFolder) && $result->isFolder != "1") {
				unset($result);
			}
		}

		if (empty($result)) {
			$this->Response->throw_exception(404, "No folders found");
		}
		return $result;
	}

	/**
	 * Remaps keys based on request type
	 *
	 * @access protected
	 * @param mixed $result (default: null)
	 * @param mixed $controller (default: null)
	 * @param mixed $tools_table (default: null)
	 * @return void
	 */
	protected function remap_keys ($result = null, $controller = null, $tools_table = null) {
		// define keys array
		$this->keys = array("switch"=>"deviceId", "state"=>"tag", "ip_addr"=>"ip");

		// exceptions
		if($controller=="vlans") 	{ $this->keys['vlanId'] = "id"; }
		if($controller=="vrfs")  	{ $this->keys['vrfId'] = "id"; }
		if($controller=="circuits") { $this->keys['cid'] = "circuit_id"; }
		if($controller=="l2domains"){ $this->keys['permissions'] = "sections"; }
		if($this->_params->controller=="tools" && $tools_table=="deviceTypes")  { $this->keys['tid'] = "id"; }
		if($this->_params->controller=="tools" && $tools_table=="nameservers")  { $this->keys['permissions'] = "sections"; }
		if($this->_params->controller=="subnets" )  								  { $this->keys['ip'] = "ip_addr"; }

		// special keys for POST / PATCH
		if ($_SERVER['REQUEST_METHOD']=="POST" || $_SERVER['REQUEST_METHOD']=="PATCH") {
		if($this->_params->controller=="circuits")   								  { $this->keys['cid'] 		= "circuit_id"; }
		}

		// POST / PATCH / DELETE
		if ($_SERVER['REQUEST_METHOD']=="POST" || $_SERVER['REQUEST_METHOD']=="PATCH" || $_SERVER['REQUEST_METHOD']=="DELETE")		{ return $this->remap_update_keys (); }
		// GET
		elseif ($_SERVER['REQUEST_METHOD']=="GET")											{ return $this->remap_result_keys ($result); }
	}

	/**
	 * Updates request keys to database ones
	 *
	 * @access private
	 * @return void
	 */
	private function remap_update_keys () {
		// loop
		foreach($this->keys as $k=>$v) {
			// match
			if(property_exists($this->_params, $v)) {
				// replace
				$this->_params->{$k} = $this->_params->{$v};
				// remove
				unset($this->_params->{$v});
			}
		}
	}

	/**
	 * Remap result keys - what is offered to client
	 *
	 * @access private
	 * @param mixed $result
	 * @return void
	 */
	private function remap_result_keys ($result) {
		# single
		if(!is_array($result)) {
			// params
			$result_remapped = new StdClass ();
			// search and replace
			if(is_array($result) || is_object($result)) {
				foreach($result as $k=>$v) {
					if(array_key_exists($k, $this->keys)) {
						// replace
						$key = $this->keys[$k];
						$result_remapped->{$key} = $v;
					}
					else {
						$result_remapped->{$k} = $v;
					}
				}
			}
		}
		# array
		else {
			// create a new array for the remapped data
			$result_remapped = array();

			// loop
			foreach ($result as $m=>$r) {
				// start object
				$result_remapped[$m] = new StdClass ();

				// search and replace
				foreach($r as $k=>$v) {
					if(array_key_exists($k, $this->keys)) {
						// replace
						$key_val = $this->keys[$k];
						$result_remapped[$m]->{$key_val} = $v;
					}
					else {
						$result_remapped[$m]->{$k} = $v;
					}
				}
			}
		}

		# result
		return $result_remapped;
	}






    /* ! @transaction_locking --------------- */

    /**
     * Open file handler to manage lock file
     *
     * @access private
     * @return void
     */
    private function file_init_handler () {
        try {
            $this->lock_file_handler = fopen($this->lock_file_name, 'w');
        }
        catch ( Exception $e ) {
            $this->Response->throw_exception(500, "Cannot init file handler for $this->lock_file_name ".$e->getMessage());
        }
    }

    /**
     * Adds Exclusive lock and writes 1 to file
     *
     * @access private
     * @return void
     */
    private function file_add_lock () {
        try {
            // add lock
            flock($this->lock_file_handler, LOCK_EX);
            // write content
            $this->file_write_content ("1");
        }
        catch ( Exception $e ) {
            $this->Response->throw_exception(500, "Cannot add LOCK_UN to $this->lock_file_name ".$e->getMessage());
        }
    }
    /**
     * Removes exclusive lock
     *
     * @access private
     * @return void
     */
    private function file_remove_lock () {
        try {
            // write content
            $this->file_write_content ("0");
            // close handler
            fclose($this->lock_file_handler);
        }
        catch ( Exception $e ) {
            $this->Response->throw_exception(500, "Cannot remove LOCK_UN from $this->lock_file_name ".$e->getMessage());
        }
    }

    /**
     * Write content to file.
     *
     * @access private
     * @param string $content (default: "")
     * @return void
     */
    private function file_write_content ($content = "") {
        try {
            fwrite($this->lock_file_handler, $content);
        }
        catch ( Exception $e ) {
            $this->Response->throw_exception(500, "Cannot write content to $this->lock_file_name ".$e->getMessage());
        }
    }

	/**
	 * Resets lock file name
	 *
	 * @access public
	 * @param string $file (default: "")
	 * @return void
	 */
	public function set_transaction_lock_file ($file = "") {
        if(!is_blank($file)) {
            $this->lock_file_name = $file;
        }
	}

	/**
	 * Sets transaction lock
	 *
	 * @access public
	 * @return void
	 */
	public function add_transaction_lock () {
    	$this->file_init_handler ();
        $this->file_add_lock ();
	}

	/**
	 * Removes transaction lock
	 *
	 * @access public
	 * @return void
	 */
	public function remove_transaction_lock () {
    	$this->file_remove_lock();
	}

	/**
	 * Checks for lock
	 *
	 * @access public
	 * @return void
	 */
	public function is_transaction_locked () {
        // check for stalled lock file
        $this->check_stalled_file ();
        // response
        if(file_exists($this->lock_file_name)) {
            return file_get_contents($this->lock_file_name) == "1" ? true : false;
        }
        else {
            return false;
        }

	}

	/**
	 * Removes stalled lock file if needed
	 *
	 * @access private
	 * @return void
	 */
	private function check_stalled_file () {
    	if(file_exists($this->lock_file_name)) {
        	// if more that 60 seconds remove it
        	if((time() - filemtime($this->lock_file_name)) > 60) {
            	$this->file_init_handler ();
            	$this->remove_transaction_lock ();
        	}
    	}
	}

	/**
	* Unmarshal nested custom field data into the root object, and unset
	* the custom_fields parameter when done. This function does not have
	* any effect on requests for controllers that don't have custom fields,
	* or if the app_nest_custom_fields setting is not enabled.
	*
	* @access public
	* @return void
	*/
	public function unmarshal_nested_custom_fields() {
		if (!$this->app->app_nest_custom_fields) {
			return;
		}
		if (is_array($this->_params->custom_fields) && isset($this->custom_fields)) {
			foreach ($this->_params->custom_fields as $key => $value) {
				if (array_key_exists($key, $this->custom_fields)) {
					$this->_params->$key = $value;
				} else {
					$this->Response->throw_exception(400, "{$key} is not a valid custom field");
				}
			}
			unset($this->_params->custom_fields);
		}
	}

	/**
	 * Returns subnet gateway
	 *
	 * @param int $id
	 * @return object|false
	 */
	protected function read_subnet_gateway($id) {
		return (is_numeric($id) && $id > 0) ? $this->Subnets->find_gateway($id) : $this->Subnets->find_gateway($this->_params->id);
	}

	/**
	 * Returns nameserver details
	 *
	 * @param int $nsid
	 * @return object|false
	 */
	protected function read_subnet_nameserver($nsid) {
		return (is_numeric($nsid) && $nsid > 0) ? $this->Tools->fetch_object("nameservers", "id", $nsid) : false;
	}
}
