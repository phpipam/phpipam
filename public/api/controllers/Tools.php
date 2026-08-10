<?php

use OpenApi\Attributes as OA;

/**
 *	phpIPAM API class to work with tools
 *
 *
 */

class Tools_controller extends Common_api_functions {

	/**
	 * subcontrollers
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $subcontrollers;

	/**
	 * sort_key for database sorting
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $sort_key;

	/**
	 * identifiers
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $identifiers;


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
		// init required objects
		$this->init_object ("Admin", $Database);
		$this->init_object ("Subnets", $Database);
		// define controllers
		$this->define_tools_controllers ();
		$this->define_available_identifiers ();

		// first validate subcontroller
		$this->validate_subcontroller ();
		// rewrite subcontroller
		$this->rewrite_subcontroller ();

        // set keys if options are not provided
		if($_SERVER['REQUEST_METHOD']!="OPTIONS" && isset($this->_params->controller)) {
            // set valid keys
    		$this->set_valid_keys ($this->_params->id);
            // set sort key
            $this->define_sort_key ();
        }
	}

	/**
	 * Defines available tools (sub)controllers.
	 *
	 *	tools has subcontrollers, defined with id2 parameter
	 *
	 * @access private
	 * @return void
	 */
	private function define_tools_controllers () {
		$this->subcontrollers = [
		                              	"ipTags"	  => "tags",
										"devices"     => "devices",
										"deviceTypes" => "device_types",
										"vlans"       => "vlans",
										"vrf"         => "vrfs",
										"nameservers" => "nameservers",
										"scanAgents"  => "scanagents",
										"locations"   => "locations",
										"racks"       => "racks",
										"nat"         => "nat",
										"customers"   => "customers"
									  ];
	}

	/**
	 * Defines available identifiers for subcontrollers
	 *
	 * @access private
	 * @return void
	 */
	private function define_available_identifiers () {
		$this->identifiers = [
								"ipTags"      => ["id2", "id3"],
								"devices"     => ["id2", "id3"],
								"deviceTypes" => ["id2", "id3"],
								"vlans"       => ["id2", "id3"],
								"vrf"         => ["id2", "id3"],
								"nameservers" => ["id2"],
								"scanAgents"  => ["id2"],
								"locations"   => ["id2", "id3"],
								"racks"       => ["id2", "id3"],
								"nat"         => ["id2", "id3"],
								"customers"   => ["id2"]
								];
	}

	/**
	 * define_sort_key function
	 *
	 * @access private
	 * @return void
	 */
	private function define_sort_key () {
		// deviceTypes
		if ($this->_params->id == "deviceTypes")	{ $this->sort_key = "tid"; }
		elseif ($this->_params->id == "vlans")		{ $this->sort_key = "vlanId"; }
		elseif ($this->_params->id == "vrf")		{ $this->sort_key = "vrfId"; }
		else										{ $this->sort_key = "id"; }
	}







	/**
	 * returns general Controllers and supported methods
	 *
	 * @access public
	 * @return void
	 */
	#[OA\Options(
	    path: "/{app_id}/tools/",
	    tags: ["tools"],
	    summary: "Discover supported tools routes/methods (HATEOAS)",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    responses: [new OA\Response(response: 200, description: "OK")]
	)]
	#[OA\Options(
	    path: "/{app_id}/tools/{subcontroller}/",
	    tags: ["tools"],
	    summary: "Discover supported routes/methods for a tools subcontroller (HATEOAS)",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "subcontroller", in: "path", required: true, description: "One of: ipTags, devices, deviceTypes, vlans, vrf, nameservers, scanAgents, locations, racks, nat, customers", schema: new OA\Schema(type: "string"))
	    ],
	    responses: [new OA\Response(response: 200, description: "OK")]
	)]
	#[\Override]
    public function OPTIONS () {
		// validate
		$this->validate_options_request ();

		// get api
		$app = $this->Tools->fetch_object ("api", "app_id", $this->_params->app_id);

		// controllers
		$controllers = [
						["rel"=>"sections",	"href"=>"/api/".$_GET['app_id']."/sections/"],
						["rel"=>"subnets",		"href"=>"/api/".$_GET['app_id']."/subnets/"],
						["rel"=>"folders",		"href"=>"/api/".$_GET['app_id']."/folders/"],
						["rel"=>"addresses",	"href"=>"/api/".$_GET['app_id']."/addresses/"],
						["rel"=>"vlans",		"href"=>"/api/".$_GET['app_id']."/vlan/"],
						["rel"=>"vrfs",		"href"=>"/api/".$_GET['app_id']."/vrf/"],
						["rel"=>"nameservers",	"href"=>"/api/".$_GET['app_id']."/tools/nameservers/"],
						["rel"=>"scanAgents",	"href"=>"/api/".$_GET['app_id']."/tools/scanagents/"],
						["rel"=>"locations",	"href"=>"/api/".$_GET['app_id']."/tools/locations/"],
						["rel"=>"racks",	    "href"=>"/api/".$_GET['app_id']."/tools/racks/"],
						["rel"=>"nat",	        "href"=>"/api/".$_GET['app_id']."/tools/nat/"],
						["rel"=>"tools",		"href"=>"/api/".$_GET['app_id']."/tools/"]
					];
		# Response
		return ["code"=>200, "data"=>["permissions"=>$this->Subnets->parse_permissions($app->app_permissions), "controllers"=>$controllers]];
	}





	/**
	 * fetch tools object
	 *
	 *	structure:
	 *		/tools/{subcontroller}/{identifier}/{parameter}/
	 *
	 *		/tools/id/id2/id3/
	 *
	 *		- {subcontroller}	- defines which tools object to work on
	 *		- {identifier}		- defines id for that object (optional)
	 *		- {parameter}		- additional parameter (optional)
	 *
	 *  Special options:
	 *      - /tools/device_types/{id}/
	 *      - /tools/device_types/{id}/devices/
	 *
	 *      - /tools/vlans/{id}/subnets/
	 *
	 *      - /tools/vrf/{id}/subnets/
	 *
	 *      - /tools/locations/{id}/subnets/
	 *      - /tools/locations/{id}/devices/
	 *      - /tools/locations/{id}/racks/
	 *      - /tools/locations/{id}/ipaddresses/
	 *
	 *      - /tools/racks/{id}/devices/
	 *
	 *      - /tools/nat/{id}/objects/
	 *      - /tools/nat/{id}/objects_full/
	 *
	 * @access public
	 * @return void
	 */
	#[OA\Get(
	    path: "/{app_id}/tools/tags/",
	    tags: ["tools"],
	    summary: "List all IP tags",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(
	            type: "object",
	            properties: [
	                new OA\Property(property: "id", type: "integer", example: 1),
	                new OA\Property(property: "type", type: "string", example: "Offline"),
	                new OA\Property(property: "showtag", type: "boolean"),
	                new OA\Property(property: "bgcolor", type: "string", example: "#f59c99"),
	                new OA\Property(property: "fgcolor", type: "string", example: "#ffffff"),
	                new OA\Property(property: "compress", type: "string", enum: ["No","Yes"]),
	                new OA\Property(property: "locked", type: "string", enum: ["No","Yes"]),
	                new OA\Property(property: "updateTag", type: "boolean")
	            ]
	        ))),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/tags/{id}/",
	    tags: ["tools"],
	    summary: "Read a single IP tag by id",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(
	            type: "object",
	            properties: [
	                new OA\Property(property: "id", type: "integer"),
	                new OA\Property(property: "type", type: "string"),
	                new OA\Property(property: "showtag", type: "boolean"),
	                new OA\Property(property: "bgcolor", type: "string"),
	                new OA\Property(property: "fgcolor", type: "string"),
	                new OA\Property(property: "compress", type: "string", enum: ["No","Yes"]),
	                new OA\Property(property: "locked", type: "string", enum: ["No","Yes"]),
	                new OA\Property(property: "updateTag", type: "boolean")
	            ]
	        )),
	        new OA\Response(response: 400, description: "Identifier must be numeric", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/tags/{id}/addresses/",
	    tags: ["tools"],
	    summary: "List addresses currently marked with this IP tag",
	    description: "Matches ipaddresses.state == {id}.",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Address"))),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/devices/",
	    tags: ["tools"],
	    summary: "List all devices",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Device"))),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/devices/{id}/",
	    tags: ["tools"],
	    summary: "Read a single device by id",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(ref: "#/components/schemas/Device")),
	        new OA\Response(response: 400, description: "Identifier must be numeric", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/devices/{id}/addresses/",
	    tags: ["tools"],
	    summary: "List addresses whose switch is this device",
	    description: "Matches ipaddresses.switch == {id}.",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Address"))),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/device_types/",
	    tags: ["tools"],
	    summary: "List all device types",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(
	            type: "object",
	            properties: [
	                new OA\Property(property: "tid", type: "integer", example: 1),
	                new OA\Property(property: "tname", type: "string", example: "Switch"),
	                new OA\Property(property: "tdescription", type: "string", nullable: true),
	                new OA\Property(property: "bgcolor", type: "string", example: "#E6E6E6"),
	                new OA\Property(property: "fgcolor", type: "string", example: "#000")
	            ]
	        ))),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/device_types/{id}/",
	    tags: ["tools"],
	    summary: "Read a single device type by id",
	    description: "{id} maps to the deviceTypes.tid field.",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(
	            type: "object",
	            properties: [
	                new OA\Property(property: "tid", type: "integer"),
	                new OA\Property(property: "tname", type: "string"),
	                new OA\Property(property: "tdescription", type: "string", nullable: true),
	                new OA\Property(property: "bgcolor", type: "string"),
	                new OA\Property(property: "fgcolor", type: "string")
	            ]
	        )),
	        new OA\Response(response: 400, description: "Identifier must be numeric", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/device_types/{id}/devices/",
	    tags: ["tools"],
	    summary: "List devices of this device type",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, description: "Device type id (tid)", schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Device"))),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/vlans/",
	    tags: ["tools"],
	    summary: "List all VLANs (read-only alias; manage VLANs via the /vlans controller)",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Vlan"))),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/vlans/{id}/",
	    tags: ["tools"],
	    summary: "Read a single VLAN by id",
	    description: "{id} maps to the vlans.vlanId field.",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(ref: "#/components/schemas/Vlan")),
	        new OA\Response(response: 400, description: "Identifier must be numeric", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/vlans/{id}/subnets/",
	    tags: ["tools"],
	    summary: "List subnets that belong to this VLAN",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, description: "VLAN id (vlanId)", schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Subnet"))),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/vrfs/",
	    tags: ["tools"],
	    summary: "List all VRFs (read-only alias; manage VRFs via the /vrf controller)",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Vrf"))),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/vrfs/{id}/",
	    tags: ["tools"],
	    summary: "Read a single VRF by id",
	    description: "{id} maps to the vrf.vrfId field.",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(ref: "#/components/schemas/Vrf")),
	        new OA\Response(response: 400, description: "Identifier must be numeric", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/vrfs/{id}/subnets/",
	    tags: ["tools"],
	    summary: "List subnets that belong to this VRF",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, description: "VRF id (vrfId)", schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Subnet"))),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/nameservers/",
	    tags: ["tools"],
	    summary: "List all nameserver sets",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(
	            type: "object",
	            properties: [
	                new OA\Property(property: "id", type: "integer", example: 1),
	                new OA\Property(property: "name", type: "string", example: "Google NS"),
	                new OA\Property(property: "namesrv1", type: "string", description: "Semicolon separated list of nameserver IPs", example: "8.8.8.8;8.8.4.4"),
	                new OA\Property(property: "description", type: "string", nullable: true),
	                new OA\Property(property: "permissions", type: "string", nullable: true, description: "JSON-encoded map of groupId => permission level")
	            ]
	        ))),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/nameservers/{id}/",
	    tags: ["tools"],
	    summary: "Read a single nameserver set by id",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(
	            type: "object",
	            properties: [
	                new OA\Property(property: "id", type: "integer"),
	                new OA\Property(property: "name", type: "string"),
	                new OA\Property(property: "namesrv1", type: "string"),
	                new OA\Property(property: "description", type: "string", nullable: true),
	                new OA\Property(property: "permissions", type: "string", nullable: true)
	            ]
	        )),
	        new OA\Response(response: 400, description: "Identifier must be numeric", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/scanagents/",
	    tags: ["tools"],
	    summary: "List all scan agents",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(
	            type: "object",
	            properties: [
	                new OA\Property(property: "id", type: "integer", example: 1),
	                new OA\Property(property: "name", type: "string", example: "localhost"),
	                new OA\Property(property: "description", type: "string", nullable: true),
	                new OA\Property(property: "type", type: "string", enum: ["direct","api","mysql"]),
	                new OA\Property(property: "code", type: "string", nullable: true, description: "Unique agent code used by the scanning script"),
	                new OA\Property(property: "last_access", type: "string", format: "date-time", nullable: true)
	            ]
	        ))),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/scanagents/{id}/",
	    tags: ["tools"],
	    summary: "Read a single scan agent by id",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(
	            type: "object",
	            properties: [
	                new OA\Property(property: "id", type: "integer"),
	                new OA\Property(property: "name", type: "string"),
	                new OA\Property(property: "description", type: "string", nullable: true),
	                new OA\Property(property: "type", type: "string", enum: ["direct","api","mysql"]),
	                new OA\Property(property: "code", type: "string", nullable: true),
	                new OA\Property(property: "last_access", type: "string", format: "date-time", nullable: true)
	            ]
	        )),
	        new OA\Response(response: 400, description: "Identifier must be numeric", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/locations/",
	    tags: ["tools"],
	    summary: "List all locations",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(
	            type: "object",
	            properties: [
	                new OA\Property(property: "id", type: "integer", example: 1),
	                new OA\Property(property: "name", type: "string"),
	                new OA\Property(property: "description", type: "string", nullable: true),
	                new OA\Property(property: "address", type: "string", nullable: true),
	                new OA\Property(property: "lat", type: "string", nullable: true),
	                new OA\Property(property: "long", type: "string", nullable: true)
	            ]
	        ))),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/locations/{id}/",
	    tags: ["tools"],
	    summary: "Read a single location by id",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(
	            type: "object",
	            properties: [
	                new OA\Property(property: "id", type: "integer"),
	                new OA\Property(property: "name", type: "string"),
	                new OA\Property(property: "description", type: "string", nullable: true),
	                new OA\Property(property: "address", type: "string", nullable: true),
	                new OA\Property(property: "lat", type: "string", nullable: true),
	                new OA\Property(property: "long", type: "string", nullable: true)
	            ]
	        )),
	        new OA\Response(response: 400, description: "Identifier must be numeric", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/locations/{id}/subnets/",
	    tags: ["tools"],
	    summary: "List subnets assigned to this location",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Subnet"))),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/locations/{id}/devices/",
	    tags: ["tools"],
	    summary: "List devices assigned to this location",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Device"))),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/locations/{id}/racks/",
	    tags: ["tools"],
	    summary: "List racks assigned to this location",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(
	            type: "object",
	            properties: [
	                new OA\Property(property: "id", type: "integer"),
	                new OA\Property(property: "name", type: "string"),
	                new OA\Property(property: "size", type: "integer", nullable: true),
	                new OA\Property(property: "location", type: "integer", nullable: true)
	            ]
	        ))),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/locations/{id}/ipaddresses/",
	    tags: ["tools"],
	    summary: "List IP addresses assigned to this location",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Address"))),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/racks/",
	    tags: ["tools"],
	    summary: "List all racks",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(
	            type: "object",
	            properties: [
	                new OA\Property(property: "id", type: "integer", example: 1),
	                new OA\Property(property: "name", type: "string"),
	                new OA\Property(property: "size", type: "integer", nullable: true),
	                new OA\Property(property: "subrack", type: "boolean"),
	                new OA\Property(property: "location", type: "integer", nullable: true),
	                new OA\Property(property: "row", type: "integer"),
	                new OA\Property(property: "hasBack", type: "boolean"),
	                new OA\Property(property: "topDown", type: "boolean"),
	                new OA\Property(property: "description", type: "string", nullable: true),
	                new OA\Property(property: "customer_id", type: "integer", nullable: true)
	            ]
	        ))),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/racks/{id}/",
	    tags: ["tools"],
	    summary: "Read a single rack by id",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(
	            type: "object",
	            properties: [
	                new OA\Property(property: "id", type: "integer"),
	                new OA\Property(property: "name", type: "string"),
	                new OA\Property(property: "size", type: "integer", nullable: true),
	                new OA\Property(property: "subrack", type: "boolean"),
	                new OA\Property(property: "location", type: "integer", nullable: true),
	                new OA\Property(property: "row", type: "integer"),
	                new OA\Property(property: "hasBack", type: "boolean"),
	                new OA\Property(property: "topDown", type: "boolean"),
	                new OA\Property(property: "description", type: "string", nullable: true),
	                new OA\Property(property: "customer_id", type: "integer", nullable: true)
	            ]
	        )),
	        new OA\Response(response: 400, description: "Identifier must be numeric", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/racks/{id}/devices/",
	    tags: ["tools"],
	    summary: "List devices mounted in this rack",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Device"))),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/nat/",
	    tags: ["tools"],
	    summary: "List all NAT rules",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(
	            type: "object",
	            properties: [
	                new OA\Property(property: "id", type: "integer", example: 1),
	                new OA\Property(property: "name", type: "string", nullable: true),
	                new OA\Property(property: "type", type: "string", enum: ["source","static","destination"]),
	                new OA\Property(property: "src", type: "string", nullable: true, description: "JSON-encoded map of object type => array of ids"),
	                new OA\Property(property: "dst", type: "string", nullable: true, description: "JSON-encoded map of object type => array of ids"),
	                new OA\Property(property: "src_port", type: "integer", nullable: true),
	                new OA\Property(property: "dst_port", type: "integer", nullable: true),
	                new OA\Property(property: "device", type: "integer", nullable: true, description: "Device id owning this NAT rule"),
	                new OA\Property(property: "description", type: "string", nullable: true),
	                new OA\Property(property: "policy", type: "string", enum: ["Yes","No"]),
	                new OA\Property(property: "policy_dst", type: "string", nullable: true)
	            ]
	        ))),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/nat/{id}/",
	    tags: ["tools"],
	    summary: "Read a single NAT rule by id",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(
	            type: "object",
	            properties: [
	                new OA\Property(property: "id", type: "integer"),
	                new OA\Property(property: "name", type: "string", nullable: true),
	                new OA\Property(property: "type", type: "string", enum: ["source","static","destination"]),
	                new OA\Property(property: "src", type: "string", nullable: true),
	                new OA\Property(property: "dst", type: "string", nullable: true),
	                new OA\Property(property: "device", type: "integer", nullable: true),
	                new OA\Property(property: "description", type: "string", nullable: true)
	            ]
	        )),
	        new OA\Response(response: 400, description: "Identifier must be numeric", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/nat/{id}/objects/",
	    tags: ["tools"],
	    summary: "Get a NAT rule with its src/dst object id lists decoded",
	    description: "src and dst are returned as a map of object type (e.g. subnets, ipaddresses) to an array of referenced object ids.",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK"),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/nat/{id}/objects_full/",
	    tags: ["tools"],
	    summary: "Get a NAT rule with its src/dst objects fully resolved",
	    description: "Like objects/, but each referenced id is replaced with the full fetched object.",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK"),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/customers/",
	    tags: ["tools"],
	    summary: "List all customers",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(
	            type: "object",
	            properties: [
	                new OA\Property(property: "id", type: "integer", example: 1),
	                new OA\Property(property: "title", type: "string"),
	                new OA\Property(property: "address", type: "string", nullable: true),
	                new OA\Property(property: "postcode", type: "string", nullable: true),
	                new OA\Property(property: "city", type: "string", nullable: true),
	                new OA\Property(property: "state", type: "string", nullable: true),
	                new OA\Property(property: "lat", type: "string", nullable: true),
	                new OA\Property(property: "long", type: "string", nullable: true),
	                new OA\Property(property: "contact_person", type: "string", nullable: true),
	                new OA\Property(property: "contact_phone", type: "string", nullable: true),
	                new OA\Property(property: "contact_mail", type: "string", nullable: true),
	                new OA\Property(property: "note", type: "string", nullable: true),
	                new OA\Property(property: "status", type: "string", enum: ["Active","Reserved","Inactive"])
	            ]
	        ))),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Get(
	    path: "/{app_id}/tools/customers/{id}/",
	    tags: ["tools"],
	    summary: "Read a single customer by id",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(
	            type: "object",
	            properties: [
	                new OA\Property(property: "id", type: "integer"),
	                new OA\Property(property: "title", type: "string"),
	                new OA\Property(property: "address", type: "string", nullable: true),
	                new OA\Property(property: "city", type: "string", nullable: true),
	                new OA\Property(property: "contact_phone", type: "string", nullable: true),
	                new OA\Property(property: "contact_mail", type: "string", nullable: true),
	                new OA\Property(property: "status", type: "string", enum: ["Active","Reserved","Inactive"])
	            ]
	        )),
	        new OA\Response(response: 400, description: "Identifier must be numeric", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 404, description: "No objects found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[\Override]
    public function GET () {
		# validate identifiers
		$this->validate_subcontroller_identifier ();

		# all ?
		if (!isset($this->_params->id2)) {
			$result = $this->Tools->fetch_all_objects ($this->_params->id,  $this->sort_key);
			// result
			if($result===false)							{ $this->Response->throw_exception(404, 'No objects found'); }
			else										{ return ["code"=>200, "data"=>$this->prepare_result ($result, "tools/".$this->_params->id, true, false)]; }
		}
		# by parameter
		elseif (isset($this->_params->id3)) {
			// devices (for deviceTypes)
			if ($this->_params->id == "deviceTypes" && $this->_params->id3=="devices") {
				// fetch
				$result = $this->Tools->fetch_multiple_objects ("devices", "type", $this->_params->id2, "id", true);
			}
			// vlans
			elseif ($this->_params->id == "vlans" && $this->_params->id3=="subnets") {
				// fetch
				$result = $this->Tools->fetch_multiple_objects ("subnets", "vlanId", $this->_params->id2, "id", true);
                // add gateway
    			if($result!=false) {
    				foreach ($result as $k=>$r) {
        				//gateway
                		$gateway = $this->read_subnet_gateway ($r->id);
                		if ( $gateway!== false) {
                    		$result[$k]->gatewayId = $gateway->id;
                		}
                    	//nameservers
                		$ns = $this->read_subnet_nameserver ($r->nameserverId);
                        if ($ns!==false) {
                            $result[$k]->nameservers = $ns;
                        }
    				}
    			}
			}
			// vrfs
			elseif ($this->_params->id == "vrf" && $this->_params->id3=="subnets") {
				// fetch
				$result = $this->Tools->fetch_multiple_objects ("subnets", "vrfId", $this->_params->id2, "id", true);
                // add gateway
    			if($result!=false) {
    				foreach ($result as $k=>$r) {
                		$gateway = $this->read_subnet_gateway ($r->id);
                		if ( $gateway!== false) {
                    		$result[$k]->gatewayId = $gateway->id;
                		}
    				}
    			}
			}
			// locations
			elseif ($this->_params->id == "locations" && ($this->_params->id3=="subnets" || $this->_params->id3=="racks" || $this->_params->id3=="devices" || $this->_params->id3=="ipaddresses")) {
				// fetch
				$result = $this->Tools->fetch_multiple_objects ($this->_params->id3, "location", $this->_params->id2, "id", true);
			}
			// racks
			elseif ($this->_params->id == "racks" && $this->_params->id3=="devices") {
				// fetch
				$result = $this->Tools->fetch_multiple_objects ($this->_params->id3, "rack", $this->_params->id2, "id", true);
			}
			// nat
			elseif ($this->_params->id == "nat" && ($this->_params->id3=="objects" || $this->_params->id3=="objects_full")) {
    			// fetch nat first
    			$result = $this->Tools->fetch_object ($this->_params->id, $this->sort_key, $this->_params->id2);
                // add objects
    			if($result!=false) {
    				// parse result
    				$result->src = $this->parse_nat_objects ($result->src);
    				$result->dst = $this->parse_nat_objects ($result->dst);
    				// full ?
    				if ($this->_params->id3=="objects_full") {
        				if(sizeof($result->src)>0) {
            				foreach ($result->src as $type=>$arr) {
                				foreach ($arr as $k=>$id) {
                    				unset($result->src[$type][$k]);
                    				$result->src[$type][] = $this->Tools->fetch_object ($type, "id", $id);
                                }
            				}
        				}
        				if(sizeof($result->dst)>0) {
            				foreach ($result->dst as $type=>$arr) {
                				foreach ($arr as $k=>$id) {
                    				unset($result->dst[$type][$k]);
                    				$result->dst[$type][] = $this->Tools->fetch_object ($type, "id", $id);
                                }
            				}
        				}
    				}
    			}
			}
			else {
    			$field = "";
				// id3 can only be addresses
				if ($this->_params->id3 != "addresses")	{ $this->Response->throw_exception(400, 'Invalid parameter'); }
				// define identifier
				if ($this->_params->id == "ipTags") 	{ $field = "state"; }
				elseif ($this->_params->id == "devices"){ $field = "switch"; }
				else									{ $this->Response->throw_exception(400, 'Invalid parameter'); }
				// fetch
				$result = $this->Tools->fetch_multiple_objects ("ipaddresses", $field, $this->_params->id2, $this->sort_key, true);
			}
			// result
			if($result===false)							{ $this->Response->throw_exception(404, 'No objects found'); }
			else										{ return ["code"=>200, "data"=>$this->prepare_result ($result, "tools/".$this->_params->id, true, true)]; }

		}
		# by id
		else {
			// numeric
			if(!is_numeric($this->_params->id2)) 		{ $this->Response->throw_exception(400, 'Identifier must be numeric'); }

			$result = $this->Tools->fetch_object ($this->_params->id, $this->sort_key, $this->_params->id2);
			// result
			if($result===false)							{ $this->Response->throw_exception(404, 'No objects found'); }
			else										{ return ["code"=>200, "data"=>$this->prepare_result ($result, "tools/".$this->_params->id, true, false)]; }
		}
	}





	/**
	 * Creates new tools object
	 *
	 *	required parameters:
	 *		id {subcontroller}
	 *
	 *		/tools/{subcontroller}/
	 *
	 * @access public
	 * @return void
	 */
	#[OA\Post(
	    path: "/{app_id}/tools/tags/",
	    tags: ["tools"],
	    summary: "Create a new IP tag",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
	        required: ["type"],
	        properties: [
	            new OA\Property(property: "type", type: "string", example: "Maintenance"),
	            new OA\Property(property: "showtag", type: "boolean", default: true),
	            new OA\Property(property: "bgcolor", type: "string", example: "#000"),
	            new OA\Property(property: "fgcolor", type: "string", example: "#fff"),
	            new OA\Property(property: "compress", type: "string", enum: ["No","Yes"], default: "No"),
	            new OA\Property(property: "locked", type: "string", enum: ["No","Yes"], default: "No"),
	            new OA\Property(property: "updateTag", type: "boolean", default: false)
	        ]
	    )),
	    responses: [
	        new OA\Response(response: 201, description: "Tag created", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "No parameters", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object creation failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Post(
	    path: "/{app_id}/tools/devices/",
	    tags: ["tools"],
	    summary: "Create a new device",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
	        required: ["hostname"],
	        properties: [
	            new OA\Property(property: "hostname", type: "string"),
	            new OA\Property(property: "ip_addr", type: "string", nullable: true),
	            new OA\Property(property: "type", type: "integer", description: "Device type id (deviceTypes.tid)"),
	            new OA\Property(property: "description", type: "string", nullable: true),
	            new OA\Property(property: "sections", type: "string", nullable: true, description: "Semicolon separated list of section ids"),
	            new OA\Property(property: "snmp_community", type: "string", nullable: true),
	            new OA\Property(property: "snmp_version", type: "string", enum: ["0","1","2","3"]),
	            new OA\Property(property: "snmp_port", type: "integer", nullable: true),
	            new OA\Property(property: "snmp_timeout", type: "integer", nullable: true),
	            new OA\Property(property: "snmp_queries", type: "string", nullable: true),
	            new OA\Property(property: "rack", type: "integer", nullable: true),
	            new OA\Property(property: "rack_start", type: "integer", nullable: true),
	            new OA\Property(property: "rack_size", type: "integer", nullable: true),
	            new OA\Property(property: "location", type: "integer", nullable: true),
	            new OA\Property(property: "address", type: "string", nullable: true, description: "If lat/long are not set, resolved to coordinates via Nominatim"),
	            new OA\Property(property: "lat", type: "string", nullable: true),
	            new OA\Property(property: "long", type: "string", nullable: true)
	        ]
	    )),
	    responses: [
	        new OA\Response(response: 201, description: "Device created", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "No parameters, invalid device type or invalid ip_addr", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object creation failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Post(
	    path: "/{app_id}/tools/device_types/",
	    tags: ["tools"],
	    summary: "Create a new device type",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
	        required: ["tname"],
	        properties: [
	            new OA\Property(property: "tname", type: "string", example: "Custom type"),
	            new OA\Property(property: "tdescription", type: "string", nullable: true),
	            new OA\Property(property: "bgcolor", type: "string", example: "#E6E6E6"),
	            new OA\Property(property: "fgcolor", type: "string", example: "#000")
	        ]
	    )),
	    responses: [
	        new OA\Response(response: 201, description: "Device type created", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "No parameters", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object creation failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Post(
	    path: "/{app_id}/tools/vlans/",
	    tags: ["tools"],
	    summary: "Not supported here - VLANs must be created via the /vlans controller",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    responses: [new OA\Response(response: 400, description: "Please use vlans controller", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))]
	)]
	#[OA\Post(
	    path: "/{app_id}/tools/vrfs/",
	    tags: ["tools"],
	    summary: "Not supported here - VRFs must be created via the /vrf controller",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    responses: [new OA\Response(response: 400, description: "Please use vrf controller", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))]
	)]
	#[OA\Post(
	    path: "/{app_id}/tools/nameservers/",
	    tags: ["tools"],
	    summary: "Create a new nameserver set",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
	        required: ["name","namesrv1"],
	        properties: [
	            new OA\Property(property: "name", type: "string", example: "Google NS"),
	            new OA\Property(property: "namesrv1", type: "string", description: "Semicolon separated list of nameserver IPs", example: "8.8.8.8;8.8.4.4"),
	            new OA\Property(property: "description", type: "string", nullable: true),
	            new OA\Property(property: "permissions", type: "string", nullable: true, description: "JSON-encoded map of groupId => permission level")
	        ]
	    )),
	    responses: [
	        new OA\Response(response: 201, description: "Nameserver set created", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "No parameters", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object creation failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Post(
	    path: "/{app_id}/tools/scanagents/",
	    tags: ["tools"],
	    summary: "Create a new scan agent",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
	        required: ["name"],
	        properties: [
	            new OA\Property(property: "name", type: "string"),
	            new OA\Property(property: "description", type: "string", nullable: true),
	            new OA\Property(property: "type", type: "string", enum: ["direct","api","mysql"]),
	            new OA\Property(property: "code", type: "string", nullable: true, description: "Unique agent code used by the scanning script")
	        ]
	    )),
	    responses: [
	        new OA\Response(response: 201, description: "Scan agent created", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "No parameters", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object creation failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Post(
	    path: "/{app_id}/tools/locations/",
	    tags: ["tools"],
	    summary: "Create a new location",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
	        required: ["name"],
	        properties: [
	            new OA\Property(property: "name", type: "string"),
	            new OA\Property(property: "description", type: "string", nullable: true),
	            new OA\Property(property: "address", type: "string", nullable: true, description: "If lat/long are not set, resolved to coordinates via Nominatim"),
	            new OA\Property(property: "lat", type: "string", nullable: true),
	            new OA\Property(property: "long", type: "string", nullable: true)
	        ]
	    )),
	    responses: [
	        new OA\Response(response: 201, description: "Location created", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "No parameters", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object creation failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Post(
	    path: "/{app_id}/tools/racks/",
	    tags: ["tools"],
	    summary: "Create a new rack",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
	        required: ["name"],
	        properties: [
	            new OA\Property(property: "name", type: "string"),
	            new OA\Property(property: "size", type: "integer", nullable: true, description: "Number of U's"),
	            new OA\Property(property: "subrack", type: "boolean", default: false),
	            new OA\Property(property: "location", type: "integer", nullable: true),
	            new OA\Property(property: "row", type: "integer", default: 1),
	            new OA\Property(property: "hasBack", type: "boolean", default: false),
	            new OA\Property(property: "topDown", type: "boolean", default: false),
	            new OA\Property(property: "description", type: "string", nullable: true),
	            new OA\Property(property: "customer_id", type: "integer", nullable: true)
	        ]
	    )),
	    responses: [
	        new OA\Response(response: 201, description: "Rack created", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "No parameters", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object creation failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Post(
	    path: "/{app_id}/tools/nat/",
	    tags: ["tools"],
	    summary: "Create a new NAT rule",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
	        properties: [
	            new OA\Property(property: "name", type: "string", nullable: true),
	            new OA\Property(property: "type", type: "string", enum: ["source","static","destination"], default: "source"),
	            new OA\Property(property: "src", type: "string", nullable: true, description: "JSON-encoded map of object type => array of ids"),
	            new OA\Property(property: "dst", type: "string", nullable: true, description: "JSON-encoded map of object type => array of ids"),
	            new OA\Property(property: "src_port", type: "integer", nullable: true),
	            new OA\Property(property: "dst_port", type: "integer", nullable: true),
	            new OA\Property(property: "device", type: "integer", nullable: true, description: "Device id owning this NAT rule"),
	            new OA\Property(property: "description", type: "string", nullable: true),
	            new OA\Property(property: "policy", type: "string", enum: ["Yes","No"], default: "No"),
	            new OA\Property(property: "policy_dst", type: "string", nullable: true)
	        ]
	    )),
	    responses: [
	        new OA\Response(response: 201, description: "NAT rule created", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "No parameters", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object creation failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Post(
	    path: "/{app_id}/tools/customers/",
	    tags: ["tools"],
	    summary: "Create a new customer",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
	        required: ["title"],
	        properties: [
	            new OA\Property(property: "title", type: "string"),
	            new OA\Property(property: "address", type: "string", nullable: true),
	            new OA\Property(property: "postcode", type: "string", nullable: true),
	            new OA\Property(property: "city", type: "string", nullable: true),
	            new OA\Property(property: "state", type: "string", nullable: true),
	            new OA\Property(property: "lat", type: "string", nullable: true),
	            new OA\Property(property: "long", type: "string", nullable: true),
	            new OA\Property(property: "contact_person", type: "string", nullable: true),
	            new OA\Property(property: "contact_phone", type: "string", nullable: true),
	            new OA\Property(property: "contact_mail", type: "string", nullable: true),
	            new OA\Property(property: "note", type: "string", nullable: true),
	            new OA\Property(property: "status", type: "string", enum: ["Active","Reserved","Inactive"], default: "Active")
	        ]
	    )),
	    responses: [
	        new OA\Response(response: 201, description: "Customer created", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "No parameters", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object creation failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[\Override]
    public function POST () {
		# rewrite tool controller _params
		$table_name = $this->rewrite_tool_input_params ();

		# Get coordinates if address is set
		if(property_exists($this->_params, 'address') && in_array('lat', $this->valid_keys))
			$this->format_location ();

		# check for valid keys
		$values = $this->validate_keys ();

		# validations
		$this->validate_post_patch ();

		# Need at least 1 parameter
		if (sizeof($values)==0)
			$this->Response->throw_exception(400, 'No parameters');

		# execute update
		if(!$this->Admin->object_modify ($table_name, "add", "", $values))
			$this->Response->throw_exception(500, $table_name." object creation failed");

		//set result
		return ["code"=>201, "data"=>$table_name." object created", "id"=>$this->Admin->lastId, "location"=>"/api/".$this->_params->app_id."/tools/".$table_name."/".$this->Admin->lastId."/"];
	}





	/**
	 * Updates tools object
	 *
	 * @access public
	 * @return void
	 */
	#[OA\Patch(
	    path: "/{app_id}/tools/tags/{id}/",
	    tags: ["tools"],
	    summary: "Update an IP tag",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    requestBody: new OA\RequestBody(content: new OA\JsonContent(
	        properties: [
	            new OA\Property(property: "type", type: "string"),
	            new OA\Property(property: "showtag", type: "boolean"),
	            new OA\Property(property: "bgcolor", type: "string"),
	            new OA\Property(property: "fgcolor", type: "string"),
	            new OA\Property(property: "compress", type: "string", enum: ["No","Yes"]),
	            new OA\Property(property: "locked", type: "string", enum: ["No","Yes"]),
	            new OA\Property(property: "updateTag", type: "boolean")
	        ]
	    )),
	    responses: [
	        new OA\Response(response: 200, description: "Tag updated", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "No parameters or invalid identifier", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object edit failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Patch(
	    path: "/{app_id}/tools/devices/{id}/",
	    tags: ["tools"],
	    summary: "Update a device",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    requestBody: new OA\RequestBody(content: new OA\JsonContent(
	        properties: [
	            new OA\Property(property: "hostname", type: "string"),
	            new OA\Property(property: "ip_addr", type: "string", nullable: true),
	            new OA\Property(property: "type", type: "integer", description: "Device type id (deviceTypes.tid)"),
	            new OA\Property(property: "description", type: "string", nullable: true),
	            new OA\Property(property: "sections", type: "string", nullable: true),
	            new OA\Property(property: "snmp_community", type: "string", nullable: true),
	            new OA\Property(property: "rack", type: "integer", nullable: true),
	            new OA\Property(property: "rack_start", type: "integer", nullable: true),
	            new OA\Property(property: "rack_size", type: "integer", nullable: true),
	            new OA\Property(property: "location", type: "integer", nullable: true),
	            new OA\Property(property: "address", type: "string", nullable: true),
	            new OA\Property(property: "lat", type: "string", nullable: true),
	            new OA\Property(property: "long", type: "string", nullable: true)
	        ]
	    )),
	    responses: [
	        new OA\Response(response: 200, description: "Device updated", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "No parameters, invalid device type, invalid ip_addr or invalid identifier", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object edit failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Patch(
	    path: "/{app_id}/tools/device_types/{id}/",
	    tags: ["tools"],
	    summary: "Update a device type",
	    description: "{id} maps to the deviceTypes.tid field.",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    requestBody: new OA\RequestBody(content: new OA\JsonContent(
	        properties: [
	            new OA\Property(property: "tname", type: "string"),
	            new OA\Property(property: "tdescription", type: "string", nullable: true),
	            new OA\Property(property: "bgcolor", type: "string"),
	            new OA\Property(property: "fgcolor", type: "string")
	        ]
	    )),
	    responses: [
	        new OA\Response(response: 200, description: "Device type updated", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "No parameters or invalid identifier", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object edit failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Patch(
	    path: "/{app_id}/tools/vlans/{id}/",
	    tags: ["tools"],
	    summary: "Not supported here - VLANs must be updated via the /vlans controller",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [new OA\Response(response: 400, description: "Please use vlans controller", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))]
	)]
	#[OA\Patch(
	    path: "/{app_id}/tools/vrfs/{id}/",
	    tags: ["tools"],
	    summary: "Not supported here - VRFs must be updated via the /vrf controller",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [new OA\Response(response: 400, description: "Please use vrf controller", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))]
	)]
	#[OA\Patch(
	    path: "/{app_id}/tools/nameservers/{id}/",
	    tags: ["tools"],
	    summary: "Update a nameserver set",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    requestBody: new OA\RequestBody(content: new OA\JsonContent(
	        properties: [
	            new OA\Property(property: "name", type: "string"),
	            new OA\Property(property: "namesrv1", type: "string"),
	            new OA\Property(property: "description", type: "string", nullable: true),
	            new OA\Property(property: "permissions", type: "string", nullable: true)
	        ]
	    )),
	    responses: [
	        new OA\Response(response: 200, description: "Nameserver set updated", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "No parameters or invalid identifier", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object edit failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Patch(
	    path: "/{app_id}/tools/scanagents/{id}/",
	    tags: ["tools"],
	    summary: "Update a scan agent",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    requestBody: new OA\RequestBody(content: new OA\JsonContent(
	        properties: [
	            new OA\Property(property: "name", type: "string"),
	            new OA\Property(property: "description", type: "string", nullable: true),
	            new OA\Property(property: "type", type: "string", enum: ["direct","api","mysql"]),
	            new OA\Property(property: "code", type: "string", nullable: true)
	        ]
	    )),
	    responses: [
	        new OA\Response(response: 200, description: "Scan agent updated", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "No parameters or invalid identifier", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object edit failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Patch(
	    path: "/{app_id}/tools/locations/{id}/",
	    tags: ["tools"],
	    summary: "Update a location",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    requestBody: new OA\RequestBody(content: new OA\JsonContent(
	        properties: [
	            new OA\Property(property: "name", type: "string"),
	            new OA\Property(property: "description", type: "string", nullable: true),
	            new OA\Property(property: "address", type: "string", nullable: true),
	            new OA\Property(property: "lat", type: "string", nullable: true),
	            new OA\Property(property: "long", type: "string", nullable: true)
	        ]
	    )),
	    responses: [
	        new OA\Response(response: 200, description: "Location updated", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "No parameters or invalid identifier", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object edit failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Patch(
	    path: "/{app_id}/tools/racks/{id}/",
	    tags: ["tools"],
	    summary: "Update a rack",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    requestBody: new OA\RequestBody(content: new OA\JsonContent(
	        properties: [
	            new OA\Property(property: "name", type: "string"),
	            new OA\Property(property: "size", type: "integer", nullable: true),
	            new OA\Property(property: "subrack", type: "boolean"),
	            new OA\Property(property: "location", type: "integer", nullable: true),
	            new OA\Property(property: "row", type: "integer"),
	            new OA\Property(property: "hasBack", type: "boolean"),
	            new OA\Property(property: "topDown", type: "boolean"),
	            new OA\Property(property: "description", type: "string", nullable: true),
	            new OA\Property(property: "customer_id", type: "integer", nullable: true)
	        ]
	    )),
	    responses: [
	        new OA\Response(response: 200, description: "Rack updated", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "No parameters or invalid identifier", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object edit failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Patch(
	    path: "/{app_id}/tools/nat/{id}/",
	    tags: ["tools"],
	    summary: "Update a NAT rule",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    requestBody: new OA\RequestBody(content: new OA\JsonContent(
	        properties: [
	            new OA\Property(property: "name", type: "string", nullable: true),
	            new OA\Property(property: "type", type: "string", enum: ["source","static","destination"]),
	            new OA\Property(property: "src", type: "string", nullable: true),
	            new OA\Property(property: "dst", type: "string", nullable: true),
	            new OA\Property(property: "src_port", type: "integer", nullable: true),
	            new OA\Property(property: "dst_port", type: "integer", nullable: true),
	            new OA\Property(property: "device", type: "integer", nullable: true),
	            new OA\Property(property: "description", type: "string", nullable: true),
	            new OA\Property(property: "policy", type: "string", enum: ["Yes","No"]),
	            new OA\Property(property: "policy_dst", type: "string", nullable: true)
	        ]
	    )),
	    responses: [
	        new OA\Response(response: 200, description: "NAT rule updated", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "No parameters or invalid identifier", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object edit failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Patch(
	    path: "/{app_id}/tools/customers/{id}/",
	    tags: ["tools"],
	    summary: "Update a customer",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    requestBody: new OA\RequestBody(content: new OA\JsonContent(
	        properties: [
	            new OA\Property(property: "title", type: "string"),
	            new OA\Property(property: "address", type: "string", nullable: true),
	            new OA\Property(property: "postcode", type: "string", nullable: true),
	            new OA\Property(property: "city", type: "string", nullable: true),
	            new OA\Property(property: "state", type: "string", nullable: true),
	            new OA\Property(property: "lat", type: "string", nullable: true),
	            new OA\Property(property: "long", type: "string", nullable: true),
	            new OA\Property(property: "contact_person", type: "string", nullable: true),
	            new OA\Property(property: "contact_phone", type: "string", nullable: true),
	            new OA\Property(property: "contact_mail", type: "string", nullable: true),
	            new OA\Property(property: "note", type: "string", nullable: true),
	            new OA\Property(property: "status", type: "string", enum: ["Active","Reserved","Inactive"])
	        ]
	    )),
	    responses: [
	        new OA\Response(response: 200, description: "Customer updated", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "No parameters or invalid identifier", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object edit failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[\Override]
    public function PATCH () {
		# rewrite tool controller _params
		$table_name = $this->rewrite_tool_input_params();

		# Get coordinates if address is changed
		if(property_exists($this->_params, 'address') && in_array('lat', $this->valid_keys))
			$this->format_location ();

		# validate and prepare keys
		$values = $this->validate_keys ();

		# validations
		$this->validate_post_patch ();

		# verify object
		$this->validate_tools_object ($table_name);

		# Need at least 2 parameter (id + patch field)
		if (sizeof($values)<=1)
			$this->Response->throw_exception(400, 'No parameters');

		# execute update
		if(!$this->Admin->object_modify ($table_name, "edit", $this->sort_key, $values))
			$this->Response->throw_exception(500, $table_name." object edit failed");

		//set result
		return ["code"=>200, "message"=>$table_name." object updated"];
	}





	/**
	 * Deletes existing vlan
	 *
	 * @access public
	 * @return void
	 */
	#[OA\Delete(
	    path: "/{app_id}/tools/tags/{id}/",
	    tags: ["tools"],
	    summary: "Delete an IP tag",
	    description: "Also clears ipaddresses.state references to this tag.",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "Tag deleted", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "Invalid identifier", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object delete failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Delete(
	    path: "/{app_id}/tools/devices/{id}/",
	    tags: ["tools"],
	    summary: "Delete a device",
	    description: "Also clears ipaddresses.switch references to this device.",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "Device deleted", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "Invalid identifier", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object delete failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Delete(
	    path: "/{app_id}/tools/device_types/{id}/",
	    tags: ["tools"],
	    summary: "Delete a device type",
	    description: "{id} maps to the deviceTypes.tid field.",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "Device type deleted", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "Invalid identifier", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object delete failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Delete(
	    path: "/{app_id}/tools/vlans/{id}/",
	    tags: ["tools"],
	    summary: "Not supported here - VLANs must be deleted via the /vlans controller",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [new OA\Response(response: 400, description: "Please use vlans controller", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))]
	)]
	#[OA\Delete(
	    path: "/{app_id}/tools/vrfs/{id}/",
	    tags: ["tools"],
	    summary: "Not supported here - VRFs must be deleted via the /vrf controller",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [new OA\Response(response: 400, description: "Please use vrf controller", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))]
	)]
	#[OA\Delete(
	    path: "/{app_id}/tools/nameservers/{id}/",
	    tags: ["tools"],
	    summary: "Delete a nameserver set",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "Nameserver set deleted", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "Invalid identifier", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object delete failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Delete(
	    path: "/{app_id}/tools/scanagents/{id}/",
	    tags: ["tools"],
	    summary: "Delete a scan agent",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "Scan agent deleted", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "Invalid identifier", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object delete failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Delete(
	    path: "/{app_id}/tools/locations/{id}/",
	    tags: ["tools"],
	    summary: "Delete a location",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "Location deleted", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "Invalid identifier", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object delete failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Delete(
	    path: "/{app_id}/tools/racks/{id}/",
	    tags: ["tools"],
	    summary: "Delete a rack",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "Rack deleted", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "Invalid identifier", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object delete failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Delete(
	    path: "/{app_id}/tools/nat/{id}/",
	    tags: ["tools"],
	    summary: "Delete a NAT rule",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "NAT rule deleted", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "Invalid identifier", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object delete failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[OA\Delete(
	    path: "/{app_id}/tools/customers/{id}/",
	    tags: ["tools"],
	    summary: "Delete a customer",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
	    ],
	    responses: [
	        new OA\Response(response: 200, description: "Customer deleted", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
	        new OA\Response(response: 400, description: "Invalid identifier", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	        new OA\Response(response: 500, description: "Object delete failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
	    ]
	)]
	#[\Override]
    public function DELETE () {
		# rewrite tool controller _params
		$table_name = $this->rewrite_tool_input_params();

		# verify object
		$this->validate_tools_object ($table_name);

		# set variables for delete
		$values = [];
		$values[$this->sort_key] = $this->_params->{$this->sort_key};

		# execute delete
		if(!$this->Admin->object_modify ($table_name, "delete", $this->sort_key, $values))
			$this->Response->throw_exception(500, $table_name." object delete failed");

		// set update field
		if ($table_name == "devices")	{ $update_field = "switch"; }
		if ($table_name == "ipTags")	{ $update_field = "state"; }

		// delete all references
		if (isset($update_field))
			$this->Admin->remove_object_references ("ipaddresses", $update_field, $this->_params->{$this->sort_key});

		// set result
		return ["code"=>200, "message"=>$table_name." object deleted"];
	}

	/**
	 * Remap _Params and record re table name
	 * @return mixed
	 */
	private function rewrite_tool_input_params() {
		$table_name = $this->_params->id;

		# vlans, vrfs
		if (in_array($table_name, ["vlans", "vrf"]))
			$this->Response->throw_exception(400, 'Please use '.$table_name.' controller');

		# remove table_name and rewrite _params
		unset($this->_params->id);
		if (isset($this->_params->id2)) {
			$this->_params->id = $this->_params->id2;
			unset($this->_params->id2);
		}
		if (isset($this->_params->id3)) {
			$this->_params->id2 = $this->_params->id3;
			unset($this->_params->id3);
		}

		# remap remaining keys
		$this->remap_keys (null, null, $table_name);

		return $table_name;
	}









	/* @validations ---------- */

	/**
	 * Validates subcontroller
	 *
	 * @access private
	 * @return void
	 */
	private function validate_subcontroller () {
		// not options
		if($_SERVER['REQUEST_METHOD']!=="OPTIONS") {
    		if (!in_array($this->_params->id, @$this->subcontrollers))			{ $this->Response->throw_exception(400, "Invalid subcontroller"); }
		}
	}

	/**
	 * Validates identifier for subcontroller
	 *
	 * @access private
	 * @return void
	 */
	private function validate_subcontroller_identifier () {
		// id3
		if (isset($this->_params->id3)) {
			if(!in_array("id3", $this->identifiers[$this->_params->id]))	{ $this->Response->throw_exception(400, "Invalid subcontroller identifier"); }
		}
		// id2
		if (isset($this->_params->id2)) {
			if(!in_array("id2", $this->identifiers[$this->_params->id]))	{ $this->Response->throw_exception(400, "Invalid subcontroller identifier"); }
		}
	}

	/**
	 * Rewrites id (tags -> ipTags) to match database fields
	 *
	 * @access private
	 * @return void
	 */
	private function rewrite_subcontroller () {
		$this->_params->id = array_search($this->_params->id, $this->subcontrollers);
	}

	/**
	 * Validates that tools object exists.
	 *
	 * @access private
	 * @return void
	 */
	private function validate_tools_object ($table_name) {
		$obj = $this->Tools->fetch_object ($table_name, $this->sort_key, $this->_params->{$this->sort_key});
		if (!is_object($obj))
			$this->Response->throw_exception(400, "Invalid identifier");
	}

	/**
	 * Validations for post and patch
	 *
	 * @access private
	 * @return void
	 */
	private function validate_post_patch () {
		$this->validate_device_type ();
		$this->validate_ip ();
	}

	/**
	 * Validates device type
	 *
	 * @access private
	 * @return void
	 */
	private function validate_device_type () {
		if ($this->_params->id == "devices" && isset($this->_params->type)) {
			// numeric
			if (!is_numeric($this->_params->type))							{ $this->Response->throw_exception(400, "Invalid devicetype identifier"); }
			// check
			if ($this->Tools->fetch_object ("deviceTypes", "tid", $this->_params->type)===false)
																			{ $this->Response->throw_exception(400, "Device type does not exist"); }
		}
	}

	/**
	 * Validates IP address
	 *
	 * @access private
	 * @return void
	 */
	private function validate_ip () {
		if (isset($this->_params->ip_addr)) {
			// check
			if(strlen($err = $this->Subnets->verify_cidr_address($this->_params->ip_addr."/32"))>1)
																			{ $this->Response->throw_exception(400, $err); }

		}
	}

	/**
	 * Parses NAT objects into array.
	 *
	 * @access private
	 * @param string $obj
	 * @return array
	 */
	private function parse_nat_objects ($obj) {
    	if($this->Tools->validate_json_string($obj)!==false) {
        	return(db_json_decode($obj, true));
    	}
    	else {
        	return  [];
    	}
	}

	/**
	 * Get latlng from Nominatim
	 *
	 * @method format_location
	 * @return void
	 */
	private function format_location () {
		if((is_blank(@$this->_params->lat) || is_blank(@$this->_params->long)) && !is_blank(@$this->_params->address)) {
            $OSM = new OpenStreetMap($this->Database);
            $latlng = $OSM->get_latlng_from_address ($this->_params->address);
            if(isset($latlng['lat']) && isset($latlng['lng'])) {
                $this->_params->lat  = $latlng['lat'];
                $this->_params->long = $latlng['lng'];
            }
		}
	}
}
