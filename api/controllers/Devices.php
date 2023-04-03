<?php

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
    protected $default_search_fields = array('hostname','ip_addr','description');


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
    public function OPTIONS () {
        // validate
        $this->validate_options_request();

        // get api
        $app = $this->Tools->fetch_object('api', 'app_id', $this->_params->app_id);

        // methods
        $result = array();
        $result['methods'] = array(
                                array("href"=>"/api/".$this->_params->app_id."/devices/",                     "methods"=>array(array("rel"=>"options", "method"=>"OPTIONS"))),
                                array("href"=>"/api/".$this->_params->app_id."/devices/search/{search_term}", "methods"=>array(array("rel"=>"search", "method"=>"GET"))),
                                array("href"=>"/api/".$this->_params->app_id."/devices/{id}/",                "methods"=>array(array("rel"=>"read", "method"=>"GET"),
                                                                                                                               array("rel"=>"create", "method"=>"POST"),
                                                                                                                               array("rel"=>"update", "method"=>"PATCH"),
                                                                                                                               array("rel"=>"delete", "method"=>"DELETE"))),
                             );
        # Response
        return array('code'=>200, 'data'=>$result);
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
    public function GET () {
        // all objects
        if (!isset($this->_params->id) || $this->_params->id == "all") {
            // fetch all devices
            $result = $this->Tools->fetch_all_objects('devices', 'id');
            // result
            if(!$result)     { return $this->Response->throw_exception(404, "No devices configured"); }
            else             { return array('code'=>200, 'data'=>$this->prepare_result($result, 'devices', true, false)); }
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
                    $result = $this->Database->getObjectsQuery($search_query, $query_params);

                    // result
                    if(!$result)     { return $this->Response->throw_exception(404, "No devices found"); }
                    else             { return array('code'=>200, 'data'=>$this->prepare_result($result, 'devices', true, false)); }
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
                else                        { return array('code'=>200, 'data'=>$this->prepare_result($result, 'devices', true, false)); }
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
    public function POST () {
        # Put incoming keys in order
        $this->remap_keys ();

        # check for valid keys
        $values = $this->validate_keys ();

        # validations
        $this->validate_device_type ();

        # only 1 parameter ?
        if (sizeof($values) == 1)   { $this->Response->throw_exception(400, 'No parameters'); }

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
            return array("code"=>201, "message"=>"Device created", "id"=>$this->Admin->lastId, "location"=>"/api/".$this->_params->app_id."/devices/".$this->Admin->lastId."/");
        }
    }






    /**
     * Update device details
     *
     * @method PATCH
     */
    public function PATCH (){
        # Put incoming keys back in order
        $this->remap_keys();

        # validations
        $this->validate_device_type();

        # validate and prepare keys
        $values = $this->validate_keys();

        # only 1 parameter ?
        if (sizeof($values) == 1)   { $this->Response->throw_exception(400, 'No parameters'); }

        # execute update
        if (!$this->Admin->object_modify('devices', 'edit', 'id', $values)) {
            $this->Response->throw_exception(500, 'Device edit failed');
        } else {
            // fetch the updated object and hand it back to the client
            return array("code"=>200, "message"=>"Device updated", "id"=>$this->Admin->lastId, "location"=>"/api/".$this->_params->app_id."/devices/".$values['id']."/");
        }
    }






    /**
     * Delete existing device
     *
     * @method DELETE
     */
    public function DELETE () {
        # set variables for delete
        $values = array();
        $values['id'] = $this->_params->id;

        # check that section exists
        if($this->Admin->fetch_object ("devices", "id", $this->_params->id)===false)
                                                        { $this->Response->throw_exception(404, "Device does not exist"); }

        # execute delete
        if (!$this->Admin->object_modify('devices', 'delete', 'id', $values)) {
            $this->Response->throw_exception(500, 'Device delete failed');
        }
        else {
            // delete all references
            $this->Admin->remove_object_references('ipaddresses', 'switch', $this->_params->id);

            // set result
            return array("code"=>200, "message"=>"Device deleted");
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
            $sections_all = array ();
            foreach ($sections as $s) {
                $sections_all[$s->id] = $s->id;
            }
            $sections = implode(";",$sections_all);
        }
        // return
        return $sections;
    }
}
