<?php

use OpenApi\Attributes as OA;

/**
 *	phpIPAM API class to work with vrfs
 *
 *
 */

class Vrfs_controller extends Common_api_functions {

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
		$this->set_valid_keys ("vrf");
	}






	/**
	 * Returns json encoded options
	 *
	 * @access public
	 * @return void
	 */
	#[OA\Options(
		path: "/{app_id}/vrfs/",
		tags: ["vrfs"],
		summary: "Discover supported vrfs routes/methods (HATEOAS)",
		parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
		responses: [new OA\Response(response: 200, description: "OK")]
	)]
	#[\Override]
    public function OPTIONS () {
		// validate
		$this->validate_options_request ();

		// methods
		$result['methods'] = [
								["href"=>"/api/".$this->_params->app_id."/vrfs/", 		"methods"=>[["rel"=>"options", "method"=>"OPTIONS"]]],
								["href"=>"/api/".$this->_params->app_id."/vrfs/{id}/", "methods"=>[["rel"=>"read", 	"method"=>"GET"],
																											 ["rel"=>"create", "method"=>"POST"],
																											 ["rel"=>"update", "method"=>"PATCH"],
																											 ["rel"=>"delete", "method"=>"DELETE"]]],
							];
		# result
		return ["code"=>200, "data"=>$result];
	}






	/**
	 * Read vrf
	 *
	 *	identifiers:
	 *		- /				        // returns all VRFs
	 *		- /custom_fields/		// returns all VRF custom fields
	 *		- /{id}/				// returns VRF by id
	 *		- /{id}/subnets/		// subnets inside vrf
	 *		- /all/			        // returns all VRFs
	 *
	 *
	 * @access public
	 * @return void
	 */
	#[OA\Get(
		path: "/{app_id}/vrfs/",
		tags: ["vrfs"],
		summary: "List all VRFs (alias: /vrfs/all/)",
		parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
		responses: [
			new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Vrf"))),
			new OA\Response(response: 404, description: "No vrfs configured", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
		]
	)]
	#[OA\Get(
		path: "/{app_id}/vrfs/custom_fields/",
		tags: ["vrfs"],
		summary: "List custom fields defined on the vrf table",
		parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
		responses: [
			new OA\Response(response: 200, description: "OK"),
			new OA\Response(response: 404, description: "No custom fields defined", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
		]
	)]
	#[OA\Get(
		path: "/{app_id}/vrfs/{id}/",
		tags: ["vrfs"],
		summary: "Read a single VRF by id",
		parameters: [
			new OA\Parameter(ref: "#/components/parameters/app_id"),
			new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
		],
		responses: [
			new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(ref: "#/components/schemas/Vrf")),
			new OA\Response(response: 400, description: "Vrf Id is required / must be numeric / Invalid VRF id", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
			new OA\Response(response: 404, description: "VRF not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
		]
	)]
	#[OA\Get(
		path: "/{app_id}/vrfs/{id}/subnets/",
		tags: ["vrfs"],
		summary: "List all subnets belonging to a VRF",
		parameters: [
			new OA\Parameter(ref: "#/components/parameters/app_id"),
			new OA\Parameter(name: "id", in: "path", required: true, description: "VRF id", schema: new OA\Schema(type: "integer"))
		],
		responses: [
			new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Subnet"))),
			new OA\Response(response: 400, description: "Vrf Id is required / must be numeric / Invalid VRF id", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
			new OA\Response(response: 404, description: "No subnets belonging to this vrf", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
		]
	)]
	#[\Override]
    public function GET () {
		// all
		if (!isset($this->_params->id) || $this->_params->id == "all") {
			$result = $this->Tools->fetch_all_objects ("vrf", 'vrfId');
			// check result
			if($result===false)						{ $this->Response->throw_exception(404, 'No vrfs configured'); }
			else									{ return ["code"=>200, "data"=>$this->prepare_result ($result, null, true, true)]; }
		}
		// custom fields
		if($this->_params->id=="custom_fields") {
			// check result
			if(sizeof($this->custom_fields)==0)			{ $this->Response->throw_exception(404, 'No custom fields defined'); }
			else										{ return ["code"=>200, "data"=>$this->custom_fields]; }
		}
		// subnets
		elseif (isset($this->_params->id2)) {
			// subnets
			if ($this->_params->id2 == "subnets") {
				// validate
				$this->validate_vrf ();
				// fetch
				$result = $this->Tools->fetch_multiple_objects ("subnets", "vrfId", $this->_params->id, 'subnet', true);
				// add gateway if present
    			if($result!=false) {
    				foreach ($result as $k=>$r) {
                		$gateway = $this->read_subnet_gateway ($r->id);
                		if ( $gateway!== false) {
                    		$result[$k]->gatewayId = $gateway->id;
                		}
    				}
    			}

				// check result
				if($result===false)					{ $this->Response->throw_exception(404, 'No subnets belonging to this vrf'); }
				else {
					$this->custom_fields = $this->Tools->fetch_custom_fields('subnets');
					return ["code"=>200, "data"=>$this->prepare_result ($result, "subnets", true, true)];
				}
			}
			// error
			else {
													{ $this->Response->throw_exception(400, "Invalid identifier"); }
			}
		}
		// by id
		else {
			// validate
			$this->validate_vrf ();
			// fetch
			$result = $this->Tools->fetch_object ("vrf", "vrfId", $this->_params->id);
			// check result
			if($result===false)						{ $this->Response->throw_exception(404, "VRF not found"); }
			else									{ return ["code"=>200, "data"=>$this->prepare_result ($result, null, true, true)]; }
		}
	}





	/**
	 * Creates new VRF
	 *
	 * @access public
	 * @return void
	 */
	#[OA\Post(
		path: "/{app_id}/vrfs/",
		tags: ["vrfs"],
		summary: "Create a new VRF",
		parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
		requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
			required: ["name"],
			properties: [
				new OA\Property(property: "name", type: "string", example: "Customer-A"),
				new OA\Property(property: "rd", type: "string", description: "Route distinguisher"),
				new OA\Property(property: "description", type: "string"),
				new OA\Property(property: "sections", type: "string", description: "Comma-separated list of section ids this VRF is restricted to"),
				new OA\Property(property: "customer_id", type: "integer")
			]
		)),
		responses: [
			new OA\Response(response: 201, description: "VRF created", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
			new OA\Response(response: 400, description: "VRF name is required", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
			new OA\Response(response: 409, description: "VRF with that name already exists", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
			new OA\Response(response: 500, description: "VRF creation failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
		]
	)]
	#[\Override]
    public function POST () {
		# check for valid keys
		$values = $this->validate_keys ();

		# validate input
		$this->validate_vrf_edit ();

		# execute update
		if(!$this->Admin->object_modify ("vrf", "add", "vrfId", $values))
													{ $this->Response->throw_exception(500, "VRF creation failed"); }
		else {
			//set result
			return ["code"=>201, "message"=>"VRF created", "id"=>$this->Admin->lastId, "location"=>"/api/".$this->_params->app_id."/vrfs/".$this->Admin->lastId."/"];
		}
	}





	/**
	 * Updates existing vrf
	 *
	 * @access public
	 * @return void
	 */
	#[OA\Patch(
		path: "/{app_id}/vrfs/{id}/",
		tags: ["vrfs"],
		summary: "Update an existing VRF",
		parameters: [
			new OA\Parameter(ref: "#/components/parameters/app_id"),
			new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
		],
		requestBody: new OA\RequestBody(content: new OA\JsonContent(
			properties: [
				new OA\Property(property: "name", type: "string", example: "Customer-A"),
				new OA\Property(property: "rd", type: "string", description: "Route distinguisher"),
				new OA\Property(property: "description", type: "string"),
				new OA\Property(property: "sections", type: "string", description: "Comma-separated list of section ids this VRF is restricted to"),
				new OA\Property(property: "customer_id", type: "integer")
			]
		)),
		responses: [
			new OA\Response(response: 200, description: "VRF updated", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
			new OA\Response(response: 400, description: "Vrf Id is required / must be numeric / Invalid VRF id", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
			new OA\Response(response: 409, description: "VRF with that name already exists", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
			new OA\Response(response: 500, description: "Vrf edit failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
		]
	)]
	#[\Override]
    public function PATCH () {
		# verify
		$this->validate_vrf ();
		# check that it exists
		$this->validate_vrf_edit ();

		# rewrite id
		$this->_params->vrfId = $this->_params->id;
		unset($this->_params->id);

		# validate and prepare keys
		$values = $this->validate_keys ();

		# execute update
		if(!$this->Admin->object_modify ("vrf", "edit", "vrfId", $values))
													{ $this->Response->throw_exception(500, "Vrf edit failed"); }
		else {
			//set result
			return ["code"=>200, "message"=>"VRF updated"];
		}
	}






	/**
	 * Deletes existing vrf
	 *
	 * @access public
	 * @return void
	 */
	#[OA\Delete(
		path: "/{app_id}/vrfs/{id}/",
		tags: ["vrfs"],
		summary: "Delete a VRF, removing all references from subnets that used it",
		parameters: [
			new OA\Parameter(ref: "#/components/parameters/app_id"),
			new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
		],
		responses: [
			new OA\Response(response: 200, description: "VRF deleted", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
			new OA\Response(response: 400, description: "Vrf Id is required / must be numeric / Invalid VRF id", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
			new OA\Response(response: 500, description: "Vrf delete failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
		]
	)]
	#[\Override]
    public function DELETE () {
		# check that vrf exists
		$this->validate_vrf ();

		# set variables for update
		$values = [];
		$values["vrfId"] = $this->_params->id;

		# execute delete
		if(!$this->Admin->object_modify ("vrf", "delete", "vrfId", $values))
													{ $this->Response->throw_exception(500, "Vrf delete failed"); }
		else {
			// delete all references
			$this->Admin->remove_object_references ("subnets", "vrfId", $this->_params->id);

			// set result
			return ["code"=>200, "message"=>"VRF deleted"];
		}
	}










	/* @validations ---------- */



	/**
	 * Validates VRF - checks if it exists
	 *
	 * @access private
	 * @return void
	 */
	private function validate_vrf () {
		// validate id
		if(!isset($this->_params->id))														{ $this->Response->throw_exception(400, "Vrf Id is required");  }
		// validate number
		if(!is_numeric($this->_params->id))													{ $this->Response->throw_exception(400, "Vrf Id must be numeric"); }
		// check that it exists
		if($this->Tools->fetch_object ("vrf", "vrfId", $this->_params->id) === false )		{ $this->Response->throw_exception(400, "Invalid VRF id"); }
	}


	/**
	 * Validates VRF on add and edit
	 *
	 * @access private
	 * @return void
	 */
	private function validate_vrf_edit () {
		// check for POST method
		if($_SERVER['REQUEST_METHOD']=="POST") {
			// check name
			if(is_blank($this->_params->name))												{ $this->Response->throw_exception(400, "VRF name is required"); }
			// check that it exists
			if($this->Tools->fetch_object ("vrf", "name", $this->_params->name) !== false )	{ $this->Response->throw_exception(409, "VRF with that name already exists"); }
		}
		// update check
		else {
			// old values
			$vrf_old = $this->Tools->fetch_object ("vrf", "vrfId", $this->_params->id);

			if(isset($this->_params->name)) {
				if ($this->_params->name != $vrf_old->name) {
					if($this->Tools->fetch_object ("vrf", "name", $this->_params->name))	{ $this->Response->throw_exception(409, "VRF with that name already exists"); }
				}
			}
		}
	}
}