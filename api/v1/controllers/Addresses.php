<?php

/**
 *	phpIPAM API class to work with IP addresses
 *
 * Reading IPs:
 *	all in subnetId: 	?controller=addresses&action=read&subnetId=1
 *	get by id: 			?controller=addresses&action=read&id=123
 */

class Addresses
{
	/* variables */
	private $_params;


	/* set parameters, provided via post */
	public function __construct($params)
	{
	  $this->_params = $params;
	}

	/** 
	* read addresses 
	*/
	public function readAddresses()
	{
		# init section class
		$address = new Address ();
		
		# set method
		if(isset($this->_params['format'])) { $address->format = $this->_params['format']; }
		
		# get all ips in subnet?
		if($this->_params['subnetId'])	{ $address->subnetId = $this->_params['subnetId']; }
		# get ip by Id
		elseif($this->_params['id']) 	{ $address->id = $this->_params['id']; }
		# false
		else 							{  }
		
		# fetch results
		$res = $address->readAddress(); 
		# return result
		return $res;
	}


	/** 
	* delete addresses 
	*/
	public function deleteAddresses()
	{
		# init section class
		$address = new Address ();
		# set Id
		if($this->_params['id']) 		{ $address->id = $this->_params['id']; }
		
		# fetch results
		$res = $address->deleteAddress(); 
		# return result
		return $res;
	}

}

?>