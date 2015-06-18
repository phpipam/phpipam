<?php

/**
 *	phpIPAM API class to work with subnets
 *
 * Reading subnets:
 *	get by id: 		?controller=subnets&action=read&id=1
 *	get by name: 	?controller=subnets&action=read&name=subnet name
 *	get all:		?controller=subnets&action=read&all=true
 */

class Subnets
{
	/* variables */
	private $_params;
	
	/* set parameters, provided via post */
	public function __construct($params)
	{
		$this->_params = $params;
		
		//ip address format, can be decimal or ip
		if(!$this->_params['format'])			  { $this->_params['format'] = "decimal"; }
		//verify IP address format
		if(!($this->_params['format']=="decimal" || $this->_params['format']== "ip")) {
			throw new Exception('Invalid format');
		}
	}


	/** 
	* create subnet 
	*/
	public function createSubnets()
	{
		/* not yet implemented */
		throw new Exception('Action not yet implemented');
	}	
	

	/** 
	* read subnets 
	*/
	public function readSubnets()
	{
		//init subnet class
		$subnet = new Subnet();
		
		//set IP address format
		$subnet->format = $this->_params['format'];
		
		//get all subnets
		if($this->_params['all'])			{ $subnet->all = true; }
		//get all subnets in subnet
		elseif($this->_params['sectionId']) { $subnet->sectionId = $this->_params['sectionId']; }
		//get subnet by ID
		else 								{ $subnet->id = $this->_params['id'];	}
		
		//fetch results
		$res = $subnet->readSubnet(); 
		
		//return subnet(s) in array format
		return $res;
	}	
	
	
	/** 
	* update existing subnet 
	*/
	public function updateSubnets()
	{
		/* not yet implemented */
		throw new Exception('Action not yet implemented');
	}	
	
	
	/** 
	* delete subnet 
	*/
	public function deleteSubnets()
	{
		//init subnet class
		$subnet = new Subnet();
		
		//provide id
		$subnet->id = $this->_params['id'];
		//delete also IPs?
		if(isset($this->_params['addresses'])) {
			if($this->_params['addresses'] == false) { $subnet->addresses = false; }
			else									 { $subnet->addresses = true; }
		} else {
													 { $subnet->addresses = true; }
		}		
		//fetch results
		$res = $subnet->deleteSubnet(); 
		
		//return subnet(s) in array format
		return $res;
	}
}

?>