<?php

/**
 *	phpIPAM API class to work with VLANS
 *
 *
 */

class Vlans_controller extends Common_api_functions {

	/**
	 * settings
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $settings;


	/**
	 * __construct function
	 *
	 * @access public
	 * @param PDO_Database $Database
	 * @param Tools $Tools
	 * @param API_params $params
	 * @param Response $response
	 */
	public function __construct($Database, $Tools, $params, $Response) {
		$this->Database = $Database;
		$this->Tools 	= $Tools;
		$this->_params 	= $params;
		$this->Response = $Response;
		// init required objects
		$this->init_object ("Admin", $Database);
		$this->init_object ("Subnets", $Database);
		// set valid keys
		$this->set_valid_keys ("vlans");
	}






	/**
	 * Returns json encoded options
	 *
	 * @access public
	 * @return void
	 */
	public function OPTIONS () {
		// validate
		$this->validate_options_request ();

		// methods
		$result['methods'] = array(
								array("href"=>"/api/".$this->_params->app_id."/vlans/", 		"methods"=>array(array("rel"=>"options", "method"=>"OPTIONS"))),
								array("href"=>"/api/".$this->_params->app_id."/vlans/{id}/", 	"methods"=>array(array("rel"=>"read", 	"method"=>"GET"),
																												 array("rel"=>"create", "method"=>"POST"),
																												 array("rel"=>"update", "method"=>"PATCH"),
																												 array("rel"=>"delete", "method"=>"DELETE"))),
							);
		# result
		return array("code"=>200, "data"=>$result);
	}





	/**
	 * Read vlan/domain functions
	 *
	 * parameters:
	 *      - /                             returns all vlans
	 *		- /{id}/                        returns vlan details
	 *		- /{id}/subnets/				returns subnets belonging to this VLAN
	 *		- /{id}/subnets/{sectionId}/	returns subnets belonging to this VLAN inside one section
	 *		- /custom_fields/			    returns custom fields
	 *		- /search/{number}/			    returns all vlans with specified number
	 *      - /all/                         returns all vlans
	 *
	 * @access public
	 * @return void
	 */
	public function GET () {
		// all
		if (!isset($this->_params->id) || $this->_params->id == "all") {
			$result = $this->Tools->fetch_all_objects ("vlans", 'vlanId');
			// check result
			if($result===false)						{ $this->Response->throw_exception(404, 'No vlans configured'); }
			else									{ return array("code"=>200, "data"=>$this->prepare_result ($result, null, true, true)); }
		}
		// check weather to read belonging subnets
		elseif(@$this->_params->id2=="subnets") {
			// first validate
			$this->validate_vlan ();
			// save result
			$result = $this->Tools->fetch_multiple_objects ("subnets", "vlanId", $this->_params->id, 'id', true);

			// only 1 section ?
			if(isset($this->_params->id3)) {
				if($result!=NULL) {
					foreach ($result as $k=>$r) {
						if($r->sectionId!=$this->_params->id3) {
							unset($result[$k]);
						}
					}
				}
			}

			// add gateway
			if($result!=NULL) {
				foreach ($result as $k=>$r) {
            		$gateway = $this->read_subnet_gateway ($r->id);
            		if ( $gateway!== false) {
                		$result[$k]->gatewayId = $gateway->id;
            		}
				}
			}

			// check result
			if($result==NULL)						{ $this->Response->throw_exception(404, "No subnets found"); }
			else {
				$this->custom_fields = $this->Tools->fetch_custom_fields('subnets');
				return array("code"=>200, "data"=>$this->prepare_result ($result, "subnets", true, true));
			}
		}
		// custom fields
		elseif (@$this->_params->id=="custom_fields") {
			// check result
			if(sizeof($this->custom_fields)==0)		{ $this->Response->throw_exception(404, 'No custom fields defined'); }
			else									{ return array("code"=>200, "data"=>$this->custom_fields); }
		}
		// search
		elseif (@$this->_params->id=="search") {
			$result = $this->Tools->fetch_multiple_objects ("vlans", "number", $this->_params->id2, "vlanId");
			// check result
			if($result==NULL)						{ $this->Response->throw_exception(404, "Vlans not found"); }
			else									{ return array("code"=>200, "data"=>$this->prepare_result ($result, null, true, true)); }
		}
		// read vlan details
		else {
			$result = $this->Tools->fetch_object ("vlans", "vlanId", $this->_params->id);
			// check result
			if($result==NULL)						{ $this->Response->throw_exception(404, "Vlan not found"); }
			else									{ return array("code"=>200, "data"=>$this->prepare_result ($result, null, true, true)); }
		}
	}





	/**
	 * Creates new vlan
	 *
	 * @access public
	 * @return void
	 */
	public function POST () {
		# check for valid keys
		$values = $this->validate_keys ();

		# verify or set domain
		$this->validate_domain ();

		# validate input
		$this->validate_vlan_edit ();

		# execute update
		if(!$this->Admin->object_modify ("vlans", "add", "vlanId", $values))
													{ $this->Response->throw_exception(500, "Vlan creation failed"); }
		else {
			//set result
			return array("code"=>201, "message"=>"Vlan created", "id"=>$this->Admin->lastId, "location"=>"/api/".$this->_params->app_id."/vlans/".$this->Admin->lastId."/");
		}
	}





	/**
	 * Updates existing vlan/domain
	 *
	 * @access public
	 * @return void
	 */
	public function PATCH () {
		# verify
		$this->validate_vlan_edit ();
		# check that it exists
		$this->validate_vlan ();

		# rewrite id
		$this->_params->vlanId = $this->_params->id;
		unset($this->_params->id);

		# validate and prepare keys
		$values = $this->validate_keys ();

		# execute update
		if(!$this->Admin->object_modify ("vlans", "edit", "vlanId", $values))
													{ $this->Response->throw_exception(500, "Vlan edit failed"); }
		else {
			//set result
			return array("code"=>200, "message"=>"Vlan updated");
		}
	}







	/**
	 * Deletes existing vlan
	 *
	 * @access public
	 * @return void
	 */
	public function DELETE () {
		# verify
		$this->validate_vlan ();

		# set variables for update
		$values = array();
		$values["vlanId"] = $this->_params->id;

		# execute delete
		if(!$this->Admin->object_modify ("vlans", "delete", "vlanId", $values))
													{ $this->Response->throw_exception(500, "Vlan delete failed"); }
		else {
			// delete all references
			$this->Admin->remove_object_references ("subnets", "vlanId", $this->_params->id);

			// set result
			return array("code"=>200, "message"=>"Vlan deleted");
		}
	}









	/* @validations ---------- */


	/**
	 * Validates Vlan - checks if it exists
	 *
	 * @access private
	 * @return void
	 */
	private function validate_vlan () {
		// validate id
		if(!isset($this->_params->id))														{ $this->Response->throw_exception(400, "Vlan Id is required");  }
		// validate number
		if(!is_numeric($this->_params->id))													{ $this->Response->throw_exception(400, "Vlan Id must be numeric"); }
		// check that it exists
		if($this->Tools->fetch_object ("vlans", "vlanId", $this->_params->id) === false )	{ $this->Response->throw_exception(404, "Invalid Vlan id"); }
	}


	/**
	 * Validates VLAN on add and edit
	 *
	 * @access private
	 * @return void
	 */
	private function validate_vlan_edit () {
		# get settings
		$this->settings = $this->Admin->get_settings();

		# Check vlan number
		if ( $this->_params->number > $this->settings->vlanMax )
			$this->Response->throw_exception(500, _('Highest possible VLAN number is ').$this->settings->vlanMax.'!');

		//if it already exist die
		if($this->settings->vlanDuplicate==0 && $_SERVER['REQUEST_METHOD']=="POST") {
			$check_vlan = $this->Admin->fetch_multiple_objects ("vlans", "domainId", $this->_params->domainId, "vlanId");
			if($check_vlan!==false) {
				foreach($check_vlan as $v) {
					if($v->number == $this->_params->number) {
																							{ $this->Response->throw_exception(409, "Vlan already exists"); }
					}
				}
			}
		}

		//if number too high
		if($this->_params->number>$this->settings->vlanMax && $_SERVER['REQUEST_METHOD']!="DELETE")
																							{ $this->Response->throw_exception(409, 'Highest possible VLAN number is '.$this->settings->vlanMax.'!'); }
		if($_SERVER['REQUEST_METHOD']=="POST") {
			if($this->_params->number<0)													{ $this->Response->throw_exception(400, "Vlan number cannot be negative"); }
			elseif(!is_numeric($this->_params->number))										{ $this->Response->throw_exception(400, "Vlan number must be number"); }
		}
		if(is_blank($this->_params->name))													{ $this->Response->throw_exception(400, "Vlan name is required"); }
	}

	/**
	 * Validates domains
	 *
	 * @access private
	 * @return void
	 */
	private function validate_domain () {
		// validate id
		if(!isset($this->_params->domainId))												{ $this->_params->domainId = 1; }
		// validate number
		if(!is_numeric($this->_params->domainId))											{ $this->Response->throw_exception(400, "Domain id must be numeric"); }
		// check that it exists
		if($this->Tools->fetch_object ("vlanDomains", "id", $this->_params->domainId) === false )
																							{ $this->Response->throw_exception(400, "Invalid domain id"); }
	}
}
