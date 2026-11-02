<?php

/**
 *  API Parameter class
 */
class API_params extends Params
{
    /**
     * Read array of arguments
     *
     * @param  array  $args
     * @param  bool  $strip_tags
     * @param  bool  $html_escape
     * @return void
     */
    #[Override]
    public function read($args, $strip_tags = false, $html_escape = false)
    {
        if (is_array($args) && isset($args['controller'])) {
            $args['controller'] = strtolower((string) $args['controller']);
        }

        parent::read($args, $strip_tags, $html_escape);
    }
}

/**
 *  phpIPAM API class for common functions
 */
class Common_api_functions
{
    /**
     * controller_keys
     *
     * @var array
     */
    protected $controller_keys;

    /**
     * _params provided from request
     *
     * @var API_params
     */
    public $_params;

    /**
     * Custom fields
     *
     * @var array
     */
    public $custom_fields;

    /**
     * valid_keys
     *
     * @var array
     */
    protected $valid_keys;

    /**
     * custom_keys
     *
     * @var array
     */
    protected $custom_keys;

    /**
     * Keys to be removed
     *
     * @var array
     */
    protected $remove_keys;

    /**
     * keys
     *
     * @var array
     */
    protected $keys;

    /**
     * Database object
     *
     * @var Database_PDO
     */
    protected $Database;

    /**
     * Master Tools class
     *
     * @var Tools
     */
    protected $Tools;

    /**
     * Response class
     *
     * @var Responses
     */
    protected $Response;

    /**
     * Master subnets class
     *
     * @var Subnets
     */
    protected $Subnets;

    /**
     * Master Addresses object
     *
     * @var Addresses
     */
    protected $Addresses;

    /**
     * Master Sections object
     *
     * @var Sections
     */
    protected $Sections;

    /**
     * Master user class
     *
     * @var User
     */
    protected $User;

    /**
     * Master Admin object
     *
     * @var Admin
     */
    protected $Admin;

    /**
     * App object - will be passed by index.php
     * to provide app details
     *
     * @var false|object
     */
    public $app = false;

    /**
     * Provide default REQUEST_METHODs
     *
     * @return string
     */
    protected function request_method()
    {
        return strtoupper((string) $_SERVER['REQUEST_METHOD']);
    }

    private function NOT_IMPLEMENTED()
    {
        return ['code' => 501, 'message' => 'Method not implemented'];
    }

    public function OPTIONS()
    {
        return $this->NOT_IMPLEMENTED();
    }

    public function GET()
    {
        return $this->NOT_IMPLEMENTED();
    }

    public function POST()
    {
        return $this->NOT_IMPLEMENTED();
    }

    public function PATCH()
    {
        return $this->NOT_IMPLEMENTED();
    }

    public function DELETE()
    {
        return $this->NOT_IMPLEMENTED();
    }

    /* Alias HEAD to GET */

    public function HEAD()
    {
        return $this->GET();
    }

    /* Alias PUT to PATCH */

    public function PUT()
    {
        return $this->PATCH();
    }

    /**
     * Initializes new Object.
     *
     * @param  mixed  $Object_name  // object name
     * @param  mixed  $Database  // Database object
     */
    protected function init_object($Object_name, $Database)
    {
        // admin fix
        if ($Object_name == 'Admin') {
            $this->{$Object_name} = new $Object_name($Database, false);
        } elseif ($Object_name == 'User') {
            // User fix
            $this->{$Object_name} = new $Object_name($Database, true);
            $this->{$Object_name}->user = null;
        } else {
            // default
            $this->{$Object_name} = new $Object_name($Database);
        }

        // set exit method
        $this->{$Object_name}->Result->exit_method = 'exception';
        // set API flag
        $this->{$Object_name}->api = true;
    }

    /**
     * Sets valid keys for actions
     *
     * @param  mixed  $controller
     * @return void
     */
    protected function set_valid_keys($controller)
    {
        // array of controller keys
        $this->controller_keys = ['app_id', 'controller'];

        // array of all valid keys - fetch from SCHEMA
        $this->valid_keys = $this->Tools->fetch_standard_fields($controller);

        // add custom fields
        $custom_fields = $this->Tools->fetch_custom_fields($controller);
        if (is_array($custom_fields)) {
            foreach ($custom_fields as $cf) {
                $this->custom_keys[] = $cf['name'];
            }
        }

        // save custom fields
        $this->custom_fields = $custom_fields;

        // merge all
        $this->valid_keys = array_merge($this->controller_keys, $this->valid_keys);
        if (isset($this->custom_keys)) {
            $this->valid_keys = array_merge($this->valid_keys, $this->custom_keys);
        }

        // set items to remove
        $this->remove_keys = ['editDate'];
        // remove update time
        foreach ($this->valid_keys as $k => $v) {
            if (in_array($v, $this->remove_keys)) {
                unset($this->valid_keys[$k]);
            }
        }
    }

    /**
     * Prepares result, creates links if requested and transforms address/subnet to
     *  decimal format
     *
     * @param  mixed  $result
     * @param  mixed  $controller  (default: null)
     * @param  bool  $links  (default: true)
     * @param  bool  $transform_address  (default: true)
     * @return array
     */
    protected function prepare_result($result, $controller = null, $links = true, $transform_address = true)
    {
        // empty controller
        $controller = $controller ?? $this->_params->controller;

        // links
        if ($links) {
            // if parameter is set obey
            if (isset($this->_params->links)) {
                if ($this->_params->links != 'false') {
                    $result = $this->add_links($result, $controller);
                }
            } else {
                // otherwise take defaults
                if ($this->app->app_show_links == 1) {
                    $result = $this->add_links($result, $controller);
                }
            }
        }

        // filter
        if (isset($this->_params->filter_by)) {
            $result = $this->filter_result($result);
        }

        // transform address
        if ($transform_address) {
            $result = $this->transform_address($result);
        }

        // remove subnets and addresses if needed
        $result = $this->remove_folders($result);
        $result = $this->remove_subnets($result);

        // remap keys
        $result = $this->remap_keys($result, $controller);

        // Reindex results to start at index 0.
        if (is_array($result)) {
            return array_values($result);
        }

        // return
        return $result;
    }

    /**
     * Filters result
     *
     *  parameters: filter_by, filter_value
     *
     * @param  array  $result
     * @return object[]
     */
    protected function filter_result($result = [])
    {
        // remap keys before applying filter
        $result = $this->remap_keys($result, false);
        // validate
        $this->validate_filter_by($result);

        // Filter single object
        if (is_object($result)) {
            $result = [$result];    // convert to array of objects
        }

        if (! is_array($result)) {
            return false;           // Bad input
        }

        // Filter array of objects
        $result2 = [];
        foreach ($result as $r) {
            if (! property_exists($r, $this->_params->filter_by)) {
                continue;
            }

            if ($this->_params->filter_match == 'partial') {
                // match partial string
                if (strpos((string) $r->{$this->_params->filter_by}, (string) $this->_params->filter_value) === false) {
                    continue;
                }
            } elseif ($this->_params->filter_match == 'regex') {
                // match regular expression
                if (preg_match($this->_params->filter_value, (string) $r->{$this->_params->filter_by}) !== 1) {
                    continue;
                }
            } else {
                // match full string
                if ($r->{$this->_params->filter_by} != $this->_params->filter_value) {
                    continue;
                }
            }

            $result2[] = $r;    // save match
        }

        if ($result2 === []) {
            $this->Response->throw_exception(404, _('No results (filter applied)'));
        }

        // reindex filtered result
        return array_values($result2);
    }

    /**
     * Validates filter_by
     *
     *  Takes first result, checks all keys against provided filter_by value
     *
     * @param  mixed  $result
     * @return void
     */
    protected function validate_filter_by($result)
    {
        // validate filter
        if (is_array($result)) {
            $result = $result[0];
        }

        // validate filter_value
        if (! isset($this->_params->filter_value)) {
            $this->Response->throw_exception(400, _('Missing filter_value'));
        }

        if (is_blank($this->_params->filter_value)) {
            $this->Response->throw_exception(400, _('Empty filter_value'));
        }

        // validate filter_by is a valid property
        if (! is_object($result) || ! property_exists($result, $this->_params->filter_by)) {
            $this->Response->throw_exception(400, _('Invalid filter_by'));
        }

        // validate filter_match (default:'full')
        if (! isset($this->_params->filter_match)) {
            $this->_params->filter_match = 'full';
        }

        if (! in_array($this->_params->filter_match, ['full', 'partial', 'regex'])) {
            $this->Response->throw_exception(400, _('Invalid filter_match'));
        }

        if ($this->_params->filter_match == 'regex') {
            @preg_match($this->_params->filter_value, 'phpIPAM');
            if (($last_err = preg_last_error()) != PREG_NO_ERROR) {
                $this->Response->throw_exception(400, _('Invalid regular expression')." (err=$last_err)");
            }
        }
    }

    /**
     * Creates links for GET requests
     *
     * @param  array  $result
     * @param  mixed  $controller
     * @return array
     */
    protected function add_links($result, $controller = null)
    {
        // lower controller
        $controller = strtolower((string) $controller);

        // multiple options
        if (is_array($result)) {
            foreach ($result as $k => $r) {
                // fix for Vlans and vrfs
                if ($controller == 'vlans') {
                    $r->id = $r->vlanId;
                }

                if ($controller == 'tools/vlans') {
                    $r->id = $r->vlanId;
                }

                if ($controller == 'vrfs') {
                    $r->id = $r->vrfId;
                }

                if ($this->_params->id == 'deviceTypes') {
                    $r->id = $r->tid;
                }

                $m = 0;
                // custom links
                $custom_links = $this->define_links($controller);
                if ($custom_links !== false) {
                    $links_arr = $this->define_links($controller);
                    if (is_array($links_arr)) {
                        foreach ($this->define_links($controller) as $link => $method) {
                            // self only !
                            if ($link == 'self') {
                                $result[$k]->links[$m] = new stdClass;
                                $result[$k]->links[$m]->rel = $link;
                                $result[$k]->links[$m]->href = '/api/'.$this->_params->app_id."/$controller/".$r->id.'/';
                            }
                        }
                    }
                }

                // remove id for vlans
                if ($controller == 'vlans') {
                    unset($r->id);
                }

                if ($controller == 'vrfs') {
                    unset($r->id);
                }
            }
        } else {
            // single item
            // fix for Vlans and Vrfs
            if ($controller == 'vlans') {
                $result->id = $result->vlanId;
            }

            if ($controller == 'tools/vlans') {
                $result->id = $result->vlanId;
            }

            if ($controller == 'vrfs') {
                $result->id = $result->vrfId;
            }

            if ($this->_params->id == 'deviceTypes') {
                $result->id = $result->tid;
            }

            $m = 0;
            // custom links
            $custom_links = $this->define_links($controller);
            if ($custom_links !== false) {
                $links_arr = $this->define_links($controller);
                if (is_array($links_arr)) {
                    foreach ($this->define_links($controller) as $link => $method) {
                        $result->links[$m] = new stdClass;
                        $result->links[$m]->rel = $link;
                        // self ?
                        if ($link == 'self') {
                            $result->links[$m]->href = '/api/'.$this->_params->app_id."/$controller/".$result->id.'/';
                        } else {
                            $result->links[$m]->href = '/api/'.$this->_params->app_id."/$controller/".$result->id."/$link/";
                        }

                        $result->links[$m]->methods = $method;
                        // next
                        $m++;
                    }
                }
            }

            // remove id for vlans
            if ($controller == 'vlans') {
                unset($result->id);
            }

            if ($controller == 'vrfs') {
                unset($result->id);
            }
        }

        // return
        return $result;
    }

    /**
     * Defines links for controller
     *
     * @param  mixed  $controller
     * @return array
     */
    private function define_links($controller)
    {
        // init
        $result = [];
        // sections
        if ($controller == 'sections') {
            $result['self'] = ['GET', 'POST', 'DELETE', 'PATCH'];
            $result['subnets'] = ['GET'];

            // return
            return $result;
        }

        // subnets
        if ($controller == 'subnets') {
            $result['self'] = ['GET', 'POST', 'DELETE', 'PATCH'];
            $result['addresses'] = ['GET'];
            $result['addresses/{ip}'] = ['GET'];
            $result['usage'] = ['GET'];
            $result['first_free'] = ['GET'];
            $result['slaves'] = ['GET'];
            $result['slaves_recursive'] = ['GET'];
            $result['truncate'] = ['DELETE'];
            $result['permissions'] = ['DELETE', 'PATCH'];
            $result['resize'] = ['PATCH'];
            $result['split'] = ['PATCH'];

            // return
            return $result;
        }

        // addresses
        if ($controller == 'addresses') {
            $result['self'] = ['GET', 'POST', 'DELETE', 'PATCH'];
            $result['ping'] = ['GET'];

            // return
            return $result;
        }

        // tags
        if ($controller == 'addresses/tags') {
            $result['self'] = ['GET'];
            $result['addresses'] = ['GET'];

            // return
            return $result;
        }

        // tools - devices
        if ($controller == 'tools/devices') {
            $result['self'] = ['GET', 'POST', 'DELETE', 'PATCH'];
            $result['addresses'] = ['GET'];

            // return
            return $result;
        }

        // tools - devices
        if ($controller == 'tools/devicetypes') {
            $result['self'] = ['GET', 'POST', 'DELETE', 'PATCH'];
            $result['devices'] = ['GET'];

            // return
            return $result;
        }

        // tools - tags
        if ($controller == 'tools/iptags') {
            $result['self'] = ['GET', 'POST', 'DELETE', 'PATCH'];
            $result['addresses'] = ['GET'];

            // return
            return $result;
        }

        // tools - tags
        if ($controller == 'tools/vlans') {
            $result['self'] = ['GET'];
            $result['subnets'] = ['GET'];

            // return
            return $result;
        }

        // tools - tags
        if ($controller == 'tools/vrf') {
            $result['self'] = ['GET'];
            $result['subnets'] = ['GET'];

            // return
            return $result;
        }

        // tags
        if ($controller == 'iptags') {
            $result['self'] = ['GET'];
            $result['addresses'] = ['GET'];

            // return
            return $result;
        }

        // tags
        if ($controller == 'devices') {
            $result['self'] = ['GET'];
            $result['addresses'] = ['GET'];

            // return
            return $result;
        }

        // vlan domains
        if ($controller == 'l2domains') {
            $result['self'] = ['GET', 'POST', 'DELETE', 'PATCH'];
            $result['vlans'] = ['GET'];

            // return
            return $result;
        }

        // vlans
        if ($controller == 'vlans') {
            $result['self'] = ['GET', 'POST', 'DELETE', 'PATCH'];
            $result['subnets'] = ['GET'];

            // return
            return $result;
        }

        // vrfs
        if ($controller == 'vrfs') {
            $result['self'] = ['GET', 'POST', 'DELETE', 'PATCH'];
            $result['subnets'] = ['GET'];

            // return
            return $result;
        }

        // default
        return false;
    }

    /**
     * Transforms IP address and subnet
     *
     * @param  mixed  $result
     * @return array|object
     */
    protected function transform_address($result)
    {
        $result_is_object = false;

        if (is_object($result)) {
            $result_is_object = true;
            $result = [$result];
        }

        if (! is_array($result)) {
            return $result;
        }

        foreach ($result as $r) {
            $properties = ['subnet', 'ip_addr'];
            foreach ($properties as $property) {
                if (property_exists($r, $property)) {
                    // remove IP & transform property to dotted notation
                    unset($r->ip);
                    $r->{$property} = $this->Subnets->transform_address($r->{$property}, 'dotted');
                }
            }
        }

        return $result_is_object ? $result[0] : $result;
    }

    /**
     * Validates posted keys and returns proper inset values
     *
     * @return array
     */
    protected function validate_keys()
    {
        // init values
        $values = [];
        // loop
        foreach ($this->_params as $pk => $pv) {
            if (! in_array($pk, $this->valid_keys)) {
                $this->Response->throw_exception(400, 'Invalid request key '.$pk);
            } else {
                // set parameters
                if (! in_array($pk, $this->controller_keys)) {
                    $values[$pk] = $pv;
                }
            }
        }

        // remove editDate
        unset($values['editDate']);

        // return
        return $values;
    }

    /**
     * Validates OPTIONS request
     *
     * @return void
     */
    protected function validate_options_request()
    {
        foreach ($this->_params as $key => $val) {
            if (! in_array($key, ['app_id', 'controller', 'id'])) {
                $this->Response->throw_exception(400, 'Invalid request key parameter '.$key);
            }
        }
    }

    /**
     * Validates MAC address
     *
     * @param  mixed  $mac
     * @return bool
     */
    public function validate_mac($mac)
    {
        // first put it to common format (1)
        $mac = $this->reformat_mac_address($mac);
        // init common class
        $Common = new Common_functions;

        // check
        return $Common->validate_mac($mac);
    }

    /**
     * Reformats MAC address to requested format
     *
     * @param  string  $mac
     * @param  int  $format  (default: 1)
     *                       1 : 00:66:23:33:55:66
     *                       2 : 00-66-23-33-55-66
     *                       3 : 0066.2333.5566
     *                       4 : 006623335566
     * @return string
     */
    public function reformat_mac_address($mac, $format = 1)
    {
        // strip al tags first
        $mac = strtolower(str_replace([':', '.', '-'], '', $mac));
        // format 4
        if ($format == 4) {
            return $mac;
        }

        // format 3
        if ($format == 3) {
            $mac = str_split($mac, 4);
            $mac = implode('.', $mac);
        } elseif ($format == 2) {
            // format 2
            $mac = str_split($mac, 2);
            $mac = implode('-', $mac);
        } else {
            // format 1
            $mac = str_split($mac, 2);
            $mac = implode(':', $mac);
        }

        // return
        return $mac;
    }

    /**
     * Returns array of possible permissions
     *
     * @return array
     */
    public function get_possible_permissions()
    {
        return [
            'na' => 0,
            'ro' => 1,
            'rw' => 2,
            'rwa' => 3,
        ];
    }

    /**
     * This method removes all folders if controller is subnets
     *
     * @param  mixed  $result
     * @return mixed
     */
    protected function remove_folders($result)
    {
        // must be subnets
        if ($this->_params->controller != 'subnets') {
            return $result;
        }

        if (is_array($result)) {
            foreach ($result as $k => $r) {
                if (isset($r->isFolder) && $r->isFolder == '1') {
                    unset($result[$k]);
                }
            }
        } else {
            if (isset($result->isFolder) && $result->isFolder == '1') {
                unset($result);
            }
        }

        if (empty($result)) {
            $this->Response->throw_exception(404, 'No subnets found');
        }

        return $result;
    }

    /**
     * This method removes all subnets if controller is subnets
     *
     * @param  mixed  $result
     * @return mixed
     */
    protected function remove_subnets($result)
    {
        // must be subnets
        if ($this->_params->controller != 'folders') {
            return $result;
        }

        if (is_array($result)) {
            foreach ($result as $k => $r) {
                if (isset($r->isFolder) && $r->isFolder != '1') {
                    unset($result[$k]);
                }
            }
        } else {
            if (isset($result->isFolder) && $result->isFolder != '1') {
                unset($result);
            }
        }

        if (empty($result)) {
            $this->Response->throw_exception(404, 'No folders found');
        }

        return $result;
    }

    /**
     * Remaps keys based on request type
     *
     * @param  mixed  $result  (default: null)
     * @param  mixed  $controller  (default: null)
     * @param  mixed  $tools_table  (default: null)
     * @return mixed
     */
    protected function remap_keys($result = null, $controller = null, $tools_table = null)
    {
        // define keys array
        $this->keys = ['switch' => 'deviceId', 'state' => 'tag', 'ip_addr' => 'ip'];

        // exceptions
        if ($controller == 'vlans') {
            $this->keys['vlanId'] = 'id';
        }

        if ($controller == 'vrfs') {
            $this->keys['vrfId'] = 'id';
        }

        if ($controller == 'circuits') {
            $this->keys['cid'] = 'circuit_id';
        }

        if ($controller == 'l2domains') {
            $this->keys['permissions'] = 'sections';
        }

        if ($this->_params->controller == 'tools' && $tools_table == 'deviceTypes') {
            $this->keys['tid'] = 'id';
        }

        if ($this->_params->controller == 'tools' && $tools_table == 'nameservers') {
            $this->keys['permissions'] = 'sections';
        }

        if ($this->_params->controller == 'subnets') {
            $this->keys['ip'] = 'ip_addr';
        }

        // special keys for POST / PATCH
        if (in_array($this->request_method(), ['POST', 'PUT', 'PATCH'])) {
            if ($this->_params->controller == 'circuits') {
                $this->keys['cid'] = 'circuit_id';
            }
        }

        // POST / PATCH / DELETE
        if (in_array($this->request_method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $this->remap_update_keys();
        }

        // GET
        if (in_array($this->request_method(), ['GET', 'HEAD'])) {
            return $this->remap_result_keys($result);
        }
    }

    /**
     * Updates request keys to database ones
     *
     * @return void
     */
    private function remap_update_keys()
    {
        // loop
        foreach ($this->keys as $k => $v) {
            // match
            if (property_exists($this->_params, $v)) {
                // replace
                $this->_params->{$k} = $this->_params->{$v};
                // remove
                unset($this->_params->{$v});
            }
        }
    }

    /**
     * Remap result keys - what is offered to client
     *
     * @param  mixed  $result
     * @return mixed
     */
    private function remap_result_keys($result)
    {
        // single
        if (! is_array($result)) {
            // params
            $result_remapped = new StdClass;
            // search and replace
            if (is_array($result) || is_object($result)) {
                foreach ($result as $k => $v) {
                    if (array_key_exists((string) $k, $this->keys)) {
                        // replace
                        $key = $this->keys[$k];
                        $result_remapped->{$key} = $v;
                    } else {
                        $result_remapped->{$k} = $v;
                    }
                }
            }
        } else {
            // array
            // create a new array for the remapped data
            $result_remapped = [];

            // loop
            foreach ($result as $m => $r) {
                // start object
                $result_remapped[$m] = new StdClass;

                // search and replace
                foreach ($r as $k => $v) {
                    if (array_key_exists((string) $k, $this->keys)) {
                        // replace
                        $key_val = $this->keys[$k];
                        $result_remapped[$m]->{$key_val} = $v;
                    } else {
                        $result_remapped[$m]->{$k} = $v;
                    }
                }
            }
        }

        // result
        return $result_remapped;
    }

    /**
     * Unmarshal nested custom field data into the root object, and unset
     * the custom_fields parameter when done. This function does not have
     * any effect on requests for controllers that don't have custom fields,
     * or if the app_nest_custom_fields setting is not enabled.
     *
     * @return void
     */
    public function unmarshal_nested_custom_fields()
    {
        if (! $this->app->app_nest_custom_fields) {
            return;
        }

        if (is_array($this->_params->custom_fields) && isset($this->custom_fields)) {
            foreach ($this->_params->custom_fields as $key => $value) {
                if (array_key_exists($key, $this->custom_fields)) {
                    $this->_params->$key = $value;
                } else {
                    $this->Response->throw_exception(400, "{$key} is not a valid custom field");
                }
            }

            unset($this->_params->custom_fields);
        }
    }

    /**
     * Returns subnet gateway
     *
     * @param  int  $id
     * @return object|false
     */
    protected function read_subnet_gateway($id)
    {
        return (is_numeric($id) && $id > 0) ? $this->Subnets->find_gateway($id) : $this->Subnets->find_gateway($this->_params->id);
    }

    /**
     * Returns nameserver details
     *
     * @param  int  $nsid
     * @return object|false
     */
    protected function read_subnet_nameserver($nsid)
    {
        return (is_numeric($nsid) && $nsid > 0) ? $this->Tools->fetch_object('nameservers', 'id', $nsid) : false;
    }
}
