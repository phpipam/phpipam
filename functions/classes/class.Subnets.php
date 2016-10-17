<?php

/**
 *	phpIPAM Subnets class
 */

class Subnets extends Common_functions {

	/**
	 * (array of ids) to store id's of all recursively slaves
	 *
	 * @var mixed
	 * @access public
	 */
	public $slaves;

	/**
	 * (array of object) full slave subnets
	 *
	 * @var mixed
	 * @access public
	 */
	public $slaves_full;

	/**
	 * (array) IP address types from Addresses object
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access public
	 */
	public $address_types = null;

	/**
	 * Last id of new entries
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access public
	 */
	public $lastInsertId = null;

	/**
	 * array of /8 ripe subnets
	 *
	 * (default value: array())
	 *
	 * @var array
	 * @access protected
	 */
	protected $ripe = array();

	/**
	 * array of /8 arin subnets
	 *
	 * (default value: array())
	 *
	 * @var array
	 * @access protected
	 */
	protected $arin = array();

	/**
	 * PEAR NET IPv4 object
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Net_IPv4;

	/**
	 * PEAR NET IPv6 object
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Net_IPv6;

	/**
	 * for Result printing
	 *
	 * @var object
	 * @access public
	 */
	public $Result;

	/**
	 * Addresses class
	 *
	 * (default value: false)
	 *
	 * @var object
	 * @access protected
	 */
	protected $Addresses = false;

	/**
	 * Subnets class
	 *
	 * (default value: false)
	 *
	 * @var object
	 * @access protected
	 */
	protected $Subnets  = false;

	/**
	 * for Database connection
	 *
	 * @var object
	 * @access protected
	 */
	protected $Database;

	/**
	 * for Logging connection
	 *
	 * @var object
	 * @access public
	 */
	public $Log;





	/**
	 * __construct function
	 *
	 * @access public
	 */
	public function __construct (Database_PDO $database) {
		# Save database object
		$this->Database = $database;
		# initialize Result
		$this->Result = new Result ();
		# Log object
		$this->Log = new Logging ($this->Database);
	}

	/**
	 * Returns array of subnet ordering
	 *
	 * @access private
	 * @return void
	 */
	private function get_subnet_order () {
	    $this->get_settings ();
	    return explode(",", $this->settings->subnetOrdering);
	}












	/**
	 *	@update subnets methods
	 *	--------------------------------
	 */

	/**
	 * Modify subnet details main method
	 *
	 * @access public
	 * @param mixed $action
	 * @param mixed $values
	 * @return bool
	 */
	public function modify_subnet ($action, $values) {
		# strip tags
		$values = $this->strip_input_tags ($values);

		# execute based on action
		if($action=="add")			{ return $this->subnet_add ($values); }
		elseif($action=="edit")		{ return $this->subnet_edit ($values); }
		elseif($action=="delete")	{ return $this->subnet_delete ($values['id']); }
		elseif($action=="truncate")	{ return $this->subnet_truncate ($values['id']); }
		elseif($action=="resize")	{ return $this->subnet_resize ($values['id'], $values['subnet'], $values['mask']); }
		else						{ return $this->Result->show("danger", _("Invalid action"), true); }
	}

	/**
	 * Create new subnet method
	 *
	 * @access private
	 * @param mixed $values
	 * @return bool
	 */
	private function subnet_add ($values) {
		# null empty values
		$values = $this->reformat_empty_array_fields ($values, null);

		# execute
		try { $this->Database->insertObject("subnets", $values); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			$this->Log->write( "Subnet creation", "Failed to add new subnet<hr>".$e->getMessage(), 2);
			return false;
		}
		# save id
		$this->lastInsertId = $this->Database->lastInsertId();
		$values['id'] = $this->lastInsertId;
		# ok
		$this->Log->write( "Subnet created", "New subnet created<hr>".$this->array_to_log($this->reformat_empty_array_fields ($values, "NULL")), 0);
		# write changelog
		$this->Log->write_changelog('subnet', "add", 'success', array(), $values);
		return true;
	}

	/**
	 * Edit subnet
	 *
	 * @access private
	 * @param mixed $values
	 * @return bool
	 */
	private function subnet_edit ($values) {
		# save old values
		$old_subnet = $this->fetch_subnet (null, $values['id']);

		# null empty values
		$values = $this->reformat_empty_array_fields ($values, null);

		# execute
		try { $this->Database->updateObject("subnets", $values, "id"); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			$this->Log->write( "Subnet edit", "Failed to edit subnet<hr>".$e->getMessage(), 2);
			return false;
		}
		# save ID
		$this->lastInsertId = $this->Database->lastInsertId();
		$this->Log->write_changelog('subnet', "edit", 'success', $old_subnet, $values);
		# ok
		$this->Log->write( "Subnet $old_subnet->description edit", "Subnet $old_subnet->description edited<hr>".$this->array_to_log($this->reformat_empty_array_fields ($values, "NULL")), 0);
		return true;
	}

	/**
	 * Deletes subnet and truncates all IP addresses in that subnet
	 *
	 * @access private
	 * @param mixed $id
	 * @return bool
	 */
	private function subnet_delete ($id) {
		# save old values
		$old_subnet = $this->fetch_subnet (null, $id);

		# first truncate it
		$this->subnet_truncate ($id);

		# delete subnet
		try { $this->Database->deleteRow("subnets", "id", $id); }
		catch (Exception $e) {
			$this->Log->write( "Subnet delete", "Failed to delete subnet $old_subnet->name<hr>".$e->getMessage(), 2);
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}
		# write changelog
		$this->Log->write_changelog('subnet', "delete", 'success', $old_subnet, array());
		# ok
		$this->Log->write( "Subnet $old_subnet->description delete", "Subnet $old_subnet->description deleted<hr>".$this->array_to_log($this->reformat_empty_array_fields ((array) $old_subnet)), 0);
		return true;
	}

	/**
	 * Truncate specified subnet (delete all IP addresses in that subnet)
	 *
	 * @access public
	 * @param int $subnetId
	 * @return bool
	 */
	public function subnet_truncate ($subnetId) {
		# save old values
		$old_subnet = $this->fetch_subnet (null, $subnetId);
		# execute
		try { $this->Database->deleteRow("ipaddresses", "subnetId", $subnetId); }
		catch (Exception $e) {
			$this->Log->write( "Subnet truncate", "Failed to truncate subnet $old_subnet->description id $old_subnet->id<hr>".$e->getMessage(), 2);
			$this->Result->show("danger", _("Error: ").$e->getMessage(), true);
		}
		$this->Log->write( "Subnet truncate", "Subnet $old_subnet->description id $old_subnet->id truncated", 0);
		return true;
	}

	/**
	 * Resize subnet with new mask
	 *
	 * @access private
	 * @param mixed $subnetId
	 * @param int $subnet
	 * @param int $mask
	 * @return bool
	 */
	private function subnet_resize ($subnetId, $subnet, $mask) {
		# save old values
		$old_subnet = $this->fetch_subnet (null, $subnetId);
		# execute
		try { $this->Database->updateObject("subnets", array("id"=>$subnetId, "subnet"=>$subnet, "mask"=>$mask), "id"); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			$this->Log->write( "Subnet edit", "Failed to resize subnet $old_subnet->description id $old_subnet->id<hr>".$e->getMessage(), 2);
			return false;
		}
		# ok
		$this->Log->write( "Subnet resize", "Subnet $old_subnet->description id $old_subnet->id resized<hr>".$this->array_to_log(array("id"=>$subnetId, "mask"=>$mask)), 0);
		return true;
	}

	/**
	 * This function splits subnet into smaller subnets
	 *
	 * @access private
	 * @param mixed $subnet_old
	 * @param mixed $number
	 * @param mixed $prefix
	 * @param string $group (default: "yes")
	 * @param string $strict (default: "yes")
	 * @return bool
	 */
	public function subnet_split ($subnet_old, $number, $prefix, $group="yes", $strict="yes") {

		# we first need to check if it is ok to split subnet and get parameters
		$check = $this->verify_subnet_split ($subnet_old, $number, $group, $strict);

		# ok, extract parameters from result array - 0 is $newsubnets and 1 is $addresses
		$newsubnets = $check[0];
		$addresses  = $check[1];

		# admin object
		$Admin = new Admin ($this->Database, false);

		# create new subnets and change subnetId for recalculated hosts
		$m = 0;
		foreach($newsubnets as $subnet) {
			//set new subnet insert values
			$values = array("description"=>strlen($prefix)>0 ? $prefix.($m+1) : "split_subnet_".($m+1),
							"subnet"=>$subnet['subnet'],
							"mask"=>$subnet['mask'],
							"sectionId"=>$subnet['sectionId'],
							"masterSubnetId"=>$subnet['masterSubnetId'],
							"vlanId"=>@$subnet['vlanId'],
							"vrfId"=>@$subnet['vrfId'],
							"allowRequests"=>@$subnet['allowRequests'],
							"showName"=>@$subnet['showName'],
							"permissions"=>$subnet['permissions']
							);
			//create new subnets
			$this->modify_subnet ("add", $values);

			//get all address ids
			unset($ids);
			foreach($addresses as $ip) {
				if($ip->subnetId == $m) {
    				if(!isset($ids)) $ids = array();
					$ids[] = $ip->id;
				}
			}

			//replace all subnetIds in IP addresses to new subnet
			if(isset($ids)) {
				if(!$Admin->object_modify("ipaddresses", "edit-multiple", $ids, array("subnetId"=>$this->lastInsertId)))	{ $this->Result->show("danger", _("Failed to move IP address"), true); }
			}

			# next
			$m++;
		}

		# do we need to remove old subnet?
		if($group!="yes") {
			if(!$Admin->object_modify("subnets", "delete", "id", array("id"=>$subnet_old->id)))								{ $this->Result->show("danger", _("Failed to remove old subnet"), true); }
		}

		# result
		return true;
	}












	/**
	* @subnet functions
	* -------------------------------
	*/

	/**
	 * Fetches subnetd by specified method
	 *
	 * @access public
	 * @param string $method (default: "id")
	 * @param mixed $value
	 * @return array|false
	 */
	public function fetch_subnet ($method="id", $value) {
		# null method
		$method = is_null($method) ? "id" : $method;
		# fetch
		return $this->fetch_object ("subnets", $method, $value);
	}

	/**
	 * Fetches all subnets in specified section
	 *
	 * @access public
	 * @param mixed $sectionId
	 * @return array
	 */
	public function fetch_section_subnets ($sectionId) {
		# check order
		$this->get_settings ();
		$order = $this->get_subnet_order ();
		// subnet fix
		if($order[0]=="subnet") $order[0] = "subnet_int";
		# fetch
		// if sectionId is not numeric, assume it is section name rather than id, set query appropriately
		if (is_numeric($sectionId)) {
			$query = "SELECT *,LPAD(subnet, 32, 0) as `subnet_int` FROM `subnets` where `sectionId` = ? order by `isFolder` desc, case `isFolder` when 1 then description else $order[0] end $order[1]";
		}
		else {
			$query = "SELECT *,LPAD(subnet, 32, 0) as `subnet_int` FROM `subnets` where `sectionId` in (SELECT id from sections where name = ?) order by `isFolder` desc, case `isFolder` when 1 then description else $order[0] end $order[1]";
		}
		try { $subnets = $this->Database->getObjectsQuery($query, array($sectionId)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# save to subnets cache
		if(sizeof($subnets)>0) {
			foreach($subnets as $subnet) {
    			// remove fake subnet_int field
    			unset($subnet->subnet_int);
    			// save
				$this->cache_write ("subnets", $subnet->id, $subnet);
			}
		}
		# result
		return sizeof($subnets)>0 ? (array) $subnets : array();
	}

	/**
	 * This function fetches id, subnet and mask for all subnets
	 *
	 *	Needed for search > search_subnets_inside
	 *
	 * @access public
	 * @return bool|array
	 */
	public function fetch_all_subnets_search ($type = "IPv4") {
		# set query (4294967295 = 255.255.255.255)
		if ($type=="IPv4")	{ $query = "SELECT `id`,`subnet`,`mask` FROM `subnets` where `subnet` < 4294967295;"; }
		else				{ $query = "SELECT `id`,`subnet`,`mask` FROM `subnets` where `subnet` > 4294967295;"; }
		# fetch
		try { $subnets = $this->Database->getObjectsQuery($query); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# result
		return $subnets;
	}

	/**
	 * This function fetches everything for all subnets
	 *
	 *      needed for API get all subnets
	 *
	 * @access public
	 * @return subnets|false
	 */
	public function fetch_all_subnets() {
	    $query = "SELECT * FROM `subnets`;";
	    try {
			$subnets = $this->Database->getObjectsQuery($query);
	    }
	    catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
	    }
	    # result
	    return $subnets;
	}

	/**
	 * This function fetches id, subnet and mask for all subnets
	 *
	 *	Needed for pingCheck script
	 *
	 * @access public
	 * @param int $agentId (default:null)
	 * @return array|false
	 */
	public function fetch_all_subnets_for_pingCheck ($agentId=null) {
		# null
		if (is_null($agentId) || !is_numeric($agentId))	{ return false; }
		# fetch
		try { $subnets = $this->Database->getObjectsQuery("SELECT `id`,`subnet`,`sectionId`,`mask` FROM `subnets` where `scanAgent` = ? and `pingSubnet` = 1 and `isFolder`= 0 and `mask` > '0' and subnet > 16843009;", array($agentId)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# result
		return sizeof($subnets)>0 ? $subnets : false;
	}

	/**
	 * This function fetches id, subnet and mask for all subnets
	 *
	 *	Needed for discoveryCheck script
	 *
	 * @access public
	 * @param int $agentId (default:null)
	 * @return array|false
	 */
	public function fetch_all_subnets_for_discoveryCheck ($agentId=null) {
		# null
		if (is_null($agentId) || !is_numeric($agentId))	{ return false; }
		# fetch
		try { $subnets = $this->Database->getObjectsQuery("SELECT `id`,`subnet`,`sectionId`,`mask` FROM `subnets` where `scanAgent` = ? and `discoverSubnet` = 1 and `isFolder`= 0 and `isFull`!= 1 and `mask` > '0' and subnet > 16843009 and `mask` > 0;", array($agentId)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# result
		return sizeof($subnets)>0 ? $subnets : false;
	}

	/**
	 * Fetches all subnets within section with specified vlan ID
	 *
	 * @access public
	 * @param mixed $vlanId
	 * @param mixed $sectionId
	 * @return array|false
	 */
	public function fetch_vlan_subnets ($vlanId, $sectionId=null) {
	    # fetch settings and set subnet ordering
	    $this->get_settings();
	    $order = array();
	    $order = $this->get_subnet_order ();

	    # fetch section and set section ordering
	    $section  = $this->fetch_object ("sections", "id", $sectionId);

	    # section ordering - overrides network
	    if(@$section->subnetOrdering!="default" && strlen(@$section->subnetOrdering)>0 ) 	{ $order = explode(",", $section->subnetOrdering); }
	    else 																				{ $order = $this->get_subnet_order (); }

		// subnet fix
		if($order[0]=="subnet") $order[0] = "subnet_int";

		# set query
		if(!is_null($sectionId)) {
			$query  = "select *,subnet*1 as subnet_int from `subnets` where `vlanId` = ? and `sectionId` = ? ORDER BY isFolder desc, $order[0] $order[1];";
			$params = array($vlanId, $sectionId);
		}
		else {
			$query  = "select *,subnet*1 as subnet_int from `subnets` where `vlanId` = ? ORDER BY isFolder desc, $order[0] $order[1];";
			$params = array($vlanId);
		}

		# fetch
		try { $subnets = $this->Database->getObjectsQuery($query, $params); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# save to subnets cache
		if(sizeof($subnets)>0) {
			foreach($subnets as $subnet) {
    			unset($subnet->subnet_int);
                $this->cache_write ("subnets", $subnet->id, $subnet);
			}
		}
		# result
		return sizeof($subnets)>0 ? (array) $subnets : false;
	}


	/**
	 * Checks if subnet is in vlan
	 *
	 * @access private
	 * @param mixed $subnetId
	 * @param mixed $vlanId
	 * @return bool
	 */
	private function is_subnet_in_vlan ($subnetId, $vlanId) {
		# fetch subnet details
		$subnet = $this->fetch_subnet ("id", $subnetId);
		# same id?
		return @$subnet->vlanId==$vlanId ? true : false;
	}

	/**
	 * Checks if subnet is linked.
	 *
	 * @access public
	 * @param mixed $subnetId
	 * @return array|false
	 */
	public function is_linked ($subnetId) {
    	if(!is_numeric($subnetId)) {
        	return false;
    	}
    	else {
    		try { $subnets = $this->Database->getObjectsQuery("select * from subnets where `linked_subnet` = ?", array($subnetId)); }
    		catch (Exception $e) {
    			$this->Result->show("danger", _("Error: ").$e->getMessage());
    			return false;
    		}
    		// check
    		if (sizeof($subnets)>0) {
        		foreach ($subnets as $s) {
                    $this->cache_write ("subnets", $s->id, $s);
        		}
        		return $subnets;
    		}
    		else {
        		return false;
    		}
    	}
	}


	/**
	 * Fetches all subnets within section with specified vrf ID
	 *
	 * @access public
	 * @param mixed $vrfId
	 * @param mixed $sectionId
	 * @return array|false
	 */
	public function fetch_vrf_subnets ($vrfId, $sectionId=null) {
	    # fetch settings and set subnet ordering
	    $this->get_settings();
	    $order = array();
	    $order = $this->get_subnet_order ();

	    # fetch section and set section ordering
	    $section  = $this->fetch_object ("sections","id", $sectionId);

	    # section ordering - overrides network
	    if(@$section->subnetOrdering!="default" && strlen(@$section->subnetOrdering)>0 ) 	{ $order = explode(",", $section->subnetOrdering); }
	    else 																				{ $order = $this->get_subnet_order (); }

		// subnet fix
		if($order[0]=="subnet") $order[0] = "subnet_int";

		# set query
		if(!is_null($sectionId)) {
			$query  = "select *,subnet*1 as subnet_int from `subnets` where `vrfId` = ? and `sectionId` = ? ORDER BY isFolder desc, $order[0] $order[1];";
			$params = array($vrfId, $sectionId);
		}
		else {
			$query  = "select *,subnet*1 as subnet_int from `subnets` where `vrfId` = ? ORDER BY isFolder desc, $order[0] $order[1];";
			$params = array($vrfId);
		}

		# fetch
		try { $subnets = $this->Database->getObjectsQuery($query, $params); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# save to subnets cache
		if(sizeof($subnets)>0) {
			foreach($subnets as $subnet) {
    			unset($subnet->subnet_int);
                $this->cache_write ("subnets", $subnet->id, $subnet);
			}
		}
		# result
		return sizeof($subnets)>0 ? (array) $subnets : false;
	}

	/**
	 * Checks if subnet is in vrf
	 *
	 * @access private
	 * @param mixed $subnetId
	 * @param mixed $vrfId
	 * @return bool
	 */
	private function is_subnet_in_vrf ($subnetId, $vrfId) {
		# fetch subnet details
		$subnet = $this->fetch_subnet ("id", $subnetId);
		# same id?
		return @$subnet->vrfId==$vrfId ? true : false;
	}

	/**
	 * Fetches all scanning agents
	 *
	 * @access public
	 * @return array|false
	 */
	public function fetch_scanning_agents () {
		# fetch
		try { $agents = $this->Database->getObjects("scanAgents"); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		return sizeof($agents)>0 ? $agents : false;
	}

	/**
	 * Checks for all subnets that are marked for scanning and new hosts discovery
	 *
	 * @access public
	 * @param int $agentId (default: null)
	 * @return array|false
	 */
	public function fetch_scanned_subnets ($agentId=null) {
		// agent not set false
		if (is_null($agentId) || !is_numeric($agentId)) { return false; }
		// set query
		$query = "select * from `subnets` where `scanAgent` = ? and ( `pingSubnet`=1 or `discoverSubnet`=1 );";
		# fetch
		try { $subnets = $this->Database->getObjectsQuery($query, array($agentId)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		return sizeof($subnets)>0 ? $subnets : false;
	}

	/**
	 * Finds gateway in subnet
	 *
	 * @access public
	 * @param int $subnetId
	 * @return object|false
	 */
	public function find_gateway ($subnetId) {
		// set query
		$query = "select `ip_addr`,`id` from `ipaddresses` where `subnetId` = ? and `is_gateway` = 1;";
		# fetch
		try { $gateway = $this->Database->getObjectQuery($query, array($subnetId)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		return sizeof($gateway)>0 ? $gateway : false;
	}

	/**
	 * Returns all IPv4 subnet masks with different presentations
	 *
	 * @access public
	 * @return array
	 */
	public function get_ipv4_masks () {
    	$out = array();
		# loop masks
		for($mask=32; $mask>=0; $mask--) {
			// initialize
			$out[$mask] = new StdClass ();

			// fake cidr
			$this->initialize_pear_net_IPv4 ();
			$net = $this->Net_IPv4->parseAddress("10.0.0.0/$mask");

			// set
			$out[$mask]->bitmask = $mask;									// bitmask
			$out[$mask]->netmask = $net->netmask;							// netmask
			$out[$mask]->host_bits = 32-$mask;								// host bits
			$out[$mask]->subnet_bits = 32-$out[$mask]->host_bits;			// network bits
			$out[$mask]->hosts = number_format($this->get_max_hosts ($mask, "IPv4"), 0, ",", ".");		// max hosts
			$out[$mask]->subnets = number_format(pow(2,($mask-8)), 0, ",", ".");
			$out[$mask]->wildcard = long2ip(~ip2long($net->netmask));	   //0.0.255.255

			// binary
			$parts = explode(".", $net->netmask);
			foreach($parts as $k=>$p) { $parts[$k] = str_pad(decbin($p),8, 0); }
			$out[$mask]->binary = implode(".", $parts);
		}
		# return result
		return $out;
	}









	/**
	* @slave subnet functions
	* -------------------------------
	*/

	/**
	 * Checks if subnet has any slaves
	 *
	 * @access public
	 * @param mixed $subnetId
	 * @return bool
	 */
	public function has_slaves ($subnetId) {
    	// NULL subnetId cannot have slaves
    	if (is_null($subnetId))     { return false; }
    	else {
        	$cnt = $this->count_database_objects ("subnets", "masterSubnetId", $subnetId);
        	if ($cnt==0) { return false; }
        	else         { return true; }
    	}
	}

	/**
	 * Fetches all immediate slave subnets for specified subnetId
	 *
	 * @access public
	 * @param mixed $subnetId
	 * @param string|array $result_fields (default: "*")
	 * @return array|false
	 */
	public function fetch_subnet_slaves ($subnetId, $result_fields = "*") {
    	// fetch
		$slaves = $this->fetch_multiple_objects ("subnets", "masterSubnetId", $subnetId, "subnet_int", true, false, $result_fields);
		# save to subnets cache
        if ($slaves!==false) {
			foreach($slaves as $slave) {
    			unset($slave->subnet_int);
                $this->cache_write ("subnets", $slave->id, $slave);
			}
			return $slaves;
		}
		else {
    		# no subnets
    		return false;
		}
	}

	/**
	 * Recursively fetches all slaves
	 *
	 * @access public
	 * @param mixed $subnetId
	 * @return void
	 */
	public function fetch_subnet_slaves_recursive ($subnetId) {
		$end = false;							//loop break flag
		# slaves array of id's, add current
		$this->slaves[] = (int) $subnetId;		//id

		# loop
		while($end == false) {
			# fetch all immediate slaves
			$slaves2 = $this->fetch_subnet_slaves ($subnetId);

			# we have more slaves
			if($slaves2) {
				# recursive
				foreach($slaves2 as $slave) {
					# save to full array of slaves
					$this->slaves_full[$slave->id] = $slave;
					# fetch possible new slaves
					$this->fetch_subnet_slaves_recursive ($slave->id);
					$end = true;
				}
			}
			# no more slaves
			else {
				$end = true;
			}
		}
	}

	/**
	 * Resets array of all slave id's. Needs to be called *BEFORE* all slaves are fetched to prevent stacking
	 *
	 * Before reset we should save array to prevent double checking!
	 *
	 * @access public
	 * @return void
	 */
	public function reset_subnet_slaves_recursive () {
		$this->slaves = null;
		$this->slaves_full = null;
	}

	/**
	 * Removes master subnet from slave subnets array
	 *
	 * @access public
	 * @param mixed $subnetId
	 * @return void
	 */
	public function remove_subnet_slaves_master ($subnetId) {
        if(isset($this->slaves_full)) {
    		foreach($this->slaves_full as $k=>$s) {
    			if($s==$subnetId) {
    				unset($this->slaves_full[$k]);
    			}
    		}
		}
		if(isset($this->slaves)) {
    		foreach ($this->slaves as $k=>$s) {
     			if($s==$subnetId) {
    				unset($this->slaves[$k]);
    			}
    		}
		}
	}

	/**
	 * fetch whole tree path for subnetId - from slave to parents
	 *
	 * @access public
	 * @param mixed $subnetId
	 * @return array
	 */
	public function fetch_parents_recursive ($subnetId) {
		$parents = array();
		$root = false;

		while($root === false) {
			$subd = $this->fetch_object("subnets", "id", $subnetId);		# get subnet details

			if($subd!==false) {
    			$subd = (array) $subd;
				# not root yet
				if(@$subd['masterSubnetId']!=0) {
					array_unshift($parents, $subd['masterSubnetId']);
					$subnetId  = $subd['masterSubnetId'];
				}
				# root
				else {
					array_unshift($parents, $subd['masterSubnetId']);
					$root = true;
				}
			}
			else {
				$root = true;
			}
		}
		# remove 0
		unset($parents[0]);
		# return array
		return $parents;
	}

	/**
	 * Searches for cidr inside section
	 *
	 * @access public
	 * @param bool $sectionId (default: false)
	 * @param bool $cidr (default: false)
	 * @return array|false
	 */
	public function find_subnet ($sectionId = false, $cidr = false) {
    	// check
    	if (!is_numeric($sectionId))                        { return false; }
    	if ($this->verify_cidr_address ($cidr) !== true)    { return false; }
    	// set subnet / mask
    	$tmp = explode("/", $cidr);
    	// search
		try { $subnet = $this->Database->getObjectQuery("select * from `subnets` where `subnet`=? and `mask`=? and `sectionId`=?;", $values = array($this->transform_address($tmp[0],"decimal"), $tmp[1], $sectionId)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
        # result
        return sizeof($subnet)>0 ? $subnet : false;

	}













	/**
	* @transform IP/subnet functions
	* -------------------------------
	*/

	/**
	 * Calculates subnet usage for subnet, including slave
	 *
	 *  If detailed = true it will group addresses in subnet by tag for drawing graph
	 *
	 * @access public
	 * @param array|object $subnet
	 * @param bool $detailed (default: false)
	 * @return array
	 */
	public function calculate_subnet_usage ($subnet, $detailed = false) {
		// cast to object
		if(is_array($subnet)) {
    		$subnet = (object) $subnet;
		}

		// init addresses object
		$this->Addresses = new Addresses ($this->Database);
		// fetch address types
		$this->get_addresses_types();

    	// is slaves
    	if ($this->has_slaves ($subnet->id)) {
            // if we have slaves we need to check against every slave
            $this->reset_subnet_slaves_recursive ();
            $this->fetch_subnet_slaves_recursive ($subnet->id);
            $this->remove_subnet_slaves_master ($subnet->id);

            // set master details
            $subnet_usage = $this->calculate_single_subnet_details ($subnet, false, false);

        	// loop and add results
            foreach ($this->slaves_full as $ss) {
                // calculate for specific subnet
                $slave_usage = $this->calculate_single_subnet_details ($ss, true, false);
                // append slave values to its master
                $subnet_usage['used']      = $subnet_usage['used'] + $slave_usage['used'];
                $subnet_usage['freehosts'] = $subnet_usage['freehosts'] - $slave_usage['used'];
            }
            // recalculate percentge
            $subnet_usage["freehosts_percent"] = round((($subnet_usage['freehosts'] * 100) / $subnet_usage['maxhosts']),2);
            $subnet_usage["Used_percent"]      = 100 - $subnet_usage["freehosts_percent"];
    	}
    	// no slaves
    	else {
            $subnet_usage = $this->calculate_single_subnet_details ($subnet, false, $detailed);
    	}
    	// return usage
    	return $subnet_usage;
	}

	/**
	 * Calculate usage for single subnet
	 *
	 * @access private
	 * @param mixed $subnet
	 * @param bool $is_slave (default: false)
	 * @param bool $detailed (default: false)
	 * @return void
	 */
	private function calculate_single_subnet_details ($subnet, $is_slave = false, $detailed = false) {
 		// set IP version
		$ip_version = $this->get_ip_version ($subnet->subnet);
    	// no strict mode if it is_slave
    	$strict_mode = $is_slave ? false : true;
    	// count hosts
    	$address_count = $this->Addresses->count_subnet_addresses ($subnet->id);

    	// init result
    	$out = array();

		// marked as full ?
		if ($subnet->isFull==1) {
     		// set values
            $out["used"]              = (int) $this->get_max_hosts ($subnet->mask, $ip_version, $strict_mode);
            $out["maxhosts"]          = $out['used'];
            $out["freehosts"]         = 0;
            $out["freehosts_percent"] = 0;
            $out["Used_percent"]      = 100;
		}
		else {
    		// set values
            $out["used"]              = (int) $address_count;
            $out["maxhosts"]          = (int) $this->get_max_hosts ($subnet->mask, $ip_version, $strict_mode);
            // slaves fix for reducing subnet and broadcast address
            if($ip_version=="IPv4" && $is_slave) {
                if($subnet->mask==32 && $out["used"]==0) {
                     $out["used"]++;
                }
                elseif($subnet->mask==31 &&  $out["used"]==0) {
                    $out["used"] = $out["used"]+2;
                }
                elseif($subnet->mask==31 &&  $out["used"]==1) {
                    $out["used"]++;
                }
                else {
                    $out["used"] = $out["used"]+2;
                }
            }
            // percentage
            $out["freehosts"]         = (int) gmp_strval(gmp_sub($out['maxhosts'],$out['used']));
            $out["freehosts_percent"] = round((($out['freehosts'] * 100) / $out['maxhosts']),2);
            // detailed results ?
            if ($detailed) {
                // fetch full addresses
                $addresses = $this->Addresses->fetch_subnet_addresses ($subnet->id);
                // order - group by tag type
                $tag_addresses = $this->calculate_subnet_usage_sort_addresses ($addresses);
        	    // calculate use percentage for each address tag
        	    foreach($this->address_types as $t) {
        		    $out[$t['type']."_percent"] = round( ( ($tag_addresses[$t['type']] * 100) / $out['maxhosts']), 2 );
        	    }
            }
		}
		# result
		return $out;
	}

	/**
	 * Calculates subnet usage per host type
	 *
	 * @access public
	 * @param mixed $addresses
	 * @return array
	 */
	public function calculate_subnet_usage_sort_addresses ($addresses) {
		$count = array();
		$count['used'] = 0;				//initial sum count
		# fetch address types
		$this->get_addresses_types();
		# create array of keys with initial value of 0
		foreach($this->address_types as $a) {
			$count[$a['type']] = 0;
		}
		# count
		if($addresses) {
			foreach($addresses as $ip) {
				$count[$this->translate_address_type($ip->state)]++;
				$count['used'] = gmp_strval(gmp_add($count['used'], 1));
			}
		}
		# result
		return $count;
	}

	/**
	 * Returns array of address types
	 *
	 * @access public
	 * @return void
	 */
	public function get_addresses_types () {
		# from cache
		if($this->address_types == null) {
        	# fetch
        	$types = $this->fetch_all_objects ("ipTags", "id");

            # save to array
			$types_out = array();
			foreach($types as $t) {
				$types_out[$t->id] = (array) $t;
			}
			# save to cache
			$this->address_types = $types_out;
		}
	}

	/**
	 * Translates address type from index (int) to type
	 *
	 *	e.g.: 0 > offline
	 *
	 * @access public
	 * @param mixed $index
	 * @return mixed
	 */
	public function translate_address_type ($index) {
		# fetch
		$this->get_addresses_types();
		# return
		return $this->address_types[$index]["type"];
	}

	/**
	 * Present numbers in pow 10, only for IPv6
	 *
	 * @access public
	 * @param mixed $number
	 * @return mixed
	 */
	public function reformat_number ($number) {
		$length = strlen($number);
		$pos	= $length - 3;

		if ($length > 8) {
			$number = "~". substr($number, 0, $length - $pos) . "&middot;10^<sup>". $pos ."</sup>";
		}
		return $number;
	}

	/**
	 * Get maxumum number of hosts for netmask
	 *
	 * @access public
	 * @param mixed $netmask
	 * @param mixed $ipversion
	 * @param bool $strict (default: true)
	 * @return int
	 */
	public function get_max_hosts ($netmask, $ipversion, $strict=true) {
		if($ipversion == "IPv4")	{ return $this->get_max_IPv4_hosts ($netmask, $strict); }
		else						{ return $this->get_max_IPv6_hosts ($netmask); }
	}

	/**
	 * Get max number of IPv4 hosts
	 *
	 * @access public
	 * @param mixed $netmask
	 * @return int
	 */
	public function get_max_IPv4_hosts ($netmask, $strict) {
		if($netmask==31)			{ return 2; }
		elseif($netmask==32)		{ return 1; }
		elseif($strict===false)		{ return (int) pow(2, (32 - $netmask)); }
		else						{ return (int) pow(2, (32 - $netmask)) -2; }
	}

	/**
	 * Get max number of IPv6 hosts
	 *
	 * @access public
	 * @param mixed $netmask
	 * @return int
	 */
	public function get_max_IPv6_hosts ($netmask) {
		return gmp_strval(gmp_pow(2, 128 - $netmask));
	}

	/**
	 * Returns maximum netmask length
	 *
	 * @access public
	 * @param mixed $address
	 * @return int
	 */
	private function get_max_netmask ($address) {
		return $this->identify_address($address)=="IPv6" ? 128 : 32;
	}

	/**
	 * Parses address
	 *
	 * @access public
	 * @param mixed $address
	 * @param mixed $netmask
	 * @return array
	 */
	public function get_network_boundaries ($address, $netmask) {
		# make sure we have dotted format
		$address = $this->transform_address ($address, "dotted");
		# set IP version
		$ipversion = $this->get_ip_version ($address);
		# return boundaries
		if($ipversion == "IPv4")	{ return $this->get_IPv4_network_boundaries ($address, $netmask); }
		else						{ return $this->get_IPv6_network_boundaries ($address, $netmask); }
	}

	/**
	 * Returns IPv4 network boundaries
	 *
	 *	network, host ip (if not network), broadcast, bitmask, netmask
	 *
	 * @access private
	 * @param mixed $address
	 * @param mixed $netmask
	 * @return array
	 */
	private function get_IPv4_network_boundaries ($address, $netmask) {
		# Initialize PEAR NET object
		$this->initialize_pear_net_IPv4 ();
		# parse IP address
		$net = $this->Net_IPv4->parseAddress( $address.'/'.$netmask );
		# return boundaries
		return (array) $net;
	}

	/**
	 * Returns IPv6 network boundaries
	 *
	 *	network, host ip (if not network), broadcast, bitmask, netmask
	 *
	 * @access private
	 * @param mixed $address
	 * @param mixed $netmask
	 * @return array
	 */
	private function get_IPv6_network_boundaries ($address, $netmask) {
		# Initialize PEAR NET object
		$this->initialize_pear_net_IPv6 ();
		# parse IPv6 subnet
		$net = $this->Net_IPv6->parseaddress( "$address/$netmask");
		# set network and masks
		$out = new StdClass();
		$out->start		= $net['start'];
		$out->network 	= $address;
		$out->netmask 	= $netmask;
		$out->bitmask 	= $netmask;
		$out->broadcast = $net['end'];		//highest IP address
		# result
		return (array) $out;
	}

	/**
	 * Calculates first possible subnet from provided subnet and number of next free IP addresses
	 *
	 * @access public
	 * @param mixed $address
	 * @param mixed $free
	 * @param bool $print (default: true)
	 * @return mixed|false
	 */
	public function get_first_possible_subnet ($address, $free, $print = true) {
		# set max possible mask for IP range
		$maxmask =  $this->get_max_netmask ($this->transform_address ($address, "dotted"));

		# calculate maximum possible IP mask
		$mask = floor(log($free)/log(2));
		$mask = $maxmask - $mask;

		# we have now maximum mask. We need to verify if subnet is valid
		# otherwise add 1 to $mask and go to $maxmask
		for($m=$mask; $m<=$maxmask; $m++) {
			# validate
			$err = $this->verify_cidr_address( $address."/".$m , true);
			if($err===true) {
				# ok, it is possible!
				$result = $address."/".$m;
				break;
			}
		}

		# result
		if(isset($result)) {
			# print or return?
			if($print)	print $result;
			else		return $result;
		}
		else {
			return false;
		}
	}










	/**
	* @verify address functions
	* -------------------------------
	*/

	/**
	 * Verifies CIDR address
	 *
	 * @access public
	 * @param mixed $cidr
	 * @param bool $issubnet (default: true)
	 * @return string|true
	 */
	public function verify_cidr_address ($cidr, $issubnet = true) {
		# first verify address and mask format
		if(strlen($error = $this->verify_cidr ($cidr))>0)	{ return $error; }
		# make checks
		return $this->identify_address ($cidr)=="IPv6" ? $this->verify_cidr_address_IPv6 ($cidr, $issubnet) : $this->verify_cidr_address_IPv4 ($cidr, $issubnet);
	}

	/**
	 * Verifies IPv4 CIDR address
	 *
	 * @access public
	 * @param mixed $cidr
	 * @param bool $issubnet (default: true)
	 * @return string|true
	 */
	public function verify_cidr_address_IPv4 ($cidr, $issubnet = true) {
		# Initialize PEAR NET object
		$this->initialize_pear_net_IPv4 ();
		# validate
        if ($net = $this->Net_IPv4->parseAddress ($cidr)) {
            if (!$this->Net_IPv4->validateIP ($net->ip)) 				{ return _("Invalid IP address!"); }														//validate IP
            elseif (($net->network != $net->ip) && ($issubnet))			{ return _("IP address cannot be subnet! (Consider using")." ". $net->network .")"; }		//network must be same as provided IP address
            elseif (!$this->Net_IPv4->validateNetmask ($net->netmask)) 	{ return _('Invalid netmask').' ' . $net->netmask; }    									//validate netmask
            else														{ return true; }
        }
        else 															{ return _('Invalid CIDR format!'); }
	}

	/**
	 * Verifies IPv6 CIDR address
	 *
	 * @access public
	 * @param mixed $cidr (cidr)
	 * @param bool $issubnet (default: true)
	 * @return string|true
	 */
	public function verify_cidr_address_IPv6 ($cidr, $issubnet = true) {
		# to lower
		$cidr = strtolower($cidr);
		# Initialize PEAR NET object
		$this->initialize_pear_net_IPv6 ();
        # validate
        if (!$this->Net_IPv6->checkIPv6 ($cidr) ) 					{ return _("Invalid IPv6 address!"); }
        else {
            $subnet = $this->Net_IPv6->getNetmask($cidr);			//validate subnet
            $subnet = $this->Net_IPv6->compress($subnet);			//get subnet part
            $subnetParse = explode("/", $cidr);
			# validate that subnet is subnet
            if ( ($subnetParse[0] != $subnet) && ($issubnet) ) 		{ return _("IP address cannot be subnet! (Consider using")." ". $subnet ."/". $subnetParse[1] .")"; }
            else													{ return true; }
	   }
	}

	/**
	 * Verifies that CIDR address is correct
	 *
	 * @access public
	 * @param mixed $cidr (cidr)
	 * @return string
	 */
	public function verify_cidr ($cidr) {
		$cidr =  explode("/", $cidr);
		# verify network part
	    if(strlen($cidr[0])==0 || strlen($cidr[1])==0) 				{ return _("Invalid CIDR format!"); }
	    # verify network part
		if($this->identify_address_format ($cidr[0])!="dotted")		{ return _("Invalid Network!"); }
		# verify mask
		if(!is_numeric($cidr[1]))									{ return _("Invalid netmask"); }
		if($this->get_max_netmask ($cidr[0])<$cidr[1])				{ return _("Invalid netmask"); }
	}

	/**
	 * Verifies if new subnet overlaps with any of existing subnets in that section and same or null VRF
	 *
	 * @access public
	 * @param int $sectionId
	 * @param mixed $new_subnet (cidr)
	 * @param int $vrfId (default: 0)
	 * @return string|false
	 */
	public function verify_subnet_overlapping ($sectionId, $new_subnet, $vrfId = 0) {
	    # fetch section subnets
	    $sections_subnets = $this->fetch_section_subnets ($sectionId);
		# fix null vrfid
		$vrfId = is_numeric($vrfId) ? $vrfId : 0;
	    # verify new against each existing
	    if (sizeof($sections_subnets)>0) {
	        foreach ($sections_subnets as $existing_subnet) {
	            //only check if vrfId's match
	            if($existing_subnet->vrfId==$vrfId || $existing_subnet->vrfId==null) {
		            # ignore folders!
		            if($existing_subnet->isFolder!=1) {
			            # check overlapping
						if($this->verify_overlapping ($new_subnet,  $this->transform_to_dotted($existing_subnet->subnet).'/'.$existing_subnet->mask)!==false) {
							 return _("Subnet $new_subnet overlaps with").' '. $this->transform_to_dotted($existing_subnet->subnet).'/'.$existing_subnet->mask." (".$existing_subnet->description.")";
						}

					}
	            }
	        }
	    }
	    # default false - does not overlap
	    return false;
	}

	/**
	 * Verifies if resized subnet overlaps with any of existing subnets in that section and same or null VRF
	 *
	 * @access public
	 * @param int $sectionId
	 * @param mixed $new_subnet
	 * @param int $old_subnet_id
	 * @param int $vrfId (default: 0)
	 * @return string|false
	 */
	public function verify_subnet_resize_overlapping ($sectionId, $new_subnet, $old_subnet_id, $vrfId = 0) {
	    # fetch section subnets
	    $sections_subnets = $this->fetch_section_subnets ($sectionId);
		# fix null vrfid
		$vrfId = is_numeric($vrfId) ? $vrfId : 0;
	    # verify new against each existing
	    if (sizeof($sections_subnets)>0) {
	        foreach ($sections_subnets as $existing_subnet) {
		        //ignore same
		        if($existing_subnet->id!=$old_subnet_id) {
		            //only check if vrfId's match
		            if($existing_subnet->vrfId==$vrfId || $existing_subnet->vrfId==null) {
			            # ignore folders!
			            if($existing_subnet->isFolder!=1) {
				            # check overlapping
				            if($this->verify_overlapping ($new_subnet,  $this->transform_to_dotted($existing_subnet->subnet).'/'.$existing_subnet->mask)!==false) {
								 return _("Subnet $new_subnet overlaps with").' '. $this->transform_to_dotted($existing_subnet->subnet).'/'.$existing_subnet->mask." (".$existing_subnet->description.")";
				            }
						}
		            }
	            }
	        }
	    }
	    # default false - does not overlap
	    return false;
	}

	/**
	 * Check if nested subnet already exists in section!
	 *
	 * Subnet policy:
	 *      - inside section subnets cannot overlap!
	 *      - same subnet can be configured in different sections
	 *		- if vrf is same do checks, otherwise skip
	 *		- mastersubnetid we need for new checks to permit overlapping of nested clients
	 *
	 * @access public
	 * @param int $sectionId
	 * @param CIDR $new_subnet
	 * @param int $vrfId (default: 0)
	 * @param int $masterSubnetId (default: 0)
	 * @return string|false
	 */
	public function verify_nested_subnet_overlapping ($sectionId, $new_subnet, $vrfId = 0, $masterSubnetId = 0) {
    	# fetch all slave subnets
    	$slave_subnets = $this->fetch_subnet_slaves ($masterSubnetId);
		# fix null vrfid
		$vrfId = is_numeric($vrfId) ? $vrfId : 0;

		// loop
		if ($slave_subnets!==false) {
			foreach ($slave_subnets as $ss) {
    			// no folders
    			if($ss->isFolder!=1) {
        			if($ss->vrfId==$vrfId || $ss->vrfId==null) {
        				if($this->verify_overlapping ( $new_subnet, $this->transform_to_dotted($ss->subnet)."/".$ss->mask)) {
        					return _("Subnet overlaps with")." ".$this->transform_to_dotted($ss->subnet).'/'.$ss->mask;
        				}
        			}

    			}
			}
		}
        # default false - does not overlap
		return false;
	}

	/**
	 * Verifies overlapping of 2 subnets
	 *
	 * @access public
	 * @param CIDR $subnet1
	 * @param CIDR $subnet2
	 * @return bool
	 */
	public function verify_overlapping ($subnet1, $subnet2) {
		return $this->identify_address ($subnet1)=="IPv4" ? $this->verify_IPv4_subnet_overlapping ($subnet1, $subnet2) : $this->verify_IPv6_subnet_overlapping ($subnet1, $subnet2);
	}

	/**
	 * Verifies overlapping of 2 IPv4 subnets
	 *
	 *	does subnet 1 overlapp with subnet 2 ?
	 *
	 * @access private
	 * @param CIDR $subnet1
	 * @param CIDR $subnet2
	 * @return bool
	 */
	private function verify_IPv4_subnet_overlapping ($subnet1, $subnet2) {
		# Initialize PEAR NET object
		$this->initialize_pear_net_IPv4 ();

		# parse subnets to get subnet and broadcast
		$net1 = $this->Net_IPv4->parseAddress( $subnet1 );
		$net2 = $this->Net_IPv4->parseAddress( $subnet2 );

	    # calculate delta
	    $delta1 = $this->transform_to_decimal( @$net1->broadcast) - $this->transform_to_decimal( @$net1->network);
	    $delta2 = $this->transform_to_decimal( @$net2->broadcast) - $this->transform_to_decimal( @$net2->network);

	    # calculate if smaller is inside bigger
	    if ($delta1 < $delta2) {
	        //check smaller nw and bc against bigger network
	        if ( $this->Net_IPv4->ipInNetwork(@$net1->network, $subnet2) || $this->Net_IPv4->ipInNetwork(@$net1->broadcast, $subnet2) ) 	{ return true; }
	    }
	    else {
	        //check smaller nw and bc against bigger network
	        if ( $this->Net_IPv4->ipInNetwork(@$net2->network, $subnet1) || $this->Net_IPv4->ipInNetwork(@$net2->broadcast, $subnet1) ) 	{ return true; }
	    }
	    # do notoverlap
	    return false;
	}

	/**
	 * Verifies overlapping of 2 IPv6 subnets
	 *
	 *	does subnet 1 overlapp with subnet 2 ?
	 *
	 * @access private
	 * @param CIDR $subnet1
	 * @param CIDR $subnet2
	 * @return boolean
	 */
	private function verify_IPv6_subnet_overlapping ($subnet1, $subnet2) {
		# Initialize PEAR NET object
		$this->initialize_pear_net_IPv6 ();

	    //remove netmask from subnet1 */
	    $subnet1 = $this->Net_IPv6->removeNetmaskSpec ($subnet1);
	    //verify
	    if ($this->Net_IPv6->isInNetmask ( $subnet1 , $subnet2 ) ) { return true; }

		# do notoverlap
	    return false;
	}

	/**
	 * Checks if subnet (cidr) is inside some subnet.
	 *
	 *	Needed for subnet nesting
	 *
	 * @access public
	 * @param mixed $masterSubnetId
	 * @param mixed $cidr
	 * @return bool
	 */
	public function verify_subnet_nesting ($masterSubnetId, $cidr) {
		//first get details for root subnet
		$master_details = $this->fetch_subnet (null, $masterSubnetId);

	    //IPv4 or ipv6?
	    $type_master = $this->identify_address( $master_details->subnet );
	    $type_nested = $this->identify_address( $cidr );

	    //both must be IPv4 or IPv6
        if($type_master != $type_nested) { return false; }

		// if child same as parent return error
		if ($cidr == $this->transform_address($master_details->subnet)."/".$master_details->mask) {
    		return false;
		}
		else {
    		//check
    		return $this->is_subnet_inside_subnet ($cidr, $this->transform_to_dotted ($master_details->subnet)."/".$master_details->mask);
		}
	}

	/**
	 * This function verifies subnet resizing
	 *
	 * @access public
	 * @param mixed $subnet		//subnet in decimal or dotted address format
	 * @param mixed $mask		//new subnet mask
	 * @param mixed $subnetId	//subnet Id
	 * @param mixed $vrfId		//vrfId
	 * @param mixed $masterSubnetId	//master Subnet Id
	 * @param mixed $mask_old	//old mask
	 * @param mixed $sectionId	//section ID
	 * @return void
	 */
	public function verify_subnet_resize ($subnet, $mask, $subnetId, $vrfId, $masterSubnetId, $mask_old, $sectionId=0) {
	    # fetch section and set section ordering
	    $section  = $this->fetch_object ("sections", "id", $sectionId);

		# new mask must be > 8
		if($mask < 8) 											{ $this->Result->show("danger", _('New mask must be at least /8').'!', true); }
		if(!is_numeric($mask))									{ $this->Result->show("danger", _('Mask must be an integer').'!', true);; }

		//new subnet
		$new_boundaries = $this->get_network_boundaries ($this->transform_address($subnet, "dotted"), $mask);
		$subnet = $this->transform_address($new_boundaries['network'], "decimal");

		# verify new address
		$verify = $this->verify_cidr_address($this->transform_address ($subnet, "dotted")."/".$mask);
		if($verify!==true) 										{ $this->Result->show("danger", $verify, true); }

		# same mask - ignore
		if($mask==$mask_old) 									{ $this->Result->show("warning", _("New network is same as old network"), true); }
		# if we are expanding network get new network address!
		elseif($mask < $mask_old) {
			//Checks for strict mode
			if ($section->strictMode=="1") {
				//if it has parent make sure it is still within boundaries
				if((int) $masterSubnetId>0) {
					//if parent is folder check for other in same folder
					$parent_subnet = $this->fetch_subnet(null, $masterSubnetId);
					if($parent_subnet->isFolder!=1) {
						//check that new is inside its master subnet
						if(!$this->verify_subnet_nesting ($parent_subnet->id, $this->transform_to_dotted($subnet)."/".$mask)) {
							$this->Result->show("danger", _("New subnet not in master subnet")."!", true);
						}
						// it cannot be same !
						if ($parent_subnet->mask == $mask) {
							$this->Result->show("danger", _("New subnet cannot be same as master subnet")."!", true);
						}
						//fetch all slave subnets and validate
						$slave_subnets = $this->fetch_subnet_slaves ($parent_subnet->id);
						if ($slave_subnets!==false) {
							foreach ($slave_subnets as $ss) {
								// not self
								if ($ss->id != $subnetId) {
									if($this->verify_overlapping ( $this->transform_to_dotted($subnet)."/".$mask, $this->transform_to_dotted($ss->subnet)."/".$ss->mask)) {
										$this->Result->show("danger", _("Subnet overlaps with")." ".$this->transform_to_dotted($ss->subnet)."/".$ss->mask, true);
									}
								}
							}
						}
					}
					//folder
					else {
						//fetch all folder subnets, remove old subnet and verify overlapping!
						$folder_subnets = $this->fetch_subnet_slaves ($parent_subnet->id);
						//check
						if(sizeof(@$folder_subnets)>0) {
							foreach($folder_subnets as $fs) {
								//dont check against old
								if($fs->id!=$subnetId) {
									//verify that all nested are inside its parent
									if($this->verify_overlapping ( $this->transform_to_dotted($subnet)."/".$mask, $this->transform_to_dotted($fs->subnet)."/".$fs->mask)) {
										$this->Result->show("danger", _("Subnet overlaps with")." ".$this->transform_to_dotted($fs->subnet)."/".$fs->mask, true);
									}
								}
							}
						}
					}
				}
				//root subnet, check overlapping !
				else {
					$section_subnets = $this->fetch_section_subnets ($section->id);
					$overlap = $this->verify_subnet_resize_overlapping ($section->id, $this->transform_to_dotted($subnet)."/".$mask, $subnetId, $vrfId);
					if($overlap!==false) {
						$this->Result->show("danger", $overlap, true);
					}
				}
			}
		}
		# we are shrinking subnet
		else {
			# addresses class
			$Addresses = new Addresses ($this->Database);
			// fetch all subnet addresses
			$subnet_addresses = $Addresses->fetch_subnet_addresses ($subnetId, "ip_addr", "asc");

			//check all IP addresses against new subnet
			foreach($subnet_addresses as $ip) {
				$Addresses->verify_address( $this->transform_to_dotted($ip->ip_addr), $this->transform_to_dotted($subnet)."/".$mask, false, true );
			}
			//Checks for strict mode
			if ($section->strictMode==1) {
				//if it has slaves make sure they are still inside network
				if($this->has_slaves($subnetId)) {
					//fetch slaves
					$nested = $this->fetch_subnet_slaves ($subnetId);
					foreach($nested as $nested_subnet) {
						//if masks and subnets match they are same, error!
						if($nested_subnet->subnet==$subnet && $nested_subnet->mask==$mask) {
							$this->Result->show("danger", _("Subnet it same as ").$this->transform_to_dotted($nested_subnet->subnet)."/$nested_subnet->mask - $nested_subnet->description)", true);
						}
						//verify that all nested are inside its parent
						if(!$this->is_subnet_inside_subnet ( $this->transform_to_dotted($nested_subnet->subnet)."/".$nested_subnet->mask, $this->transform_to_dotted($subnet)."/".$mask)) {
							$this->Result->show("danger", _("Nested subnet out of new subnet")."!<br>(".$this->transform_to_dotted($nested_subnet->subnet)."/$nested_subnet->mask - $nested_subnet->description)", true);
						}
					}
				}
			}
		}
	}

	/**
	 * Checks if it ok to split subnet
	 *
	 * @access private
	 * @param mixed $subnet_old
	 * @param mixed $number
	 * @param string $group
	 * @param string $strict
	 * @return array
	 */
	private function verify_subnet_split ($subnet_old, $number, $group, $strict) {
		# addresses class
		$Addresses = new Addresses ($this->Database);

		# get new mask - how much we need to add to old mask?
		$mask_diff = int;
		switch($number) {
			case "2":   $mask_diff = 1; break;
			case "4":   $mask_diff = 2; break;
			case "8":   $mask_diff = 3; break;
			case "16":  $mask_diff = 4; break;
			case "32":  $mask_diff = 5; break;
			case "64":  $mask_diff = 6; break;
			case "128": $mask_diff = 7; break;
			case "256": $mask_diff = 8; break;
			//otherwise die
			default:	$this->Result->show("danger", _("Invalid number of subnets"), true);
		}
		//set new mask
		$mask = $subnet_old->mask + $mask_diff;
		//set number of subnets
		$number_of_subnets = pow(2,$mask_diff);
		//set max hosts per new subnet
		$max_hosts = $this->get_max_hosts ($mask, $this->identify_address($this->transform_to_dotted($subnet_old->subnet)), false);

		# create array of new subnets based on number of subnets (number)
		$newsubnets = array();
		for($m=0; $m<$number_of_subnets; $m++) {
			$newsubnets[$m] 		 = (array) $subnet_old;
			$newsubnets[$m]['id']    = $m;
			$newsubnets[$m]['mask']  = $mask;

			// if group is selected rewrite the masterSubnetId!
			if($group=="yes") {
				$newsubnets[$m]['masterSubnetId'] = $subnet_old->id;
			}
			// recalculate subnet
			if($m>0) {
				$newsubnets[$m]['subnet'] = gmp_strval(gmp_add($newsubnets[$m-1]['subnet'], $max_hosts));
			}
		}

		// recalculate old hosts to put it to right subnet
		$addresses   = $Addresses->fetch_subnet_addresses ($subnet_old->id, "ip_addr", "asc");		# get all IP addresses
		$subSize = sizeof($newsubnets);		# how many times to check
		$n = 0;								# ip address count
		// loop
		foreach($addresses as $ip) {
			//cast
			$ip = (array) $ip;
			# check to which it belongs
			for($m=0; $m<$subSize; $m++) {

				# check if between this and next - strict
				if($strict == "yes") {
					# check if last
					if(($m+1) == $subSize) {
						if($ip['ip_addr'] > $newsubnets[$m]['subnet']) {
							$addresses[$n]->subnetId = $newsubnets[$m]['id'];
						}
					}
					elseif( ($ip['ip_addr'] > $newsubnets[$m]['subnet']) && ($ip['ip_addr'] < @$newsubnets[$m+1]['subnet']) ) {
						$addresses[$n]->subnetId = $newsubnets[$m]['id'];
					}
				}
				# unstrict - permit network and broadcast
				else {
					# check if last
					if(($m+1) == $subSize) {
						if($ip['ip_addr'] >= $newsubnets[$m]['subnet']) {
							$addresses[$n]->subnetId = $newsubnets[$m]['id'];
						}
					}
					elseif( ($ip['ip_addr'] >= $newsubnets[$m]['subnet']) && ($ip['ip_addr'] < $newsubnets[$m+1]['subnet']) ) {
						$addresses[$n]->subnetId = $newsubnets[$m]['id'];
					}
				}
			}

			# if subnetId is still the same save to error
			if($addresses[$n]->subnetId == $subnet_old->id) {
				$this->Result->show("danger", _('Wrong IP addresses (subnet or broadcast)').' - '.$this->transform_to_dotted($ip['ip_addr']), true);
			}
			# next IP address
			$n++;
		}

		# check if new overlap (e.g. was added twice)
		$nested_subnets = $this->fetch_subnet_slaves ($subnet_old->id);
		if($nested_subnets!==false) {
			//loop through all current slaves and check
			foreach($nested_subnets as $nested_subnet) {
				//check all new
				foreach($newsubnets as $new_subnet) {
					$new_subnet = (object) $new_subnet;
					if($this->verify_overlapping ($this->transform_to_dotted($new_subnet->subnet)."/".$new_subnet->mask, $this->transform_to_dotted($nested_subnet->subnet)."/".$nested_subnet->mask)===true) {
						$this->Result->show("danger", _("Subnet overlapping - ").$this->transform_to_dotted($new_subnet->subnet)."/".$new_subnet->mask." overlaps with ".$this->transform_to_dotted($nested_subnet->subnet)."/".$nested_subnet->mask, true);
					}
				}
			}
		}

		# all good, return result array of newsubnets and addresses
		return array(0=>$newsubnets, 1=>$addresses);
	}

	/**
	 * Checks if subnet 1 is inside subnet 2
	 *
	 * @access public
	 * @param mixed $cidr1
	 * @param mixed $cidr2
	 * @return bool
	 */
	public function is_subnet_inside_subnet ($cidr1, $cidr2) {
		$type = $this->identify_address ($cidr1);
		# check based on type
		return $type=="IPv4" ? $this->is_IPv4_subnet_inside_subnet($cidr1, $cidr2) : $this->is_IPv6_subnet_inside_subnet($cidr1, $cidr2);
	}

	/**
	 * Checks if IPv4 subnet 1 is inside subnet 2
	 *
	 * @access private
	 * @param mixed $cidr1
	 * @param mixed $cidr2
	 * @return bool
	 */
	private function is_IPv4_subnet_inside_subnet ($cidr1, $cidr2) {
		# Initialize PEAR NET object
		$this->initialize_pear_net_IPv4 ();

    	//subnet 1 needs to be parsed to get subnet and broadcast
    	$cidr1 = $this->Net_IPv4->parseAddress($cidr1);

		//both network and broadcast must be inside root subnet!
		if( ($this->Net_IPv4->ipInNetwork($cidr1->network, $cidr2)) && ($this->Net_IPv4->ipInNetwork($cidr1->broadcast, $cidr2)) )  { return true; }
		else 																														{ return false; }
	}

	/**
	 * Checks if IPv6 subnet 1 is inside subnet 2
	 *
	 * @access private
	 * @param mixed $cidr1
	 * @param mixed $cidr2
	 * @return bool
	 */
	private function is_IPv6_subnet_inside_subnet ($cidr1, $cidr2) {
    	//mask 2 must be bigger than mask 1
    	$mask1 = end(explode("/", $cidr1));
    	$mask2 = end(explode("/", $cidr2));

        //check mask
        if ($mask1 < $mask2)                                    { return false; }

		// Initialize PEAR NET object
		$this->initialize_pear_net_IPv6 ();

    	//remove netmask from subnet1
    	$cidr1 = $this->Net_IPv6->removeNetmaskSpec ($cidr1);

	    //check
    	if ($this->Net_IPv6->isInNetmask ( $cidr1, $cidr2 ) ) 	{ return true; }
    	else 													{ return false; }
	}

	/**
	 * Finds invalid subnets - that have masterSubnetId that does not exist
	 *
	 * @access public
	 * @return array|false
	 */
	public function find_invalid_subnets () {
		// find unique ids
		$ids = $this->find_unique_mastersubnetids ();
		if ($ids===false)										{ return false; }
		// validate
		foreach ($ids as $id) {
			if ($this->verify_subnet_id ($id->masterSubnetId)===0) {
    			if(!isset($false)) $false = array();
				$false[] = $this->fetch_subnet_slaves ($id->masterSubnetId);
			}
		}
		// return
		return isset($false) ? $false : false;
	}

	/**
	 * Finds all unique master subnet ids
	 *
	 * @access private
	 * @return array|false
	 */
	private function find_unique_mastersubnetids () {
		try { $res = $this->Database->getObjectsQuery("select distinct(`masterSubnetId`) from `subnets` where `masterSubnetId` != '0' order by `masterSubnetId` asc;"); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# return
		return sizeof($res)>0 ? $res : false;
	}

	/**
	 * Verifies that subnetid exists
	 *
	 * @access private
	 * @param mixed $id
	 * @return int
	 */
	private function verify_subnet_id ($id) {
		try { $res = $this->Database->getObjectQuery("select count(*) as `cnt` from `subnets` where `id` = ?;", array($id)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# return
		return (int) $res->cnt;
	}

	/**
	 * Fetches all subnets that are marked for threshold
	 *
	 * @access public
	 * @param int $limit (default: 10)
	 * @return array|false
	 */
	public function fetch_threshold_subnets ($limit = 10) {
 		try { $res = $this->Database->getObjectsQuery("select * from `subnets` where `threshold` != 0 and `threshold` is not null limit $limit;"); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# return
		return sizeof($res)>0 ? $res : false;
	}

	/**
	 * Finds inactive hosts
	 *
	 * @access public
	 * @param mixed $timelimit
	 * @param int $limit (default: 100)
	 * @return array|false
	 */
	public function find_inactive_hosts ($timelimit = 86400, $limit = 100) {
    	// fetch settings
    	$this->settings ();
    	// search
  		try { $res = $this->Database->getObjectsQuery("select ipaddresses.* from `ipaddresses` join subnets on ipaddresses.subnetId = subnets.id where subnets.pingSubnet = 1 and `lastSeen` between ? and ? limit $limit;", array(date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s"))-$timelimit), date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s"))-(int) str_replace(";","",strstr($this->settings->pingStatus, ";")))) ); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# return
		return sizeof($res)>0 ? $res : false;
	}










	/**
	* @multicast methods
	* -------------------------------
	*/

	/**
	 * Fetches all multicast networks from database
	 *
	 * @access public
	 * @return array|false
	 */
	public function fetch_multicast_subnets () {
    	// set query
    	$query = "select * from `subnets` where `subnet` between '3758096384' and '4026531839' or `subnet` between '338953138925153547590470800371487866878' and '338958331222012082418099330867817086976' order by subnet asc, mask asc;";
    	// fetch
		try { $res = $this->Database->getObjectsQuery($query); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}

		# fetch all subnet ids
		$res2 = $this->fetch_distinct_multicast_folders ();

		# array chack
		if($res===false)    $res = array();
		if($res2===false)   $res2 = array();

		# create
		if (sizeof($res)>0 && sizeof($res2)>0)  { return $res2 + $res; }
		elseif (sizeof($res)>0)                 { return $res; }
		elseif (sizeof($res2)>0)                { return $res2; }
		else                                    { return false; }
	}

	/**
	 * Fetch all ids for multicast folders
	 *
	 * @access public
	 * @return array|false
	 */
	public function fetch_distinct_multicast_folders () {
    	// set query
    	$query = "select distinct(`subnetId`) as `id` from `ipaddresses` where `ip_addr` between '3758096384' and '4026531839' or `ip_addr` between '338953138925153547590470800371487866878' and '338958331222012082418099330867817086976';";
    	// fetch
		try { $res = $this->Database->getObjectsQuery($query); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# return
		if(sizeof($res)>0) {
    		$out = array();
    		foreach ($res as $r) {
        		$out[] = "(`isfolder` = '1' and `id` = $r->id)";
    		}
        	// set query
        	$query = "select * from subnets where ".implode(" or ",$out)." order by description asc;";
        	// fetch
    		try { $res2 = $this->Database->getObjectsQuery($query); }
    		catch (Exception $e) {
    			$this->Result->show("danger", _("Error: ").$e->getMessage());
    			return false;
    		}
    		// return result
    		return $res2;
		}
		else {
    		return false;
		}
	}

	/**
	 * Checks if address is multicast.
	 *
	 * @access public
	 * @param mixed $address
	 * @return bool
	 */
	public function is_multicast ($address) {
    	# IPv4
    	if ($this->identify_address ($address)=="IPv4") {
    	    # transform to decimal
            $address = $this->transform_address ($address, "decimal");
            # check
        	if ($address >= 3758096384 && $address <= 4026531839) {
            	return true;
        	}
    	}
    	else {
    	    # transform to ip
            $address = $this->transform_address ($address, "dotted");
            $this->initialize_pear_net_IPv6 ();
            if ($this->Net_IPv6->getAddressType($address)==31) {
                return true;
            }
    	}
    	// default false
    	return false;
	}

	/**
	 * This function returns multicast MAC address from provided multicast address
	 *
	 * @access public
	 * @param mixed $address
	 * @return string|false
	 */
	public function create_multicast_mac ($address) {
    	// first verify that it is multicast
    	if ($this->is_multicast ($address)===false) {
        	return false;
    	}
    	// ipv4
    	if ($this->identify_address ($address)=="IPv4") {
        	// to array
        	$mac_tmp = explode(".", $address);
        	// check 3rd octet
        	if ($mac_tmp[1]>=128) { $mac_tmp[1]=$mac_tmp[1]-128; }
        	// create mac
        	$mac = strtolower("01:00:5e:".str_pad(dechex($mac_tmp[1]),2,"0",STR_PAD_LEFT).":".str_pad(dechex($mac_tmp[2]),2,"0",STR_PAD_LEFT).":".str_pad(dechex($mac_tmp[3]),2,"0",STR_PAD_LEFT));
    	}
    	else {
        	$this->initialize_pear_net_IPv6 ();
        	if ($this->Net_IPv6->getAddressType($address)==31) {
            	//expand
            	$expanded = $this->Net_IPv6->uncompress($address);
            	// to array
                $mac_tmp = explode(":", $expanded);
            	$mac = strtolower("33:33:".str_pad(dechex($mac_tmp[4]),2,"0",STR_PAD_LEFT).":".str_pad(dechex($mac_tmp[5]),2,"0",STR_PAD_LEFT).":".str_pad(dechex($mac_tmp[6]),2,"0",STR_PAD_LEFT).":".str_pad(dechex($mac_tmp[7]),2,"0",STR_PAD_LEFT));
        	}
        	else {
                return false;
        	}
    	}
    	// return
    	return $mac;
	}

	/**
	 * Finds duplicate mac address
	 *
	 * @access public
	 * @param mixed $address_id
	 * @return array|false
	 */
	public function find_duplicate_multicast_mac ($address_id, $mac) {
    	// query
    	$query = "select i.ip_addr,i.dns_name,i.mac,i.subnetId,i.description as i_description,s.sectionId,s.description,s.isFolder,se.name from `ipaddresses` as `i`, `subnets` as `s`, `sections` as `se` where `i`.`mac` = ? and `i`.`id` != ? and `se`.`id`=`s`.`sectionId` and `i`.`subnetId`=`s`.`id`";
		// fetch
		try { $res = $this->Database->getObjectsQuery($query, array($mac, $address_id)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		// return
		return sizeof($res)>0 ? $res : false;
	}

    /**
     * Checks if address exists in database
     *
     *  parameter $unique_required defines where it cannot overlap:
     *      - section : within section
     *      - vlan    : within l2 domain
     *
     * @access private
     * @param mixed $mac
	 * @param mixed $sectionId
	 * @param mixed $vlanId
     * @param string $unique_required (default: "vlan")
     * @param int $address_id (dafault: 0)
     * @return bool
     */
    private function multicast_address_exists ($mac, $sectionId, $vlanId, $unique_required = "vlan", $address_id = 0) {
        // if vlan fetch l2 domainid
        if ($unique_required=="vlan") {
            $vlan_details = $this->fetch_object("vlans", "vlanId", $vlanId);

            // set query
            $query = "select
                `s`.`vlanId`,`v`.`domainId`,`v`.`number`,`i`.`id`,
                LOWER(REPLACE(REPLACE(`mac`,\".\",\"\"),\":\", \"\")) as `mac`, `subnetId`
                from `ipaddresses` as `i`, `subnets` as `s`, `vlans` as `v`
                where `i`.`subnetId`=`s`.`id` and `s`.`vlanId`=`v`.`vlanId` and LOWER(REPLACE(REPLACE(`mac`,\".\",\"\"),\":\", \"\")) = ? and `i`.`id`!= ?;";
        }
        else {
            // set query
            $query = "select
                `s`.`sectionId`,`v`.`number`,`i`.`id`,
                LOWER(REPLACE(REPLACE(`mac`,\".\",\"\"),\":\", \"\")) as `mac`, `subnetId`
                from `ipaddresses` as `i`, `subnets` as `s`, `vlans` as `v`
                where `i`.`subnetId`=`s`.`id` and LOWER(REPLACE(REPLACE(`mac`,\".\",\"\"),\":\", \"\")) = ? and `i`.`id`!= ?;";
        }

		// fetch
		try { $res = $this->Database->getObjectsQuery($query, array($mac, $address_id)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
        // than check by required unique
        if (sizeof($res)>0) {
            // check
            foreach ($res as $line) {
                // overlap requirement
                if ($unique_required=="vlan" && $vlan_details->domainId==$line->domainId) { return true; }
                elseif ($unique_required=="section" && $sectionId==$line->sectionId)      { return true; }
            }
        }
        // default doesnt exist
        return false;
    }

	/**
	 * Validates provided mac address - checks if it already exist
	 *
     *  parameter $unique_required defines where it cannot overlap:
     *      - section : within section
     *      - vlan    : within l2 domain
     *
	 * @access public
	 * @param mixed $mac
	 * @param mixed $sectionId
	 * @param mixed $vlanId
	 * @param mixed $unique_required
	 * @param int $address_id (defaut: 0)
	 * @return string|true true if ok, else error text to be displayed
	 */
	public function validate_multicast_mac ($mac, $sectionId, $vlanId, $unique_required="vlan", $address_id = 0) {
    	// first put it to common format (1)
    	$mac = $this->reformat_mac_address ($mac);
    	$mac_delimited =  explode(":", $mac);
    	// we permit empty
        if (strlen($mac)==0) {
            return true;
        }
    	// validate mac
    	elseif (preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $mac) != 1) {
        	return "Invalid MAC address";
    	}
    	// multicast check
    	elseif (!($mac_delimited[0]=="33" && $mac_delimited[1]=="33") && !($mac_delimited[0]=="01" && $mac_delimited[1]=="00" && $mac_delimited[2]=="5e")) {
            return "Not multicast MAC address";
    	}
    	// check if it already exists
    	elseif ($this->multicast_address_exists ($this->reformat_mac_address($mac, 4), $sectionId, $vlanId, $unique_required, $address_id)) {
        	return "MAC address already exists";
    	}
    	else {
        	return true;
    	}
	}












	/**
	* @permission methods
	* -------------------------------
	*/

	/**
	 * Checks permission for specified subnet
	 *
	 *	we provide user details and subnetId
	 *
	 * @access public
	 * @param object $user
	 * @param int $subnetId
	 * @return int
	 */
	public function check_permission ($user, $subnetId) {

		# get all user groups
		$groups = json_decode($user->groups, true);

		# if user is admin then return 3, otherwise check
		if($user->role == "Administrator")	{ return 3; }

		# set subnet permissions
		$subnet  = $this->fetch_subnet ("id", $subnetId);
		if($subnet===false)	return 0;
		//null?
		if(is_null($subnet->permissions) || $subnet->permissions=="null")	return 0;
		$subnetP = json_decode(@$subnet->permissions);

		# set section permissions
		$Section = new Sections ($this->Database);
		$section = $Section->fetch_section ("id", $subnet->sectionId);
		$sectionP = json_decode($section->permissions);

		# default permission
		$out = 0;

		# for each group check permissions, save highest to $out
		if(sizeof($sectionP) > 0) {
			foreach($sectionP as $sk=>$sp) {
				# check each group if user is in it and if so check for permissions for that group
				if(is_array($groups)) {
					foreach($groups as $uk=>$up) {
						if($uk == $sk) {
							if($sp > $out) { $out = $sp; }
						}
					}
				}
			}
		}
		else {
			return 0;
		}

		# if section permission == 0 then return 0
		if($out == 0) {
			return 0;
		}
		else {
			$out = 0;
			# ok, user has section access, check also for any higher access from subnet
			if(sizeof($subnetP) > 0) {
				foreach($subnetP as $sk=>$sp) {
					# check each group if user is in it and if so check for permissions for that group
					foreach($groups as $uk=>$up) {
						if($uk == $sk) {
							if($sp > $out) { $out = $sp; }
						}
					}
				}
			}
		}

		# return result
		return $out;
	}

	/**
	 * Parse subnet permissions to user readable format
	 *
	 * @access public
	 * @param mixed $permissions
	 * @return string
	 */
	public function parse_permissions ($permissions) {
		switch($permissions) {
			case 0: 	$r = _("No access");			break;
			case 1: 	$r = _("Read");					break;
			case 2: 	$r = _("Read / Write");			break;
			case 3: 	$r = _("Read / Write / Admin");	break;
			default:	$r = _("error");
		}
		return $r;
	}












	/**
	* @menu print methods
	* -------------------------------
	*/

	/**
	 * Creates HTML menu for left subnets
	 *
	 *	based on http://pastebin.com/GAFvSew4
	 *
	 * @access public
	 * @param mixed $user
	 * @param mixed $section_subnets	//array of all subnets in section
	 * @param int $rootId (default: 0)
	 * @return string
	 */
	public function print_subnets_menu( $user, $section_subnets, $rootId = 0 ) {
		# open / close via cookie
		if (isset($_COOKIE['sstr'])) { $cookie = array_filter(explode("|", $_COOKIE['sstr'])); }
		else						 { $cookie= array(); }

		# initialize html array
		$html = array();
		# create children array
		$children_subnets = array();
		foreach ( $section_subnets as $item )
			$children_subnets[$item->masterSubnetId][] = (array) $item;

		# loop will be false if the root has no children (i.e., an empty menu!)
		$loop = !empty( $children_subnets[$rootId] );

		# initializing $parent as the root
		$parent = $rootId;
		$parent_stack = array();

		# must be numeric
		if(isset($_GET['section']))		if(!is_numeric($_GET['section']))	{ $this->Result->show("danger",_("Invalid ID"), true); }
		if(isset($_GET['subnetId']))	if(!is_numeric($_GET['subnetId']))	{ $this->Result->show("danger",_("Invalid ID"), true); }

		# display selected subnet as opened
		$allParents = isset($_GET['subnetId']) ? $this->fetch_parents_recursive($_GET['subnetId']) : array();

		# Menu start
		$html[] = '<ul id="subnets">';

		# loop through subnets
		while ( $loop && ( ( $option = each( $children_subnets[$parent] ) ) || ( $parent > $rootId ) ) )
		{
			# save id for structure on reloading
			$curr_id = $option['value']['id'];

			# count levels
			$count = count( $parent_stack ) + 1;

			# set opened or closed tag for displaying proper folders
			if(in_array($option['value']['id'], $allParents) ||
				in_array($option['value']['id'], $cookie))			{ $open = "open";	$openf = "-open"; }
			else													{ $open = "close";	$openf = ""; }

			# show also child's by default
			if($option['value']['id']==@$_GET['subnetId']) {
				if($this->has_slaves(@$_GET['subnetId']))			{ $open = "open";	$openf = "-open"; }
				else												{ $open = "close";	$openf = ""; }
			}

			# override if cookie is set
			if(isset($_COOKIE['expandfolders'])) {
				if($_COOKIE['expandfolders'] == "1")				{ $open='open';		$openf = "-open"; }
			}

			# for active class
			if($_GET['page']=="subnets" && ($option['value']['id'] == @$_GET['subnetId']))			{ $active = "active";	$leafClass=""; }
			else 																					{ $active = ""; 		$leafClass="icon-gray" ;}

			# override folder
			if($option['value']['isFolder'] == 1 && ($option['value']['id'] == @$_GET['subnetId']))	{ $open = "open"; $openf = "-open"; $active = "active"; }

			# set permission
			$permission = $option['value']['id']!="" ? $this->check_permission ($user, $option['value']['id']) : 0;

			# set view
			$current_description = string;
			if ($this->settings->subnetView == 0) {
				$current_description = $this->transform_to_dotted($option['value']['subnet']).'/'.$option['value']['mask'];
			}
			elseif ($this->settings->subnetView == 1) {
				$description_print = strlen($option['value']['description'])>0 ? $option['value']['description'] : $this->transform_to_dotted($option['value']['subnet']).'/'.$option['value']['mask'];		// fix for empty
				$current_description = $description_print;
			}
			elseif ($this->settings->subnetView == 2) {
			    if (strlen($option['value']['description'])>0) {
                    $temp_description = "(".$option['value']['description'].")";

                    if (strlen($temp_description)>34) {
                        $temp_description = substr($temp_description, 0, 32) . "...)";
                    }
                }
                else {
                    $temp_description = "";
                }
                $description_print = $temp_description;
                $current_description = $this->transform_to_dotted($option['value']['subnet']).'/'.$option['value']['mask'].' '.$description_print;
			}

			if ( $option === false )
			{
				$parent = array_pop( $parent_stack );

				# HTML for menu item containing childrens (close)
				$html[] = '</ul>';
				$html[] = '</li>';
			}
			# Has children
			elseif ( !empty( $children_subnets[$option['value']['id']] ) )
			{
				# if user has access permission
				if($permission != 0) {
					# folder
					if($option['value']['isFolder'] == 1) {
						$html[] = '<li class="folderF folder-'.$open.' '.$active.'"><i data-str_id="'.$curr_id.'" class="fa fa-gray fa-folder fa-folder'.$openf.'" rel="tooltip" data-placement="right" data-html="true" title="'._('Folder contains more subnets').'<br>'._('Click on folder to open/close').'"></i>';
						$html[] = '<a href="'.create_link("folder",$option['value']['sectionId'],$option['value']['id']).'">'.$option['value']['description'].'</a>';
					}
					# print name
					elseif($option['value']['showName'] == 1) {
						$html[] = '<li class="folder folder-'.$open.' '.$active.'"><i data-str_id="'.$curr_id.'" class="fa fa-gray fa-folder-'.$open.'-o" rel="tooltip" data-placement="right" data-html="true" title="'._('Subnet contains more subnets').'<br>'._('Click on folder to open/close').'"></i>';
						$html[] = '<a href="'.create_link("subnets",$option['value']['sectionId'],$option['value']['id']).'" rel="tooltip" data-placement="right" title="'.$this->transform_to_dotted($option['value']['subnet']).'/'.$option['value']['mask'].'">'.$option['value']['description'].'</a>';
					}
					# print subnet
					else {
						$html[] = '<li class="folder folder-'.$open.' '.$active.'"><i data-str_id="'.$curr_id.'" class="fa fa-gray fa-folder-'.$open.'-o" rel="tooltip" data-placement="right" data-html="true" title="'._('Subnet contains more subnets').'<br>'._('Click on folder to open/close').'"></i>';
						$html[] = '<a href="'.create_link("subnets",$option['value']['sectionId'],$option['value']['id']).'" rel="tooltip" data-placement="right" title="'.$option['value']['description'].'">'.$current_description.'</a>';
					}

					# print submenu
					if($open == "open") { $html[] = '<ul class="submenu submenu-'.$open.'">'; }							# show if opened
					else 				{ $html[] = '<ul class="submenu submenu-'.$open.'" style="display:none">'; }	# hide - prevent flickering

					array_push( $parent_stack, $option['value']['masterSubnetId'] );
					$parent = $option['value']['id'];
				}
			}
			# Leaf items (last)
			else
				if($permission != 0) {
					# folder - opened
					if($option['value']['isFolder'] == 1) {
						$html[] = '<li class="leaf '.$active.'"><i data-str_id="'.$curr_id.'" class="fa fa-gray fa-sfolder fa-folder'.$openf.'"></i>';
						$html[] = '<a href="'.create_link("folder",$option['value']['sectionId'],$option['value']['id']).'">'.$option['value']['description'].'</a></li>';
					}
					# print name
					elseif($option['value']['showName'] == 1) {
						$html[] = '<li class="leaf '.$active.'"><i data-str_id="'.$curr_id.'" class="'.$leafClass.' fa fa-gray fa-angle-right"></i>';
						$html[] = '<a href="'.create_link("subnets",$option['value']['sectionId'],$option['value']['id']).'" rel="tooltip" data-placement="right" title="'.$this->transform_to_dotted($option['value']['subnet']).'/'.$option['value']['mask'].'">'.$option['value']['description'].'</a></li>';
					}
					# print subnet
					else {
						$html[] = '<li class="leaf '.$active.'"><i data-str_id="'.$curr_id.'" class="'.$leafClass.' fa fa-gray fa-angle-right"></i>';
						$html[] = '<a href="'.create_link("subnets",$option['value']['sectionId'],$option['value']['id']).'" rel="tooltip" data-placement="right" title="'.$option['value']['description'].'">'.$current_description.'</a></li>';
					}
				}
		}

		# Close menu
		$html[] = '</ul>';
		# return menu list
		return implode( "\n", $html );
	}

	/**
	 * Creates HTML menu for left VLANs in section
	 *
	 * @access public
	 * @param mixed $user
	 * @param mixed $vlans
	 * @param mixed $section_subnets
	 * @param mixed $sectionId
	 * @return string
	 */
	public function print_vlan_menu( $user, $vlans, $section_subnets, $sectionId ) {
		# initialize html array
		$html = array();
		# must be numeric
		if(isset($_GET['section']))		if(!is_numeric($_GET['section']))	{ $this->Result->show("danger",_("Invalid ID"), true); }
		if(isset($_GET['subnetId']))	if(!is_numeric($_GET['subnetId']))	{ $this->Result->show("danger",_("Invalid ID"), true); }

		# Menu start
		$html[] = '<ul id="subnets">';
		# loop through vlans
		foreach ( $vlans as $item ) {
			$item = (array) $item;
			# set open / closed -> vlan directly
			if(@$_GET['subnetId'] == $item['vlanId'] && @$_GET['page']=="vlan") {
				$open = "open";
				$active = "active";
				$leafClass="fa-gray";
			}
			elseif($this->is_subnet_in_vlan(@$_GET['subnetId'], $item['vlanId'])) {
				$open = "open";
				$active = "";
				$leafClass="fa-gray";
			}
			else {
				$open = "close";
				$active = "";
				$leafClass="fa-gray";
			}

			# domain
			$item['l2domain'] = "";
			if($item['domainId']!=1) {
    			$domain = $this->fetch_object("vlanDomains", "id", $item['domainId']);
    			if ($domain!==false) {
        			$item['l2domain'] = " <span class='badge badge1 badge5' rel='tooltip' title='VLAN is in domain $domain->name'>$domain->name</span>";
    			}
			}

			# new item
			$html[] = '<li class="folder folder-'.$open.' '.$active.'"><i class="fa fa-gray fa-folder-'.$open.'-o" rel="tooltip" data-placement="right" data-html="true" title="'._('VLAN contains subnets').'.<br>'._('Click on folder to open/close').'"></i>';
			$html[] = '<a href="'.create_link("vlan",$sectionId,$item['vlanId']).'" rel="tooltip" data-placement="right" title="'.$item['description'].'">'.$item['number'].' ('.$item['name'].') '.$item['l2domain'].'</a>';

            # set all subnets in this vlan
            $subnets = array();
            foreach ($section_subnets as $s) {
                if ($s->vlanId==$item['vlanId']) {
                    $subnets[] = $s;
                }
            }

			# if some exist print next ul
			if($subnets) {
				# print subnet
				if($open == "open") { $html[] = '<ul class="submenu submenu-'.$open.'">'; }							# show if opened
				else 				{ $html[] = '<ul class="submenu submenu-'.$open.'" style="display:none">'; }	# hide - prevent flickering

				# loop through subnets
				foreach($subnets as $subnet) {
					$subnet = (array) $subnet;
					# set permission
					$permission = $subnet['id']!="" ? $this->check_permission ($user, $subnet['id']) : 0;

					if($permission > 0) {
						# for active class
						if(isset($_GET['subnetId']) && ($subnet['id'] == $_GET['subnetId']))	{ $active = "active";	$leafClass=""; }
						else 																	{ $active = ""; 		$leafClass="icon-gray" ;}

						# check if showName is set
						if($subnet['showName'] == 1) {
							$html[] = '<li class="leaf '.$active.'"><i class="'.$leafClass.' fa fa-gray fa-angle-right"></i>';
							$html[] = '<a href="'.create_link("subnets",$subnet['sectionId'],$subnet['id']).'" rel="tooltip" data-placement="right" title="'.$this->transform_to_dotted($subnet['subnet']).'/'.$subnet['mask'].'">'.$subnet['description'].'</a></li>';
						}
						else {
							$html[] = '<li class="leaf '.$active.'""><i class="'.$leafClass.' fa fa-gray fa-angle-right"></i>';
							$html[] = '<a href="'.create_link("subnets",$subnet['sectionId'],$subnet['id']).'" rel="tooltip" data-placement="right" title="'.$subnet['description'].'">'.$this->transform_to_dotted($subnet['subnet']).'/'.$subnet['mask'].'</a></li>';
						}
					}
				}
				# close ul
				$html[] = '</ul>';
				$html[] = '</li>';
			}
		}
		# Close menu
		$html[] = '</ul>';
		# return html
		return implode( "\n", $html );
	}

	/**
	 * Creates HTML menu for left VRFs in section
	 *
	 * @access public
	 * @param mixed $user
	 * @param mixed $vrfs
	 * @param mixed $section_subnets
	 * @param mixed $sectionId
	 * @return string
	 */
	public function print_vrf_menu( $user, $vrfs, $section_subnets, $sectionId ) {
	 	# initialize html array
		$html = array();

		# Menu start
		$html[] = '<ul id="subnets">';

		# must be numeric
		if(isset($_GET['section']))		if(!is_numeric($_GET['section']))	{ $this->Result->show("danger",_("Invalid ID"), true); }
		if(isset($_GET['subnetId']))	if(!is_numeric($_GET['subnetId']))	{ $this->Result->show("danger",_("Invalid ID"), true); }

		# loop through vlans
		foreach ( $vrfs as $item ) {
			$item = (array) $item;

			# set open / closed -> vlan directly
			if(@$_GET['subnetId'] == $item['vrfId'] && @$_GET['page']=="vrf") {
				$open = "open";
				$active = "active";
				$leafClass="fa-gray";
			}
			elseif($this->is_subnet_in_vrf(@$_GET['subnetId'], $item['vrfId'])) {
				$open = "open";
				$active = "";
				$leafClass="fa-gray";
			}
			else {
				$open = "close";
				$active = "";
				$leafClass="fa-gray";
			}

			# new item
			$html[] = '<li class="folder folder-'.$open.' '.$active.'"><i class="fa fa-gray fa-folder-'.$open.'-o" rel="tooltip" data-placement="right" data-html="true" title="'._('VRF contains subnets').'.<br>'._('Click on folder to open/close').'"></i>';
			$html[] = '<a href="'.create_link("vrf",$sectionId,$item['vrfId']).'" rel="tooltip" data-placement="right" title="'.$item['description'].'">'.$item['name'].'</a>';

            # set all subnets in this vrf
            $subnets = array();
            foreach ($section_subnets as $s) {
                if ($s->vrfId==$item['vrfId']) {
                    $subnets[] = $s;
                }
            }

			# if some exist print next ul
			if($subnets) {
				# print subnet
				if($open == "open") { $html[] = '<ul class="submenu submenu-'.$open.'">'; }							# show if opened
				else 				{ $html[] = '<ul class="submenu submenu-'.$open.'" style="display:none">'; }	# hide - prevent flickering

				# loop through subnets
				foreach($subnets as $subnet) {
					$subnet = (array) $subnet;
					# set permission
					$permission = $subnet['id']!="" ? $this->check_permission ($user, $subnet['id']) : 0;

					if($permission > 0) {
						# for active class
						if(isset($_GET['subnetId']) && ($subnet['id'] == $_GET['subnetId']))	{ $active = "active";	$leafClass=""; }
						else 																	{ $active = ""; 		$leafClass="icon-gray" ;}
						# check if showName is set
						if($subnet['showName'] == 1) {
							$html[] = '<li class="leaf '.$active.'"><i class="'.$leafClass.' fa fa-gray fa-angle-right"></i>';
							$html[] = '<a href="'.create_link("subnets",$subnet['sectionId'],$subnet['id']).'" rel="tooltip" data-placement="right" title="'.$this->transform_to_dotted($subnet['subnet']).'/'.$subnet['mask'].'">'.$subnet['description'].'</a></li>';
						}
						else {
							$html[] = '<li class="leaf '.$active.'""><i class="'.$leafClass.' fa fa-gray fa-angle-right"></i>';
							$html[] = '<a href="'.create_link("subnets",$subnet['sectionId'],$subnet['id']).'" rel="tooltip" data-placement="right" title="'.$subnet['description'].'">'.$this->transform_to_dotted($subnet['subnet']).'/'.$subnet['mask'].'</a></li>';
						}
					}
				}
				# close ul
				$html[] = '</ul>';
				$html[] = '</li>';
			}
		}

		# Close menu
		$html[] = '</ul>';
		# return html
		return implode( "\n", $html );
	}

	/**
	 * Print all subnets in section
	 *
	 * @access public
	 * @param array $user
	 * @param array $subnets
	 * @param array $custom_fields
	 * @param bool $print
	 * @return string
	 */
	public function print_subnets_tools( $user, $subnets, $custom_fields, $print = true ) {

		# tools object
		$Tools = new Tools ($this->Database);
		# set hidden fields
		$this->get_settings ();
		$hidden_fields = json_decode($this->settings->hiddenCustomFields, true);
		$hidden_fields = is_array($hidden_fields['subnets']) ? $hidden_fields['subnets'] : array();

		# set html array
		$html = array();
		# root is 0
		$rootId = 0;

		# remove all not permitted!
		if(sizeof($subnets)>0) {
		foreach($subnets as $k=>$s) {
			$permission = $this->check_permission ($user, $s->id);
			if($permission == 0) { unset($subnets[$k]); }
		}
		}

		# create loop array
		if(sizeof($subnets) > 0) {
        $children_subnets = array();
		foreach ( $subnets as $item ) {
			$item = (array) $item;
			$children_subnets[$item['masterSubnetId']][] = $item;
		}
		}
		else {
			return false;
		}

		# loop will be false if the root has no children (i.e., an empty menu!)
		$loop = !empty( $children_subnets[$rootId] );

		# initializing $parent as the root
		$parent = $rootId;
		$parent_stack = array();

		# old count
		$old_count = 0;

		# fetch all vlans and domains and reindex
		$vlans_and_domains = $Tools->fetch_all_domains_and_vlans ();
		$all_vlans = array();
		if ($vlans_and_domains) {
    		foreach ($vlans_and_domains as $vd) {
        		$all_vlans[$vd->id] = $vd;
    		}
		}

		# return table content (tr and td's)
		while ( $loop && ( ( $option = each( $children_subnets[$parent] ) ) || ( $parent > $rootId ) ) )
		{
			# repeat
			$repeat  = str_repeat( " - ", ( count($parent_stack)) );

			if(count($parent_stack) == 0) {
				$margin = "0px";
				$padding = "0px";
			}
			else {
				# padding
				$padding = "10px";

				# margin
				$margin  = (count($parent_stack) * 10) -10;
				$margin  = $margin *1.5;
				$margin  = $margin."px";
			}

			# count levels
			$count = count( $parent_stack ) + 1;

			# vlan
			if (!array_key_exists ($option['value']['vlanId'], $all_vlans)) { $vlan['number'] = ""; }
			else {
    			$vlan['number'] = $all_vlans[$option['value']['vlanId']]->domainId==1 ? $all_vlans[$option['value']['vlanId']]->number : $all_vlans[$option['value']['vlanId']]->number." <span class='badge badge1 badge5' rel='tooltip' title='VLAN is in domain ".$all_vlans[$option['value']['vlanId']]->domainName."'>".$all_vlans[$option['value']['vlanId']]->domainName."</span>";
            }

			# description
			$description = strlen($option['value']['description'])==0 ? "/" : $option['value']['description'];


			# print table line
			if(strlen($option['value']['subnet']) > 0 || $option['value']['isFolder']==1) {

    			# count change?
    			if ($count != $old_count) { $html[] = "</tbody><tbody>"; }

    			$last_item = $count < $old_count ? "last_item" : "";


				$html[] = "<tr class='level$count'>";

				//which level?
				if($count==1) {
					# is folder?
					if($option['value']['isFolder']==1) {
					$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-sfolder fa-pad-right-3 fa-folder-open'></i> <a href='".create_link("folder",$option['value']['sectionId'],$option['value']['id'])."'> $description</a></td>";
					$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-sfolder fa-pad-right-3 fa-folder-open'></i>  $description</td>";

					}
					else {
                        # add full information
                        $fullinfo = $option['value']['isFull']==1 ? " <span class='badge badge1 badge2 badge4'>"._("Full")."</span>" : "";

						# last?
						if(!empty( $children_subnets[$option['value']['id']])) {
							$html[] = "	<td class='level$count'><span class='structure-last' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open-o'></i><a href='".create_link("subnets",$option['value']['sectionId'],$option['value']['id'])."'>  ".$this->transform_to_dotted($option['value']['subnet']) ."/".$option['value']['mask']." $fullinfo</a></td>";
							$html[] = "	<td class='level$count'><span class='structure-last' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open-o'></i> $description</td>";
						} else {
							$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-angle-right'></i><a href='".create_link("subnets",$option['value']['sectionId'],$option['value']['id'])."'>  ".$this->transform_to_dotted($option['value']['subnet']) ."/".$option['value']['mask']." $fullinfo</a></td>";
							$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-angle-right'></i> $description</td>";
						}
				    }
				}
				else {
					# is folder?
					if($option['value']['isFolder']==1) {
						# last?
							$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open'></i> <a href='".create_link("folder",$option['value']['sectionId'],$option['value']['id'])."'> $description</a></td>";
							$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open'></i> $description</td>";
					}
					else {
                        # add full information
                        $fullinfo = $option['value']['isFull']==1 ? " <span class='badge badge1 badge2 badge4'>"._("Full")."</span>" : "";

						# last?
						if(!empty( $children_subnets[$option['value']['id']])) {
							$html[] = "	<td class='level$count'><span class='structure-last' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open-o'></i> <a href='".create_link("subnets",$option['value']['sectionId'],$option['value']['id'])."'>  ".$this->transform_to_dotted($option['value']['subnet']) ."/".$option['value']['mask']." $fullinfo</a></td>";
							$html[] = "	<td class='level$count'><span class='structure-last' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open-o'></i> $description</td>";
						}
						else {
							$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-angle-right'></i> <a href='".create_link("subnets",$option['value']['sectionId'],$option['value']['id'])."'>  ".$this->transform_to_dotted($option['value']['subnet']) ."/".$option['value']['mask']." $fullinfo</a></td>";
							$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-angle-right'></i> $description</td>";
						}
					}
				}

				//vlan
				$html[] = "	<td>$vlan[number]</td>";

				//vrf
				if($this->settings->enableVRF == 1) {
					# fetch vrf
					$vrf = $this->fetch_object("vrf", "vrfId", $option['value']['vrfId']);
					$html[] = !$vrf ? "<td></td>" : "<td>$vrf->name</td>";
				}

				//masterSubnet
				$masterSubnet = ( $option['value']['masterSubnetId']==0 || empty($option['value']['masterSubnetId']) ) ? true : false;

				if($masterSubnet) { $html[] ='	<td>/</td>' . "\n"; }
				else {
					$master = (array) $this->fetch_subnet (null, $option['value']['masterSubnetId']);
					if($master['isFolder']==1)
						$html[] = "	<td><i class='fa fa-sfolde fa-gray fa-folder-open'></i> <a href='".create_link("folder",$option['value']['sectionId'],$master['id'])."'>$master[description]</a></td>" . "\n";
					else {
						$html[] = "	<td><a href='".create_link("subnets",$option['value']['sectionId'],$master['id'])."'>".$this->transform_to_dotted($master['subnet']) .'/'. $master['mask'] .'</a></td>' . "\n";
					}
				}

				//device
				$device = ( $option['value']['device']==0 || empty($option['value']['device']) ) ? false : true;

				if($device===false) { $html[] ='	<td>/</td>' . "\n"; }
				else {
					$device = $this->fetch_object ("devices", "id", $option['value']['device']);
					if ($device!==false) {
						$html[] = "	<td><a href='".create_link("tools","devices",$option['value']['device'])."'>".$device->hostname .'</a></td>' . "\n";
					}
					else {
						$html[] ='	<td>/</td>' . "\n";
					}
				}

				//requests
				$requests = $option['value']['allowRequests']==1 ? "<i class='fa fa-gray fa-check'></i>" : "/";
				$html[] = "	<td class='hidden-xs hidden-sm'>$requests</td>";

				//custom
				if(sizeof($custom_fields) > 0) {
			   		foreach($custom_fields as $field) {
				   		# hidden?
				   		if(!in_array($field['name'], $hidden_fields)) {

				   			$html[] =  "<td class='hidden-xs hidden-sm hidden-md'>";

				   			//booleans
							if($field['type']=="tinyint(1)")	{
								if($option['value'][$field['name']] == "0")			{ $html[] = _("No"); }
								elseif($option['value'][$field['name']] == "1")		{ $html[] = _("Yes"); }
							}
							//text
							elseif($field['type']=="text") {
								if(strlen($option['value'][$field['name']])>0)		{ $html[] = "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", $option['value'][$field['name']])."'>"; }
								else												{ $html[] = ""; }
							}
							else {
								$html[] = $option['value'][$field['name']];

							}

				   			$html[] =  "</td>";
			   			}
			    	}
			    }

				# set permission
				$permission = $this->check_permission ($user, $option['value']['id']);

				$html[] = "	<td class='actions' style='padding:0px;'>";
				$html[] = "	<div class='btn-group'>";

				if($permission>1) {
					if($option['value']['isFolder']==1) {
						$html[] = "		<button class='btn btn-xs btn-default add_folder'     data-action='edit'   data-subnetid='".$option['value']['id']."'  data-sectionid='".$option['value']['sectionId']."'><i class='fa fa-gray fa-pencil'></i></button>";
						$html[] = "		<button class='btn btn-xs btn-default showSubnetPerm' data-action='show'   data-subnetid='".$option['value']['id']."'  data-sectionid='".$option['value']['sectionId']."'><i class='fa fa-gray fa-tasks'></i></button>";
						$html[] = "		<button class='btn btn-xs btn-default add_folder'     data-action='delete' data-subnetid='".$option['value']['id']."'  data-sectionid='".$option['value']['sectionId']."'><i class='fa fa-gray fa-times'></i></button>";
					} else {
						$html[] = "		<button class='btn btn-xs btn-default editSubnet'     data-action='edit'   data-subnetid='".$option['value']['id']."'  data-sectionid='".$option['value']['sectionId']."'><i class='fa fa-gray fa-pencil'></i></button>";
						$html[] = "		<button class='btn btn-xs btn-default showSubnetPerm' data-action='show'   data-subnetid='".$option['value']['id']."'  data-sectionid='".$option['value']['sectionId']."'><i class='fa fa-gray fa-tasks'></i></button>";
						$html[] = "		<button class='btn btn-xs btn-default editSubnet'     data-action='delete' data-subnetid='".$option['value']['id']."'  data-sectionid='".$option['value']['sectionId']."'><i class='fa fa-gray fa-times'></i></button>";
					}
				}
				else {
						$html[] = "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-pencil'></i></button>";
						$html[] = "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-tasks'></i></button>";
						$html[] = "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-times'></i></button>";
				}
				$html[] = "	</div>";
				$html[] = "	</td>";

				$html[] = "</tr>";

                # save old level count
                $old_count = $count;
			}

			if ( $option === false ) { $parent = array_pop( $parent_stack ); }
			# Has slave subnets
			elseif ( !empty( $children_subnets[$option['value']['id']] ) ) {
				array_push( $parent_stack, $option['value']['masterSubnetId'] );
				$parent = $option['value']['id'];
			}
			# Last items
			else { }
		}
		# print or return
		if($print)
		print implode( "\n", $html );
		else
		return $html;
	}

	/**
	 * Prints dropdown menu for master subnet selection in subnet editing
	 *
	 * @access public
	 * @param mixed $sectionId
	 * @param string $current_master (default: "0")
	 * @param boolean $isFolder (default: false)
	 * @return void
	 */
	public function print_mastersubnet_dropdown_menu($sectionId, $current_master = 0, $isFolder = false) {
		# must be integer
		if(!is_numeric($sectionId))		{ $this->Result->show("danger", _("Invalid ID"), true); }

		# fetch all subnets in section
		$section_subnets = $this->fetch_section_subnets ($sectionId);
		# folder or subnet?
		foreach($section_subnets as $s) {
			// folders array
			if($s->isFolder==1)	{ $children_folders[$s->masterSubnetId][] = (array) $s; }
			// all subnets, including folders
			$children_subnets[$s->masterSubnetId][] = (array) $s;
		}

		//initialize html
		$html = array();

		$rootId = 0;			//root is 0

		# loop will be false if the root has no children (i.e., an empty menu!)
		$loopF = !empty( $children_folders[$rootId] );
		$loop  = !empty( $children_subnets[$rootId] );

		# initializing $parent as the root
		$parent = $rootId;

		$parent_stack_folder = array();
		$parent_stack_subnet = array();

		# structure
		$html[] = "<select name='masterSubnetId' class='form-control input-sm input-w-auto input-max-200'>";

		# folders
		if(sizeof(@$children_folders)>0 || $isFolder) {
			$html[] = "<optgroup label='"._("Folders")."'>";

    		# root subnet
    		if(!isset($current_master) || $current_master==0) {
    			$html[] = "<option value='0' selected='selected'>"._("Root folder")."</option>";
    		} else {
    			$html[] = "<option value='0'>"._("Root folder")."</option>";
    		}

			# return table content (tr and td's) - folders
			while ( $loopF && ( ( $option = each( $children_folders[$parent] ) ) || ( $parent > $rootId ) ) )
			{
				# repeat
				$repeat  = str_repeat( " - ", ( count($parent_stack_folder)) );

				# count levels
				$count = count($parent_stack_folder)+1;

				# selected
				if(strlen($option['value']['description'])>0) {
					if($option['value']['id'] == $current_master) 	{ $html[] = "<option value='".$option['value']['id']."' selected='selected'>$repeat ".$option['value']['description']."</option>"; }
					else 											{ $html[] = "<option value='".$option['value']['id']."'					   >$repeat ".$option['value']['description']."</option>"; }
				}
				if ( $option === false ) { $parent = array_pop( $parent_stack_folder ); }
				# Has slave subnets
				elseif ( !empty( $children_folders[$option['value']['id']] ) ) {
					array_push( $parent_stack_folder, $option['value']['masterSubnetId'] );
					$parent = $option['value']['id'];
				}
				# Last items
				else { }
			}
			$html[] = "</optgroup>";
		}

		# if not folder
        if ($isFolder===false) {

		# subnets
		$html[] = "<optgroup label='"._("Subnets")."'>";

		# root subnet
		if(!isset($current_master) || $current_master==0) {
			$html[] = "<option value='0' selected='selected'>"._("Root subnet")."</option>";
		} else {
			$html[] = "<option value='0'>"._("Root subnet")."</option>";
		}

		# return table content (tr and td's) - subnets
		if(sizeof(@$children_subnets)>0) {
		while ( $loop && ( ( $option = each( $children_subnets[$parent] ) ) || ( $parent > $rootId ) ) )
		{
			# repeat
			$repeat  = str_repeat( " - ", ( count($parent_stack_subnet)) );

			# count levels
			$count = count($parent_stack_subnet)+1;

			# print table line if it exists and it is not folder
			if(strlen($option['value']['subnet']) > 0 && $option['value']['isFolder']!=1) {
				# selected
				if($option['value']['id'] == $current_master) 	{
					if($option['value']['description']) {
                        if(strlen($option['value']['description'])>34) {
                            $option['value']['description'] = substr($option['value']['description'],0,31) . "...";
    				    }
                        $html[] = "<option value='".$option['value']['id']."' selected='selected'>$repeat ".$this->transform_to_dotted($option['value']['subnet'])."/".$option['value']['mask']." (".$option['value']['description'].")</option>";
				    }
                    else {
                        $html[] = "<option value='".$option['value']['id']."' selected='selected'>$repeat ".$this->transform_to_dotted($option['value']['subnet'])."/".$option['value']['mask']."</option>";
                    }
                }
				else {
					if($option['value']['description']) {
                        if(strlen($option['value']['description'])>34) {
                            $option['value']['description'] = substr($option['value']['description'],0,31) . "...";
                        }
                        $html[] = "<option value='".$option['value']['id']."'>$repeat ".$this->transform_to_dotted($option['value']['subnet'])."/".$option['value']['mask']." (".$option['value']['description'].")</option>";
                    }
					else {
                        $html[] = "<option value='".$option['value']['id']."'>$repeat ".$this->transform_to_dotted($option['value']['subnet'])."/".$option['value']['mask']."</option>";
                    }
                }
			}
			// folder - disabled
			elseif ($option['value']['isFolder']==1) {
					 if(strlen($option['value']['description'])>34) { $option['value']['description'] = substr($option['value']['description'],0,31) . "..."; }
                     $html[] = "<option value=''	 disabled>$repeat ".$option['value']['description']."</option>";
				//if($option['value']['id'] == $current_master) { $html[] = "<option value='' selected='selected' disabled>$repeat ".$option['value']['description']."</option>"; }
				//else 											{ $html[] = "<option value=''					    disabled>$repeat ".$option['value']['description']."</option>"; }
			}

			if ( $option === false ) { $parent = array_pop( $parent_stack_subnet ); }
			# Has slave subnets
			elseif ( !empty( $children_subnets[$option['value']['id']] ) ) {
				array_push( $parent_stack_subnet, $option['value']['masterSubnetId'] );
				$parent = $option['value']['id'];
			}
			# Last items
			else { }
		}
		}
		$html[] = "</optgroup>";
		}
		$html[] = "</select>";
		# join and print
		print implode( "\n", $html );
	}

	/**
	 * Print only master.
	 *
	 * @access public
	 * @param mixed $subnetMasterId
	 * @return void
	 */
	public  function subnet_dropdown_master_only($subnetMasterId ) {
		$subnet = $this->fetch_subnet (null, $subnetMasterId);

		$html = array();

		$html[] = "<select name='masterSubnetId' class='form-control input-sm input-w-auto input-max-200'>";

		// false subnet
		if($subnet===false) {
			$html[] = "</select>";
		}
		else {
			// foder
			if ($subnet->isFolder==1) {
				$html[] = "<option value='".$subnetMasterId."' selected='selected'>".$subnet->description."</option> </select>";
			}
			else {
				$html[] = "<option value='".$subnetMasterId."' selected='selected'>".$this->transform_to_dotted($subnet->subnet)."/".$subnet->mask."</option> </select>";
			}
		}
		$html[] = "</select>";

		// result
		print implode( "\n", $html );
	}

	/**
	 * 	Print dropdown menu for Available subnets under a given subnet!
	 *
	 * @access public
	 * @param mixed $sectionId
	 * @param mixed $subnetMasterId
	 * @return void
	 */
	public function subnet_dropdown_print_available($sectionId, $subnetMasterId) {

		/* Remove STRICT Error reporting for ParseAddress fuction */
		error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
		$mask_drill_down = 8;

		# must be integer
		if(isset($_GET['subnetId'])) { if(!is_numeric($_GET['subnetId']))    { $this->Result->show("danger", _("Invalid ID"), true); } }

		// result array
		$html = array();
		$history_subnet = array ();

		// Get Current and Previous subnets
		$subnets 			= $this->fetch_subnet_slaves($subnetMasterId);
		$taken_subnet 		= $this->fetch_subnet (null, $subnetMasterId);
		$parent_subnet 		= $taken_subnet->subnet;
		$parent_subnetmask 	= $taken_subnet->mask;

		// folder
		if ($taken_subnet->isFolder=="1") 	return "";

		// detect type
		$type = $this->identify_address( $parent_subnet );

		// initialize pear objet
		if ($type == 'IPv4') 	{ $this->initialize_pear_net_IPv4 (); }
		else 					{ $this->initialize_pear_net_IPv6 (); }

		// if it has slaves
		if($subnets) {
			foreach ($subnets as $row ) {
				$history_subnet[] =  $this->transform_to_dotted($row->subnet) .'/'. $row->mask;
			}
		}

		# prepare the entry into for loop
		$subnetmask_start = $parent_subnetmask + 1;
		$subnetmask_final = $parent_subnetmask + $mask_drill_down; // plus 'X' numbers, default 8, gives you /16 -> /24, /24 -> /32 etc..
		if ($subnetmask_final > 32 && $type == 'IPv4'){
			$subnetmask_final = 32; // Cant be larger then /32
		}
		elseif ($subnetmask_final > 128 && $type == 'IPv6'){
			$subnetmask_final = 128; // Cant be larger then /128
		}

		$dec_subnet = $parent_subnet ;
		$square_count = 1;

		# Outer for loop, start with mask one more then current, increment up to X more, or 32, which ever is first
		for ($i = $subnetmask_start; $i <= $subnetmask_final; $i++){
			$showmask = 1; // Set so only show subnet masks that are available
			$dec_subnet = $parent_subnet; // have to reset each time though the loop
			$isquare = pow(2,$square_count); // 2^nth power, that's how many subnets there are per this unique mask
			for ($ii = 0; $ii < $isquare; $ii++ ){
				$cidr_subnet = $this->transform_to_dotted($dec_subnet).'/'.$i;
				if ($type == 'IPv4'){
					// Get broadcast, which is one decimal away from next subnet, and increment
					$net1 = $this->Net_IPv4->parseAddress($cidr_subnet);
					$bc1  = $net1->broadcast;
					$dec_subnet = $this->transform_to_decimal ($bc1);
					$dec_subnet++;
				}
				else {
					// Get broadcast, which is one decimal away from next subnet, and increment
					$net1 = $this->Net_IPv6->parseAddress($cidr_subnet);
					$bc1  = $net1['end'];
					$dec_subnet = $this->transform_to_decimal ($bc1);
					$dec_subnet = $this->subnet_dropdown_ipv6_decimal_add_one($dec_subnet);
				}
				foreach ($history_subnet as $unavailable_sub){ // Go through each subnet and check for over las->transform_to_dotted(p
    				$overlap = $this->verify_overlapping ($cidr_subnet,$unavailable_sub);
					if ($overlap!==false){
						$match = 1;
						break;
					}
				}
				if ($match != 1){
					if ($showmask){ // Highlight Change in Masks
					$html[] = "<li class='disabled'>Subnet Mask: $i</li>";
						$showmask = 0;
					}
					$html[] = "<li><a href='' data-cidr='$cidr_subnet'>- $cidr_subnet</a></li>";
				}
				$match = 0; //Reset
			}
			$square_count++;
		}
		// return html
		return implode( "\n", $html );
	 }


	/**
	 * Returns all free subnets for master subnet for specified mask
	 *
	 * @access public
	 * @param mixed $subnetMasterId
	 * @param bool $mask (default: false)
	 * @param int $mask_drill_down (default: 8)
	 * @param bool $first_result (default: false)
	 * @return array|false
	 */
	public function search_available_subnets ($subnetMasterId, $mask = false, $mask_drill_down = 8, $first_result = false) {

		/* Remove STRICT Error reporting for ParseAddress fuction */
		error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

		# mask check
		if(!is_numeric($mask))               { $this->Result->show("danger", _("Invalid Mask"), true); }
		if($mask>128 || $mask<1)             { $this->Result->show("danger", _("Invalid Mask"), true); }

		# must be integer
		if(!is_numeric(@$subnetMasterId))    { $this->Result->show("danger", _("Invalid ID"), true); }

		// result array
		$html = array();
		$history_subnet = array ();

		// Get Current and Previous subnets
		$subnets 			= $this->fetch_subnet_slaves($subnetMasterId, $result_fields = array("subnet", "mask"));
		$taken_subnet 		= $this->fetch_subnet (null, $subnetMasterId);
		$parent_subnet 		= $taken_subnet->subnet;
		$parent_subnetmask 	= $taken_subnet->mask;

		// folder
		if ($taken_subnet->isFolder=="1") 	return "";

		// detect type
		$type = $this->identify_address( $parent_subnet );

		// initialize pear objet
		if ($type == 'IPv4') 	{ $this->initialize_pear_net_IPv4 (); }
		else 					{ $this->initialize_pear_net_IPv6 (); }

		// reset levels for IPv6 !
		if ($type == "IPv6")    { $mask_drill_down = 8; }
		else                    { $mask_drill_down = 32 - $taken_subnet->mask; }

		// if it has slaves
		if($subnets) {
			foreach ($subnets as $row ) {
				$history_subnet[] =  $this->transform_to_dotted($row->subnet) .'/'. $row->mask;
			}
		}

		# prepare the entry into for loop
		$subnetmask_start = $parent_subnetmask + 1;
		$subnetmask_final = $parent_subnetmask + $mask_drill_down; // plus 'X' numbers, default 8, gives you /16 -> /24, /24 -> /32 etc..
		if ($subnetmask_final > 32 && $type == 'IPv4'){
			$subnetmask_final = 32; // Cant be larger then /32
		}
		elseif ($subnetmask_final > 128 && $type == 'IPv6'){
			$subnetmask_final = 128; // Cant be larger then /128
		}

		$dec_subnet = $parent_subnet ;
		$square_count = 1;

		# Outer for loop, start with mask one more then current, increment up to X more, or 32, which ever is first
		for ($i = $subnetmask_start; $i <= $subnetmask_final; $i++){
			$showmask = 1; // Set so only show subnet masks that are available
			$dec_subnet = $parent_subnet; // have to reset each time though the loop
			$isquare = pow(2,$square_count); // 2^nth power, that's how many subnets there are per this unique mask
			for ($ii = 0; $ii < $isquare; $ii++ ){
        		if($i==$mask) {
    				$cidr_subnet = $this->transform_to_dotted($dec_subnet).'/'.$i;
    				if ($type == 'IPv4'){
    					// Get broadcast, which is one decimal away from next subnet, and increment
    					$net1 = $this->Net_IPv4->parseAddress($cidr_subnet);
    					$bc1  = $net1->broadcast;
    					$dec_subnet = $this->transform_to_decimal ($bc1);
    					$dec_subnet++;
    				}
    				else {
    					// Get broadcast, which is one decimal away from next subnet, and increment
    					$net1 = $this->Net_IPv6->parseAddress($cidr_subnet);
    					$bc1  = $net1['end'];
    					$dec_subnet = $this->transform_to_decimal ($bc1);
    					$dec_subnet = $this->subnet_dropdown_ipv6_decimal_add_one($dec_subnet);
    				}
    				foreach ($history_subnet as $unavailable_sub){ // Go through each subnet and check for over las->transform_to_dotted(p
        				$overlap = $this->verify_overlapping ($cidr_subnet,$unavailable_sub);
    					if ($overlap!==false){
    						$match = 1;
    						break;
    					}
    				}
    				if ($match != 1) {
        				if ($i==$mask) {
            				$html[] = "$cidr_subnet";
            				if($first_result) {
                				return $html;
            				}
        				}
    				}
    				$match = 0; //Reset
    			}
			}
			$square_count++;
		}
		// return html
		return sizeof($html)>0 ? $html : false;
    }



	/**
	 * Returns first free subnet for master subnet for requested mask
	 *
	 * @access public
	 * @param mixed $subnetMasterId
	 * @param bool $mask (default: false)
	 * @return array|false
	 */
	public function search_available_single_subnet ($subnetMasterId, $mask = false) {

		/* Remove STRICT Error reporting for ParseAddress fuction */
		error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

		# mask check
		if(!is_numeric($mask))               { $this->Result->show("danger", _("Invalid Mask"), true); }
		if($mask>128 || $mask<1)             { $this->Result->show("danger", _("Invalid Mask"), true); }

		# must be integer
		if(!is_numeric(@$subnetMasterId))    { $this->Result->show("danger", _("Invalid ID"), true); }

		// result array and existing subnets array
		$html = array();
		$history_subnet = array ();

		// Get Current and Previous subnets
		$slave_subnets 		= $this->fetch_subnet_slaves($subnetMasterId, $result_fields = array("subnet", "mask"));
		$taken_subnet 		= $this->fetch_subnet (null, $subnetMasterId);
		$parent_subnet 		= $taken_subnet->subnet;

		# mask must be smaller than parent !
		if ($taken_subnet->mask > $mask)    { return false; }

		// folder
		if ($taken_subnet->isFolder=="1") 	{ return false; }

		// detect type
		$type = $this->identify_address( $parent_subnet );

		// initialize pear objet
		if ($type == 'IPv4') 	{ $this->initialize_pear_net_IPv4 (); }
		else 					{ $this->initialize_pear_net_IPv6 (); }

		// if it has slaves
		if($slave_subnets) {
			foreach ($slave_subnets as $row ) {
				$history_subnet[] =  $this->transform_to_dotted($row->subnet) .'/'. $row->mask;
			}
		}

		// number of possible masks
        $square_count = $mask - $taken_subnet->mask;

		# Outer for loop, start with mask one more then current, increment up to X more, or 32, which ever is first
		$dec_subnet = $parent_subnet; // have to reset each time though the loop
		$isquare = pow(2,$square_count); // 2^nth power, that's how many subnets there are per this unique mask
		for ($ii = 0; $ii < $isquare; $ii++ ){
			$cidr_subnet = $this->transform_to_dotted($dec_subnet).'/'.$mask;
			if ($type == 'IPv4'){
				// Get broadcast, which is one decimal away from next subnet, and increment
				$net1 = $this->Net_IPv4->parseAddress($cidr_subnet);
				$bc1  = $net1->broadcast;
				$dec_subnet = $this->transform_to_decimal ($bc1);
				$dec_subnet++;
			}
			else {
				// Get broadcast, which is one decimal away from next subnet, and increment
				$net1 = $this->Net_IPv6->parseAddress($cidr_subnet);
				$bc1  = $net1['end'];
				$dec_subnet = $this->transform_to_decimal ($bc1);
				$dec_subnet = $this->subnet_dropdown_ipv6_decimal_add_one($dec_subnet);
			}
			// ignore if it same is in array to speed up !
			if (!in_array($cidr_subnet, $history_subnet)) {
    			// overlap check
    			foreach ($history_subnet as $unavailable_sub){ // Go through each subnet and check for over las->transform_to_dotted(p
        			// if subnet and mask are equal match fails, otherwise chck
        			if ($cidr_subnet == $unavailable_sub) {
                        $match = 1;
                        break;
        			}
        			// check
        			else {
        				$overlap = $this->verify_overlapping ($cidr_subnet,$unavailable_sub);
        				if ($overlap!==false){
        					$match = 1;
        					break;
        				}
        			}
    			}
			}
			else {
    			$match = 1;
			}
			if ($match != 1) {
				return array($cidr_subnet);
			}
			$match = 0; //Reset
		}
		// return html
		return sizeof($html)>0 ? $html : false;
    }

	/**
	 * Take in decimal from IPv6 address and add one to it
	 *
	 * @access public
	 * @param mixed $decimalIpv6
	 * @return int
	 */
	public  function subnet_dropdown_ipv6_decimal_add_one ($decimalIpv6) {
		# Take digit, make array of earch number and reverse it
		$singledigit = array_reverse(str_split($decimalIpv6));
		$start = 1;
		# Foreach array of individual digits and add the first one, until it doesn't carry over, prepend output from there on out
		foreach ($singledigit as $digit) {
			if ($start && $digit == '9') {
				$digit++;
				$output = $output.'0';
			}
			elseif ($start){
				$digit++;
				$output = $digit.$output;
				$start = 0;
			}
			else {
				$output = $digit.$output;
			}
		}
		$decimalIpv6 = $output;
		// return result
		return $decimalIpv6;
	}










	/**
	 * @ripe @arin methods
	 *
	 * https://apps.db.ripe.net/search/query.html
	 *
	 * -------------------------------
	 */

	/**
	 * Fetch subnet information form  RIPE / ARIN
	 *
	 * @access public
	 * @param mixed $subnet
	 * @return array
	 */
	public function resolve_ripe_arin ($subnet) {
		// set subnet allocations
		$this->define_ripe_arin_subnets ();
		// take only first bit of ip address to match /8 delegations
		$subnet_check = reset(explode(".", $subnet));
		// ripe or arin?
		if (in_array($subnet_check, $this->ripe))		{ return $this->query_ripe ($subnet); }
		elseif (in_array($subnet_check, $this->arin))	{ return $this->query_arin ($subnet); }
		else											{ return array("result"=>"error", "error"=>"$subnet Not RIPE or ARIN subnet"); }
	}


	/**
	 * Queries ripe for subnet information
	 *
	 *	Example:
	 *		curl -X GET -H "Accept: application/json" "http://rest.db.ripe.net/ripe/inetnum/185.72.140.0/24"
	 *
	 * @access private
	 * @param mixed $subnet
	 * @return array
	 */
	private function query_ripe ($subnet) {
		// fetch
		$ripe_result = $this->identify_address ($subnet)=="IPv4" ? $this->curl_fetch ("ripe", "inetnum", $subnet) : $this->curl_fetch ("ripe", "inet6num", $subnet);
		// not existings
		if ($ripe_result['result_code']==404) {
			// return array
			return array("result"=>"error", "error"=>$ripe_result['result']->errormessages->errormessage[0]->text);
		}
		// fail
		if ($ripe_result['result_code']!==200) {
			// return array
			return array("result"=>"error", "error"=>"Error connecting to ripe rest api");
		}
		else {
    		$out = array();
			// loop
			if (isset($ripe_result['result']->objects->object[0]->attributes->attribute)) {
				foreach($ripe_result['result']->objects->object[0]->attributes->attribute as $k=>$v) {
					$out[$v->name] = $v->value;
				}
			}
			// return array
			return array("result"=>"success", "data"=>array_filter($out));
		}
	}

	/**
	 * Query arin for subnet information
	 *
	 * @access private
	 * @param mixed $subnet
	 * @return array
	 */
	private function query_arin ($subnet) {
		// remove netmask
		$subnet = reset(explode("/", $subnet));
		// fetch
		$arin_result = $this->curl_fetch ("arin", null, $subnet);

		// not existings
		if ($arin_result['result_code']==404) {
			// return array
			return array("result"=>"error", "error"=>"Subnet not found");
		}
		// fail
		if ($arin_result['result_code']!==200) {
			// return array
			return array("result"=>"error", "error"=>"Error connecting to arin rest api");
		}
		else {
    		$out = array();
			// loop
			if (isset($arin_result['result']->nets->net )) {
				foreach($arin_result['result']->nets->net  as $k=>$v) {
					// netblocks ?
					if($k=="netBlocks") {
						foreach ($v->netBlock as $k1=>$v1) {
							$out[$k1] = $v1->{'$'};
						}
					}
					else {
						$out[$k] = $v->{'$'};
					}
				}
			}
			// do some formats
			if (array_key_exists("cidrLength", $out) && array_key_exists("startAddress", $out)) {
				$out = array_merge(array("CIDR"=>$out['startAddress']."/".$out['cidrLength']), $out);
				$out = array_merge(array('NetRange'=>$out['startAddress']." - ".$out['endAddress']), $out);
				unset($out['startAddress'], $out['endAddress'], $out['cidrLength']);
			}
			unset($out['orgRef'], $out['parentNetRef'], $out['version'], $out['registrationDate']);

			// return array
			return array("result"=>"success", "data"=>array_filter($out));
		}
	}

	/**
	 * Fetch details from ripe
	 *
	 * @access private
	 * @param string $network (default: "ripe")
	 * @param string $type (default: "inetnum")
	 * @param mixed $subnet
	 * @return array
	 */
	private function curl_fetch ($network = "ripe", $type = "inetnum", $subnet) {
		// set url
		$url = $network=="ripe" ? "http://rest.db.ripe.net/ripe/$type/$subnet" : "http://whois.arin.net/rest/nets;q=$subnet?showDetails=true&showARIN=false&showNonArinTopLevelNet=false&ext=netref2";
		// fetch with curl
	    $curl = curl_init();
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Accept: application/json"));
	    // fetch result
		$result = json_decode(curl_exec ($curl));
	    // http response code
	    $result_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	    // close
	    curl_close ($curl);

	    // result
	    return array("result"=>$result, "result_code"=>$result_code);
	}

	/**
	 * Fetch subnets from RIPE for specified AS
	 *
	 * @access public
	 * @param mixed $as
	 * @return array
	 */
	public function ripe_fetch_subnets ($as) {
		//open connection
		$ripe_connection = fsockopen("whois.ripe.net", 43, $errno, $errstr, 5);
		if(!$ripe_connection) {
			$this->Result->show("danger", "$errstr ($errno)", false);
			return false;
		}
		else {
			//fetch result
			fputs ($ripe_connection, '-i origin as'. $as ."\r\n");
			//save result to var out
			$out = "";
		    while (!feof($ripe_connection)) { $out .= fgets($ripe_connection); }

		    //parse it
		    $out = explode("\n", $out);

		    //we only need route
		    foreach($out as $line) {
				if (strlen(strstr($line,"route"))>0) {
    				if(!isset($subnet)) $subnet = array();
					//replace route6 with route
					$line = str_replace("route6:", "route:", $line);
					//only take IP address
					$line = explode("route:", $line);
					$line = trim($line[1]);
					//set result
					$subnet[] = $line;
				}
		    }
		    //return
		    return isset($subnet) ? $subnet : array();
		}
	}


	/**
	 * Defines master (/8) subnets for arin and ripe allocations
	 *
	 * @access private
	 * @return: void
	 */
	private function define_ripe_arin_subnets () {
		// ripe
		if (sizeof($this->ripe)==0) {
			$this->ripe = array (
						"2", "5", "31", "37", "46", "51", "62", "77", "78", "79", "8", "81", "82", "83", "84", "85", "86", "87",
						"88", "89", "9", "91", "92", "93", "94", "95", "19", "141", "145", "151", "176", "178", "185", "188", "193",
						"194", "195", "212", "213", "217"
						);
		}
		// arin
		if (sizeof($this->arin)==0) {
			$this->arin = array (
						"7", "13", "23", "24", "32", "35", "4", "45", "47", "5", "52", "54", "63", "64", "65", "66", "67", "68",
						"69", "7", "71", "72", "73", "74", "75", "76", "96", "97", "98", "99", "1", "14", "17", "18", "128", "129",
						"13", "131", "132", "134", "135", "136", "137", "138", "139", "14", "142", "143", "144", "146", "147", "148", "149",
						"152", "155", "156", "157", "158", "159", "16", "161", "162", "164", "165", "166", "167", "168", "169", "17", "172",
						"173", "174", "184", "192", "198", "199", "24", "25", "26", "27", "28", "29", "216"
						);
		}
	}


}

?>
