<?php

/**
 *	phpIPAM API class to work with Logical Circuits
 *
 *
 */
class Circuitslogical_controller extends Common_api_functions {

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
		$this->_params  = $params;
		$this->Response = $Response;
		// init required objects
		$this->init_object ("Admin", $Database);
		// set valid keys
		$this->set_valid_keys ("circuitsLogical");
	}





	/**
	 * Returns json encoded options
	 *
	 * @access public
	 * @return void
	 */
	#[\Override]
    public function OPTIONS () {
		// validate
		$this->validate_options_request ();

		// methods
		$result['methods'] = [
								["href"=>"/api/".$this->_params->app_id."/circuitsLogical/", 				"methods"=>[["rel"=>"options", "method"=>"OPTIONS"]]],
								["href"=>"/api/".$this->_params->app_id."/circuitsLogical/{id}/", 			"methods"=>[["rel"=>"read", 	"method"=>"GET"],
																												 ["rel"=>"create", "method"=>"POST"],
																												 ["rel"=>"update", "method"=>"PATCH"],
																												 ["rel"=>"delete", "method"=>"DELETE"]]],
								["href"=>"/api/".$this->_params->app_id."/circuitsLogical/{id}/circuits/", 	"methods"=>[["rel"=>"read", 	"method"=>"GET"],
																												 ["rel"=>"update", "method"=>"PATCH"]]],
							];
		# result
		return ["code"=>200, "data"=>$result];
	}





	/**
	 * Read logical circuit functions
	 *
	 * parameters:
	 * 		- / 							returns all logical circuits
	 *		- /{id}/                        returns logical circuit details
	 * 		- /all/							returns all logical circuits
	 *		- /{id}/circuits/				returns member (physical) circuits, in order
	 *
	 * @access public
	 * @return void|array
	 */
	#[\Override]
    public function GET () {
		// all
		if (!isset($this->_params->id) || $this->_params->id == "all") {
			$result = $this->Tools->fetch_all_objects ("circuitsLogical", "id");
			// check result
			if($result===false)						{ $this->Response->throw_exception(404, "No logical circuits configured"); }
			else									{ return ["code"=>200, "data"=>$this->prepare_result ($result, null, true, false)]; }
		}
		// members of logical circuit
		elseif (isset($this->_params->id2) && $this->_params->id2=="circuits") {
			// validate that logical circuit exists
			$this->validate_id ("edit");
			// fetch members, in mapping order
			$result = $this->Tools->fetch_all_logical_circuit_members ($this->_params->id);
			// check result
			if($result===false)						{ $this->Response->throw_exception(404, "No circuits belong to this logical circuit"); }
			else									{ return ["code"=>200, "data"=>$this->prepare_result ($result, "circuits", true, true)]; }
		}
		// read details
		else {
			// numeric check
			if(!is_numeric($this->_params->id))		{ $this->Response->throw_exception(400, "Invalid ID"); }
			// fetch
			$result = $this->Tools->fetch_object ("circuitsLogical", "id", $this->_params->id);
			// check result
			if($result==NULL)						{ $this->Response->throw_exception(404, "Logical circuit not found"); }
			else									{ return ["code"=>200, "data"=>$this->prepare_result ($result, null, true, false)]; }
		}
	}





	/**
	 * Creates new logical circuit
	 *
	 * Accepts an optional "circuits" key - an ordered array of member (physical)
	 * circuit ids to attach on creation, mirroring the non-API
	 * edit-logical-circuit form. At least one member circuit is required, same
	 * as that form.
	 *
	 * /circuitsLogical/
	 *
	 * @access public
	 * @return void
	 */
	#[\Override]
    public function POST () {
		# extract and validate member circuits list before schema-key validation,
		# "circuits" is not a circuitsLogical column
		$members = $this->validate_members ("add");
		# validate logical circuit id
		$this->validate_cid ("add");
		# check for valid keys
		$values = $this->validate_keys ();
		# member_count is derived, not client-settable
		$values['member_count'] = sizeof($members);
		# validate that something is present
		$this->validate_values ($values);

		# execute update
		if(!$this->Admin->object_modify ("circuitsLogical", "add", "id", $values))
													{ $this->Response->throw_exception(500, "logical circuit creation failed"); }

		# circuitsLogicalMapping has no auto_increment id column, so lastId must be
		# captured now - inserting members below overwrites Admin->lastId
		$new_id = $this->Admin->lastId;

		# attach members
		$this->replace_members ($new_id, $members);

		//set result
		return ["code"=>201, "message"=>"logical circuit created", "id"=>$new_id, "location"=>"/api/".$this->_params->app_id."/circuitsLogical/".$new_id."/"];
	}





	/**
	 * Updates logical circuit, or replaces its member circuits
	 *
	 * /circuitsLogical/{id}/				update logical_cid/purpose/comments,
	 * 										optionally replacing members if
	 * 										"circuits" is provided
	 * /circuitsLogical/{id}/circuits/		replace member circuits only
	 *
	 * @method PATCH
	 *
	 * @return void|array
	 */
	#[\Override]
    public function PATCH () {
		# validate id
		$this->validate_id ("edit");

		# members-only update
		if (isset($this->_params->id2) && $this->_params->id2=="circuits") {
			$members = $this->validate_members ("edit");
			if(!is_array($members))				{ $this->Response->throw_exception(400, "circuits is mandatory"); }

			$this->replace_members ($this->_params->id, $members);

			if(!$this->Admin->object_modify ("circuitsLogical", "edit", "id", ["id"=>$this->_params->id, "member_count"=>sizeof($members)]))
													{ $this->Response->throw_exception(500, "logical circuit member update failed"); }

			return ["code"=>200, "message"=>"logical circuit members updated"];
		}

		# fetch object
		$old_object = $this->Tools->fetch_object ("circuitsLogical", "id", $this->_params->id);
		# extract and validate member circuits list, if provided
		$members = $this->validate_members ("edit");
		# validate logical circuit id
		$this->validate_cid ("edit", $old_object);
		# check for valid keys
		$values = $this->validate_keys ();
		# member_count is derived, not client-settable
		if(is_array($members)) { $values['member_count'] = sizeof($members); }
		# validate that something is present
		$this->validate_values ($values);

		# execute update
		if(!$this->Admin->object_modify ("circuitsLogical", "edit", "id", $values))
													{ $this->Response->throw_exception(500, "logical circuit edit failed"); }

		# replace members if provided
		if(is_array($members)) { $this->replace_members ($this->_params->id, $members); }

		//set result
		return ["code"=>200, "message"=>"logical circuit updated"];
	}





	/**
	 * Deletes logical circuit
	 *
	 * /circuitsLogical/{id}/
	 *
	 * @method DELETE
	 *
	 * @return void|array
	 */
	#[\Override]
    public function DELETE () {
		# verify
		$this->validate_id ("delete");

		# set variables for delete
		$values = [];
		$values["id"] = $this->_params->id;
		# validate that something is present
		$this->validate_values ($values);

		# execute delete
		if(!$this->Admin->object_modify ("circuitsLogical", "delete", "id", $values))
													{ $this->Response->throw_exception(500, "logical circuit delete failed"); }

		# drop membership mapping
		try { $this->Database->deleteObjectsByIdentifier ("circuitsLogicalMapping", "logicalCircuit_id", $values["id"]); }
		catch (Exception $e) {
			$this->Response->throw_exception(500, "Failed to clean up logical circuit members: ".$e->getMessage());
		}

		// set result
		return ["code"=>200, "message"=>"logical circuit deleted"];
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
			if(!isset($this->_params->id))													{ $this->Response->throw_exception(400, "Logical circuit id is required");  }
			// validate number
			if(!is_numeric($this->_params->id))												{ $this->Response->throw_exception(400, "Logical circuit id must be numeric"); }
			// check that it exists
			if($this->Tools->fetch_object("circuitsLogical","id", $this->_params->id)===false)	{ $this->Response->throw_exception(404, "Nonexisting logical circuit id"); }
		}
	}

	/**
	 * Validate logical_cid
	 *
	 * @method validate_cid
	 *
	 * @param  string $action
	 * @param  object|null $old_object
	 *
	 * @return void
	 */
	private function validate_cid ($action, $old_object = null) {
		// add
		if ($action=="add") {
			if (!isset($this->_params->logical_cid) || is_blank($this->_params->logical_cid))	{ $this->Response->throw_exception(400, "Logical Circuit ID is mandatory"); }
			elseif ($this->Tools->fetch_object ("circuitsLogical", "logical_cid", $this->_params->logical_cid))	{ $this->Response->throw_exception(409, "Logical Circuit ID already exists"); }
		}
		// edit
		else {
			if (isset($this->_params->logical_cid) && $old_object->logical_cid!=$this->_params->logical_cid) {
				if($this->Tools->fetch_object ("circuitsLogical", "logical_cid", $this->_params->logical_cid)) { $this->Response->throw_exception(409, "Logical Circuit ID already exists"); }
			}
		}
	}

	/**
	 * Extracts, validates and removes the "circuits" member list from the
	 * request so it doesn't fail schema-key validation (it's not a
	 * circuitsLogical column).
	 *
	 * On add a non-empty list is mandatory (same rule as the non-API
	 * edit-logical-circuit form). On edit it's optional - null means
	 * "leave membership unchanged".
	 *
	 * @method validate_members
	 *
	 * @param  string $action
	 *
	 * @return array|null
	 */
	private function validate_members ($action) {
		$members = isset($this->_params->circuits) ? $this->_params->circuits : null;
		unset($this->_params->circuits);

		if($action=="add" && (!is_array($members) || sizeof($members)==0))	{ $this->Response->throw_exception(400, "No circuits selected"); }
		if($members===null)														{ return null; }
		if(!is_array($members))													{ $this->Response->throw_exception(400, "circuits must be an array of circuit ids"); }
		if(sizeof($members)!=sizeof(array_unique($members)))						{ $this->Response->throw_exception(400, "Remove duplicates of circuit"); }

		foreach ($members as $circuit_id) {
			if(!is_numeric($circuit_id) || $this->Tools->fetch_object("circuits","id",$circuit_id)===false)
																					{ $this->Response->throw_exception(400, "Invalid circuit id in circuits list: ".$circuit_id); }
		}

		return $members;
	}

	/**
	 * Replaces all member (physical) circuits of a logical circuit, preserving
	 * the order they were provided in. Mirrors the delete-then-reinsert
	 * approach used by the non-API edit-logical-circuit form.
	 *
	 * @method replace_members
	 *
	 * @param  int   $logical_circuit_id
	 * @param  array $members
	 *
	 * @return void
	 */
	private function replace_members ($logical_circuit_id, $members) {
		try { $this->Database->deleteObjectsByIdentifier ("circuitsLogicalMapping", "logicalCircuit_id", $logical_circuit_id); }
		catch (Exception $e) {
			$this->Response->throw_exception(500, "Failed to update logical circuit members: ".$e->getMessage());
		}

		$order = 0;
		foreach ($members as $circuit_id) {
			$values = ["logicalCircuit_id"=>$logical_circuit_id, "circuit_id"=>$circuit_id, "order"=>$order];
			if(!$this->Admin->object_modify ("circuitsLogicalMapping", "add", "id", $values))
													{ $this->Response->throw_exception(500, "Failed to insert logical circuit member"); }
			$order++;
		}
	}
}
