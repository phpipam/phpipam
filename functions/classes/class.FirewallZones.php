<?php

// phpIPAM firewall zone management class

class FirewallZones extends Common_functions {

	/* variables */
	public $error = false;				// connection error string
	public $db_settings;				// (obj) db settings
	public $defaults;					// (obj) defaults settings
	private $settings = false;			// (obj) settings

	public $limit;						// number of results
	public $orderby;					// order field
	public $orderdir;					// $order direction

	public $domain_types;				// (obj) types of domain
	public $record_types;				// (obj) record types


	/* objects */
	protected $Database;				// Database object - phpipam

	/**
	 * protected variables
	 */
	protected $user = null;					//(object) for User profile

	/**
	 * __construct method
	 *
	 * @access public
	 * @return void
	 */
	public function __construct (Database_PDO $Database) {
		// initialize Result
		$this->Result = new Result ();
		// initialize object
		$this->Database = $Database;
		
		// initialize user
		$this->User = new User ($this->Database);
		
	}

	/**
	 * fetches settings from database
	 *
	 * @access private
	 * @return void
	 */
/*	protected function get_settings () {
		# cache check
		if($this->settings == false) {
			try { $this->settings = $this->Database->getObject("settings", 1); }
			catch (Exception $e) { $this->Result->show("danger", _("Database error: ").$e->getMessage()); }
		}
	}*/

	public function get_zone_mapping () {
		// try to fetch all mappings
		try { $mapping = $this->firewallZones = $this->Database->getObjectsQuery('SELECT 
						firewallZones.id AS id,
						firewallZones.generator AS generator,
						firewallZoneMapping.id AS mappingId,
						firewallZones.zone AS zone,
						firewallZones.indicator AS indicator,
						firewallZoneMapping.alias AS alias,
						firewallZones.description AS description,
						firewallZoneMapping.deviceId AS deviceId,
						devices.hostname AS deviceName,
						firewallZoneMapping.interface AS interface,
						firewallZones.subnetId AS subnetId,
						subnets.subnet AS subnet,
						subnets.mask AS mask,
						firewallZones.vlanId AS vlanId,
						vlans.number As vlan,
						vlans.name AS vlanName
						FROM firewallZoneMapping
						RIGHT JOIN firewallZones ON zoneId = firewallZones.id
						LEFT JOIN devices ON deviceId = devices.id
						LEFT JOIN subnets ON firewallZones.subnetId = subnets.id
						LEFT JOIN vlans ON firewallZones.vlanId = vlans.vlanId
						having  deviceId is not NULL order by firewallZones.id ASC;');}
		// throw exception 
		catch (Exception $e) {$this->Result->show("danger", _("Database error: ").$e->getMessage());}
		// return the values
		return sizeof($mapping)>0 ? $mapping : false;
	}

	public function get_zones () {
		// try to fetch all mappings
		try { $mapping = $this->firewallZones = $this->Database->getObjectsQuery('SELECT 
						firewallZones.id AS id,
						firewallZones.generator AS generator,
						firewallZones.zone AS zone,
						firewallZones.indicator AS indicator,
						firewallZones.description AS description,
						firewallZones.permissions AS permissions,
						firewallZones.subnetId AS subnetId,
						subnets.sectionId AS sectionId,
						subnets.subnet AS subnet,
						subnets.mask AS subnetMask,
						firewallZones.vlanId AS vlanId,
						vlans.domainId AS domainId,
						vlans.number As vlan, 
						vlans.name AS vlanName
						FROM firewallZones
						LEFT JOIN subnets ON firewallZones.subnetId = subnets.id
						LEFT JOIN vlans ON firewallZones.vlanId = vlans.vlanId ORDER BY id ASC;');}
		// throw exception 
		catch (Exception $e) {$this->Result->show("danger", _("Database error: ").$e->getMessage());}
		// return the values
		return sizeof($mapping)>0 ? $mapping : false;
	}

	public function get_zone ($id) {
		// try to fetch all mappings
		try { $mapping = $this->firewallZones = $this->Database->getObjectsQuery('SELECT 
						firewallZones.id AS id,
						firewallZones.generator AS generator,
						firewallZones.zone AS zone,
						firewallZones.indicator AS indicator,
						firewallZones.description AS description,
						firewallZones.permissions AS permissions,
						firewallZones.subnetId AS subnetId,
						subnets.sectionId AS sectionId,
						subnets.subnet AS subnet,
						subnets.mask AS subnetMask,
						firewallZones.vlanId AS vlanId,
						vlans.domainId AS domainId, 
						vlans.number As vlan,
						vlans.name AS vlanName
						FROM firewallZones
						LEFT JOIN subnets ON firewallZones.subnetId = subnets.id
						LEFT JOIN vlans ON firewallZones.vlanId = vlans.vlanId 
						HAVING id = ? ORDER BY id ASC;', $id);}
		// throw exception 
		catch (Exception $e) {$this->Result->show("danger", _("Database error: ").$e->getMessage());}
		// return the values
		return sizeof($mapping)>0 ? $mapping : false;
	}

	public function add_zone ($values) {
		// try to fetch all mappings
		try { $mapping = $this->firewallZones = $this->Database->insertObject('firewallZones', $values);}
		// throw exception 
		catch (Exception $e) {$this->Result->show("danger", _("Database error: ").$e->getMessage());}
		// return the values
		return sizeof($mapping)>0 ? $mapping : false;
	}

	/**
	 * Changes empty array fields to specified character
	 *
	 * @access public
	 * @param array $fields
	 * @param string $char (default: "/")
	 * @return array
	 */
	public function reformat_empty_array_fields ($fields, $char = "/") {
		foreach($fields as $k=>$v) {
			if(is_null($v) || strlen($v)==0) {
				$out[$k] = 	$char;
			} else {
				$out[$k] = $v;
			}
		}
		// result
		return $out;
	}

	/**
	 * Modify zone details main method
	 *
	 * @access public
	 * @param mixed $action
	 * @param mixed $values
	 * @return void
	 */
	public function modify_zone ($action, $values) {

		// // fetch user
		// $User = new User ($this->Database);
		// $this->user = $User->user;
		//return $this->Result->show("danger","username: ".$this->User->username, true);
		// null empty values
		$values = $this->reformat_empty_array_fields ($values, null);

		// execute based on action
		if($action=="add")			{ return $this->zone_add ($values); }
		elseif($action=="edit")		{ return $this->zone_edit ($values); }
		elseif($action=="delete")	{ return $this->zone_delete ($values['id']); }
		else						{ return $this->Result->show("danger", _("Invalid action"), true); }
	}

	/**
	 * Create new subnet method
	 *
	 * @access private
	 * @param mixed $values
	 * @return void
	 */
	private function zone_add ($values) {
		// execute
		try { $this->Database->insertObject("firewallZones", $values); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			write_log( "Firewall zone creation", "Failed to add new firewall zone<hr>".$e->getMessage(), 2, $this->User->username);
			return false;
		}
		// save id
		$this->lastInsertId = $this->Database->lastInsertId();
		// ok
		write_log( "Firewall zone creation", "New firewall zone created<hr>".array_to_log($values), 0, $this->User->username);
		return true;
	}

	/**
	 * Edit zone
	 *
	 * @access private
	 * @param mixed $values
	 * @return void
	 */
	private function zone_edit ($values) {
		// execute
		try { $this->Database->updateObject("firewallZones", $values, "id"); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			write_log( "Firewall zone edited", "Failed to edit firewall zone<hr>".$e->getMessage(), 2, $this->User->username);
			return false;
		}
		# save ID
		$this->lastInsertId = $this->Database->lastInsertId();
		# ok
		write_log( "Firewall zone edited", "Firewall zone edited<hr>".array_to_log($values), 0, $this->User->username);
		return true;
	}

	/**
	 * Deletes zone and all corresponding mappings
	 *
	 * @access private
	 * @param mixed $id
	 * @return void
	 */
	private function zone_delete ($id) {
		// save old values
		$old_zone = $this->get_zone($id);

		// first truncate it
		//$this->subnet_truncate ($id);

		// delete subnet
		try { $this->Database->deleteRow("firewallZones", "id", $id); }
		catch (Exception $e) {
			write_log( "Firewall zone delete", "Failed to delete firewall zone $old_zone->zone<hr>".$e->getMessage(), 2, $this->User->username);
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}
		// ok
		write_log( "Firewall zone deleted", "Firewall zone ".$old_zone->zone." deleted<hr>".array_to_log($old_subnet), 0, $this->User->username);

		return true;
	}


}