<?php

use OpenApi\Attributes as OA;

/**
 *	phpIPAM API class to work with devices.
 *
 *
 */
class Devices_controller extends Common_api_functions {

    /**
     * Default fields to search.
     *
     * @var mixed
     */
    protected $default_search_fields = ['hostname','ip_addr','description'];


    /**
     * __construct function.
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
        $this->Tools    = $Tools;
        $this->_params  = $params;

        // init required objects
        $this->init_object('Admin', $Database);
        $this->init_object('Subnets', $Database);

        // set valid keys
        $this->set_valid_keys("devices");
    }





    /**
     * Returns json encoded options and version
     *
     * @access public
     * @return void
     */
    #[OA\Options(
        path: "/{app_id}/devices/",
        tags: ["devices"],
        summary: "Discover supported devices routes/methods (HATEOAS)",
        parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
        responses: [new OA\Response(response: 200, description: "OK")]
    )]
    #[\Override]
    public function OPTIONS () {
        // validate
        $this->validate_options_request();

        // get api
        $app = $this->Tools->fetch_object('api', 'app_id', $this->_params->app_id);

        // methods
        $result = [];
        $result['methods'] = [
                                ["href"=>"/api/".$this->_params->app_id."/devices/",                     "methods"=>[["rel"=>"options", "method"=>"OPTIONS"]]],
                                ["href"=>"/api/".$this->_params->app_id."/devices/search/{search_term}", "methods"=>[["rel"=>"search", "method"=>"GET"]]],
                                ["href"=>"/api/".$this->_params->app_id."/devices/{id}/",                "methods"=>[["rel"=>"read", "method"=>"GET"],
                                                                                                                               ["rel"=>"create", "method"=>"POST"],
                                                                                                                               ["rel"=>"update", "method"=>"PATCH"],
                                                                                                                               ["rel"=>"delete", "method"=>"DELETE"]]],
                             ];
        # Response
        return ['code'=>200, 'data'=>$result];
    }






    /**
     * GET devices functions
     *
     *  ID can be:
     *      - /                     // returns all devices
     *      - /{id}/                // returns device details
     *      - /{id}/{subnets}/      // returns all subnets attached to device
     *      - /{id}/{addresses}/    // returns all IP addresses attached to device
     *      - /search/{search_q}/   // searches for devices
     *      - /all/                 // returns all devices
     *
     * @access public
     * @return void
     */
    #[OA\Get(
        path: "/{app_id}/devices/",
        tags: ["devices"],
        summary: "List all devices",
        parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
        responses: [
            new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Device"))),
            new OA\Response(response: 404, description: "No devices configured", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    #[OA\Get(
        path: "/{app_id}/devices/search/{search_term}/",
        tags: ["devices"],
        summary: "Search devices by hostname, ip_addr, description, or custom fields (substring match)",
        parameters: [
            new OA\Parameter(ref: "#/components/parameters/app_id"),
            new OA\Parameter(name: "search_term", in: "path", required: true, schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Device"))),
            new OA\Response(response: 404, description: "No devices found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    #[OA\Get(
        path: "/{app_id}/devices/search/",
        tags: ["devices"],
        summary: "Search devices without a search term (always fails)",
        parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
        responses: [new OA\Response(response: 400, description: "No search term given", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))]
    )]
    #[OA\Get(
        path: "/{app_id}/devices/{id}/",
        tags: ["devices"],
        summary: "Read a single device by id",
        parameters: [
            new OA\Parameter(ref: "#/components/parameters/app_id"),
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(ref: "#/components/schemas/Device")),
            new OA\Response(response: 400, description: "ID must be numeric", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 404, description: "Device not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    #[OA\Get(
        path: "/{app_id}/devices/{id}/addresses/",
        tags: ["devices"],
        summary: "List all IP addresses assigned to this device",
        parameters: [
            new OA\Parameter(ref: "#/components/parameters/app_id"),
            new OA\Parameter(name: "id", in: "path", required: true, description: "Device id", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Address"))),
            new OA\Response(response: 404, description: "No addresses found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    #[OA\Get(
        path: "/{app_id}/devices/{id}/subnets/",
        tags: ["devices"],
        summary: "List all subnets whose gateway/device is this device",
        parameters: [
            new OA\Parameter(ref: "#/components/parameters/app_id"),
            new OA\Parameter(name: "id", in: "path", required: true, description: "Device id", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Subnet"))),
            new OA\Response(response: 404, description: "No subnets found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    #[\Override]
    public function GET () {
        // all objects
        if (!isset($this->_params->id) || $this->_params->id == "all") {
            // fetch all devices
            $result = $this->Tools->fetch_all_objects('devices', 'id');
            // result
            if(!$result)     { return $this->Response->throw_exception(404, "No devices configured"); }
            else             { return ['code'=>200, 'data'=>$this->prepare_result($result, 'devices', true, false)]; }
        }
        // parameters are set
        else {
            // search for devices
            if ($this->_params->id == 'search') {
                // verify that search params are set
                if (isset($this->_params->id2)) {
                    // set query
                    $base_query = "SELECT * from `devices` where ";

                    # Search all custom fields
                    $cfs = array_keys($this->Tools->fetch_custom_fields('devices'));

                    # Merge default fields with custom fields
                    $search_fields = array_merge($cfs, $this->default_search_fields);

                    # Using the search fields, build a string to query parameters chained together with " or "
                    $search_term = $this->_params->id2;
                    $extended_query = implode(' or ', array_map(
                                                         function ($k) {
                                                             return " `$k` like ? ";
                                                         }, $search_fields));

                    # Set up an array of parameters to match the query we built
                    $query_params = array_fill(0, count($search_fields), "%$search_term%");

                    # Put together with the base query
                    $search_query = $base_query . $extended_query;

                    # Search query
                    $result = $this->Database->getObjectsQuery('devices', $search_query, $query_params);

                    // result
                    if(!$result)     { return $this->Response->throw_exception(404, "No devices found"); }
                    else             { return ['code'=>200, 'data'=>$this->prepare_result($result, 'devices', true, false)]; }
                }
                else {
                    $this->Response->throw_exception(400, 'No search term given');
                }
            }
            // not search
            else {
                // Id must be numeric
                if (!is_numeric($this->_params->id)) { $this->Response->throw_exception(400, 'ID must be numeric'); }

                // additional parameter is set?
                if(isset($this->_params->id2)) {
                    // addresses
                    if ($this->_params->id2 == 'addresses') {
                        $result = $this->Tools->fetch_multiple_objects("ipaddresses", 'switch', $this->_params->id, 'id', true);
                    }
                    // subnets
                    elseif ($this->_params->id2 == 'subnets') {
                        $result = $this->Tools->fetch_multiple_objects("subnets", 'device', $this->_params->id, 'id', true);
                    }
                    // error
                    else {
                        $this->Response->throw_exception(400, 'Invalid parameters');
                    }
                }
                // device details
                else {
                    // fetch device
                    $result = $this->Tools->fetch_object('devices', 'id', $this->_params->id);
                    if (!$result) { $this->Response->throw_exception(404, 'Device not found'); }
                }

                // all ok, prepare result
                if($result === false)       { return $this->Response->throw_exception(404, "No ".$this->_params->id2." found"); }
                else                        { return ['code'=>200, 'data'=>$this->prepare_result($result, 'devices', true, false)]; }
            }
        }
    }




    /**
     * Creates new device
     *
     * /devices/
     *
     * @method POST
     */
    #[OA\Post(
        path: "/{app_id}/devices/",
        tags: ["devices"],
        summary: "Create a new device",
        parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ["hostname"],
            properties: [
                new OA\Property(property: "hostname", type: "string", example: "switch-01"),
                new OA\Property(property: "ip_addr", type: "string", example: "192.168.1.1"),
                new OA\Property(property: "type", type: "integer", description: "Device type id, see tools/deviceTypes"),
                new OA\Property(property: "description", type: "string"),
                new OA\Property(property: "sections", type: "string", description: "Comma/semicolon separated list of section ids; defaults to all sections if omitted"),
                new OA\Property(property: "snmp_community", type: "string"),
                new OA\Property(property: "snmp_version", type: "string", enum: ["0","1","2","3"], description: "0=disabled, 1=v1, 2=v2c, 3=v3"),
                new OA\Property(property: "snmp_port", type: "integer", example: 161),
                new OA\Property(property: "snmp_timeout", type: "integer", example: 1000),
                new OA\Property(property: "snmp_queries", type: "string"),
                new OA\Property(property: "snmp_v3_sec_level", type: "string", enum: ["none","noAuthNoPriv","authNoPriv","authPriv"], description: "Representative SNMPv3 field; other snmp_v3_* sub-fields (auth/priv protocol/pass, context name/engine id) are also accepted"),
                new OA\Property(property: "rack", type: "integer"),
                new OA\Property(property: "rack_start", type: "integer"),
                new OA\Property(property: "rack_size", type: "integer"),
                new OA\Property(property: "location", type: "integer")
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: "Device created", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
            new OA\Response(response: 400, description: "Hostname is mandatory / Invalid devicetype identifier / Device type does not exist", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 500, description: "Device creation failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    #[\Override]
    public function POST () {
        # Put incoming keys in order
        $this->remap_keys ();

        # check for valid keys
        $values = $this->validate_keys ();

        # validations
        $this->validate_device_type ();
		$this->validate_device_edit();

        // provide default params if they are not set
        if(!isset($this->_params->sections)) {
            $sections_json = $this->get_all_sections_delimited ();
            if($sections_json!==false) {
                $values['sections'] = $sections_json;
            }
        }

        // execute update
        if (!$this->Admin->object_modify('devices', 'add', '', $values)) {
                                    { $this->Response->throw_exception(500, 'Device creation failed'); }
        }
        else {
            //set result
            return ["code"=>201, "message"=>"Device created", "id"=>$this->Admin->lastId, "location"=>"/api/".$this->_params->app_id."/devices/".$this->Admin->lastId."/"];
        }
    }






    /**
     * Update device details
     *
     * @method PATCH
     */
    #[OA\Patch(
        path: "/{app_id}/devices/{id}/",
        tags: ["devices"],
        summary: "Update an existing device",
        parameters: [
            new OA\Parameter(ref: "#/components/parameters/app_id"),
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "hostname", type: "string", example: "switch-01"),
                new OA\Property(property: "ip_addr", type: "string", example: "192.168.1.1"),
                new OA\Property(property: "type", type: "integer", description: "Device type id, see tools/deviceTypes"),
                new OA\Property(property: "description", type: "string"),
                new OA\Property(property: "sections", type: "string"),
                new OA\Property(property: "snmp_community", type: "string"),
                new OA\Property(property: "snmp_version", type: "string", enum: ["0","1","2","3"]),
                new OA\Property(property: "snmp_port", type: "integer"),
                new OA\Property(property: "snmp_timeout", type: "integer"),
                new OA\Property(property: "snmp_queries", type: "string"),
                new OA\Property(property: "snmp_v3_sec_level", type: "string", enum: ["none","noAuthNoPriv","authNoPriv","authPriv"], description: "Representative SNMPv3 field; other snmp_v3_* sub-fields are also accepted"),
                new OA\Property(property: "rack", type: "integer"),
                new OA\Property(property: "rack_start", type: "integer"),
                new OA\Property(property: "rack_size", type: "integer"),
                new OA\Property(property: "location", type: "integer")
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: "Device updated", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
            new OA\Response(response: 400, description: "Invalid device id / Hostname is mandatory / Invalid devicetype identifier / Device type does not exist / No parameters", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 500, description: "Device edit failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    #[\Override]
    public function PATCH (){
        # Put incoming keys back in order
        $this->remap_keys();

        # validations
        $this->validate_device_type();
		$this->validate_device_edit();

        # validate and prepare keys
        $values = $this->validate_keys();

        # only 1 parameter ?
        if (sizeof($values) == 1)   { $this->Response->throw_exception(400, 'No parameters'); }

        # execute update
        if (!$this->Admin->object_modify('devices', 'edit', 'id', $values)) {
            $this->Response->throw_exception(500, 'Device edit failed');
        } else {
            // fetch the updated object and hand it back to the client
            return ["code"=>200, "message"=>"Device updated", "id"=>$this->Admin->lastId, "location"=>"/api/".$this->_params->app_id."/devices/".$values['id']."/"];
        }
    }






    /**
     * Delete existing device
     *
     * @method DELETE
     */
    #[OA\Delete(
        path: "/{app_id}/devices/{id}/",
        tags: ["devices"],
        summary: "Delete a device",
        parameters: [
            new OA\Parameter(ref: "#/components/parameters/app_id"),
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Device deleted", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
            new OA\Response(response: 400, description: "Invalid device id", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 404, description: "Device does not exist", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 500, description: "Device delete failed", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    #[\Override]
    public function DELETE () {
		# validations
		$this->validate_device_edit();

        # set variables for delete
        $values = [];
        $values['id'] = $this->_params->id;

        # execute delete
        if (!$this->Admin->object_modify('devices', 'delete', 'id', $values)) {
            $this->Response->throw_exception(500, 'Device delete failed');
        }
        else {
            // delete all references
            $this->Admin->remove_object_references('ipaddresses', 'switch', $this->_params->id);

            // set result
            return ["code"=>200, "message"=>"Device deleted"];
        }
    }






    /**
     * Validate device type
     *
     * @method validate_device_type
     * @return [type]               [description]
     */
    private function validate_device_type() {
        if (isset($this->_params->type)) {
            // numeric
            if (!is_numeric($this->_params->type)) {
                $this->Response->throw_exception(400, 'Invalid devicetype identifier');
            }
            // check
            if ($this->Tools->fetch_object('deviceTypes', 'tid', $this->_params->type) === false) {
                $this->Response->throw_exception(400, 'Device type does not exist');
            }
        }
    }

    /**
     * Create delimited string from all sections for default permissions
     *
     * @method get_all_sections_delimited
     * @return [type]                [description]
     */
    private function get_all_sections_delimited () {
        $sections = $this->Admin->fetch_all_objects ("sections");
        // reformat
        if($sections!==false) {
            $sections_all =  [];
            foreach ($sections as $s) {
                $sections_all[$s->id] = $s->id;
            }
            $sections = implode(";",$sections_all);
        }
        // return
        return $sections;
    }


	/**
	 * Validates device on edit
	 *
	 * @access private
	 * @return void
	 */
	private function validate_device_edit () {
		// delete checks
		if($_SERVER['REQUEST_METHOD']=="DELETE") {
			// ID must be numeric
			if(!is_numeric($this->_params->id))												{ $this->Response->throw_exception(400, "Invalid device id"); }
			// check that device exists
			if($this->Admin->fetch_object ("devices", "id", $this->_params->id)===false)	{ $this->Response->throw_exception(404, "Device does not exist"); }
		}
		// create checks
		elseif ($_SERVER['REQUEST_METHOD']=="POST") {
			// name must be present
			if(@$this->_params->hostname == "" || !isset($this->_params->hostname))			{ $this->Response->throw_exception(400, "Hostname is mandatory"); }
		}
		// update checks
		elseif ($_SERVER['REQUEST_METHOD']=="PATCH") {
			// ID must be numeric
			if(!is_numeric($this->_params->id))												{ $this->Response->throw_exception(400, "Invalid device id"); }
			// name cannot be nothing
			if(isset($this->_params->hostname) && @$this->_params->hostname == "")			{ $this->Response->throw_exception(400, "Hostname is mandatory"); }
		}
	}

}
