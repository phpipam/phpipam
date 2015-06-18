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
		$this->$Object	= new $Object ($Database);
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
		if($links) { $result = $this->add_links ($result, $controller); }
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
		// multiple options
		if(is_array($result)) {
			foreach($result as $k=>$r) {
				$result[$k]->links = new stdClass ();
				$result[$k]->links->rel  = "self";
				$result[$k]->links->href = "/api/".$this->_params->app_id."/$controller/".$r->id."/";
			}
		}
		// single item
		else {
				$result->links = new stdClass ();
				$result->links->rel  = "self";
				$result->links->href = "/api/".$this->_params->app_id."/$controller/".$result->id."/";
		}
		# return
		return $result;
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
			if(!in_array($pk, $this->valid_keys)) 		{ $this->Exceptions->throw_exception(400, 'Invalid request key '.$pk); }
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
}

?>