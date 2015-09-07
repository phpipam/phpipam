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
		// get settings form parent
		$this->get_settings ();

	}

	/**
	 * fetches settings from database
	 *
	 * @access private
	 * @return void
	 */
	protected function get_settings () {
		# cache check
		if($this->settings == false) {
			try { $this->settings = $this->Database->getObject("settings", 1); }
			catch (Exception $e) { $this->Result->show("danger", _("Database error: ").$e->getMessage()); }
		}
	}

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
}