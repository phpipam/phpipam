<?php

/**
 *  phpIPAM API class to work with subnets
 */
class Subnets_controller extends Common_api_functions
{
    /**
     * settings
     *
     * @var mixed
     */
    protected $settings;

    /**
     * __construct function
     *
     * @param  Database_PDO  $Database
     * @param  Tools  $Tools
     * @param  API_params  $params
     * @param  Responses  $Response
     */
    public function __construct($Database, $Tools, $params, $Response)
    {
        $this->Database = $Database;
        $this->Tools = $Tools;
        $this->_params = $params;
        $this->Response = $Response;
        // init required objects
        $this->init_object('Subnets', $Database);
        $this->init_object('Addresses', $Database);
        $this->init_object('User', $Database);
        // set valid keys
        $this->set_valid_keys('subnets');
    }

    /**
     * Returns json encoded options
     *
     * @return array
     */
    #[Override]
    public function OPTIONS()
    {
        // validate
        $this->validate_options_request();

        // methods
        $result = [];
        $result['methods'] = [
            ['href' => '/api/'.$this->_params->app_id.'/subnets/',        'methods' => [['rel' => 'options', 'method' => 'OPTIONS']]],
            ['href' => '/api/'.$this->_params->app_id.'/subnets/{id}/',   'methods' => [['rel' => 'read',     'method' => 'GET'],
                ['rel' => 'create', 'method' => 'POST'],
                ['rel' => 'update', 'method' => 'PATCH'],
                ['rel' => 'delete', 'method' => 'DELETE']]],
        ];

        // result
        return ['code' => 200, 'data' => $result];
    }

    /**
     * Creates new subnet
     *
     *  required params : subnet, mask, name
     *  optional params : all subnet values
     *
     *      - /subnets/{id}/first_subnet/{mask}/       // creates first free subnet under master with specified mask
     *      - /subnets/{id}/last_subnet/{mask}/        // creates last free subnet under master with specified mask
     *
     * @return array
     */
    #[Override]
    public function POST()
    {
        // add required parameters
        if (! isset($this->_params->isFolder)) {
            $this->_params->isFolder = '0';
        } elseif ($this->_params->isFolder == 1) {
            unset($this->_params->subnet, $this->_params->mask);
        }

        if ($this->_params->id2 == 'first_subnet' || $this->_params->id2 == 'last_subnet') {
            $this->validate_subnet_id();

            $direction = ($this->_params->id2 == 'first_subnet') ? Subnets::SEARCH_FIND_FIRST : Subnets::SEARCH_FIND_LAST;
            $this->post_find_free_subnet($direction);
        }

        // validate parameters
        $this->validate_create_parameters();
        // check for valid keys
        $values = $this->validate_keys();

        // transform subnet to decimal format
        $values['subnet'] = $this->Addresses->transform_address($values['subnet'], 'decimal');

        // execute
        if (! $this->Subnets->modify_subnet('add', $values)) {
            $this->Response->throw_exception(500, 'Failed to create subnet');
        } else {
            // set result
            return ['code' => 201, 'message' => 'Subnet created', 'id' => $this->Subnets->lastInsertId, 'data' => $this->Addresses->transform_address($values['subnet'], 'dotted').'/'.$values['mask'], 'location' => '/api/'.$this->_params->app_id.'/subnets/'.$this->Subnets->lastInsertId.'/'];
        }

        return null;
    }

    /**
     * Populate subnet details from first/last available subnet
     *
     * @param  int  $direction
     * @return void
     */
    private function post_find_free_subnet($direction = Subnets::SEARCH_FIND_FIRST)
    {
        $subnet_tmp = $this->Subnets->cidr_network_and_mask($this->subnet_find_free(1, $direction));

        // get master subnet
        $master = $this->read_subnet();

        $this->_params->subnet = $subnet_tmp[0];
        $this->_params->mask = $subnet_tmp[1];
        $this->_params->sectionId = $master->sectionId;
        $this->_params->masterSubnetId = $master->id;
        $this->_params->permissions = $master->permissions;
        unset($this->_params->id2, $this->_params->id3);
        // description
        if (! isset($this->_params->description)) {
            $this->_params->description = 'API autocreated';
        }
    }

    /**
     * Reads subnet functions
     *
     *  Identifier can be:
     *      - /                             // returns all subnets in all sections
     *      - /{id}/
     *      - /custom_fields/               // returns custom fields
     *      - /cidr/{subnet}/               // subnets in CIDR format
     *      - /search/{subnet}/             // subnets in CIDR format (same as above)
     *      - /overlaping/{subnet}/         // returns all overlapping subnets
     *      - /{id}/usage/                  // returns subnet usage
     *      - /{id}/slaves/                 // returns all immediate slave subnets
     *      - /{id}/slaves_recursive/       // returns all slave subnets recursively
     *      - /{id}/addresses/              // returns all IP addresses in subnet
     *      - /{id}/addresses/{ip}/         // returns IP address from subnet
     *      - /{id}/first_free/             // returns first free address in subnet
     *      - /{id}/first_subnet/{mask}/    // returns first available subnets with specified mask
     *      - /{id}/last_subnet/{mask}/     // returns last available subnets with specified mask
     *      - /{id}/all_subnets/{mask}/     // returns all available subnets with specified mask
     *      - /{id}/changelog/              // returns changelog
     *      - /all/                         // returns all subnets in all sections
     *
     * @return array
     */
    #[Override]
    public function GET()
    {
        // all
        if (! isset($this->_params->id) || $this->_params->id == 'all') {
            $result = $this->read_all_subnets();
            // check result
            if ($result === false) {
                $this->Response->throw_exception(500, 'Unable to read subnets');
            } else {
                return ['code' => 200, 'data' => $this->prepare_result($result, 'subnets', true, true)];
            }
        }
        // cidr check
        elseif (isset($this->_params->id2)) {
            // check if id2 is set ?
            // is IP address provided
            if ($this->_params->id == 'cidr' || $this->_params->id == 'search') {
                $result = $this->read_search_subnet();
                // check result
                if ($result == false) {
                    $this->Response->throw_exception(404, 'No subnets found');
                } else {
                    return ['code' => 200, 'data' => $this->prepare_result($result, null, true, true)];
                }
            }

            if ($this->_params->id == 'overlapping') {
                $result = $this->read_overlapping_subnet();
                if ($result == false) {
                    $this->Response->throw_exception(404, 'No subnets found');
                } else {
                    return ['code' => 200, 'data' => $this->prepare_result($result, null, true, true)];
                }
            }

            // validate id
            $this->validate_subnet_id();

            // addresses in subnet
            if ($this->_params->id2 == 'addresses') {
                $result = $this->read_subnet_addresses();
                // if {ip} is set filter it out
                if (isset($this->_params->id3)) {
                    if (is_array($result)) {
                        foreach ($result as $k => $r) {
                            if ($r->ip !== $this->_params->id3) {
                                unset($result[$k]);
                            }
                        }
                    }

                    if (count($result) == 0) {
                        $result = false;
                    }
                }

                // check result
                if ($result === false) {
                    $this->Response->throw_exception(404, 'No addresses found');
                } else {
                    $this->custom_fields = $this->Tools->fetch_custom_fields('ipaddresses');

                    return ['code' => 200, 'data' => $this->prepare_result($result, 'addresses', true, true)];
                }
            } elseif ($this->_params->id2 == 'slaves') {
                // slaves
                $result = $this->read_subnet_slaves();
                // check result
                if ($result == null) {
                    $this->Response->throw_exception(404, 'No slaves');
                } else {
                    return ['code' => 200, 'data' => $this->prepare_result($result, null, true, true)];
                }
            } elseif ($this->_params->id2 == 'slaves_recursive') {
                // slaves-recursive
                $result = $this->read_subnet_slaves_recursive();
                // check result
                if ($result == null) {
                    $this->Response->throw_exception(404, 'No slaves');
                } else {
                    return ['code' => 200, 'data' => $this->prepare_result($result, null, true, true)];
                }
            } elseif ($this->_params->id2 == 'usage') {
                // usage
                return ['code' => 200, 'data' => $this->subnet_usage()];
            } elseif ($this->_params->id2 == 'first_free') {
                // first available address
                return ['code' => 200, 'data' => $this->subnet_first_free_address()];
            } elseif ($this->_params->id2 == 'all_subnets') {
                // search for new free subnet
                return ['code' => 200, 'data' => $this->subnet_find_free(Subnets::SEARCH_FIND_ALL, Subnets::SEARCH_FIND_FIRST)];
            } elseif ($this->_params->id2 == 'first_subnet') {
                // search for new free subnet
                return ['code' => 200, 'data' => $this->subnet_find_free(1, Subnets::SEARCH_FIND_FIRST)];
            } elseif ($this->_params->id2 == 'last_subnet') {
                // search for new free subnet
                return ['code' => 200, 'data' => $this->subnet_find_free(1, Subnets::SEARCH_FIND_LAST)];
            } elseif ($this->_params->id2 == 'changelog') {
                // return changelog
                return ['code' => 200, 'data' => $this->subnet_changelog()];
            } else {
                // fail
                $this->Response->throw_exception(400, 'Invalid request');
            }
        } elseif ($this->_params->id == 'custom_fields') {
            // custom fields
            // check result
            if (count($this->custom_fields) == 0) {
                $this->Response->throw_exception(404, 'No custom fields defined');
            } else {
                return ['code' => 200, 'data' => $this->custom_fields];
            }
        } elseif (is_numeric($this->_params->id)) {
            // id
            $result = $this->read_subnet();
            // check result
            if ($result == false) {
                $this->Response->throw_exception(400, 'Invalid subnet Id ('.$this->_params->id.')');
            } else {
                return ['code' => 200, 'data' => $this->prepare_result($result, 'subnets', true, true)];
            }
        } else {
            // false
            $this->Response->throw_exception(404, 'Invalid Id');
        }

        return null;
    }

    /**
     * Updates existing subnet
     *
     *  required params : id
     *  forbidden params : subnet, mask
     *
     *  if id2 is present than execute:
     *      - {id}/resize/
     *      - {id}/split/
     *      - {id}/permissions/               // changes permissions (?3=2&41=1 || ?groupname1=3&groupname2=1) 0=na, 1=ro, 2=rw, 3=rwa
     *
     * @return array
     */
    #[Override]
    public function PATCH()
    {
        // Check for id
        $this->validate_subnet_id();

        // check if id2 is set > additional methods
        if (isset($this->_params->id2)) {
            // resize
            if ($this->_params->id2 == 'resize') {
                return $this->subnet_resize();
            }

            // split
            if ($this->_params->id2 == 'split') {
                return $this->subnet_split();
            }

            // permissions
            if ($this->_params->id2 == 'permissions') {
                return $this->subnet_change_permissions();
            }

            $this->Response->throw_exception(400, 'Invalid parameters');
        } else {
            // ok, normal update
            // new section
            if (isset($this->_params->sectionId)) {
                $this->validate_section();
            }

            // validate vlan and vrf
            $this->validate_vlan();
            $this->validate_vrf();

            // if subnet is provided die
            if (isset($this->_params->subnet)) {
                $this->Response->throw_exception(400, 'Subnet cannot be changed');
            }

            if (isset($this->_params->mask)) {
                $this->Response->throw_exception(400, 'To change mask use resize method');
            }

            // check for valid keys
            $values = $this->validate_keys();
            // add id
            $values['id'] = $this->_params->id;

            // execute update
            if (! $this->Subnets->modify_subnet('edit', $values)) {
                $this->Response->throw_exception(500, 'Subnet update failed');
            } else {
                return ['code' => 200, 'message' => 'Subnet updated'];
            }
        }
    }

    /**
     * Deletes existing subnet along with and addresses
     *
     *  required params : id
     *
     *  if id2 is present than execute:
     *      - {id}/truncate/
     *      - {id}/permissions/
     *
     * @return array
     */
    #[Override]
    public function DELETE()
    {
        // Check for id
        $this->validate_subnet_id();

        // check if id2 is set > additional methods
        if (isset($this->_params->id2)) {
            // truncate
            if ($this->_params->id2 == 'truncate') {
                return $this->subnet_truncate();
            }

            // remove
            if ($this->_params->id2 == 'permissions') {
                return $this->subnet_remove_permissions();
            }

            $this->Response->throw_exception(400, 'Invalid parameters');
        } else {
            // ok, delete subnet
            // set variables for delete
            $values = [];
            $values['id'] = $this->_params->id;

            // execute update
            if (! $this->Subnets->modify_subnet('delete', $values)) {
                $this->Response->throw_exception(500, 'Failed to delete subnet');
            } else {
                // set result
                return ['code' => 200, 'message' => 'Subnet deleted'];
            }
        }
    }

    /**
     * Truncates subnet
     *
     *  required params : id
     *
     * @return array
     */
    private function subnet_truncate()
    {
        // Check for id
        $this->validate_subnet_id();
        // ok, try to truncate
        $this->Subnets->modify_subnet('truncate', (array) $this->_params);

        // set result
        return ['code' => 200, 'message' => 'Subnet truncated'];
    }

    /**
     * Resize subnet
     *
     *  required params : id, mask
     *
     * @return array
     */
    private function subnet_resize()
    {
        // Check for id
        $this->validate_subnet_id();

        // validate input parameters
        if (! isset($this->_params->mask)) {
            $this->Response->throw_exception(400, 'Subnet mask not provided');
        }

        // fetch old subnet
        $old_subnet = $this->Subnets->fetch_subnet('id', $this->_params->id);

        // validate resizing
        $this->Subnets->verify_subnet_resize($old_subnet->subnet, $this->_params->mask, $this->_params->id, $old_subnet->vrfId, $old_subnet->masterSubnetId, $old_subnet->mask, $old_subnet->sectionId);

        // regenerate subnet if needed
        if ($old_subnet->mask < $this->_params->mask) {
            $subnet_new = $old_subnet->subnet;
        } else {
            $new_boundaries = $this->Subnets->get_network_boundaries($this->Subnets->transform_address($old_subnet->subnet, 'dotted'), $this->_params->mask);
            $subnet_new = $this->Subnets->transform_address($new_boundaries['network'], 'decimal');
        }

        // set update values
        $values = ['id' => $this->_params->id,
            'subnet' => $subnet_new,
            'mask' => $this->_params->mask,
        ];
        $this->Subnets->modify_subnet('resize', $values);

        // set result
        return ['code' => 200, 'message' => 'Subnet resized'];
    }

    /**
     * Splits existing network into new networks
     *
     *  required params : id, number
     *  optional params : group (default yes), strict (default yes), prefix, copy_custom (default: yes)
     *
     * @return array
     */
    private function subnet_split()
    {
        // Check for id
        $this->validate_subnet_id();

        // validate input parmeters
        if (! is_numeric($this->_params->number)) {
            $this->Response->throw_exception(400, 'Invalid number of new subnets');
        }

        if (! isset($this->_params->group)) {
            $this->_params->group = 'yes';
        }

        if (! isset($this->_params->copy_custom)) {
            $this->_params->copy_custom = 'yes';
        }

        // fetch old subnet
        $subnet_old = $this->Subnets->fetch_subnet('id', $this->_params->id);
        // create new subnets and move addresses
        $this->Subnets->subnet_split($subnet_old, $this->_params->number, $this->_params->prefix, $this->_params->group, $this->_params->copy_custom);

        // set result
        return ['code' => 200, 'message' => 'Subnet split'];
    }

    /**
     * Changes subnet permissions
     *
     *  required params : id, number
     *  optional params : group (default yes), strict (default yes), prefix
     *
     * @return array
     */
    private function subnet_change_permissions()
    {
        // Check for id
        $this->validate_subnet_id();

        // validate groups, permissions and save to _params
        $this->validate_create_permissions();
        // save perms
        $values['id'] = $this->_params->id;
        $values['permissions'] = $this->_params->permissions;

        // execute update
        if (! $this->Subnets->modify_subnet('edit', $values)) {
            $this->Response->throw_exception(500, 'Subnet permissions update failed');
        } else {
            return ['code' => 200, 'message' => 'Subnet permissions updated', 'data' => $this->_params->permissions_text];
        }

        return null;
    }

    /**
     * Validates update permission groups
     *
     * @return void
     */
    private function validate_create_permissions()
    {
        // set valid permissions array
        $valid_permissions_array = $this->get_possible_permissions();
        // requested permissions
        $requested_permissions = [];
        $requested_permissions_full = [];
        // save ids
        $id = $this->_params->id;
        unset($this->_params->controller, $this->_params->app_id, $this->_params->id, $this->_params->id2, $this->_params->isFolder);

        // loop and validate
        if (count($this->_params) > 0) {
            foreach ($this->_params as $gid => $perm) {
                // fetch and validate group
                $group = is_numeric($gid) ? $this->Tools->fetch_object('userGroups', 'g_id', $gid) : $this->Tools->fetch_object('userGroups', 'g_name', $gid);
                if ($group === false) {
                    $this->Response->throw_exception(500, 'Invalid group identifier '.$gid);
                }

                // validate permissions
                if (is_numeric($perm)) {
                    if (! in_array($perm, $valid_permissions_array)) {
                        $this->Response->throw_exception(500, 'Invalid permissions '.$perm);
                    }
                } else {
                    if (! array_key_exists((string) $perm, $valid_permissions_array)) {
                        $this->Response->throw_exception(500, 'Invalid permissions '.$perm);
                    } else {
                        $perm = $valid_permissions_array[$perm];
                    }
                }

                // validated, add to permissions array
                $requested_permissions[$group->g_id] = $perm;
                $requested_permissions_full[$group->g_name] = array_search($perm, $valid_permissions_array);
            }

            // add id1 param back and set permissions
            $this->_params->id = $id;
            $this->_params->permissions = json_encode($requested_permissions);
            $this->_params->permissions_text = $requested_permissions_full;
        } else {
            $this->Response->throw_exception(500, 'Cannot remove permissions, use DELETE call');
        }
    }

    /**
     * Removes permissions
     *
     *  required params : id
     *
     * @return array
     */
    private function subnet_remove_permissions()
    {
        // Check for id
        $this->validate_subnet_id();
        // ok, try to truncate
        $this->Subnets->modify_subnet('edit', ['id' => $this->_params->id, 'permissions' => '']);

        // set result
        return ['code' => 200, 'message' => 'Subnet permissions removed'];
    }

    /**
     * Calculates subnet usage
     *
     * @return array
     */
    private function subnet_usage()
    {
        // check that section exists
        $subnet = $this->Subnets->fetch_subnet('id', $this->_params->id);
        if ($subnet === false) {
            $this->Response->throw_exception(400, 'Subnet does not exist');
        }

        // get usage
        $subnet_usage = $this->Subnets->calculate_subnet_usage($subnet);

        // return
        return $subnet_usage;
    }

    /**
     * Returns first available address in subnet
     *
     * @return array|string
     */
    public function subnet_first_free_address()
    {
        // Check for id
        $this->validate_subnet_id();
        // check for isFull
        $subnet = $this->read_subnet();
        if ($subnet->isFull == 1) {
            $this->Response->throw_exception(404, 'No free addresses found');
        }

        // slaves
        if ($this->Subnets->has_slaves($this->_params->id)) {
            $this->Response->throw_exception(409, 'Subnet contains subnets');
        }

        // fetch
        $first = $this->Addresses->get_first_available_address($this->_params->id);
        // available?
        if ($first === false) {
            $this->Response->throw_exception(404, 'No free addresses found');
        } else {
            $first = $this->Addresses->transform_to_dotted($first);
        }

        // return
        return $first;
    }

    /**
     * Returns first|last $count available subnets with specified mask
     *
     * @param  int  $count  (default: Subnets::SEARCH_FIND_ALL)
     * @param  int  $direction  (default: Subnets::SEARCH_FIND_FIRST)
     * @return array|string
     */
    public function subnet_find_free($count = Subnets::SEARCH_FIND_ALL, $direction = Subnets::SEARCH_FIND_FIRST)
    {
        // Check for id
        $this->validate_subnet_id();

        $found = $this->Subnets->search_available_subnets($this->_params->id, $this->_params->id3, $count, $direction);

        if ($found === false) {
            $this->Response->throw_exception(404, 'No subnets found');
        }

        return ($count == 1) ? $found[0] : $found;
    }

    /**
     * Get changelog for subnet
     *
     * @return array|null
     */
    private function subnet_changelog()
    {
        // Check for id
        $this->validate_subnet_id();
        // get changelog
        $Log = new Logging($this->Database);
        $clogs = $Log->fetch_changlog_entries('subnet', $this->_params->id, true);
        // reformat
        $clogs_formatted = [];
        // loop
        if (is_array($clogs)) {
            foreach ($clogs as $l) {
                // diff to array
                $l->cdiff = explode("\r\n", str_replace(['[', ']'], '', trim((string) $l->cdiff)));
                // save
                $clogs_formatted[] = [
                    'user' => $l->real_name,
                    'action' => $l->caction,
                    'result' => $l->cresult,
                    'date' => $l->cdate,
                    'diff' => $l->cdiff,
                ];
            }
        }

        // result
        if (count($clogs_formatted) > 0) {
            return $clogs_formatted;
        }

        $this->Response->throw_exception(404, 'No changelogs found');

        return null;
    }

    /* @helper methods ---------- */

    /**
     * Fetches subnet by id
     *
     * @return object|false
     */
    private function read_subnet($subnetId = null)
    {
        // null
        $subnetId = $subnetId ?? $this->_params->id;
        // fetch
        $result = $this->Subnets->fetch_subnet('id', $subnetId);
        // add nameservers, GW and calculation
        if ($result !== false) {
            $ns = $this->read_subnet_nameserver($result->nameserverId);
            if ($ns !== false) {
                $result->nameservers = $ns;
            }

            $gateway = $this->read_subnet_gateway(null);
            if ($gateway !== false) {
                $result->gatewayId = $gateway->id;
                $gateway = $this->transform_address($gateway);
                $result->gateway = $gateway;
            }

            if (! $result->isFolder) {
                $result->calculation = $this->Tools->calculate_ip_calc_results($this->Subnets->transform_address($result->subnet, 'dotted').'/'.$result->mask);
            }
        }

        // result
        return empty($result) ? false : $result;
    }

    /**
     * Fetches all subnets in database
     *
     * @return array|false
     */
    private function read_all_subnets()
    {
        // fetch
        $results = $this->Subnets->fetch_all_subnets();

        if (! is_array($results)) {
            return false;
        }

        $subnet_gws = [];
        $gateways = $this->Subnets->fetch_multiple_objects('ipaddresses', 'is_gateway', 1);
        if (is_array($gateways)) {
            foreach ($gateways as $gw) {
                $subnet_gws[$gw->id][] = $this->transform_address($gw);
            }
        }

        // add nameservers, GW, permission and location for each network found
        foreach ($results as $key => $result) {
            $ns = $this->read_subnet_nameserver($result->nameserverId);
            if ($ns !== false) {
                $result->nameservers = $ns;
            }

            if (isset($subnet_gws[$result->id])) {
                $gateway = $subnet_gws[$result->id][0];
                $result->gatewayId = $gateway->id;
                $result->gateway = $gateway;
            }

            $result->permissions = $this->User->get_user_permissions_from_json($result->permissions);

            // location details
            if (! empty($result->location)) {
                $result->location = $this->Tools->fetch_object('locations', 'id', $result->location);
            } else {
                $result->location = [];
            }

            // erase old values
            $results[$key] = $result;
        }

        // result
        return count($results) == 0 ? false : $results;
    }

    /**
     * Fetches all addresses in subnet
     *
     * @return array|false
     */
    private function read_subnet_addresses()
    {
        // fetch
        $result = $this->Addresses->fetch_subnet_addresses($this->_params->id);

        // result
        return count($result) == 0 ? false : $result;
    }

    /**
     * Returns all immediate subnet slaves
     *
     * @return array|false
     */
    private function read_subnet_slaves()
    {
        // fetch
        $result = $this->Subnets->fetch_subnet_slaves($this->_params->id);

        // result
        return $result === false ? null : $result;
    }

    /**
     * Returns all subnet slaves (recursive)
     *
     * @return array|null
     */
    private function read_subnet_slaves_recursive()
    {
        // get array of ids
        $this->Subnets->fetch_subnet_slaves_recursive($this->_params->id);
        // init result
        $result = [];
        // fetch all;
        foreach ($this->Subnets->slaves as $s) {
            $result[] = $this->read_subnet($s);
        }

        // result
        return count($result) == 0 ? null : $result;
    }

    /**
     * Searches for subnet in database
     *
     * @return array|false
     */
    private function read_search_subnet()
    {
        // transform
        $this->_params->id2 = $this->Subnets->transform_address($this->_params->id2, 'decimal');
        // check
        $subnet = $this->Tools->fetch_multiple_objects('subnets', 'subnet', $this->_params->id2);
        // validate mask
        if ($subnet !== false) {
            foreach ($subnet as $s) {
                if ($s->mask == $this->_params->id3) {
                    $result[] = $s;
                }
            }
        }

        // result
        return ! isset($result) ? false : $result;
    }

    /**
     * Searches for overlapping subnets in database (Supports IPv4 & IPv6)
     *
     * @return array|false
     */
    private function read_overlapping_subnet()
    {
        // Fetch overlapping subnets
        $subnet = $this->Subnets->fetch_overlapping_subnets($this->_params->id2.'/'.$this->_params->id3);

        return is_array($subnet) ? $subnet : false;
    }

    /* @validations ---------- */

    /**
     * Validates create parameters before adding new subnet
     *
     *  checks and validations - cidr check, issubnet, mastersubnet, sectionId
     *
     * @return void
     */
    public function validate_create_parameters()
    {
        // make sure subnet is in dotted format for checks
        $this->_params->subnet = $this->Addresses->transform_address($this->_params->subnet, 'dotted');

        // cidr check
        $this->validate_cidr();
        // verify that it is subnet
        $this->validate_network();
        // verify that master subnet exists
        $this->validate_master_subnet();
        // verify section
        $this->validate_section();
        // verify folder
        $this->validate_folder();
        // verify overlapping
        $this->validate_overlapping();
        // verify vlan
        $this->validate_vlan();
        // verify vrf
        $this->validate_vrf();
    }

    /**
     * Validates provided CIDR address
     *
     * @return void
     */
    private function validate_cidr()
    {
        // not for folder
        if ($this->_params->isFolder != 1) {
            // check
            if (strlen($err = $this->Subnets->verify_cidr_address($this->_params->subnet.'/'.$this->_params->mask)) > 1) {
                $this->Response->throw_exception(400, $err);
            }
        }
    }

    /**
     * Validates that provided subnet is network and not host
     *
     * @return void
     */
    private function validate_network()
    {
        // not for folder
        if ($this->_params->isFolder != 1) {
            if (! $this->Addresses->is_network($this->_params->subnet, $this->_params->mask)) {
                $this->Response->throw_exception(400, 'Address is not subnet');
            }
        }
    }

    /**
     * Validates master subnet
     *
     * @return void
     */
    private function validate_master_subnet()
    {
        // set 0 if not set
        if (! isset($this->_params->masterSubnetId) || $this->_params->masterSubnetId == '0') {
            $this->_params->masterSubnetId = 0;
        } else {
            // validate master subnet
            $master_subnet = $this->Subnets->fetch_subnet('id', $this->_params->masterSubnetId);
            if ($master_subnet === false) {
                $this->Response->throw_exception(404, 'Master Subnet does not exist (id='.$this->_params->masterSubnetId.')');
            } else {
                // check that it is inside subnet
                // not for folders
                if (@$this->_params->isFolder != 1 && $master_subnet->isFolder != 1) {
                    if (! $this->Subnets->verify_subnet_nesting($this->_params->masterSubnetId, $this->_params->subnet.'/'.$this->_params->mask)) {
                        $this->Response->throw_exception(409, 'Subnet is not within boundaries of its master subnet');
                    }
                }

                // set permissions
                $this->_params->permissions = $master_subnet->permissions;
            }
        }
    }

    /**
     * Validates section
     *
     * @return void
     */
    private function validate_section()
    {
        // Section Id must be present and numeric
        if (! isset($this->_params->sectionId)) {
            $this->Response->throw_exception(400, 'Invalid Section ('.$this->_params->sectionId.')');
        } elseif (! is_numeric($this->_params->sectionId)) {
            $this->Response->throw_exception(400, 'Section Id must be numeric ('.$this->_params->sectionId.')');
        } else {
            $master = $this->Tools->fetch_object('sections', 'id', $this->_params->sectionId);
            if ($master === false) {
                $this->Response->throw_exception(400, 'Section id ('.$this->_params->sectionId.') does not exist');
            } else {
                // inherit permissions from section
                if ($this->_params->masterSubnetId == 0) {
                    $this->_params->permissions = $master->permissions;
                }
            }
        }
    }

    /**
     * Validates subnet by Id
     *
     * @return void
     */
    private function validate_subnet_id()
    {
        // numeric
        if (! is_numeric($this->_params->id)) {
            $this->Response->throw_exception(400, 'Subnet Id must be numeric ('.$this->_params->id.')');
        }

        // check subnet
        if ($this->Subnets->fetch_subnet('id', $this->_params->id) === false) {
            $this->Response->throw_exception(404, 'Invalid subnet Id ('.$this->_params->id.')');
        }
    }

    /**
     * Folder validation
     *
     * @return void
     */
    private function validate_folder()
    {
        // only fo folders
        if (@$this->_params->isFolder == 1) {
            // if parent is set it must be a folder!
            if ($this->_params->masterSubnetId != 0) {
                $parent = $this->Subnets->fetch_subnet('id', $this->_params->masterSubnetId);
                if ($parent->isFolder != 1) {
                    $this->Response->throw_exception(409, 'Parent is not a folder');
                }
            }
        }
    }

    /**
     * Validates overlapping for newly created subnet
     *
     * @return void
     */
    private function validate_overlapping()
    {
        // section details
        $section = $this->Tools->fetch_object('sections', 'id', $this->_params->sectionId);
        if ($section === false) {
            $this->Response->throw_exception(400, 'Invalid section Id');
        }

        // settings
        $this->settings = $this->Tools->get_settings();

        // get master subnet details for folder overrides
        if ($this->_params->masterSubnetId != 0) {
            $master_section = $this->Subnets->fetch_subnet(null, $this->_params->masterSubnetId);
            if ($master_section->isFolder == 1) {
                $parent_is_folder = true;
            } else {
                $parent_is_folder = false;
            }
        } else {
            $parent_is_folder = false;
        }

        // create cidr address
        $cidr = $this->Addresses->transform_address($this->_params->subnet, 'dotted').'/'.$this->_params->mask;

        // root subnet
        if ($this->_params->masterSubnetId == 0) {
            // check overlapping
            if ($section->strictMode == 1 && ! $parent_is_folder) {
                /* verify that no overlapping occurs if we are adding root subnet only check for overlapping if vrf is empty or not exists! */
                $overlap = $this->Subnets->verify_subnet_overlapping($this->_params->sectionId, $cidr, $this->_params->vrfId);
                if ($overlap !== false) {
                    $this->Response->throw_exception(409, $overlap);
                }
            }
        } else {
            // not root
            // disable checks for folders and if strict check enabled
            if ($section->strictMode == 1 && ! $parent_is_folder) {
                // verify that nested subnet is inside root subnet
                if (! $this->Subnets->verify_subnet_nesting($this->_params->masterSubnetId, $cidr)) {
                    $this->Response->throw_exception(409, 'Nested subnet not in root subnet');
                }

                // nested?
                $overlap = $this->Subnets->verify_nested_subnet_overlapping($cidr, $this->_params->vrfId, $this->_params->masterSubnetId);
                if ($overlap !== false) {
                    $this->Response->throw_exception(409, $overlap);
                }
            }
        }
    }

    /**
     * Validates VLAN id
     *
     * @return void
     */
    private function validate_vlan()
    {
        if (isset($this->_params->vlanId)) {
            if ($this->Tools->fetch_object('vlans', 'vlanId', $this->_params->vlanId) === false) {
                $this->Response->throw_exception(404, 'Vlan does not exist');
            }
        }
    }

    /**
     * Validates VRF id
     *
     * @return void
     */
    private function validate_vrf()
    {
        if (isset($this->_params->vrfId)) {
            if ($this->Tools->fetch_object('vrf', 'vrfId', $this->_params->vrfId) === false) {
                $this->Response->throw_exception(404, 'VRF does not exist');
            }
        }
    }
}
