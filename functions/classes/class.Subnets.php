<?php

/**
 *	phpIPAM Subnets class
 */

class Subnets extends Common_functions {

	/**
	 * public variables
	 */
	public $subnets;						// (array of objects) to store subnets, subnet ID is array index
	public $slaves;							// (array of ids) to store id's of all recursively slaves
	public $address_types = null;			// (array) IP address types from Addresses object

	/**
	 * protected variables
	 */
	protected $user = null;					// (object) for User profile

	/**
	 * object holders
	 */
	protected $Net_IPv4;					// PEAR NET IPv4 object
	protected $Net_IPv6;					// PEAR NET IPv6 object
	public    $Result;						// for Result printing
	protected $Database;					// for Database connection
	public $Log;							// for Logging connection





	/**
	 * __construct function
	 *
	 * @access public
	 * @return void
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
	 * Initializes PEAR Net IPv4 object
	 *
	 * @access private
	 * @return void
	 */
	private function initialize_pear_net_IPv4 () {
		//initialize NET object
		if(!is_object($this->Net_IPv4)) {
			require_once( dirname(__FILE__) . '/../../functions/PEAR/Net/IPv4.php' );
			//initialize object
			$this->Net_IPv4 = new Net_IPv4();
		}
	}
	/**
	 * Initializes PEAR Net IPv6 object
	 *
	 * @access private
	 * @return void
	 */
	private function initialize_pear_net_IPv6 () {
		//initialize NET object
		if(!is_object($this->Net_IPv6)) {
			require_once( dirname(__FILE__) . '/../../functions/PEAR/Net/IPv6.php' );
			//initialize object
			$this->Net_IPv6 = new Net_IPv6();
		}
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
	 * @return void
	 */
	public function modify_subnet ($action, $values) {
		# strip tags
		$values = $this->strip_input_tags ($values);

		# fetch user
		$User = new User ($this->Database);
		$this->user = $User->user;

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
	 * @return void
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
	 * @return void
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
	 * @return void
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
	 * @param mixed $values
	 * @return void
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
	 * @return void
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
	 * @return void
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
							"showName"=>@$subnet['showName']
							);
			//create new subnets
			$this->modify_subnet ("add", $values);

			//get all address ids
			unset($ids);
			foreach($addresses as $ip) {
				if($ip->subnetId == $m) {
					$ids[] = $ip->id;
				}
			}

			//replace all subnetIds in IP addresses to new subnet
			if(isset($ids)) {
				if(!$Admin->object_modify("ipaddresses", "edit-multiple", $ids, array("subnetId"=>$this->lastInsertId)))	{ $Result->show("danger", _("Failed to move IP address"), true); }
			}

			# next
			$m++;
		}

		# do we need to remove old subnet?
		if($group!="yes") {
			if(!$Admin->object_modify("subnets", "delete", "id", array("id"=>$subnet_old->id)))								{ $Result->show("danger", _("Failed to remove old subnet"), true); }
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
	 * @param mixed $id
	 * @return void
	 */
	public function fetch_subnet ($method=null, $id) {
		# null method
		$method = is_null($method) ? "id" : $this->Database->escape($method);
		# check cache first
		if(isset($this->subnets[$id]))	{
			return $this->subnets[$id];
		}
		else {
			try { $subnet = $this->Database->getObjectQuery("SELECT * FROM `subnets` where `$method` = ? limit 1;", array($id)); }
			catch (Exception $e) {
				$this->Result->show("danger", _("Error: ").$e->getMessage());
				return false;
			}
			# save to subnets cache
			if(sizeof($subnet)>0) {
				# add decimal format
				$subnet->ip = $this->transform_to_dotted ($subnet->subnet);
				# save to subnets
				$this->subnets[$subnet->id] = (object) $subnet;
			}
			#result
			return $subnet;
		}
	}

	/**
	 * Fetches all subnets in specified section
	 *
	 * @access public
	 * @param mixed $sectionId
	 * @param mixed $orderType
	 * @param mixed $orderBy
	 * @return void
	 */
	public function fetch_section_subnets ($sectionId) {
		# check order
		$this->get_settings ();
		$order = $this->get_subnet_order ();
		# fetch
		try { $subnets = $this->Database->getObjectsQuery("SELECT * FROM `subnets` where `sectionId` = ? order by isFolder desc, $order[0] $order[1];", array($sectionId)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# save to subnets cache
		if(sizeof($subnets)>0) {
			foreach($subnets as $subnet) {
				# add decimal format
				$subnet->ip = $this->transform_to_dotted ($subnet->subnet);
				# save to subnets
				$this->subnets[$subnet->id] = (object) $subnet;
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
	 * @return void
	 */
	public function fetch_all_subnets_search () {
		# fetch
		try { $subnets = $this->Database->getObjectsQuery("SELECT `id`,`subnet`,`mask` FROM `subnets`;"); }
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
	 * @return void
	 */
	public function fetch_all_subnets_for_pingCheck () {
		# fetch
		try { $subnets = $this->Database->getObjectsQuery("SELECT `id`,`subnet`,`sectionId`,`mask` FROM `subnets` where `pingSubnet` = 1 and `isFolder`= 0 and `mask` > '0' and subnet > 16843009;"); }
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
	 * @return void
	 */
	public function fetch_all_subnets_for_discoveryCheck () {
		# fetch
		try { $subnets = $this->Database->getObjectsQuery("SELECT `id`,`subnet`,`sectionId`,`mask` FROM `subnets` where `discoverSubnet` = 1 and `isFolder`= 0 and `mask` > '0' and subnet > 16843009 and `mask` > 20;"); }
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
	 * @return void
	 */
	public function fetch_vlan_subnets ($vlanId, $sectionId=null) {
	    # fetch settings and set subnet ordering
	    $this->get_settings();
	    $order = $this->get_subnet_order ();

	    # fetch section and set section ordering
		$Sections = new Sections ($this->Database);
	    $section  = $Sections->fetch_section (null, $sectionId);

	    # section ordering - overrides network
	    if(@$section->subnetOrdering!="default" && strlen(@$section->subnetOrdering)>0 ) 	{ $order = explode(",", $section->subnetOrdering); }
	    else 																				{ $order = $this->get_subnet_order (); }

		# set query
		if(!is_null($sectionId)) {
			$query  = "select * from `subnets` where `vlanId` = ? and `sectionId` = ? ORDER BY isFolder desc, ? ?;";
			$params = array($vlanId, $sectionId, $order[0], $order[1]);
		}
		else {
			$query  = "select * from `subnets` where `vlanId` = ? ORDER BY isFolder desc, ? ?;";
			$params = array($vlanId, $order[0], $order[1]);
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
				# add decimal format
				$subnet->ip = $this->transform_to_dotted ($subnet->subnet);
				# save to subnets
				$this->subnets[$subnet->id] = (object) $subnet;
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
	 * @return void
	 */
	private function is_subnet_in_vlan ($subnetId, $vlanId) {
		# fetch subnet details
		$subnet = $this->fetch_subnet ("id", $subnetId);
		# same id?
		return @$subnet->vlanId==$vlanId ? true : false;
	}


	/**
	 * Fetches all subnets within section with specified vrf ID
	 *
	 * @access public
	 * @param mixed $vrfId
	 * @param mixed $sectionId
	 * @return void
	 */
	public function fetch_vrf_subnets ($vrfId, $sectionId=null) {
	    # fetch settings and set subnet ordering
	    $this->get_settings();
	    $order = $this->get_subnet_order ();

	    # fetch section and set section ordering
		$Sections = new Sections ($this->Database);
	    $section  = $Sections->fetch_section (null, $sectionId);

	    # section ordering - overrides network
	    if(@$section->subnetOrdering!="default" && strlen(@$section->subnetOrdering)>0 ) 	{ $order = explode(",", $section->subnetOrdering); }
	    else 																				{ $order = $this->get_subnet_order (); }

		# set query
		if(!is_null($sectionId)) {
			$query  = "select * from `subnets` where `vrfId` = ? and `sectionId` = ? ORDER BY isFolder desc, ? ?;";
			$params = array($vrfId, $sectionId, $order[0], $order[1]);
		}
		else {
			$query  = "select * from `subnets` where `vrfId` = ? ORDER BY isFolder desc, ? ?;";
			$params = array($vrfId, $order[0], $order[1]);
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
				# add decimal format
				$subnet->ip = $this->transform_to_dotted ($subnet->subnet);
				# save to subnets
				$this->subnets[$subnet->id] = (object) $subnet;
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
	 * @return void
	 */
	private function is_subnet_in_vrf ($subnetId, $vrfId) {
		# fetch subnet details
		$subnet = $this->fetch_subnet ("id", $subnetId);
		# same id?
		return @$subnet->vrfId==$vrfId ? true : false;
	}

	/**
	 * Checks for all subnets that are marked for scanning and new hosts discovery
	 *
	 * @access public
	 * @return void
	 */
	public function fetch_scanned_subnets () {
		// set query
		$query = "select * from `subnets` where `pingSubnet`=1 or `discoverSubnet`=1;";
		# fetch
		try { $subnets = $this->Database->getObjectsQuery($query); }
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
	 * @return void
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
	 * @return void
	 */
	public function get_ipv4_masks () {
		# loop masks
		for($mask=30; $mask>=8; $mask--) {
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
	 * @param mixed $subnetid
	 * @return void
	 */
	public function has_slaves ($subnetid) {
		try { $count = $this->Database->numObjectsFilter("subnets", "masterSubnetId", $subnetid); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# result
		return $count>0 ? true : false;
	}

	/**
	 * Fetches all immediate slave subnets for specified subnetId
	 *
	 * @access public
	 * @param mixed $subnetid
	 * @return void
	 */
	public function fetch_subnet_slaves ($subnetid) {
		try { $slaves = $this->Database->getObjectsQuery("SELECT * FROM `subnets` where `masterSubnetId` = ? order by `subnet` asc;", array($subnetid)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# save to subnets cache
		if(sizeof($slaves)>0) {
			foreach($slaves as $slave) {
				# add decimal format
				$slave->ip = $this->transform_to_dotted ($slave->subnet);
				# save to subnets
				$this->subnets[$slave->id] = (object) $slave;
			}
			return $slaves;
		}
		# no subnets
		return false;
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
	}

	/**
	 * Removes master subnet from slave subnets array
	 *
	 * @access public
	 * @param mixed $subnetId
	 * @return void
	 */
	public function remove_subnet_slaves_master ($subnetId) {
		foreach($this->slaves as $k=>$s) {
			if($s==$subnetId) {
				unset($this->slaves[$k]);
			}
		}
	}

	/**
	 * fetch whole tree path for subnetId - from slave to parents
	 *
	 * @access public
	 * @param mixed $subnetId
	 * @return void
	 */
	public function fetch_parents_recursive ($subnetId) {
		$parents = array();
		$root = false;

		while($root == false) {
			$subd = (array) $this->fetch_subnet("id", $subnetId);		# get subnet details
			if(sizeof($subd)>0) {
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
		# return array
		return $parents;
	}













	/**
	* @transform IP/subnet functions
	* -------------------------------
	*/

	/**
	 * Calculate subnet usage
	 *
	 *	used, maximum, free, free_percentage
	 *
	 * @access public
	 * @param mixed $used_hosts (int)
	 * @param mixed $netmask (int)
	 * @param mixed $subnet	(int)
	 * @return void
	 */
	public function calculate_subnet_usage ($used_hosts, $netmask, $subnet) {
		# set IP version
		$ipversion = $this->get_ip_version ($subnet);
		# set initial vars
		$out['used'] = (int) $used_hosts;														//set used hosts
		$out['maxhosts'] = (int) $this->get_max_hosts ($netmask,$ipversion);					//get maximum hosts
		$out['freehosts'] = (int) gmp_strval(gmp_sub($out['maxhosts'],$out['used']));					//free hosts
		$out['freehosts_percent'] = round((($out['freehosts'] * 100) / $out['maxhosts']),2);	//free percentage
		# result
		return $out;
	}

	/**
	 * Calculates detailed network usage - dhcp, active, ...
	 *
	 * @access public
	 * @param mixed $subnet		//subnet in decimal format
	 * @param mixed $bitmask	//netmask in decimal format
	 * @param mixed $addresses	//all addresses to be calculated, either all slave or per subnet
	 * @return void
	 */
	public function calculate_subnet_usage_detailed ($subnet, $bitmask, $addresses) {
		# get IP address count per address type
		$details = $this->calculate_subnet_usage_sort_addresses ($addresses);

	    # calculate max number of hosts
	    $details['maxhosts'] = $this->get_max_hosts($bitmask, $this->identify_address($subnet));
	    # calculate free hosts
	    $details['freehosts']         = gmp_strval( gmp_sub ($details['maxhosts'] , $details['used']) );
	    # calculate use percentage for each type
	    $details['freehosts_percent'] = round( ( ($details['freehosts'] * 100) / $details['maxhosts']), 2 );
	    foreach($this->address_types as $t) {
		    $details[$t['type']."_percent"] = round( ( ($details[$t['type']] * 100) / $details['maxhosts']), 2 );
	    }
	    return( $details );
	}

	/**
	 * Calculates subnet usage per host type
	 *
	 * @access public
	 * @param mixed $addresses
	 * @return void
	 */
	public function calculate_subnet_usage_sort_addresses ($addresses) {
		$count['used'] = 0;				//initial sum count
		# fetch address types
		$address_types = $this->get_addresses_types();
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
			# addresses class
			$Addresses = new Addresses ($this->Database);
			# fetch
			$this->address_types = $Addresses->addresses_types_fetch();
		}
	}

	/**
	 * Translates address type from index (int) to type
	 *
	 *	e.g.: 0 > offline
	 *
	 * @access public
	 * @param mixed $index
	 * @return void
	 */
	public function translate_address_type ($index) {
		# fetch
		$all_types = $this->get_addresses_types();
		# return
		return $this->address_types[$index]["type"];
	}

	/**
	 * Calculates subnet usage recursive for underlaying hosts
	 *
	 * @access public
	 * @param mixed $subnetId
	 * @param mixed $subnet
	 * @param mixed $netmask
	 * @param mixed $Addresses
	 * @return void
	 */
	public function calculate_subnet_usage_recursive ($subnetId, $subnet, $netmask, $Addresses) {
		# identify address
		$address_type = $this->get_ip_version ($subnet);
		# fetch all slave subnets recursive
		$this->reset_subnet_slaves_recursive ();
		$this->fetch_subnet_slaves_recursive ($subnetId);
		$this->remove_subnet_slaves_master ($subnetId);

		# go through each and calculate used hosts
		# add +2 for subnet and broadcast if required
		foreach($this->slaves_full as $s) {
			# fetch all addresses
			$used = (int) $used + $Addresses->count_subnet_addresses ($s->id);					//add to used hosts calculation
			# mask fix
			if($address_type=="IPv4" && $netmask<31) {
				$used = $used+1;
			}
		}
		# we counted, now lets calculate and return result
		return $this->calculate_subnet_usage ($used, $netmask, $subnet);
	}

	/**
	 * Present numbers in pow 10, only for IPv6
	 *
	 * @access public
	 * @param mixed $number
	 * @return void
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
	 * @return void
	 */
	public function get_max_hosts ($netmask, $ipversion, $strict=true) {
		if($ipversion == "IPv4")	{ return $this->get_max_IPv4_hosts ($netmask, $strict); }
		else						{ return $this->get_max_IPv6_hosts ($netmask, $strict); }
	}

	/**
	 * Get max number of IPv4 hosts
	 *
	 * @access public
	 * @param mixed $netmask
	 * @return void
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
	 * @return void
	 */
	public function get_max_IPv6_hosts ($netmask, $strict) {
		return gmp_strval(gmp_pow(2, 128 - $netmask));
	}

	/**
	 * Returns maximum netmask length
	 *
	 * @access public
	 * @param mixed $address
	 * @return void
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
	 * @return void
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
	 * @return void
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
	 * @return void
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
	 * @return void
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
			$err = $this->verify_cidr_address( $address."/".$m , 1);
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
	 * @return void
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
	 * @return void
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
	 * @param mixed $cidr
	 * @param bool $issubnet (default: true)
	 * @return void
	 */
	public function verify_cidr_address_IPv6 ($cidr, $issubnet = true) {
		# Initialize PEAR NET object
		$this->initialize_pear_net_IPv6 ();
        # validate
        if (!$this->Net_IPv6->checkIPv6 ($cidr) ) 						{ return _("Invalid IPv6 address!"); }
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
	 * @param mixed $cidr
	 * @return void
	 */
	public function verify_cidr ($cidr) {
		$cidr =  explode("/", $cidr);
		# verify network part
	    if(empty($cidr[0]) || empty($cidr[1])) 						{ return _("Invalid CIDR format!"); }
	    # verify network part
		if($this->identify_address_format ($cidr[0])!="dotted")		{ return _("Invalid Network!"); }
		# verify mask
		if(!is_numeric($cidr[1]))									{ return _("Invalid netmask"); }
		if($this->get_max_netmask ($cidr[0])<$cidr[1])				{ return _("Invalid netmask"); }
	}

	/**
	 * Verifies if new subnet overlapps with any of existing subnets in that section and same or null VRF
	 *
	 * @access public
	 * @param int $sectionId
	 * @param CIDR $new_subnet
	 * @param int $vrfId (default: 0)
	 * @return void
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
						if($this->identify_address($new_subnet)=="IPv4") {
							if($this->verify_IPv4_subnet_overlapping ($new_subnet,  $this->transform_to_dotted($existing_subnet->subnet).'/'.$existing_subnet->mask)!==false) {
								 return _("Subnet $new_subnet overlapps with").' '. $this->transform_to_dotted($existing_subnet->subnet).'/'.$existing_subnet->mask." (".$existing_subnet->description.")";
							}
						}
						else {
							if($this->verify_IPv6_subnet_overlapping ($new_subnet,  $this->transform_to_dotted($existing_subnet->subnet).'/'.$existing_subnet->mask)!==false) {
								 return _("Subnet $new_subnet overlapps with").' '. $this->transform_to_dotted($existing_subnet->subnet).'/'.$existing_subnet->mask." (".$existing_subnet->description.")";
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
	 * Verifies if resized subnet overlapps with any of existing subnets in that section and same or null VRF
	 *
	 * @access public
	 * @param int $sectionId
	 * @param CIDR $new_subnet
	 * @param int $old_subnet_id
	 * @param int $vrfId (default: 0)
	 * @return void
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
							if($this->identify_address($new_subnet)=="IPv4") {
								if($this->verify_IPv4_subnet_overlapping ($new_subnet,  $this->transform_to_dotted($existing_subnet->subnet).'/'.$existing_subnet->mask)!==false) {
									 return _("Subnet $new_subnet overlapps with").' '. $this->transform_to_dotted($existing_subnet->subnet).'/'.$existing_subnet->mask." (".$existing_subnet->description.")";
								}
							}
							else {
								if($this->verify_IPv6_subnet_overlapping ($new_subnet,  $this->transform_to_dotted($existing_subnet->subnet).'/'.$existing_subnet->mask)!==false) {
									 return _("Subnet $new_subnet overlapps with").' '. $this->transform_to_dotted($existing_subnet->subnet).'/'.$existing_subnet->mask." (".$existing_subnet->description.")";
								}
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
	 * @return void
	 */
	public function verify_nested_subnet_overlapping ($sectionId, $new_subnet, $vrfId = 0, $masterSubnetId = 0) {
		# fetch section subnets
		$sections_subnets = $this->fetch_section_subnets ($sectionId);
		# fix null vrfid
		$vrfId = is_numeric($vrfId) ? $vrfId : 0;
		# check
		if(sizeof($section_subnets)>0) {
			foreach ($section_subnets as $existing_subnet) {
	            //only check if vrfId's match
	            if($existing_subnet->vrfId==$vrfId || $existing_subnet->vrfId==null) {
		            # ignore folders!
		            if($existing_subnet->isFolder!=1) {
	                	# check if it is nested properly - inside its own parent, otherwise check for overlapping
	                	$allParents = $this->$this->fetch_parents_recursive($masterSubnetId);
	                	//loop
	                	$ignore = false;
	                	foreach($allParents as $kp=>$p) {
		                	if($existing_subnet->id == $kp) {
			                	$ignore = true;
		                	}
	                	}
	                	if($ignore==false)  {
				            # check overlapping
							if($this->identify_address($new_subnet)=="IPv4") {
								if($this->verify_IPv4_subnet_overlapping ($new_subnet,  $this->transform_to_dotted($existing_subnet->subnet).'/'.$existing_subnet->mask)!==false) {
									 return _("Subnet $new_subnet overlapps with").' '. $this->transform_to_dotted($existing_subnet->subnet).'/'.$existing_subnet->mask." (".$existing_subnet->description.")";
								}
							}
							else {
								if($this->verify_IPv6_subnet_overlapping ($new_subnet,  $this->transform_to_dotted($existing_subnet->subnet).'/'.$existing_subnet->mask)!==false) {
									 return _("Subnet $new_subnet overlapps with").' '. $this->transform_to_dotted($existing_subnet->subnet).'/'.$existing_subnet->mask." (".$existing_subnet->description.")";
								}
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
	 * Verifies overlapping of 2 subnets
	 *
	 * @access public
	 * @param CIDR $subnet1
	 * @param CIDR $subnet2
	 * @return void
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
	 * @return boolean
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
	 * @return void
	 */
	public function verify_subnet_nesting ($masterSubnetId, $cidr) {
		//first get details for root subnet
		$master_details = $this->fetch_subnet (null, $masterSubnetId);

	    //IPv4 or ipv6?
	    $type_master = $this->identify_address( $master_details->subnet );
	    $type_nested = $this->identify_address( $cidr );

	    //both must be IPv4 or IPv6
		if($type_master != $type_nested) { return false; }

		//check
		return $this->is_subnet_inside_subnet ($cidr, $this->transform_to_dotted ($master_details->subnet)."/".$master_details->mask);
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
	 * @return void
	 */
	public function verify_subnet_resize ($subnet, $mask, $subnetId, $vrfId, $masterSubnetId, $mask_old) {
	    # fetch section and set section ordering
		$Sections = new Sections ($this->Database);
	    $section  = $Sections->fetch_section (null, $sectionId);

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
			if ($section->strictMode==1) {
				//if it has parent make sure it is still within boundaries
				if((int) $masterSubnetId>0) {
					//if parent is folder check for other in same folder
					$parent_subnet = $this->fetch_subnet(null, $masterSubnetId);
					if($parent_subnet->isFolder!=1) {
						//check that new is inside its master subnet
						if(!$this->verify_subnet_nesting ($parent_subnet->id, $this->transform_to_dotted($subnet)."/".$mask)) {
							$this->Result->show("danger", _("New subnet not in master subnet")."!", true);
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
										$this->Result->show("danger", _("Subnet overlapps with")." ".$this->transform_to_dotted($fs->subnet)."/".$fs->mask, true);
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
	 * @return void
	 */
	private function verify_subnet_split ($subnet_old, $number, $group, $strict) {
		# addresses class
		$Addresses = new Addresses ($this->Database);

		# get new mask - how much we need to add to old mask?
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
			default:	$Result->show("danger", _("Invalid number of subnets"), true);
		}
		//set new mask
		$mask = $subnet_old->mask + $mask_diff;
		//set number of subnets
		$number_of_subnets = pow(2,$mask_diff);
		//set max hosts per new subnet
		$max_hosts = $this->get_max_hosts ($mask, $this->identify_address($this->transform_to_dotted($subnet_old->subnet)), false);

		# create array of new subnets based on number of subnets (number)
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
						$Result->show("danger", _("Subnet overlapping - ").$this->transform_to_dotted($new_subnet->subnet)."/".$new_subnet->mask." overlapps with ".$this->transform_to_dotted($nested_subnet->subnet)."/".$nested_subnet->mask, true);
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
	 * @return void
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
	 * @return void
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
	 * @return void
	 */
	private function is_IPv6_subnet_inside_subnet ($cidr1, $cidr2) {
		# Initialize PEAR NET object
		$this->initialize_pear_net_IPv6 ();

    	//remove netmask from subnet1
    	$cidr1 = $this->Net_IPv6->removeNetmaskSpec ($cidr1);

	    //check
    	if ($this->Net_IPv6->isInNetmask ( $cidr1, $cidr2 ) ) 	{ return true; }
    	else 													{ return false; }
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
	 * @param int $subnetid
	 * @return void
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
	 * @param mixed $perm
	 * @return void
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
	 * @return void
	 */
	public function print_subnets_menu( $user, $section_subnets, $rootId = 0 ) {
		# open / close via cookie
		if (isset($_COOKIE['sstr'])) { $cookie = array_filter(explode("|", $_COOKIE['sstr'])); }
		else						 { $cookie= array(); }

		# initialize html array
		$html = array();
		# create children array
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
						$html[] = '<a href="'.create_link("subnets",$option['value']['sectionId'],$option['value']['id']).'" rel="tooltip" data-placement="right" title="'.$option['value']['description'].'">'.$this->transform_to_dotted($option['value']['subnet']).'/'.$option['value']['mask'].'</a>';
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
						$html[] = '<a href="'.create_link("subnets",$option['value']['sectionId'],$option['value']['id']).'" rel="tooltip" data-placement="right" title="'.$option['value']['description'].'">'.$this->transform_to_dotted($option['value']['subnet']).'/'.$option['value']['mask'].'</a></li>';
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
	 * @param mixed $sectionId
	 * @return void
	 */
	public function print_vlan_menu( $user, $vlans, $sectionId ) {
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

			# new item
			$html[] = '<li class="folder folder-'.$open.' '.$active.'"><i class="fa fa-gray fa-folder-'.$open.'-o" rel="tooltip" data-placement="right" data-html="true" title="'._('VLAN contains subnets').'.<br>'._('Click on folder to open/close').'"></i>';
			$html[] = '<a href="'.create_link("vlan",$sectionId,$item['vlanId']).'" rel="tooltip" data-placement="right" title="'.$item['description'].'">'.$item['number'].' ('.$item['name'].')</a>';

			# fetch all subnets in VLAN
			$subnets = $this->fetch_vlan_subnets ($item['vlanId'], $sectionId);

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
	 * @param mixed $sectionId
	 * @return void
	 */
	public function print_vrf_menu( $user, $vrfs, $sectionId ) {
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

			# fetch all subnets in VLAN
			$subnets = $this->fetch_vrf_subnets ($item['vrfId'], $sectionId);

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
	 * @return none - print
	 */
	public function print_subnets_tools( $user, $subnets, $custom_fields ) {
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

		# return table content (tr and td's)
		while ( $loop && ( ( $option = each( $children_subnets[$parent] ) ) || ( $parent > $rootId ) ) )
		{
			# repeat
			$repeat  = str_repeat( " - ", ( count($parent_stack)) );
			# dashes
			if(count($parent_stack) == 0)	{ $dash = ""; }
			else							{ $dash = "-"; }

			if(count($parent_stack) == 0) {
				$margin = "0px";
				$padding = "0px";
			}
			else {
				# padding
				$padding = "10px";

				# margin
				$margin  = (count($parent_stack) * 10) -10;
				$margin  = $margin *2;
				$margin  = $margin."px";
			}

			# count levels
			$count = count( $parent_stack ) + 1;

			# get VLAN
			$vlan = (array) $Tools->fetch_object("vlans", "vlanId", $option['value']['vlanId']);
			if(@$vlan[0]===false) 	{ $vlan['number'] = ""; }			# no VLAN

			# description
			$description = strlen($option['value']['description'])==0 ? "/" : $option['value']['description'];


			# print table line
			if(strlen($option['value']['subnet']) > 0) {
				$html[] = "<tr>";

				//which level?
				if($count==1) {
					# is folder?
					if($option['value']['isFolder']==1) {
					$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-sfolder fa-pad-right-3 fa-folder-open'></i> <a href='".create_link("folder",$option['value']['sectionId'],$option['value']['id'])."'> $description</a></td>";
					$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-sfolder fa-pad-right-3 fa-folder-open'></i>  $description</td>";

					}
					else {
						# last?
						if(!empty( $children_subnets[$option['value']['id']])) {
							$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open-o'></i><a href='".create_link("subnets",$option['value']['sectionId'],$option['value']['id'])."'>  ".$this->transform_to_dotted($option['value']['subnet']) ."/".$option['value']['mask']."</a></td>";
							$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open-o'></i> $description</td>";
						} else {
							$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-angle-right'></i><a href='".create_link("subnets",$option['value']['sectionId'],$option['value']['id'])."'>  ".$this->transform_to_dotted($option['value']['subnet']) ."/".$option['value']['mask']."</a></td>";
							$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-angle-right'></i> $description</td>";
						}
				}
				} else {
					# is folder?
					if($option['value']['isFolder']==1) {
						# last?
							$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open'></i> <a href='".create_link("folder",$option['value']['sectionId'],$option['value']['id'])."'> $description</a></td>";
							$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open'></i> $description</td>";
					}
					else {
						# last?
						if(!empty( $children_subnets[$option['value']['id']])) {
							$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open-o'></i> <a href='".create_link("subnets",$option['value']['sectionId'],$option['value']['id'])."'>  ".$this->transform_to_dotted($option['value']['subnet']) ."/".$option['value']['mask']."</a></td>";
							$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open-o'></i> $description</td>";
						}
						else {
							$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-angle-right'></i> <a href='".create_link("subnets",$option['value']['sectionId'],$option['value']['id'])."'>  ".$this->transform_to_dotted($option['value']['subnet']) ."/".$option['value']['mask']."</a></td>";
							$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-angle-right'></i> $description</td>";

						}
					}
				}

				//vlan
				$html[] = "	<td>$vlan[number]</td>";

				//vrf
				if($this->settings->enableVRF == 1) {
					# fetch vrf
					$vrf = $Tools->fetch_vrf(null, $option['value']['vrfId']);
					$html[] = !$vrf ? "<td></td>" : "<td>$vrf->name</td>";
				}

				//masterSubnet
				$masterSubnet = ( $option['value']['masterSubnetId']==0 || empty($option['value']['masterSubnetId']) ) ? true : false;

				if($masterSubnet) { $html[] ='	<td>/</td>' . "\n"; }
				else {
					$master = (array) $this->fetch_subnet (null, $option['value']['masterSubnetId']);
					if($master['isFolder']==1)
						$html[] = "	<td><i class='fa fa-gray fa-folder-open-o'></i> <a href='".create_link("folder",$option['value']['sectionId'],$master['id'])."'>$master[description]</a></td>" . "\n";
					else {
						$html[] = "	<td><a href='".create_link("subnets",$option['value']['sectionId'],$master['id'])."'>".$this->transform_to_dotted($master['subnet']) .'/'. $master['mask'] .'</a></td>' . "\n";
					}
				}

				//device
				$device = ( $option['value']['device']==0 || empty($option['value']['device']) ) ? false : true;

				if($device===false) { $html[] ='	<td>/</td>' . "\n"; }
				else {
					$device = $Tools->fetch_object ("devices", "id", $option['value']['device']);
					if ($device!==false) {
						$html[] = "	<td><a href='".create_link("tools","devices","hosts",$option['value']['device'])."'>".$device->hostname .'</a></td>' . "\n";
					}
					else {
						$html[] ='	<td>/</td>' . "\n";
					}
				}

				//requests
				$requests = $option['value']['allowRequests']==1 ? "<i class='fa fa-gray fa-check'></i>" : "";
				$html[] = "	<td class='hidden-xs hidden-sm'>$requests</td>";
				//ping check
				$pCheck	= $option['value']['pingSubnet']==1 ? "<i class='fa fa-gray fa-check'></i>" : "";
				$html[] = "	<td class='hidden-xs hidden-sm'>$pCheck</td>";
				//discover subnet
				$discover = $option['value']['discoverSubnet']==1 ? "<i class='fa fa-gray fa-check'></i>" : "";
				$html[] = "	<td class='hidden-xs hidden-sm'>$discover</td>";

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
		# print
		print implode( "\n", $html );
	}

	/**
	 * Prints dropdown menu for master subnet selection in subnet editing
	 *
	 * @access public
	 * @param mixed $sectionId
	 * @param string $current_master (default: "0")
	 * @return void
	 */
	public function print_mastersubnet_dropdown_menu($sectionId, $current_master = 0) {
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
		if(sizeof(@$children_folders)>0) {
			$html[] = "<optgroup label='"._("Folders")."'>";
			# return table content (tr and td's) - folders
			while ( $loopF && ( ( $option = each( $children_folders[$parent] ) ) || ( $parent > $rootId ) ) )
			{
				# repeat
				$repeat  = str_repeat( " - ", ( count($parent_stack_folder)) );
				# dashes
				if(count($parent_stack_folder)==0)	{ $dash = ""; }
				else								{ $dash = $repeat; }

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
			# dashes
			if(count($parent_stack_subnet)==0)	{ $dash = ""; }
			else								{ $dash = $repeat; }

			# count levels
			$count = count($parent_stack_subnet)+1;

			# print table line if it exists and it is not folder
			if(strlen($option['value']['subnet']) > 0 && $option['value']['isFolder']!=1) {
				# selected
				if($option['value']['id'] == $current_master) 	{ $html[] = "<option value='".$option['value']['id']."' selected='selected'>$repeat ".$this->transform_to_dotted($option['value']['subnet'])."/".$option['value']['mask']." (".$option['value']['description'].")</option>"; }
				else 											{ $html[] = "<option value='".$option['value']['id']."'					   >$repeat ".$this->transform_to_dotted($option['value']['subnet'])."/".$option['value']['mask']." (".$option['value']['description'].")</option>"; }
			}
			// folder - disabled
			elseif ($option['value']['isFolder']==1) {
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
		$html[] = "</select>";
		# join and print
		print implode( "\n", $html );
	}
}
