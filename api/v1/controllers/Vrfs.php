<?php

/**
 *	phpIPAM API class to work with Vrfs
 *
 * Reading Vrfs:
 *	get by id: 		?controller=vrfs&action=read&id=1
 *	get all:		?controller=vrfs&action=read&all=true
 */

class Vrfs
{
	/* variables */
	private $_params;
	
	/* set parameters, provided via post */
	public function __construct($params)
	{
		$this->_params = $params;
	}


	/** 
	* create Vrf 
	*/
	public function createVrfs()
	{
		/* not yet implemented */
		throw new Exception('Action not yet implemented');
	}	
	

	/** 
	* read Vrfs 
	*/
	public function readVrfs()
	{
		//init Vrf class
		$vrf = new Vrf();
		
		//get also ids of belonging subnets?
		if($this->_params['subnets'])		{ $vrf->subnets = true; }
		
		//get all Vrfs
		if($this->_params['all'])			{ $vrf->all = true; }
		//get Vrf by ID
		else 								{ $vrf->id = $this->_params['id'];	}
		
		//fetch results
		$res = $vrf->readVrf(); 
		
		//return Vrf(s) in array format
		return $res;
	}	
	
	
	/** 
	* update existing Vrf 
	*/
	public function updateVrfs()
	{
		/* not yet implemented */
		throw new Exception('Action not yet implemented');
	}	
	
	
	/** 
	* delete Vrf 
	*/
	public function deleteVrfs()
	{
		//init Vrf class
		$vrf = new Vrf();
		
		//provide id
		$vrf->id = $this->_params['id'];

		//fetch results
		$res = $vrf->deleteVrf(); 
		
		//return Vrf(s) in array format
		return $res;
	}
}

?>