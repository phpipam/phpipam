<?php

/**
 *	phpIPAM class with common functions
 */

class Common_functions  {

	/**
	 * __construct function
	 *
	 * @access public
	 * @return void
	 */
	public function __construct () {
	}

	/**
	 * Strip tags from array or field to protect from XSS
	 *
	 * @access public
	 * @param mixed $input
	 * @return void
	 */
	public function strip_input_tags ($input) {
		if(is_array($input)) {
			foreach($input as $k=>$v) { $input[$k] = strip_tags($v); }
		}
		else {
			$input = strip_tags($input);
		}
		# stripped
		return $input;
	}

	/**
	 * Changes empty array fields to specified character
	 *
	 * @access public
	 * @param array $fields
	 * @param string $char (default: "/")
	 * @return array
	 */
	public function reformat_empty_array_fields ($fields, $char = "/") {
		foreach($fields as $k=>$v) {
			if(is_null($v) || strlen($v)==0) {
				$out[$k] = 	$char;
			} else {
				$out[$k] = $v;
			}
		}
		# result
		return $out;
	}

	/**
	 * Removes empty array fields
	 *
	 * @access public
	 * @param mixed $fields
	 * @return void
	 */
	public function remove_empty_array_fields ($fields) {
		foreach($fields as $k=>$v) {
			if(is_null($v) || strlen($v)==0) {
			} else {
				$out[$k] = $v;
			}
		}
		# result
		return $out;
	}

	/**
	 * Function to verify checkbox if 0 length
	 *
	 * @access public
	 * @param mixed $field
	 * @return void
	 */
	public function verify_checkbox ($field) {
		return @$field==""||strlen(@$field)==0 ? 0 : $field;
	}

	/**
	 * identify ip address type - ipv4 or ipv6
	 *
	 * @access public
	 * @param mixed $address
	 * @return mixed IP version
	 */
	public function identify_address ($address) {
	    # dotted representation
	    if (strpos($address, ":")) 		{ return 'IPv6'; }
	    elseif (strpos($address, ".")) 	{ return 'IPv4'; }
	    # decimal representation
	    else  {
	        # IPv4 address
	        if(strlen($address) < 12) 	{ return 'IPv4'; }
	        # IPv6 address
	    	else 						{ return 'IPv6'; }
	    }
	}

}

?>
