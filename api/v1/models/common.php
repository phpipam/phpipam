<?php

/**
 *	phpIPAM Common functions
 */

class Common
{	
	/**
	 *	Sanitize
	 */
	public function check_input () 
	{
		# id
		if($this->id) {
			//id must be set and numberic
			if ( is_null($this->id) || !is_numeric($this->id) ) 			{ throw new Exception('Invalid address Id - '.$this->id); }	
		}
		# subnetId
		elseif($this->subnetId) {
			//id must be set and numberic
			if ( is_null($this->subnetId) || !is_numeric($this->subnetId) ) { throw new Exception('Invalid Subnet Id - '.$this->subnetId); }	
		}
		# name
		elseif($this->name) {
			if ( is_null($this->name) || strlen($this->name)==0 ) 			{ throw new Exception('Invalid section name - '.$this->name); }
		}
	}
	
	
	/**
	 *	Check vars
	 *
	 *		type = int, text (extra = length)
	 */
	public function check_var ($type, $var, $extra = null)
	{
		# int
		if($type=="int") {
			if(!is_numeric($var))											{ throw new Exception('Invalid integer'); }
		}
	}
	
	
	/**
	 *	Format IP address
	 */
	public function format_ip ($result)
	{
		if(sizeof($result)>0)	{
			foreach($result as $r) {
				# ip?
				if(isset($r['ip_addr'])) {
					$r['ip_addr'] = transform2long($r['ip_addr']);
				}
				# subnet
				elseif(isset($r['subnet'])) {
					$r['subnet'] = transform2long($r['subnet']);
				}
				$out[] = $r;
			}	
		}
		else {
			$out = $result;
		}
		# formatted
		return $out;
	}
	

	/**
	* function to return multidimensional array
	*/
	public function toArray($obj)
	{
		//if object create array
		if(is_object($obj)) $obj = (array) $obj;
		if(is_array($obj)) {
			$arr = array();
			foreach($obj as $key => $val) {
				$arr[$key] = $this->toArray($val);
			}
		}
		else { 
			$arr = $obj;
		}
		//return an array of items
		return $arr;
	}
}
