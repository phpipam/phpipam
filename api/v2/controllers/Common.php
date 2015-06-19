<?php

/**
 *	phpIPAM API class for common functions
 *
 *
 */
class Common_functions {

	/**
	 * Initializes new Object.
	 *
	 * @access protected
	 * @param mixed $Object		// object name
	 * @param mixed $Database	// Database object
	 * @return void
	 */
	protected function init_object ($Object, $Database) {
		// admin fix
		if($Object=="Admin")	{ $this->$Object	= new $Object ($Database, false); }
		else					{ $this->$Object	= new $Object ($Database); }
		// set exit method
		$this->$Object->Result->exit_method = "exception";
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

		# array of all valid keys - fetch from SHCEMA
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
		// transform address
		if($transform_address)	{ $result = $this->transform_address ($result); }

		# return
		return $result;
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

		// reset controller for vlans subnets
		if($controller=="vlans" && @$this->_params->id2=="subnets")	{ $controller="subnets"; }

		// multiple options
		if(is_array($result)) {
			foreach($result as $k=>$r) {
				// fix for Vlans
				if($controller=="vlans")	{ $r->id = $r->vlanId; }

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
			}
		}
		// single item
		else {
				// fix for Vlans
				if($controller=="vlans")	{ $result->id = $result->vlanId; }

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
		// vlans
		elseif($controller=="vlans") {
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
				if (isset($r->subnet))				{ $r->subnet  = $this->Subnets->transform_address ($r->subnet, "dotted"); }
				elseif (isset($r->ip_addr))			{ $r->ip_addr = $this->Subnets->transform_address ($r->subnet, "dotted"); }
			}
		}
		// single item
		else {
				// remove IP
				if (isset($result->ip))				{ unset($result->ip); }
				// transform
				if (isset($result->subnet))			{ $result->subnet  = $this->Subnets->transform_address ($result->subnet, "dotted"); }
				elseif (isset($result->ip_addr))	{ $result->ip_addr = $this->Subnets->transform_address ($result->subnet, "dotted"); }
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
		foreach($this->_params as $pk=>$pv) {
			if(!in_array($pk, $this->valid_keys)) 	{ $this->Response->throw_exception(400, 'Invalid request key '.$pk); }
			// set parameters
			else {
				if(!in_array($pk, $this->controller_keys)) {
					 $values[$pk] = $pv;
				}
			}
		}
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
			if(!in_array($key, array("app_id", "controller"))) {
													{ $this->Response->throw_exception(400, 'Invalid request key parameter '.$key); }
			}
		}
	}
}

?>