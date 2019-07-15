<?php

/**
 *	phpIPAM API class to work with groups
 *
 *
 */
class Groups_controller extends Common_api_functions {

    /**
     * _params provided
     *
     * @var mixed
     * @access public
     */
    public $_params;

    /**
     * Database object
     *
     * @var mixed
     * @access protected
     */
    protected $Database;

    /**
     *  Response handler
     *
     * @var Responses
     * @access protected
     */
    protected $Response;

    /**
     * Master Subnets object
     *
     * @var mixed
     * @access protected
     */
    protected $Subnets;

    /**
     * Master Groups object
     *
     * @var ipGroups
     * @access protected
     */
    protected $ipGroups;

    /**
     * Master Tools object
     *
     * @var Tools
     * @access protected
     */
    protected $Tools;

    /**
     * __construct function
     *
     * @access public
     * @param Database_PDO $Database
     * @param Tools        $Tools
     * @param array        $params
     * @param Responses    $Response
     */
    public function __construct($Database, $Tools, $params, $Response) {
        $this->Database = $Database;
        $this->Response = $Response;
        $this->Tools 	= $Tools;
        $this->_params 	= $params;
        # groups
        // init required objects
        $this->init_object ("ipGroups", $Database);
        # set valid keys
        $this->set_valid_keys ("ipGroups");
    }

    /**
     * Returns json encoded options and version
     *
     * @access public
     * @return array
     */
    public function OPTIONS () {
        // validate
        $this->validate_options_request ();

        // methods
        $result = array();
        $result['methods'] = array(
            array(
                "href"    => "/api/".$this->_params->app_id."/groups/",
                "methods" => array(
                    array(
                        "rel"    => "options",
                        "method" => "OPTIONS"
                    )
                )
            ),
            array(
                "href"    =>"/api/" . $this->_params->app_id . "/groups/{id}/",
                "methods" => array(
                    array("rel"=>"read",   "method"=>"GET"),
                    array("rel"=>"create", "method"=>"POST"),
                    array("rel"=>"update", "method"=>"PATCH"),
                    array("rel"=>"delete", "method"=>"DELETE")
                )
            ),
        );
        # result
        return $this->getPreparedResult($result);
    }

    /**
     * GET groups functions
     *
     *	ID can be:
     *      - /                     // returns all groups
     *		- /{id}/                // returns group details
     *		- /{name}/ 				// group name
     *      - /addresses/           // returns all addresses grouped by ipGroups
     *
     *	If no ID is provided all groups are returned
     *
     * @access public
     * @throws Exception
     * @return array
     */
    public function GET() {
        if ($this->_params->id == 'addresses') {
            $result = $this->ipGroups->get_addresses_grouped_by_group();

            return $this->getPreparedResult($result);
        }

        if (isset($this->_params->id)) {
            $method = is_numeric($this->_params->id) ? 'id' : 'name';
            $result = $this->ipGroups->fetch_group($method, $this->_params->id);
        } else {
            $result = $this->ipGroups->fetch_all_groups();
        }

        if ($result === false) {
            $this->Response->throw_exception(404, $this->Response->errors[404]);
        }

        return $this->getPreparedResult($result);
    }

    /**
     * HEAD, no response
     *
     * @access public
     * @throws Exception
     * @return array
     */
    public function HEAD() {
        return $this->GET();
    }

    /**
     * Creates new group
     *
     * @access public
     * @throws Exception
     * @return array
     */
    public function POST() {
        # check for valid keys
        $values = $this->validate_keys ();

        // remove editDate if set
        unset($values['editDate']);

        # validate mandatory parameters
        if (strlen($this->_params->name) < 2) {
            $this->Response->throw_exception(400, 'Name is mandatory or too short (mininum 3 characters)');
        }

        # execute update
        if (!$this->ipGroups->modify_group ("add", $values)) {
            $this->Response->throw_exception(500, "Group create failed");
        } else {
            //set result
            return array(
                "code"     => 201,
                "message"  => "Group created",
                "id"       => $this->ipGroups->lastInsertId,
                "location" => "/api/".$this->_params->app_id."/groups/".$this->ipGroups->lastInsertId."/"
            );
        }
    }

    /**
     * Updates existing group
     *
     * @access public
     * @throws Exception
     * @return array
     */
    public function PATCH() {
        # Check for id
        if (!isset($this->_params->id)) {
            $this->Response->throw_exception(400, "Group Id required");
        }

        # check that group exists
        if ($this->ipGroups->fetch_group("id", $this->_params->id) === false) {
            $this->Response->throw_exception(404, "Group does not exist");
        }

        # validate and prepare keys
        $values = $this->validate_keys ();

        # execute update
        if (!$this->ipGroups->modify_group ("edit", $values)) {
            $this->Response->throw_exception(500, "Group update failed");
        }

        return array("code"=>200, "data"=>NULL);
    }


    /**
     * Deletes existing group along with subnets and addresses
     *
     * @access public
     * @throws Exception
     * @return array
     */
    public function DELETE() {
        # Check for id
        if (!isset($this->_params->id)) {
            $this->Response->throw_exception(400, "Group Id required");
        }

        if ($this->ipGroups->fetch_group("id", $this->_params->id) === false) {
            $this->Response->throw_exception(404, "Group does not exist");
        }

        # set variables for update
        $values       = array();
        $values["id"] = $this->_params->id;

        # execute update
        if (!$this->ipGroups->modify_group("delete", $values)) {
            $this->Response->throw_exception(500, "Group delete failed");
        }

        return array("code"=>200, "data"=>NULL);
    }
}

?>
