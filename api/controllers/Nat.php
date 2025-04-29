<?php

/**
 *  phpIPAM API class to work with NAT
 *
 *
 */
class Nat_controller extends Common_api_functions {
    /**
     * _params provided
     *
     * @var mixed
     * @access public
     */
    public $_params;

    /**
     * custom_fields
     *
     * @var mixed
     * @access protected
     */
    public $custom_fields;

    /**
     * Database object
     *
     * @var mixed
     * @access protected
     */
    protected $Database;


    /**
     * Addresses object from master Addresses class
     *
     * @var mixed
     * @access public
     */
    public $Addresses;

    /**
     * Devices objects from master Devices class
     *
     * @var mixed
     * @access public
     **/
    public $Devices;

    /**
     *  Response handler
     *
     * @var mixed
     * @access protected
     */
    protected $Response;

    /**
     * Master Tools object
     *
     * @var mixed
     * @access protected
     */
    protected $Tools;

    /**
     * valid src & dst sub-fields
     **/
    protected $valid_subfields;

    /**
     * Indirection using variable name to data
     **/
    protected $validation_data;

    /**
     * List of valid NAT types
     **/
    protected $valid_types;

    /**
     * __construct function
     *
     * @access public
     * @param class $Database
     * @param class $Tools
     * @param mixed $params
     * @param mixed $Response
     */
    public function __construct($Database, $Tools, $params, $Response) {
        $this->Database = $Database;
        $this->Response = $Response;
        $this->Tools    = $Tools;
        $this->_params  = $params;
        $this->init_object ("Admin", $Database);
        $this->init_object ("Addresses", $Database);
        $this->init_object ("Devices", $Database);
        $this->set_valid_keys ("nat");
        $this->valid_types = array("source", "destination", "static");
        $this->validation_data = array(
            "ipaddresses" => array(
                "object"   => $this->Addresses,
                "function" => 'fetch_address',
            )
        );
    }

    /**
     * Returns json encoded options and version
     *
     * @access public
     * @return void
     */
    public function OPTIONS () {
        // validate
        $this->validate_options_request ();

        // methods
        $result = array();
        $result['methods'] = array(
            array("href"=>"/api/".$this->_params->app_id."/nat/",       "methods"=>array(array("rel"=>"options", "method"=>"OPTIONS"))),
            array("href"=>"/api/".$this->_params->app_id."/nat/{id}/",  "methods"=>array(array("rel"=>"read",    "method"=>"GET"),
                                                                                         array("rel"=>"create",  "method"=>"POST"),
                                                                                         array("rel"=>"update",  "method"=>"PATCH"),
                                                                                         array("rel"=>"delete",  "method"=>"DELETE"))),
        );
        return array("code"=>200, "data"=>$result);
    }

    /**
     * GET NAT functions
     *
     *  ID can be:
     *      - /                     // returns all NATs
     *      - /{id}/                // returns NAT details
     *
     *  If no ID is provided all nats are returned
     *
     * @access public
     * @return void
     */
    public function GET () {
        # Get all NATs from DB
        if (!isset($this->_params->id) || $this->_params->id == "all") {
            $result = $this->Tools->fetch_all_objects ("nat", 'id');
            return array("code"=>200, "data"=>$this->convert_from_DB($result), true, true);#$this->prepare_result ($result, null, true, true));
        }
        elseif (isset($this->_params->id) && is_numeric($this->_params->id)) {
            $result = $this->Tools->fetch_multiple_objects ("nat", "id", $this->_params->id, 'id', true);
            return array("code"=>200, "data"=>$this->convert_from_DB($result), true, true);
        }
        else {
            $this->Response->throw_exception(400, "Invalid ID format");
        }
    }

    /**
     * Convert DB<>API format (especially for JSON-formatted fields)
     * APÏ shows a valid data structure instead of JSON-formatted field
     *
     * @access private
     * @return list of formatted results
     **/
    private function convert_from_DB($results) {
        $out = array();
        foreach ($results as $r) {
            $rr = array(
                "src"  => array(),
                "dst"  => array()
            );
            foreach ($r as $k => $v) {
                if ($k != "src" && $k != "dst") {
                    $rr[$k] = $v;
                }
                else {
                    # Better source and destination presentation (json decode values from DB)
                    $jd = (array) db_json_decode($v, true);
                    if ($jd && $jd["ipaddresses"]) {
                        $rr[$k] = array('ipaddresses' => $jd["ipaddresses"]);
                    }
                }
            }
            array_push($out, $rr);
        }
        return $out;
    }

    /**
     * HEAD, no response
     *
     * @access public
     * @return void
     */
    public function HEAD () {
        return $this->GET ();
    }

    /**
     * Creates new NAT
     *
     * @access public
     * @return void
     */
    public function POST () {
        # check for valid keys
        $values = $this->validate_keys ();
        # validate input format
        $this->validate_nat_edit();
        foreach (array("src","dst") as $k)
            $values[$k] = json_encode($values[$k]);
        #$this->Response->throw_exception(500, "AAA".json_encode($values['src']));
        if (!$this->Admin->object_modify ("nat", "add", "id", $values)) {
            $this->Response->throw_exception(500, "NAT creation failed");
        }
        return array("code"=>201, "message"=>"NAT created", "id"=>$this->Admin->lastId, "location"=>"/api".$this->_params->app_id."/nat/".$this->Admin->lastId."/");
    }


    /**
     * Updates existing NAT
     *
     * @access public
     * @return void
     */
    public function PATCH () {
        # check for valid keys
        $values = $this->validate_keys ();
        # validate input format
        $this->validate_nat_edit();
        foreach (array("src","dst") as $k) {
            if ( array_key_exists($k, $values) ) {
                $values[$k] = json_encode($values[$k]);
            }
        }

        if (!$this->Admin->object_modify ("nat", "edit", "id", $values)) {
            $this->Response->throw_exception(500, "NAT modification failed");
        }
        return array("code"=>200, "message"=>"NAT modified", "id"=>$this->_params->id, "location"=>"/api".$this->_params->app_id."/nat/".$this->Admin->lastId."/");
    }

    /**
     * Validates request's fields. Throw exception including error message if failing.
     *
     * @access private
     * @return void
     **/
    private function validate_nat_edit() {
        if ($_SERVER['REQUEST_METHOD']=="PATCH" || $_SERVER['REQUEST_METHOD']=="DELETE") {
            if (!isset($this->_params->id) || !is_numeric($this->_params->id)) {
                $this->Response->throw_exception(400, "Invalid ID format");
            }
            if (! $this->Tools->fetch_multiple_objects ("nat", "id", $this->_params->id, 'id', true)) {
                $this->Response->throw_exception(404, "ID not found");
            }
        }
        if ($_SERVER['REQUEST_METHOD']=="POST" || $_SERVER['REQUEST_METHOD']=="PATCH") {
            # Check optional fields format
            # TBD: subnets for src & dst ?
            foreach (array("src_port", "dst_port") as  $k) {
                if ( $this->_params->$k && !is_numeric($this->_params->$k) )  {
                    $this->Response->throw_exception(400, "Invalid value for $k (must be numeric)");
                }
            }
            foreach (array("src","dst") as $k) {
                $this->verify_src_dst($k);
            }
            $this->verify_device();

        }
        if ($_SERVER['REQUEST_METHOD']=="POST") {
            if (!isset($this->_params->name))
                $this->Response->throw_exception(400, "Missing NAT name"); # Seems to be mandatory in the GUI
            if (!isset($this->_params->type))
                $this->Response->throw_exception(400, "Missing NAT type");
            elseif (!in_array($this->_params->type, $this->valid_types))
                $this->Response->throw_exception(400, "Invalid type");
            if (isset($this->_params->id) )
                $this->Response->throw_exception(400, "ID should not be set for creation");
        }
    }

    /**
     * Verify the device ID provided by the user is valid
     *
     * @access private
     * @return boolean
     **/
    private function verify_device() {
        if (isset($this->_params->device)) {
            if (is_numeric($this->_params->device)) {
                $result = $this->Tools->fetch_object('devices', 'id', $this->_params->device);
                if (!$result)
                    $this->Response->throw_exception(400, "Device ID not found");
            }
            else {
                $this->Response->throw_exception(400, "Wrong format for device ID (must be numeric)");
            }
        }
    }

    /**
     * Verify user-provided "src" or "dst" parameters. Checking format is valid and the provided IDs are actually real.
     *
     * @access private
     * @param String $k
     * @return boolean
     **/
    private function verify_src_dst($k) {
        if ($this->_params->$k) {
            #$input_param = (array) db_json_decode($this->_params->$k);
            $input_param = $this->_params->$k;
            if (!is_array($input_param)) {
                $this->Response->throw_exception(400, "Invalid $k format (Must be an array");
            }
            $input_out = array();
            foreach ($input_param as $pk => $pv) {
                if (!in_array($pk, array_keys($this->validation_data))) {
                    $this->Response->throw_exception(400, "Invalid key identifier for $k ($pk)");
                }
                if (!is_array($pv)) {
                    $this->Response->throw_exception(400, "Invalid value for $k (must be an array)");
                }
                $values = array();
                foreach ($pv as $v) {
                    if (!is_numeric($v)) {
                        $this->Response->throw_exception(400, "Invalid value $v (must be an integer)");
                    }
                    else {
                        # Validate IDs !
                        $func   = $this->validation_data[$pk]["function"];
                        $object = $this->validation_data[$pk]["object"]->$func("id", $v);
                        if ($object) {
                            array_push($values, $v);
                        }
                        else {
                            $this->Response->throw_exception(404, "ID not found for type $pk, ID: $v");
                        }
                    }
                }
                array_push($input_out, array($pk => $values));
            }
        }
    }

    /**
     * Deletes existing NAT
     *
     * @access public
     * @return void
     */
    public function DELETE () {
        $values = $this->validate_keys();
        $this->validate_nat_edit();
        if (isset($this->_params->id) && is_numeric($this->_params->id)) {
            // $values = array();
            // $values['id'] = $this->_params->id;
            if(!$this->Admin->object_modify ("nat", "delete", "id", $values)){
                $this->Response->throw_exception(500, "NAT delete failed");
            }
            else {
                return array("code"=>200, "message"=>"NAT deleted");
            }
        }
        else {
            $this->Response->throw_exception(400, "Invalid ID format");
        }
    }
}
