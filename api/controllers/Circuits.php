<?php

/**
 *	phpIPAM API class to work with Circuits and Circuit providers
 *
 *
 */
class Circuits_controller extends Common_api_functions {

	/**
	 * Request type - circuits or circuitProviders
	 *
	 * @var string
	 */
	protected $type = "circuits";

	/**
	 * Request type text for result - circuits or providers
	 *
	 * @var string
	 */
	protected $type_text = "circuit";


	/**
	 * __construct function
	 *
	 * @access public
	 * @param class $Database
	 * @param class $Tools
	 * @param mixed $params		// post/get values
	 * @param class $Response
	 */
	public function __construct($Database, $Tools, $params, $Response) {
		$this->Database = $Database;
		$this->Tools 	= $Tools;
		$this->Response = $Response;
		// init required objects
		$this->init_object ("Admin", $Database);
		// reset parameters
		if($params->id=="providers") {
			$this->rewrite_controller_params ((array) $params);
			// set valid keys
			$this->set_valid_keys ("circuitProviders");
			// set type
			$this->type = "circuitProviders";
			$this->type_text = "provider";
		}
		else {
			// set params
			$this->_params  = $params;
			// set valid keys
			$this->set_valid_keys ("circuits");
		}
	}

	/**
	 * Rewrite request parameters if circuit providers controller is requested
	 *
	 * @method rewrite_controller_params
	 *
	 * @param  array $params
	 *
	 * @return void
	 */
	private function rewrite_controller_params ($params) {
		// change parameters
		$this->_params = new StdClass ();
		unset($params['id']);
		if(isset($params['id2'])) 	{ $this->_params->id  = $params['id2'];	unset($params['id2']); }
		if(isset($params['id3'])) 	{ $this->_params->id2 = $params['id3']; unset($params['id3']); }
		if(isset($params['id4'])) 	{ $this->_params->id3 = $params['id4']; unset($params['id4']);  }
		// post, get etc
		if(sizeof($params)>0) {
			foreach ($params as $k=>$p) {
				$this->_params->{$k} = $p;
			}
		}
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
								array("href"=>"/api/".$this->_params->app_id."/circuits/", 					"methods"=>array(array("rel"=>"options", "method"=>"OPTIONS"))),
								array("href"=>"/api/".$this->_params->app_id."/circuits/{id}/", 			"methods"=>array(array("rel"=>"read", 	"method"=>"GET"),
																												 			 array("rel"=>"create", "method"=>"POST"),
																												 			 array("rel"=>"update", "method"=>"PATCH"),
																												 			 array("rel"=>"delete", "method"=>"DELETE"))),
								array("href"=>"/api/".$this->_params->app_id."/circuits/providers/", 		"methods"=>array(array("rel"=>"options", "method"=>"OPTIONS"))),
								array("href"=>"/api/".$this->_params->app_id."/circuits/providers/{id}/", 	"methods"=>array(array("rel"=>"read", 	"method"=>"GET"),
																												 			 array("rel"=>"create", "method"=>"POST"),
																												 			 array("rel"=>"update", "method"=>"PATCH"),
																												 			 array("rel"=>"delete", "method"=>"DELETE"))),
							);
		# result
		return array("code"=>200, "data"=>$result);
	}





	/**
	 * Read circuits/providers functions
	 *
	 * parameters (circuits):
	 * 		- / 							returns all circuits
	 *		- /{id}/                        returns circuit details
	 *		- /id/{id}/                     returns circuit details by cid
	 *		- /circuit_id/{id}/             returns circuit details by cid
	 * 		- /all/							returns all circuits
	 *
	 *
	 * parameters (providers):
	 * 		- /providers/ 					returns all providers
	 * 		- /providers/{id}/				returns providers details
	 * 		- /providers/{id}/circuits/		returns all circuits belonging to provider
	 *
	 * @access public
	 * @return void|array
	 */
	public function GET () {
		// all
		if (!isset($this->_params->id) || $this->_params->id == "all") {
			$result = $this->Tools->fetch_all_objects ($this->type, 'id');
			// check result
			if($result===false)						{ $this->Response->throw_exception(200, "No {$this->type_text}s configured"); }
			else									{ return array("code"=>200, "data"=>$this->prepare_result ($result, null, true, true)); }
		}
		// provider circuits
		elseif($this->type==="circuitProviders" && isset($this->_params->id2)) {
			if ($this->_params->id2=="circuits") {
				$result = $this->Tools->fetch_multiple_objects ("circuits", "provider", $this->_params->id);
				// check result
				if($result==NULL)						{ $this->Response->throw_exception(200, "No circuits belonging to provider"); }
				else									{ return array("code"=>200, "data"=>$this->prepare_result ($result, null, true, true)); }
			}
			else {
				$this->Response->throw_exception(400, "Invalid API query");
			}
		}
		// circuit by cid
		elseif($this->type==="circuits" && ($this->_params->id=="circuit_id" || $this->_params->id=="id")) {
			$result = $this->Tools->fetch_object ($this->type, "cid", $this->_params->id2);
			// check result
			if($result==NULL)						{ $this->Response->throw_exception(200, "{$this->type_text} not found"); }
			else									{ return array("code"=>200, "data"=>$this->prepare_result ($result, null, true, true)); }
		}
		// read details
		else {
			// numeric check
			if(!is_numeric($this->_params->id))		{ $this->Response->throw_exception(400, "Invalid ID"); }
			// fetch
			$result = $this->Tools->fetch_object ($this->type, "id", $this->_params->id);
			// check result
			if($result==NULL)						{ $this->Response->throw_exception(200, "{$this->type_text} not found"); }
			else									{ return array("code"=>200, "data"=>$this->prepare_result ($result, null, true, true)); }
		}
	}





	/**
	 * Creates new provider / circuit
	 *
	 * /circuits/
	 * /circuits/providers/
	 *
	 * @access public
	 * @return void
	 */
	public function POST () {
		# remap keys
		$this->remap_keys ();
		# validate id
		$this->validate_id ("add");
		# validate POST input parameters
		$this->validate_object ("add");
		# check for valid keys
		$values = $this->validate_keys ();
		# validate that something is present
		$this->validate_values ($values);

		# execute update
		if(!$this->Admin->object_modify ($this->type, "add", "id", $values))
													{ $this->Response->throw_exception(500, "{$this->type_text} creation failed"); }
		else {
			//set result
			if($this->type=="circuits") {
				return array("code"=>201, "message"=>"{$this->type_text} created", "id"=>$this->Admin->lastId, "location"=>"/api/".$this->_params->app_id."/circuits/".$this->Admin->lastId."/");
			}
			else {
				return array("code"=>201, "message"=>"{$this->type_text} created", "id"=>$this->Admin->lastId, "location"=>"/api/".$this->_params->app_id."/circuits/providers/".$this->Admin->lastId."/");
			}
		}
	}





	/**
	 * Updates Circuit of Provider
	 *
	 * /circuits/{id}/
	 * /circuits/providers/{id}/
	 *
	 * @method PATCH
	 *
	 * @return void|array
	 */
	public function PATCH () {
		# remap keys
		$this->remap_keys ();
		# validate id
		$this->validate_id ("edit");
		# fetch object
		$old_object = $this->Tools->fetch_object ($this->type, "id", $this->_params->id);
		# validate PATCH input parameters
		$this->validate_object ("edit", $old_object);
		# check for valid keys
		$values = $this->validate_keys ();
		# validate that something is present
		$this->validate_values ($values);

		# execute update
		if(!$this->Admin->object_modify ($this->type, "edit", "id", $values))
													{ $this->Response->throw_exception(500, "{$this->type_text} edit failed"); }
		else {
			//set result
			return array("code"=>200, "message"=>"{$this->type_text} updated");
		}
	}




	/**
	 * Deletes provider or circuit
	 *
	 * /circuits/{id}/
	 * /circuits/providers/{id}/
	 *
	 * @method DELETE
	 *
	 * @return void|array
	 */
	public function DELETE () {
		# verify
		$this->validate_id ("delete");

		# set variables for delete
		$values = array();
		$values["id"] = $this->_params->id;
		# validate that something is present
		$this->validate_values ($values);

		# execute delete
		if(!$this->Admin->object_modify ($this->type, "delete", "id", $values))
													{ $this->Response->throw_exception(500, "{$this->type_text} delete failed"); }
		else {
			// delete all references
			if($this->type=="circuitProvider") {
				$this->Admin->remove_object_references ("circuits", "id", $this->_params->id);
			}
			// set result
			return array("code"=>200, "message"=>"{$this->type_text} deleted");
		}
	}



	/* @validations ---------- */

	/**
	 * Make sure any values are provided
	 *
	 * @method validate_values
	 *
	 * @param  array $values
	 *
	 * @return void
	 */
	private function validate_values ($values) {
		if (sizeof($values)==0)		{ $this->Response->throw_exception(409, "No values present"); }
	}

	/**
	 * Validate provided parameters
	 *
	 * @method validate_object
	 *
	 * @param  string $action
	 * @param  object $old_object
	 *
	 * @return void
	 */
	private function validate_object ($action="edit", $old_object = null) {
		# circuits validation
		if($this->type=="circuits") {
			// chech that id doesnt already exist
			$this->validate_cid ($action, $old_object);
			// validate provider
			$this->validate_circuit_provider ($action);
			// validate type
			$this->validate_circuit_type ($action);
			// validate type
			$this->validate_circuit_status ($action);
			// validate device and location
			$this->validate_circuit_devices_locations ($action);
		}
		# providers
		else {
			$this->validate_provider_name ($action);
		}
	}

	/**
	 * Make sure provided ID is correct
	 *
	 * @method validate_id
	 *
	 * @param  string $action
	 *
	 * @return void
	 */
	private function validate_id ($action = "add") {
		// not for add
		if($action!=="add") {
			// validate id
			if(!isset($this->_params->id))													{ $this->Response->throw_exception(400, "{$this->type_text} id is required");  }
			// validate number
			if(!is_numeric($this->_params->id))												{ $this->Response->throw_exception(400, "{$this->type_text} id must be numeric"); }
		}
		// for delete
		if($action=="delete" || $action=="edit") {
			if($this->Tools->fetch_object($this->type,"id", $this->_params->id)===false)	{ $this->Response->throw_exception(404, "Nonexisting {$this->type_text} id"); }
		}
	}

	/**
	 * Validate provided provider name
	 *
	 * @method validate_provider_name
	 *
	 * @param  atring $action
	 *
	 * @return void
	 */
	private function validate_provider_name ($action) {
		if($action=="add") {
			if (!isset($this->_params->name)) 		 { $this->Response->throw_exception(404, "Name is mandatory"); }
			elseif (strlen($this->_params->name)==0) { $this->Response->throw_exception(404, "Name is mandatory"); }
		}
	}

	/**
	 * Validate circiuit provider for circuit object
	 *
	 * @method validate_circuit_provider
	 *
	 * @param  string $action
	 *
	 * @return void
	 */
	private function validate_circuit_provider ($action = "add") {
		if($action=="add") {
			if($this->Tools->fetch_object("circuitProviders","id", $this->_params->provider)===false)		{ $this->Response->throw_exception(400, "Invalid circuit provider"); }
		}
		elseif($action=="edit" && isset($this->_params->provider)) {
			if($this->Tools->fetch_object("circuitProviders","id", $this->_params->provider)===false)		{ $this->Response->throw_exception(400, "Invalid circuit provider"); }
		}
	}

	/**
	 * Validate circuit_id
	 *
	 * @method validate_cid
	 *
	 * @param  string $action
	 * @param  object|null $old_object
	 *
	 * @return void
	 */
	private function validate_cid ($action, $old_object) {
		// edit
		if ($old_object->cid!==null) {
			if (isset($this->_params->cid)) {
				if ($old_object->cid!=$this->_params->cid) {
					if($this->Tools->fetch_object ($this->type, "cid", $this->_params->cid)){ $this->Response->throw_exception(409, "Circuit ID already exists"); }
				}
			}
		}
		// add
		else {
			// mandatory
			if (!isset($this->_params->cid))												{ $this->Response->throw_exception(400, "Circuit ID is mandatory"); }
			// check
			elseif ($this->Tools->fetch_object ($this->type, "cid", $this->_params->cid))	{ $this->Response->throw_exception(409, "Circuit ID already exists"); }
		}
	}

	/**
	 * Validate circuit type
	 *
	 * @method validate_circuit_type
	 *
	 * @param  string $action
	 *
	 * @return void
	 */
	private function validate_circuit_type ($action="add") {
		if(isset($this->_params->type)) {
			$type_desc = $this->Database->getFieldInfo ("circuits", "type");
			$all_types = explode(",", str_replace(array("enum","(",")","'"), "",$type_desc->Type));
			if(!in_array($this->_params->type, $all_types))									{ $this->Response->throw_exception(400, "Invalid circuit type"); }
		}
		else {
			if ($action=="add") {
				$this->_params->type = "Default";
			}
		}
	}

	/**
	 * Validate circuit status
	 *
	 * @method validate_circuit_status
	 *
	 * @param  string $action
	 *
	 * @return void
	 */
	private function validate_circuit_status ($action="add") {
		if (isset($this->_params->status)) {
			$statuses = array ("Active", "Inactive", "Reserved");
			if(!in_array($this->_params->status, $statuses))								{ $this->Response->throw_exception(400, _("Invalid status")); }
		}
		else {
			if ($action=="add") {
				$this->_params->status = "Active";
			}
		}
	}

	/**
	 * Validate circuit devide and location
	 *
	 * @method validate_circuit_devices_locations
	 *
	 * @param  string $action
	 *
	 * @return void
	 */
	private function validate_circuit_devices_locations ($action="add") {
		// check
		for($m=1; $m<3; $m++) {
			if(!isset($this->_params->{device.$m}) && !isset($this->_params->{location.$m})) {
				if($action=="add") {
					$this->_params->{device.$m}   = 0;
					$this->_params->{location.$m} = 0;
				}
			}
			else {
				if ($this->_params->{device.$m}!==null) {
					if($this->Tools->fetch_object("devices","id",$this->_params->{device.$m})===false) 	{ $this->Response->throw_exception(400, "Invalid device $m"); }
					$this->_params->{location.$m} = 0;
				}
				else {
					if($this->Tools->fetch_object("locations","id",$this->_params->{location.$m})===false) 	{ $this->Response->throw_exception(400, "Invalid location $m"); }
					$this->_params->{device.$m} = 0;
				}
			}
		}
	}
}