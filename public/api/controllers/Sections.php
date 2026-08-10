<?php

use OpenApi\Attributes as OA;

/**
 *	phpIPAM API class to work with sections
 *
 *
 */
class Sections_controller extends Common_api_functions {

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
		$this->Response = $Response;
		$this->Tools 	= $Tools;
		$this->_params 	= $params;
		# sections
		// init required objects
		$this->init_object ("Sections", $Database);
		# set valid keys
		$this->set_valid_keys ("sections");
	}





	/**
	 * Returns json encoded options and version
	 *
	 * @access public
	 * @return void
	 */
	#[OA\Options(
		path: "/{app_id}/sections/",
		tags: ["sections"],
		summary: "Discover supported sections routes/methods (HATEOAS)",
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
								["href"=>"/api/".$this->_params->app_id."/sections/", 			"methods"=>[["rel"=>"options", "method"=>"OPTIONS"]]],
								["href"=>"/api/".$this->_params->app_id."/sections/{id}/", 	"methods"=>[["rel"=>"read", 	"method"=>"GET"],
																													 ["rel"=>"create", "method"=>"POST"],
																													 ["rel"=>"update", "method"=>"PATCH"],
																													 ["rel"=>"delete", "method"=>"DELETE"]]],
							];
		# result
		return ["code"=>200, "data"=>$result];
	}





	/**
	 * GET sections functions
	 *
	 *	ID can be:
	 *      - /                     // returns all sections
	 *		- /{id}/                // returns section details
	 *		- /{id}/subnets/		// returns all subnets in this section
	 *		- /{id}/subnets/addresses/ // returns all subnets in this section + addresses
	 *		- /{name}/subnets/		// returns all subnets in this named section
	 *		- /{name}/ 				// section name
	 *
	 *	If no ID is provided all sections are returned
	 *
	 * @access public
	 * @return void
	 */
	#[OA\Get(
		path: "/{app_id}/sections/",
		tags: ["sections"],
		summary: "List all sections",
		parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
		responses: [new OA\Response(response: 200, description: "OK, or no sections available", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Section")))]
	)]
	#[OA\Get(
		path: "/{app_id}/sections/{id}/",
		tags: ["sections"],
		summary: "Read a single section by numeric id",
		parameters: [
			new OA\Parameter(ref: "#/components/parameters/app_id"),
			new OA\Parameter(name: "id", in: "path", required: true, description: "Section id", schema: new OA\Schema(type: "integer"))
		],
		responses: [
			new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(ref: "#/components/schemas/Section")),
			new OA\Response(response: 404, description: "Section does not exist", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
		]
	)]
	#[OA\Get(
		path: "/{app_id}/sections/{name}/",
		tags: ["sections"],
		summary: "Read a single section by name",
		parameters: [
			new OA\Parameter(ref: "#/components/parameters/app_id"),
			new OA\Parameter(name: "name", in: "path", required: true, description: "Section name", schema: new OA\Schema(type: "string"))
		],
		responses: [
			new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(ref: "#/components/schemas/Section")),
			new OA\Response(response: 404, description: "Section does not exist", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
		]
	)]
	#[OA\Get(
		path: "/{app_id}/sections/custom_fields/",
		tags: ["sections"],
		summary: "Custom fields on sections (not supported)",
		parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
		responses: [new OA\Response(response: 409, description: "Custom fields not supported", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))]
	)]
	#[OA\Get(
		path: "/{app_id}/sections/{id}/subnets/",
		tags: ["sections"],
		summary: "List all subnets in a section",
		parameters: [
			new OA\Parameter(ref: "#/components/parameters/app_id"),
			new OA\Parameter(name: "id", in: "path", required: true, description: "Section id", schema: new OA\Schema(type: "integer"))
		],
		responses: [
			new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Subnet"))),
			new OA\Response(response: 404, description: "No subnets found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
		]
	)]
	#[OA\Get(
		path: "/{app_id}/sections/{id}/subnets/addresses/",
		tags: ["sections"],
		summary: "List all subnets in a section, including their addresses",
		parameters: [
			new OA\Parameter(ref: "#/components/parameters/app_id"),
			new OA\Parameter(name: "id", in: "path", required: true, description: "Section id", schema: new OA\Schema(type: "integer"))
		],
		responses: [
			new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Subnet"))),
			new OA\Response(response: 404, description: "No subnets found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
		]
	)]
	#[OA\Get(
		path: "/{app_id}/sections/{id}/changelog/",
		tags: ["sections"],
		summary: "Get the change log for a section",
		parameters: [
			new OA\Parameter(ref: "#/components/parameters/app_id"),
			new OA\Parameter(name: "id", in: "path", required: true, description: "Section id", schema: new OA\Schema(type: "integer"))
		],
		responses: [
			new OA\Response(response: 200, description: "OK"),
			new OA\Response(response: 404, description: "No changelogs found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
		]
	)]
	#[\Override]
    public function GET () {
		// fetch subnets in section
		if(@$this->_params->id2=="subnets" && is_numeric($this->_params->id)) {
			// we don't need id2 anymore
			unset($this->_params->id2);
			// init required objects
			$this->init_object ("Subnets", $this->Database);
			$this->init_object ("Addresses", $this->Database);
			//fetch
			$result = $this->Subnets->fetch_section_subnets ($this->_params->id);
			if(is_array($result)) {
				// add subnet details
				foreach ($result as $k=>$r) {
					// Don't calculate statistics for folders.
					if ($r->isFolder == 1) continue;

					//gw
					$gateway = $this->read_subnet_gateway ($r->id);
					if ( $gateway!== false) {
						$result[$k]->gatewayId = $gateway->id;
					}

					//nameservers
					$ns = $this->read_subnet_nameserver ($r->nameserverId);
					if ($ns!==false) {
						$result[$k]->nameservers = $ns;
					}

					// get usage
					$result[$k]->usage = $this->read_subnet_usage($r->id);

					// fetch addresses
					if(@$this->_params->id3=="addresses") {
						// fetch
						$result[$k]->addresses = $this->Addresses->fetch_subnet_addresses ($r->id);
					}
				}
			}
			// check result
			if(empty($result)) 						{ $this->Response->throw_exception(404, "No subnets found"); }
			else {
				$this->custom_fields = $this->Tools->fetch_custom_fields('subnets');
				return ["code"=>200, "data"=>$this->prepare_result ($result, "subnets", true, true)];
			}
		}
		// verify ID
		elseif(isset($this->_params->id)) {
			# changelog
			if($this->_params->id2=="changelog") 		{
				return ["code"=>200, "data"=>$this->section_changelog ()];
			}
			# fetch by id
			elseif(is_numeric($this->_params->id)) {
				$result = $this->Sections->fetch_section ("id", $this->_params->id);
				// check result
				if($result===false) 					{ $this->Response->throw_exception(404, "Section does not exist"); }
				else									{ return ["code"=>200, "data"=>$this->prepare_result ($result, null, true, true)]; }
			}
			# Custom fields not supported
			elseif($this->_params->id=="custom_fields") {
				$this->Response->throw_exception(409, 'Custom fields not supported');
			}
			# fetch by name
			else {
				$result = $this->Sections->fetch_section ("name", $this->_params->id);
				// check result
				if($result==false) 					    { $this->Response->throw_exception(404, $this->Response->errors[404]); }
				else									{ return ["code"=>200, "data"=>$this->prepare_result ($result, null, true, true)]; }
			}
		}
		# all sections
		else {
				// all sections
				$result = $this->Sections->fetch_all_sections();
				// check result
				if($result===false) 					{ return ["code"=>200, "message"=>"No sections available"]; }
				else									{ return ["code"=>200, "data"=>$this->prepare_result ($result, null, true, true)]; }
		}
	}





	/**
	 * Creates new section
	 *
	 * @access public
	 * @return void
	 */
	#[OA\Post(
		path: "/{app_id}/sections/",
		tags: ["sections"],
		summary: "Create a new section",
		parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
		requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
			required: ["name"],
			properties: [
				new OA\Property(property: "name", type: "string", description: "Minimum 3 characters", example: "Customers"),
				new OA\Property(property: "description", type: "string"),
				new OA\Property(property: "masterSection", type: "integer", description: "Parent section id. Only 1 level of nesting is permitted (parent must itself be a top-level section)", example: 0),
				new OA\Property(property: "permissions", type: "string", description: "JSON-encoded map of groupId => permission level"),
				new OA\Property(property: "strictMode", type: "boolean"),
				new OA\Property(property: "subnetOrdering", type: "string"),
				new OA\Property(property: "order", type: "integer"),
				new OA\Property(property: "showSubnet", type: "boolean"),
				new OA\Property(property: "showVLAN", type: "boolean"),
				new OA\Property(property: "showVRF", type: "boolean"),
				new OA\Property(property: "showSupernetOnly", type: "boolean"),
				new OA\Property(property: "DNS", type: "string")
			]
		)),
		responses: [
			new OA\Response(response: 201, description: "Section created", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
			new OA\Response(response: 400, description: "Name is mandatory or too short (minimum 3 characters), or invalid masterSection id, or nesting more than 1 level deep", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
			new OA\Response(response: 500, description: "Section create failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
		]
	)]
	#[\Override]
    public function POST () {
		# check for valid keys
		$values = $this->validate_keys ();

		// remove editDate if set
		unset($values['editDate']);

		# validate mandatory parameters
		if(strlen((string) $this->_params->name)<3)				{ $this->Response->throw_exception(400, 'Name is mandatory or too short (mininum 3 characters)'); }

		# verify masterSection
		if(isset($this->_params->masterSection)) {
			$masterSection = $this->Sections->fetch_section ("id", $this->_params->masterSection);
			// checks
			if(!is_object($masterSection))				{ $this->Response->throw_exception(400, 'Invalid masterSection id '.$this->_params->masterSection); }
			elseif($masterSection->masterSection!="0")	{ $this->Response->throw_exception(400, 'Only 1 level of nesting is permitted for sections');  }
		}

		# execute update
		if(!$this->Sections->modify_section ("add", $values))
														{ $this->Response->throw_exception(500, "Section create failed"); }
		else {
			//set result
			return ["code"=>201, "message"=>"Section created", "id"=>$this->Sections->lastInsertId, "location"=>"/api/".$this->_params->app_id."/sections/".$this->Sections->lastInsertId."/"];
		}
	}





	/**
	 * Updates existing section
	 *
	 * @access public
	 * @return void
	 */
	#[OA\Patch(
		path: "/{app_id}/sections/{id}/",
		tags: ["sections"],
		summary: "Update a section",
		parameters: [
			new OA\Parameter(ref: "#/components/parameters/app_id"),
			new OA\Parameter(name: "id", in: "path", required: true, description: "Section id", schema: new OA\Schema(type: "integer"))
		],
		requestBody: new OA\RequestBody(content: new OA\JsonContent(
			properties: [
				new OA\Property(property: "name", type: "string", description: "Minimum 3 characters"),
				new OA\Property(property: "description", type: "string"),
				new OA\Property(property: "masterSection", type: "integer"),
				new OA\Property(property: "permissions", type: "string", description: "JSON-encoded map of groupId => permission level"),
				new OA\Property(property: "strictMode", type: "boolean"),
				new OA\Property(property: "subnetOrdering", type: "string"),
				new OA\Property(property: "order", type: "integer"),
				new OA\Property(property: "showSubnet", type: "boolean"),
				new OA\Property(property: "showVLAN", type: "boolean"),
				new OA\Property(property: "showVRF", type: "boolean"),
				new OA\Property(property: "showSupernetOnly", type: "boolean"),
				new OA\Property(property: "DNS", type: "string")
			]
		)),
		responses: [
			new OA\Response(response: 200, description: "Section updated", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
			new OA\Response(response: 400, description: "Section Id required", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
			new OA\Response(response: 404, description: "Section does not exist", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
			new OA\Response(response: 500, description: "Section update failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
		]
	)]
	#[\Override]
    public function PATCH () {
		# Check for id
		if(!isset($this->_params->id))					{ $this->Response->throw_exception(400, "Section Id required"); }
		# check that section exists
		if($this->Sections->fetch_section ("id", $this->_params->id)===false)
														{ $this->Response->throw_exception(404, "Section does not exist"); }

		# validate and prepare keys
		$values = $this->validate_keys ();

		# execute update
		if(!$this->Sections->modify_section ("edit", $values))
														{ $this->Response->throw_exception(500, "Section update failed"); }
		else {
			//set result
			return ["code"=>200, "data"=>NULL];
		}
	}





	/**
	 * Deletes existing section along with subnets and addresses
	 *
	 * @access public
	 * @return void
	 */
	#[OA\Delete(
		path: "/{app_id}/sections/{id}/",
		tags: ["sections"],
		summary: "Delete a section, along with its subnets and addresses",
		parameters: [
			new OA\Parameter(ref: "#/components/parameters/app_id"),
			new OA\Parameter(name: "id", in: "path", required: true, description: "Section id", schema: new OA\Schema(type: "integer"))
		],
		responses: [
			new OA\Response(response: 200, description: "Section deleted", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
			new OA\Response(response: 400, description: "Section Id required", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
			new OA\Response(response: 404, description: "Section does not exist", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
			new OA\Response(response: 500, description: "Section delete failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
		]
	)]
	#[\Override]
    public function DELETE () {
		# Check for id
		if(!isset($this->_params->id))					{ $this->Response->throw_exception(400, "Section Id required"); }
		# check that section exists
		if($this->Sections->fetch_section ("id", $this->_params->id)===false)
														{ $this->Response->throw_exception(404, "Section does not exist"); }

		# set variables for update
		$values = [];
		$values["id"] = $this->_params->id;

		# execute update
		if(!$this->Sections->modify_section ("delete", $values))
														{ $this->Response->throw_exception(500, "Section delete failed"); }
		else {
			//set result
			return ["code"=>200, "data"=>NULL];
		}
	}

	/**
 	 * Calculates subnet usage
	 *
	 * @access private
	 * @param mixed $subnetId
	 * @return array
	 */
	private function read_subnet_usage ($subnetId) {
		# check that section exists
		$subnet = $this->Subnets->fetch_subnet ("id", $subnetId);
		if($subnet===false)
														{ $this->Response->throw_exception(404, "Subnet does not exist"); }
        # calculate
        $subnet_usage = $this->Subnets->calculate_subnet_usage ($subnet);     //Calculate free/used etc

        # return
        return $subnet_usage;
	 }

	/**
	 * Get changelog for subnet
	 * @method subnet_changelog
	 * @return [type]
	 */
	private function section_changelog () {
		// get changelog
		$Log = new Logging ($this->Database);
		$clogs = $Log->fetch_changlog_entries("section", $this->_params->id, true);
		// reformat
		$clogs_formatted = [];
		// loop
		if (is_array($clogs)) {
			if (sizeof($clogs)>0) {
				foreach ($clogs as $l) {
					// diff to array
					$l->cdiff = explode("\r\n", str_replace(["[","]"], "", trim((string) $l->cdiff)));
					// save
					$clogs_formatted[] = [
						"user"   => $l->real_name,
						"action" => $l->caction,
						"result" => $l->cresult,
						"date"   => $l->cdate,
						"diff"   => $l->cdiff,
					];
				}
			}
		}
		// result
		if(sizeof($clogs_formatted)>0) 	{ return $clogs_formatted; }
		else 							{ $this->Response->throw_exception(404, "No changelogs found"); }
	}
}
