<?php

use OpenApi\Attributes as OA;

/**
 *	phpIPAM API class to work with VLAN domains
 *
 *
 */

class L2domains_controller extends Common_api_functions {

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
		$this->set_valid_keys ("vlanDomains");
	}






	/**
	 * Returns json encoded options
	 *
	 * @access public
	 * @return void
	 */
	#[OA\Options(
		path: "/{app_id}/l2domains/",
		tags: ["l2domains"],
		summary: "Discover supported l2domains routes/methods (HATEOAS)",
		parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
		responses: [new OA\Response(response: 200, description: "OK")]
	)]
	#[\Override]
    public function OPTIONS () {
		// validate
		$this->validate_options_request ();

		// methods
		$result = [];
		$result['methods'] = [
								["href"=>"/api/l2domains/".$this->_params->app_id."/", 		"methods"=>[["rel"=>"options", "method"=>"OPTIONS"]]],
								["href"=>"/api/l2domains/".$this->_params->app_id."/{id}/", 	"methods"=>[["rel"=>"read", 	"method"=>"GET"],
																												 	["rel"=>"create", "method"=>"POST"],
																												 	["rel"=>"update", "method"=>"PATCH"],
																												 	["rel"=>"delete", "method"=>"DELETE"]]],
							];
		# result
		return ["code"=>200, "data"=>$result];
	}





	/**
	 * Read domain functions
	 *
	 *	identifier can be:
	 *		- / 				// will return all domains
	 *		- /{id}/
	 *		- /{id}/vlans/
	 *		- /custom_fields/
	 *		- /all/				// will return all domains
	 *
	 * @access public
	 * @return void
	 */
	#[OA\Get(
		path: "/{app_id}/l2domains/",
		tags: ["l2domains"],
		summary: "List all L2 (VLAN) domains",
		parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
		responses: [
			new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(properties: [
				new OA\Property(property: "id", type: "integer", example: 1),
				new OA\Property(property: "name", type: "string", example: "default"),
				new OA\Property(property: "description", type: "string", nullable: true, example: "default L2 domain"),
				new OA\Property(property: "sections", type: "string", nullable: true, description: "JSON-encoded map of groupId => permission level (stored internally as 'permissions', exposed as 'sections' in read responses)")
			]))),
			new OA\Response(response: 404, description: "No domains configured", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
		]
	)]
	#[OA\Get(
		path: "/{app_id}/l2domains/custom_fields/",
		tags: ["l2domains"],
		summary: "List custom fields defined on the vlanDomains table",
		parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
		responses: [
			new OA\Response(response: 200, description: "OK"),
			new OA\Response(response: 404, description: "No custom fields defined", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
		]
	)]
	#[OA\Get(
		path: "/{app_id}/l2domains/{id}/vlans/",
		tags: ["l2domains"],
		summary: "List all VLANs belonging to an L2 domain",
		parameters: [
			new OA\Parameter(ref: "#/components/parameters/app_id"),
			new OA\Parameter(name: "id", in: "path", required: true, description: "L2 domain id", schema: new OA\Schema(type: "integer"))
		],
		responses: [
			new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Vlan"))),
			new OA\Response(response: 400, description: "Domain id must be numeric", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
			new OA\Response(response: 404, description: "Invalid domain id / No vlans belonging to this domain", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
		]
	)]
	#[OA\Get(
		path: "/{app_id}/l2domains/{id}/",
		tags: ["l2domains"],
		summary: "Read a single L2 domain by id",
		parameters: [
			new OA\Parameter(ref: "#/components/parameters/app_id"),
			new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
		],
		responses: [
			new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(properties: [
				new OA\Property(property: "id", type: "integer", example: 1),
				new OA\Property(property: "name", type: "string", example: "default"),
				new OA\Property(property: "description", type: "string", nullable: true, example: "default L2 domain"),
				new OA\Property(property: "sections", type: "string", nullable: true, description: "JSON-encoded map of groupId => permission level (stored internally as 'permissions', exposed as 'sections' in read responses)")
			])),
			new OA\Response(response: 400, description: "Domain id must be numeric", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
			new OA\Response(response: 404, description: "Invalid domain id", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
		]
	)]
	#[\Override]
    public function GET () {
		// all domains
		if(!isset($this->_params->id) || $this->_params->id == "all") {
			$result = $this->Tools->fetch_all_objects ("vlanDomains", 'id', true);
			// check result
			if($result===false)						{ $this->Response->throw_exception(404, 'No domains configured'); }
			else									{ return ["code"=>200, "data"=>$this->prepare_result ($result, null, true, true)]; }
		}
		// set
		else {
			// custom fields
			if($this->_params->id=="custom_fields") {
				if(sizeof($this->custom_fields)==0)	{ $this->Response->throw_exception(404, 'No custom fields defined'); }
				else								{ return ["code"=>200, "data"=>$this->custom_fields]; }
			}
			// vlans
			elseif (@$this->_params->id2=="vlans") {
				// validate domain
				$this->validate_domain ();
				// save result
				$result = $this->Tools->fetch_multiple_objects ("vlans", "domainId", $this->_params->id, 'vlanId', true);
				// check result
				if($result==NULL)					{ $this->Response->throw_exception(404, "No vlans belonging to this domain"); }
				else								{ return ["code"=>200, "data"=>$this->prepare_result ($result, null, true, true)]; }
			}
			// id
			else {
				// validate domain
				$this->validate_domain ();
				// result
				$result = $this->Tools->fetch_object ("vlanDomains", "id", $this->_params->id);
				// check result
				if($result==NULL)					{ $this->Response->throw_exception(404, "Invalid domain id"); }
				else								{ return ["code"=>200, "data"=>$this->prepare_result ($result, null, true, true)]; }
			}

		}
	}






	/**
	 * Creates new domain
	 *
	 * @access public
	 * @return void
	 */
	#[OA\Post(
		path: "/{app_id}/l2domains/",
		tags: ["l2domains"],
		summary: "Create a new L2 (VLAN) domain",
		parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
		requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
			required: ["name"],
			properties: [
				new OA\Property(property: "name", type: "string", example: "Colo 1"),
				new OA\Property(property: "description", type: "string", nullable: true),
				new OA\Property(property: "permissions", type: "string", nullable: true, description: "JSON-encoded map of groupId => permission level")
			]
		)),
		responses: [
			new OA\Response(response: 201, description: "L2 domain created", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
			new OA\Response(response: 400, description: "Domain name is mandatory", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
			new OA\Response(response: 500, description: "Domain creation failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
		]
	)]
	#[\Override]
    public function POST () {
		# remap keys
		$this->remap_keys ();

		# check for valid keys
		$values = $this->validate_keys ();

		# validate input
		$this->validate_domain_edit ();

		# execute update
		if(!$this->Admin->object_modify ("vlanDomains", "add", "id", $values))
													{ $this->Response->throw_exception(500, "Domain creation failed"); }
		else {
			//set result
			return ["code"=>201, "message"=>"L2 domain created", "id"=>$this->Admin->lastId, "location"=>"/api/".$this->_params->app_id."/l2domains/".$this->Admin->lastId."/"];
		}
	}





	/**
	 * Updates existing domain
	 *
	 * @access public
	 * @return void
	 */
	#[OA\Patch(
		path: "/{app_id}/l2domains/{id}/",
		tags: ["l2domains"],
		summary: "Update an existing L2 (VLAN) domain",
		parameters: [
			new OA\Parameter(ref: "#/components/parameters/app_id"),
			new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
		],
		requestBody: new OA\RequestBody(content: new OA\JsonContent(
			properties: [
				new OA\Property(property: "name", type: "string", example: "Colo 1"),
				new OA\Property(property: "description", type: "string", nullable: true),
				new OA\Property(property: "permissions", type: "string", nullable: true, description: "JSON-encoded map of groupId => permission level")
			]
		)),
		responses: [
			new OA\Response(response: 200, description: "L2 domain updated", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
			new OA\Response(response: 400, description: "Invalid domain id / Domain name is mandatory", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
			new OA\Response(response: 404, description: "Invalid domain id", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
			new OA\Response(response: 500, description: "Domain edit failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
		]
	)]
	#[\Override]
    public function PATCH () {
		# remap keys
		$this->remap_keys ();

		# verify
		$this->validate_domain_edit ();
		# check that it exists
		$this->validate_domain ();

		# validate and prepare keys
		$values = $this->validate_keys ();

		# execute update
		if(!$this->Admin->object_modify ("vlanDomains", "edit", "id", $values))
													{ $this->Response->throw_exception(500, "Domain edit failed"); }
		else {
			//set result
			return ["code"=>200, "message"=>"L2 domain updated"];
		}
	}







	/**
	 * Deletes existing domain
	 *
	 * @access public
	 * @return void
	 */
	#[OA\Delete(
		path: "/{app_id}/l2domains/{id}/",
		tags: ["l2domains"],
		summary: "Delete an L2 (VLAN) domain (VLANs in this domain are migrated to the default domain)",
		description: "The default domain (id=1) cannot be deleted.",
		parameters: [
			new OA\Parameter(ref: "#/components/parameters/app_id"),
			new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
		],
		responses: [
			new OA\Response(response: 200, description: "L2 domain deleted and vlans migrated to default domain", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
			new OA\Response(response: 400, description: "Domain id must be numeric", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
			new OA\Response(response: 404, description: "Invalid domain id", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
			new OA\Response(response: 409, description: "Default domain cannot be deleted", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
			new OA\Response(response: 500, description: "L2 domain delete failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
		]
	)]
	#[\Override]
    public function DELETE () {
		# check that domain exists
		$this->validate_domain_edit ();

		# set variables for update
		$values = [];
		$values["id"] = $this->_params->id;

		# execute delete
		if(!$this->Admin->object_modify ("vlanDomains", "delete", "id", $values))
													{ $this->Response->throw_exception(500, "L2 domain delete failed"); }
		else {
			// delete references, reset to default
			$this->Admin->update_object_references ("vlans", "domainId", $this->_params->id, 1);

			// set result
			return ["code"=>200, "message"=>"L2 domain deleted and vlans migrated to default domain"];
		}
	}









	/* @validations ---------- */

	/**
	 * Validates domains
	 *
	 * @access private
	 * @return void
	 */
	private function validate_domain () {
		// validate id
		if(!isset($this->_params->id))														{ $this->_params->id = 1; }
		// validate number
		if(!is_numeric($this->_params->id))													{ $this->Response->throw_exception(400, "Domain id must be numeric"); }
		// check that it exists
		if($this->Tools->fetch_object ("vlanDomains", "id", $this->_params->id) === false )
																							{ $this->Response->throw_exception(404, "Invalid domain id"); }
	}


	/**
	 * Validates domain on edit
	 *
	 * @access private
	 * @return void
	 */
	private function validate_domain_edit () {
		// delete checks
		if($_SERVER['REQUEST_METHOD']=="DELETE") {
			// we cannot delete default domain
			if(@$this->_params->id==1 && $_SERVER['REQUEST_METHOD']=="DELETE")				{ $this->Response->throw_exception(409, "Default domain cannot be deleted"); }
			// ID must be numeric
			if(!is_numeric($this->_params->id))												{ $this->Response->throw_exception(400, "Domain id must be numeric"); }
			// check that it exists
			if($this->Tools->fetch_object ("vlanDomains", "id", $this->_params->id) === false )
																							{ $this->Response->throw_exception(404, "Invalid domain id"); }
		}
		// create checks
		elseif ($_SERVER['REQUEST_METHOD']=="POST") {
			// name must be present
			if(@$this->_params->name == "" || !isset($this->_params->name)) 				{ $this->Response->throw_exception(400, "Domain name is mandatory"); }
		}
		// update checks
		elseif ($_SERVER['REQUEST_METHOD']=="PATCH") {
			// ID must be numeric
			if(!is_numeric($this->_params->id))												{ $this->Response->throw_exception(400, "Invalid domain id"); }
			// name must be present
			if(@$this->_params->name == "" && isset($this->_params->name)) 					{ $this->Response->throw_exception(400, "Domain name is mandatory"); }
		}

	}
}
