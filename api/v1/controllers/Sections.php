<?php

/**
 *	phpIPAM API class to work with sections
 *
 * Reading sections:
 *	get by id: 		?controller=sections&action=read&id=1
 *	get by name: 	?controller=sections&action=read&name=Section name
 *	get all:		?controller=sections&action=read&all=true
 */

class Sections
{
	/* variables */
	private $_params;


	/* set parameters, provided via post */
	public function __construct($params)
	{
	  $this->_params = $params;
	}


	/** 
	* create new section 
	*/
	public function createSections()
	{
		/* not yet implemented */
		throw new Exception('Action not yet implemented');	
	}


	/** 
	* read sections 
	*/
	public function readSections()
	{
		//init section class
		$section = new Section();
		
		//get all sections?
		if($this->_params['all'])		{ $section->all	 = true; }
		//get section by name
		elseif($this->_params['name']) 	{ $section->name = $this->_params['name']; }
		//get section by ID
		else 							{ $section->id 	 = $this->_params['id'];	}
		
		//fetch results
		$res = $section->readSection(); 
		//return section(s) in array format
		return $res;
	}	
	
	
	/** 
	* update existing section 
	*/
	public function updateSections()
	{
		/* not yet implemented */
		throw new Exception('Action not yet implemented');	
	}	
	
	
	/** 
	* delete section 
	*/
	public function deleteSections()
	{
		//init section class
		$section = new Section();
		//required parameters
		$section->id        	= $this->_params['id'];

		//delete also IPs and subnets?
		if(isset($this->_params['subnets'])) {
			if($this->_params['subnets'] == false) 			{ $section->subnets = false; }
			else											{ $section->subnets = true; }
		} else {
															{ $section->subnets = true; }
		}		
		//delete also addresses?
		if($section->subnets == true) {
			if(isset($this->_params['addresses'])) {
				if($this->_params['addresses'] == false) 	{ $section->addresses = false; }
				else										{ $section->addresses = true; }
			} else {
															{ $section->addresses = true; }
			}				
		}

		//delete section
		$res = $section->deleteSection(); 	
		//return result
		return $res;		
	}
}

?>