<?php

/**
 *	phpIPAM firewall zone management class
 *
 */

class FirewallZones extends Common_functions {

	/* variables */
	public $error = false;				// connection error string
	public $db_settings;				// (obj) db settings
	public $defaults;					// (obj) defaults settings
	// private $settings = false;			// (obj) settings

	public $limit;						// number of results
	public $orderby;					// order field
	public $orderdir;					// $order direction
	public $firewallZoneSettings;		// Settings
	// public $domain_types;				// (obj) types of domain
	// public $record_types;				// (obj) record types
	public $Log;						// for Logging connection

	/* objects */
	protected $Database;				// Database object - phpipam



	/**
	 * __construct method
	 *
	 * @access public
	 */
	public function __construct (Database_PDO $Database) {
		// initialize Result
		$this->Result = new Result ();
		// initialize object
		$this->Database = $Database;
		// Log object
		$this->Log = new Logging ($this->Database);
		// get settings
		$this->get_settings();
		// subnet object
		$this->Subnets = new Subnets ($this->Database);
	}

	/**
	 * convert zone name from decimal to hex (only for display reasons)
	 *
	 * @access public
	 * @param mixed $zone
	 * @return void
	 */
	public function zone2hex ($zone) {
		$firewallZoneSettings = json_decode($this->settings->firewallZoneSettings,true);
		if ($firewallZoneSettings['padding'] == 'on') {
			return str_pad(dechex($zone),$firewallZoneSettings['zoneLength'],"0",STR_PAD_LEFT);
		} else {
			return dechex($zone);
		}

	}

	/**
	 * Generate unique zone names by generator type
	 *
	 * @access public
	 * @param mixed $zone
	 * @param mixed $id
	 * @return void
	 */
	public function generate_zone_name ($values = NULL) {
		// get settings
		$firewallZoneSettings = json_decode($this->settings->firewallZoneSettings,true);
		// execute based on action
		if($firewallZoneSettings['zoneGenerator'] == 0 || $firewallZoneSettings['zoneGenerator'] == 1 ) {
			return $this->generate_numeric_zone_name ($firewallZoneSettings['zoneLength']);
		} elseif($firewallZoneSettings['zoneGenerator'] == 2 ) {
			return $this->validate_text_zone_name ($values);
		} else {
			return $this->Result->show("danger", _("Invalid generator ID"), true);
		}
	}

	/**
	 * Create decimal zone name
	 *
	 * @access private
	 * @param mixed $zoneLength
	 * @return void
	 */
	private function generate_numeric_zone_name ($zoneLength) {

		// execute
		try { $maxZone = $this->Database->getObjectsQuery('SELECT MAX(CAST(zone as UNSIGNED)) as zone FROM firewallZones WHERE generator NOT LIKE 2;');}
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}

		if ($maxZone[0]->zone) {
			// add 1 to the zone name
			$zoneName = ++$maxZone[0]->zone;

		} else {
			// set the initial zone name to "1"
			$zoneName = 1;
		}

		// return the values
		return sizeof($zoneName)>0 ? $zoneName : false;
	}

	/**
	 * validate text zone names
	 *
	 * @access private
	 * @param mixed $values
	 * @return void
	 */
	private function validate_text_zone_name ($values) {

		if($values[1]){
			$query = 'SELECT zone FROM firewallZones WHERE zone = ? AND id NOT LIKE ?;';
			$params = $values;
		} else {
			$query = 'SELECT zone FROM firewallZones WHERE zone = ?;';
			$params = $values[0];
		}

		// get settings
		$firewallZoneSettings = json_decode($this->settings->firewallZoneSettings,true);
		// execute
		try { $uniqueZone = $this->Database->getObjectsQuery($query,$params);}
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}

		if ($uniqueZone[0]->zone && $firewallZoneSettings['strictMode'] == 'on') {

			$this->Result->show("danger", _("Error: The zone name ".$zone." is not unique!"), false);

		} else {
			// set the initial zone name to "1"
			$zoneName = $values[0];
		}

		// return the values
		return sizeof($zoneName)>0 ? $zoneName : false;
	}

	/**
	 * Fetches zone mappings from database
	 *
	 * @access public
	 * @return void
	 */
	public function get_zone_mappings () {
		// try to fetch all mappings
		try { $mapping = $this->Database->getObjectsQuery('SELECT
						firewallZones.id AS id,
						firewallZones.generator AS generator,
						firewallZones.length AS length,
						firewallZones.padding AS padding,
						firewallZoneMapping.id AS mappingId,
						firewallZones.zone AS zone,
						firewallZones.indicator AS indicator,
						firewallZoneMapping.alias AS alias,
						firewallZones.description AS description,
						firewallZoneMapping.deviceId AS deviceId,
						devices.hostname AS deviceName,
						firewallZoneMapping.interface AS interface,
						firewallZones.subnetId AS subnetId,
						subnets.sectionId AS sectionId,
						subnets.subnet AS subnet,
						subnets.description AS subnetDescription,
						subnets.isFolder AS subnetIsFolder,
						subnets.mask AS subnetMask,
						firewallZones.vlanId AS vlanId,
						vlans.domainId AS domainId,
						vlans.number As vlan,
						vlans.name AS vlanName
						FROM firewallZoneMapping
						RIGHT JOIN firewallZones ON zoneId = firewallZones.id
						LEFT JOIN devices ON deviceId = devices.id
						LEFT JOIN subnets ON firewallZones.subnetId = subnets.id
						LEFT JOIN vlans ON firewallZones.vlanId = vlans.vlanId
						having  deviceId is not NULL order by firewallZones.id ASC;');}
		// throw exception
		catch (Exception $e) {
			$this->Result->show("danger", _("Database error: ").$e->getMessage());
		}
		// modify the zone output values
		foreach ($mapping as $key => $val) {
			// transform the zone name from decimal to hex
			if($mapping[$key]->generator == 1 ){
				$mapping[$key]->zone = dechex($mapping[$key]->zone);
			}

			// add some padding if it is activated and the zone generatore is not text
			if($mapping[$key]->padding == 1 && $mapping[$key]->generator != 2){
			// remove leading zeros (padding) and raise the value in case of any zone name length changes
			// add some padding to reach the maximum zone name lenght
			$mapping[$key]->zone = str_pad(ltrim($mapping[$key]->zone,0),$mapping[$key]->length,"0",STR_PAD_LEFT);
			}
		}
		// return the values
		return sizeof($mapping)>0 ? $mapping : false;
	}

	/**
	 * Fetches zone mapping from database, depending on id
	 *
	 * @access public
	 * @param mixid $id
	 * @return void
	 */
	public function get_zone_mapping ($id) {
		// try to fetch all mappings
		try { $mapping = $this->Database->getObjectsQuery('SELECT
						firewallZones.id AS id,
						firewallZones.generator AS generator,
						firewallZones.length AS length,
						firewallZones.padding AS padding,
						firewallZoneMapping.zoneId AS mappingId,
						firewallZones.zone AS zone,
						firewallZones.indicator AS indicator,
						firewallZoneMapping.alias AS alias,
						firewallZones.description AS description,
						firewallZoneMapping.deviceId AS deviceId,
						devices.hostname AS deviceName,
						firewallZoneMapping.interface AS interface,
						firewallZones.subnetId AS subnetId,
						subnets.subnet AS subnet,
						subnets.description AS subnetDescription,
						subnets.isFolder AS subnetIsFolder,
						subnets.mask AS subnetMask,
						firewallZones.vlanId AS vlanId,
						vlans.domainId AS domainId,
						vlans.number As vlan,
						vlans.name AS vlanName
						FROM firewallZoneMapping
						RIGHT JOIN firewallZones ON zoneId = firewallZones.id
						LEFT JOIN devices ON deviceId = devices.id
						LEFT JOIN subnets ON firewallZones.subnetId = subnets.id
						LEFT JOIN vlans ON firewallZones.vlanId = vlans.vlanId
						having  deviceId is not NULL AND mappingId = ?;',$id);}
		// throw exception
		catch (Exception $e) {
			$this->Result->show("danger", _("Database error: ").$e->getMessage());
		}
		// modify the zone output values
		if (sizeof($mapping)>0) {
			foreach ($mapping as $key => $val) {
				// transform the zone name from decimal to hex
				if($mapping[$key]->generator == 1 ){
					$mapping[$key]->zone = dechex($mapping[$key]->zone);
				}

				// add some padding if it is activated and the zone generatore is not text
				if($mapping[$key]->padding == 1 && $mapping[$key]->generator != 2){
				// remove leading zeros (padding) and raise the value in case of any zone name length changes
				// add some padding to reach the maximum zone name lenght
				$mapping[$key]->zone = str_pad(ltrim($mapping[$key]->zone,0),$mapping[$key]->length,"0",STR_PAD_LEFT);
				}
			}
		}
		// return the values
		return sizeof($mapping)>0 ? $mapping[0] : false;
	}

	/**
	 * Fetches all zones from database
	 *
	 * @access public
	 * @return void
	 */
	public function get_zones () {
		// try to fetch all mappings
		try { $zones =  $this->Database->getObjectsQuery('SELECT
						firewallZones.id AS id,
						firewallZones.generator AS generator,
						firewallZones.length AS length,
						firewallZones.padding AS padding,
						firewallZones.zone AS zone,
						firewallZones.indicator AS indicator,
						firewallZones.description AS description,
						firewallZones.permissions AS permissions,
						firewallZones.subnetId AS subnetId,
						subnets.sectionId AS sectionId,
						subnets.subnet AS subnet,
						subnets.mask AS subnetMask,
						subnets.description AS subnetDescription,
						subnets.isFolder AS subnetIsFolder,
						firewallZones.vlanId AS vlanId,
						vlans.domainId AS domainId,
						vlans.number As vlan,
						vlans.name AS vlanName
						FROM firewallZones
						LEFT JOIN subnets ON firewallZones.subnetId = subnets.id
						LEFT JOIN vlans ON firewallZones.vlanId = vlans.vlanId ORDER BY id ASC;');}
		// throw exception
		catch (Exception $e) {$this->Result->show("danger", _("Database error: ").$e->getMessage());}

		// modify the zone output values
		foreach ($zones as $key => $val) {
			// transform the zone name from decimal to hex
			if($zones[$key]->generator == 1 ){
				$zones[$key]->zone = dechex($zones[$key]->zone);
			}
			// add some padding if it is activated and the zone generatore is not text
			if($zones[$key]->padding == 1 && $zones[$key]->generator != 2){
			// remove leading zeros (padding) and raise the value in case of any zone name length changes
			// add some padding to reach the maximum zone name lenght
			$zones[$key]->zone = str_pad(ltrim($zones[$key]->zone,0),$zones[$key]->length,"0",STR_PAD_LEFT);
			}
		}
		// return the values
		return sizeof($zones)>0 ? $zones : false;
	}

	/**
	 * Fetches single zone from database, depending on zone id
	 *
	 * @access public
	 * @param mixid $id
	 * @return void
	 */
	public function get_zone ($id) {
		// try to fetch all mappings
		try { $zone = $this->Database->getObjectsQuery('SELECT
						firewallZones.id AS id,
						firewallZones.generator AS generator,
						firewallZones.length AS length,
						firewallZones.padding AS padding,
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
						HAVING id = ?;', $id);}
		// throw exception
		catch (Exception $e) {$this->Result->show("danger", _("Database error: ").$e->getMessage());}
		// modify the zone output values
		foreach ($zone as $key => $val) {
			// transform the zone name from decimal to hex
			if($zone[$key]->generator == 1 ){
				$zone[$key]->zone = dechex($zone[$key]->zone);
			}

			// add some padding if it is activated and the zone generatore is not text
			if($zone[$key]->padding == 1 && $zone[$key]->generator != 2){
			// remove leading zeros (padding) and raise the value in case of any zone name length changes
			// add some padding to reach the maximum zone name lenght
			$zone[$key]->zone = str_pad(ltrim($zone[$key]->zone,0),$zone[$key]->length,"0",STR_PAD_LEFT);
			}
		}
		// return the values
		return sizeof($zone)>0 ? $zone[0] : false;
	}


	/**
	 * display formated zone data
	 *
	 * @access public
	 * @param mixid $id
	 * @return void
	 */
	public function get_zone_detail ($id) {

		$zoneInformation = $this->get_zone($id);

		print '<table class="table table-condensed">';
		print '<tr>';
		print '<td>'._('Zone Name').'</td>';
		print '<td>'.$zoneInformation->zone.'</td>';
		print '</tr><tr>';
		print '<td>'._('Indicator').'</td>';
		if ($zoneInformation->indicator == 0) {
			print '<td><span class="fa fa-home"  title="'._('Own Zone').'"></span></td>';
		} else {
			print '<td><span class="fa fa-group" title="'._('Customer Zone').'"></span></td>';
		}
		print '</tr><tr>';
		print '<td>'._('Description').'</td>';
		print '<td>'.$zoneInformation->description.'</td>';
		print '</tr><tr>';
		print '<td>'._('Subnet').'</td>';
		if ($zoneInformation->subnetId) {
			if (!$zoneInformation->subnetIsFolder) {
				print '<td>'.$this->Subnets->transform_to_dotted($zoneInformation->subnet).'/'.$zoneInformation->subnetMask.'</td>';
				print '<td>'.$zoneInformation->subnetDescription.'</td>';
			} else{
				print '<td>'.$this->Subnets->transform_to_dotted($zoneInformation->subnet).'/'.$zoneInformation->subnetMask.'</td>';
				print '<td>Folder - '.$zoneInformation->subnetDescription.'</td>';
			}
		} else {
			print '</td><td>';
		}

		print '</tr><tr>';
		print '<td>'._('VLAN').'</td>';
		if ($zoneInformation->vlan) {
			print '<td>'.$zoneInformation->vlan.' ('.$zoneInformation->vlanName.')</td>';
		} else {
			print '</td><td>';
		}
		print '</table>';
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


		// initialize user
		$this->User = new User ($this->Database);

		// null empty values
		$values = $this->reformat_empty_array_fields ($values, null);

		// execute based on action
		if($action=="add")			{ return $this->zone_add ($values); }
		elseif($action=="edit")		{ return $this->zone_edit ($values); }
		elseif($action=="delete")	{ return $this->zone_delete ($values['id']); }
		else						{ return $this->Result->show("danger", _("Invalid action"), true); }
	}

	/**
	 * Create new zone method
	 *
	 * @access private
	 * @param mixed $values
	 * @return void
	 */
	private function zone_add ($values) {
		// get the settings
		$firewallZoneSettings = json_decode($this->settings->firewallZoneSettings,true);

		// push the zone name length into the values array
		$values['length'] = $firewallZoneSettings['zoneLength'];

		// execute
		try { $this->Database->insertObject("firewallZones", $values); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			$this->Log->write( "Firewall zone created", "Failed to add new firewall zone<hr>".$e->getMessage(), 2, $this->User->username);
			return false;
		}
		// ok
		$this->Log->write( "Firewall zone created", "New firewall zone created<hr>".$this->array_to_log($values), 0, $this->User->username);
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
			$this->Log->write( "Firewall zone edited", "Failed to edit firewall zone<hr>".$e->getMessage(), 2, $this->User->username);
			return false;
		}
		// ok
		$this->Log->write( "Firewall zone edited", "Firewall zone edited<hr>".$this->array_to_log($values), 0, $this->User->username);
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

		// delete mappings
		try { $this->Database->deleteRow("firewallZoneMapping", "zoneId", $id); }
		catch (Exception $e) {
			$this->Log->write( "Firewall zone and mappings delete", "Failed to delete firewall zone mappfings of $old_zone->zone<hr>".$e->getMessage(), 2, $this->User->username);
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}

		// delete zone
		try { $this->Database->deleteRow("firewallZones", "id", $id); }
		catch (Exception $e) {
			$this->Log->write( "Firewall zone delete", "Failed to delete firewall zone $old_zone->zone<hr>".$e->getMessage(), 2, $this->User->username);
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}
		// ok
		$this->Log->write( "Firewall zone deleted", "Firewall zone ".$old_zone->zone." deleted<hr>".$this->array_to_log($old_subnet), 0, $this->User->username);

		return true;
	}


	/**
	 * Modify mapping - main method
	 *
	 * @access public
	 * @param mixed $action
	 * @param mixed $values
	 * @return void
	 */
	public function modify_mapping ($action, $values) {
		// initialize user
		$this->User = new User ($this->Database);

		// null empty values
		$values = $this->reformat_empty_array_fields ($values, null);

		// execute based on action
		if($action=="add")			{ return $this->mapping_add ($values); }
		elseif($action=="edit")		{ return $this->mapping_edit ($values); }
		elseif($action=="delete")	{ return $this->mapping_delete ($values['id']); }
		else						{ return $this->Result->show("danger", _("Invalid action"), true); }
	}

	/**
	 * Create new mapping
	 *
	 * @access private
	 * @param mixed $values
	 * @return void
	 */
	private function mapping_add ($values) {
		// get the settings
		$firewallZoneSettings = json_decode($this->settings->firewallZoneSettings,true);

		// execute
		try { $this->Database->insertObject("firewallZoneMapping", $values); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			$this->Log->write( "Firewall zone mapping created", "Failed to add new firewall zone mapping<hr>".$e->getMessage(), 2, $this->User->username);
			return false;
		}
		// ok
		$this->Log->write( "Firewall zone mapping created", "New firewall zone mapping created<hr>".$this->array_to_log($values), 0, $this->User->username);
		return true;
	}

	/**
	 * Edit mapping
	 *
	 * @access private
	 * @param mixed $values
	 * @return void
	 */
	private function mapping_edit ($values) {
		// execute
		try { $this->Database->updateObject("firewallZoneMapping", $values, "id"); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			$this->Log->write( "Firewall zone mapping edited", "Failed to edit firewall zone mapping<hr>".$e->getMessage(), 2, $this->User->username);
			return false;
		}
		// ok
		$this->Log->write( "Firewall zone mapping edited", "Firewall zone mapping edited<hr>".$this->array_to_log($values), 0, $this->User->username);
		return true;
	}

	/**
	 * Deletes single mapping
	 *
	 * @access private
	 * @param mixed $id
	 * @return void
	 */
	private function mapping_delete ($id) {
		// save old values
		$old_mapping = $this->get_zone_mapping($id);

		// delete mapping
		try { $this->Database->deleteRow("firewallZoneMapping", "id", $id); }
		catch (Exception $e) {
			$this->Log->write( "Firewall zone mapping delete", "Failed to delete firewall zone mapping $old_zone->zone<hr>".$e->getMessage(), 2, $this->User->username);
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}
		// ok
		$this->Log->write( "Firewall zone mapping deleted", "Firewall zone mapping ".$old_zone->zone." deleted<hr>".$this->array_to_log($old_subnet), 0, $this->User->username);

		return true;
	}
}