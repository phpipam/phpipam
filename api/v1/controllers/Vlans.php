<?php

/**
 *	phpIPAM API class to work with vlans
 *
 * Reading Vlans:
 *	get by id: 		?controller=vlans&action=read&id=1
 *	get all:		?controller=vlans&action=read&all=true
 */

class Vlans
{
	/* variables */
	private $_params;
	
	/* set parameters, provided via post */
	public function __construct($params)
	{
		$this->_params = $params;
	}


	/** 
	* create vlan 
	*/
	public function createVlans()
	{
		/* not yet implemented */
		throw new Exception('Action not yet implemented');
	}	
	

	/** 
	* read vlans 
	*/
	public function readVlans()
	{
		//init Vlan class
		$vlan = new Vlan();
		
		//get also ids of belonging subnets?
		if($this->_params['subnets'])		{ $vlan->subnets = true; }
		
		//get all vlans
		if($this->_params['all'])			{ $vlan->all = true; }
		//get vlan by ID
		else 								{ $vlan->id = $this->_params['id'];	}
		
		//fetch results
		$res = $vlan->readVlan(); 
		
		//return Vlan(s) in array format
		return $res;
	}	
	
	
	/** 
	* update existing Vlan 
	*/
	public function updateVlans()
	{
		/* not yet implemented */
		throw new Exception('Action not yet implemented');
	}	
	
	
	/** 
	* delete Vlan 
	*/
	public function deleteVlans()
	{
		//init Vlan class
		$vlan = new Vlan();
		
		//provide id
		$vlan->id = $this->_params['id'];

		//fetch results
		$res = $vlan->deleteVlan(); 
		
		//return Vlan(s) in array format
		return $res;
	}
}

?>