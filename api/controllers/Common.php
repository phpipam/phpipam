<?php

/**
 *	phpIPAM API class for common functions
 *
 *
 */
class Common_api_functions {


	/**
	 * controller_keys
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $controller_keys;

	/**
	 * _params provided from request
	 *
	 * @var mixed
	 * @access public
	 */
	public $_params;

    /**
     * Custom fields
     *
     * @var mixed
     * @access public
     */
    public $custom_fields;

	/**
	 * valid_keys
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $valid_keys;

	/**
	 * custom_keys
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $custom_keys;

	/**
	 * Keys to be removed
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $remove_keys;

	/**
	 * keys
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $keys;

	/**
	 * Master Tools class
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Tools;

	/**
	 * Response class
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Response;

	/**
	 * Master subnets class
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Subnets;






	/**
	 * Initializes new Object.
	 *
	 * @access protected
	 * @param mixed $Object_name		// object name
	 * @param mixed $Database	       // Database object
	 */
	protected function init_object ($Object_name, $Database) {
		// admin fix
		if($Object_name=="Admin")	    { $this->$Object_name	= new $Object_name ($Database, false); }
		// User fix
		elseif($Object_name=="User")	{ $this->$Object_name	= new $Object_name ($Database, true); $this->$Object_name->user = null; }
		// default
		else					        { $this->$Object_name	= new $Object_name ($Database); }
		// set exit method
		$this->$Object_name->Result->exit_method = "exception";
		// set API flag
		$this->$Object_name->api = true;
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
			// explicitly set to no
			if(@$this->_params->links!="false")
								{ $result = $this->add_links ($result, $controller); }
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

		# return
		return $result;
	}

	/**
	 * Filters result
	 *
	 *	parameters: filter_by, filter_value
	 *
	 * @access protected
	 * @param mixed $result
	 * @return void
	 */
	protected function filter_result ($result) {
    	// remap keys before applying filter
    	$result = $this->remap_keys ($result, false);
		// validate
		$this->validate_filter_by ($result);

		// filter - array
		if (is_array($result)) {
			foreach ($result as $m=>$r) {
				foreach ($r as $k=>$v) {
					if ($k == $this->_params->filter_by) {
						if ($v != $this->_params->filter_value) {
							unset($result[$m]);
							break;
						}
					}
				}
			}
		}
		// filter - single
		else {
				foreach ($result as $k=>$v) {
					if ($k == $this->_params->filter_by) {
						if ($v != $this->_params->filter_value) {
							unset($result);
							break;
						}
					}
				}
		}

		# null?
		if (sizeof($result)==0)				{ $this->Response->throw_exception(404, 'No results (filter applied)'); }

		# result
		return $result;
	}

	/**
	 * Validates filter_by
	 *
	 * @access protected
	 * @param mixed $result
	 * @return void
	 */
	protected function validate_filter_by ($result) {
		// validate filter
		if (is_array($result))	{ $result_tmp = $result[0]; }
		else					{ $result_tmp = $result; }

		$error = true;
		foreach ($result_tmp as $k=>$v) {
			if ($k==$this->_params->filter_by) {
				$error = false;
			}
		}
		// die
		if ($error)							{ $this->Response->throw_exception(400, 'Invalid filter value'); }
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
					foreach($this->define_links ($controller) as $link=>$method) {
						// self only !
						if ($link=="self") {
						$result[$k]->links[$m] = new stdClass ();
						$result[$k]->links[$m]->rel  	= $link;
						$result[$k]->links[$m]->href 	= "/api/".$this->_params->app_id."/$controller/".$r->id."/";
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
	 * @return void
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
			$result["usage"]            = array ("GET");
			$result["first_free"]       = array ("GET");
			$result["slaves"]           = array ("GET");
			$result["slaves_recursive"] = array ("GET");
			$result["truncate"]         = array ("DELETE");
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
		// multiple options
		if (is_array($result)) {
			foreach($result as $k=>$r) {
				// remove IP
				if (isset($r->ip))					{ unset($r->ip); }
				// transform
				if (isset($r->subnet))				{ $r->subnet  = $this->Subnets->transform_address ($r->subnet,  "dotted"); }
				elseif (isset($r->ip_addr))			{ $r->ip_addr = $this->Subnets->transform_address ($r->ip_addr, "dotted"); }
			}
		}
		// single item
		else {
				// remove IP
				if (isset($result->ip))				{ unset($result->ip); }
				// transform
				if (isset($result->subnet))			{ $result->subnet  = $this->Subnets->transform_address ($result->subnet,  "dotted"); }
				elseif (isset($result->ip_addr))	{ $result->ip_addr = $this->Subnets->transform_address ($result->ip_addr, "dotted"); }
		}

		# return
		return $result;
	}

	/**
	 * Validates posted keys and returns proper inset values
	 *
	 * @access private
	 * @return void
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
	 * This method removes all folders if controller is subnets
	 *
	 * @access protected
	 * @param mixed $result
	 * @return void
	 */
	protected function remove_folders ($result) {
		// must be subnets
		if($this->_params->controller!="subnets") {
			return $result;
		}
		else {
			// multiple options
			if (is_array($result)) {
				foreach($result as $k=>$r) {
					// remove
					if($r->isFolder=="1")				{ unset($r); }
			}	}
			// single item
			else {
					// remove
					if($result->isFolder=="1")			{ unset($result); }
			}
			# return
			if($result===false)	{ $this->Response->throw_exception(404, "No subnets found"); }
			else				{ return $result; }
	}	}

	/**
	 * This method removes all subnets if controller is subnets
	 *
	 * @access protected
	 * @param mixed $result
	 * @return void
	 */
	protected function remove_subnets ($result) {
		// must be subnets
		if($this->_params->controller!="folders") {
			return $result;
		}
		else {
			// multiple options
			if (is_array($result)) {
				foreach($result as $k=>$r) {
					// remove
					if($r->isFolder!="1")				{ unset($r); }
			}	}
			// single item
			else {
					// remove
					if($result->isFolder!="1")			{ unset($result); }
			}
			# return
			if($result===NULL)	{ $this->Response->throw_exception(404, "No folders found"); }
			else				{ return $result; }
	}	}




	/**
	 * Remaps keys based on request type
	 *
	 * @access protected
	 * @param mixed $result (default: null)
	 * @param mixed $controller (default: null)
	 * @return void
	 */
	protected function remap_keys ($result = null, $controller = null) {
		// define keys array
		$this->keys = array("switch"=>"deviceId", "state"=>"tag", "ip_addr"=>"ip", "dns_name"=>"hostname");

		// exceptions
		if($controller=="vlans") 	{ $this->keys['vlanId'] = "id"; }
		if($controller=="vrfs")  	{ $this->keys['vrfId'] = "id"; }
		if($this->_params->controller=="tools" && $this->_params->id=="deviceTypes")  { $this->keys['tid'] = "id"; }

		// POST / PATCH
		if ($_SERVER['REQUEST_METHOD']=="POST" || $_SERVER['REQUEST_METHOD']=="PATCH")		{ return $this->remap_update_keys (); }
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
			if(array_key_exists($v, $this->_params)) {
				// replace
				$this->_params->$k = $this->_params->$v;
				// remove
				unset($this->_params->$v);
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
			foreach($result as $k=>$v) {
				if(array_key_exists($k, $this->keys)) {
					// replace
					$key = $this->keys[$k];
					$result_remapped->$key = $v;
				}
				else {
					$result_remapped->$k = $v;
				}
			}
		}
		# array
		else {
			// loop
			foreach ($result as $m=>$r) {
				// start object
				$result_remapped = array();
				$result_remapped[$m] = new StdClass ();

				// search and replace
				foreach($r as $k=>$v) {
					if(array_key_exists($k, $this->keys)) {
						// replace
						$key_val = $this->keys[$k];
						$result_remapped[$m]->$key_val = $v;
					}
					else {
						$result_remapped[$m]->$k = $v;
					}
				}
			}
		}


		# result
		return $result_remapped;
	}

}

?>