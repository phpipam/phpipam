<?php

/**
 *	phpIPAM firewall zone management class
 *
 */

class FirewallZones extends Common_functions {

	/* variables */
	public $error = false;				// connection error string
	public $limit;						// number of results
	public $orderby;					// order field
	public $orderdir;					// $order direction
	public $firewallZoneSettings;		// Settings
	public $Log;						// for Logging connection

	/* objects */
	protected $Database;				// Database object - phpipam



	/**
	 * __construct method
	 *
	 * @access public
	 */
	public function __construct (Database_PDO $Database) {
		# initialize Result
		$this->Result = new Result ();
		# initialize object
		$this->Database = $Database;
		# Log object
		$this->Log = new Logging ($this->Database);
		# get settings
		$this->get_settings();
		# subnet object
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
	 * @param mixed $values
	 * @return void
	 */
	public function generate_zone_name ($values = NULL) {
		# get settings
		$firewallZoneSettings = json_decode($this->settings->firewallZoneSettings,true);
		# execute based on action
		if($firewallZoneSettings['zoneGenerator'] == 0 || $firewallZoneSettings['zoneGenerator'] == 1 ) {
			return $this->generate_numeric_zone_name ($firewallZoneSettings['zoneLength'],$firewallZoneSettings['zoneGenerator']);
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
	 * @param mixed $zoneGenerator
	 * @return void
	 */
	private function generate_numeric_zone_name ($zoneLength,$zoneGenerator) {

		# execute
		try { $maxZone = $this->Database->getObjectsQuery('SELECT MAX(CAST(zone as UNSIGNED)) as zone FROM firewallZones WHERE generator NOT LIKE 2;');}
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}

		if($maxZone[0]->zone) {
			# add 1 to the zone name
			$zoneName = ++$maxZone[0]->zone;
			if($zoneGenerator == 0 ) {
				if(strlen($zoneName) > $zoneLength) {
					return $this->Result->show("danger", _("Maximum zone name length reached! Consider to change your settings in order to generate larger zone names."), true);
				}
			} elseif($zoneGenerator == 1) {
				# the highest convertable integer value for dechex() is 4294967295!
				if($zoneName > 4294967295) {
					return $this->Result->show("danger", _("The maximum convertable vlaue is reached. Consider to switch to decimal or text mode and change the zone name length value."), true);
				}
				if(strlen(dechex($zoneName)) > $zoneLength){
					return $this->Result->show("danger", _("Maximum zone name length reached! Consider to change your settings in order to generate larger zone names."), true);
				}
			}

		} else {
			# set the initial zone name to "1"
			$zoneName = 1;
		}

		# return the values
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
		# get settings
		$firewallZoneSettings = json_decode($this->settings->firewallZoneSettings,true);

		if($values[1]){
			$query = 'SELECT zone FROM firewallZones WHERE zone = ? AND id NOT LIKE ?;';
			$params = $values;
		} else {
			$query = 'SELECT zone FROM firewallZones WHERE zone = ?;';
			$params = $values[0];
		}

		# execute
		try { $uniqueZone = $this->Database->getObjectsQuery($query,$params);}
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}

		if ($uniqueZone[0]->zone && $firewallZoneSettings['strictMode'] == 'on') {

			$this->Result->show("danger", _("Error: The zone name ".$zone." is not unique!"), false);

		} else {
			# set the initial zone name to "1"
			$zoneName = $values[0];
		}

		# return the values
		return sizeof($zoneName)>0 ? $zoneName : false;
	}


	/**
	 * Fetches zone mappings from database
	 *
	 * @access public
	 * @return void
	 */
	public function get_zone_mappings () {
		# try to fetch all zone mappings
		try { $mappings =  $this->Database->getObjectsQuery('SELECT
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
						devices.description AS deviceDescription,
						firewallZoneMapping.interface AS interface
						FROM firewallZoneMapping
						RIGHT JOIN firewallZones ON zoneId = firewallZones.id
						LEFT JOIN devices ON deviceId = devices.id
						having  deviceId is not NULL order by firewallZones.id ASC;');}
		# throw exception
		catch (Exception $e) {$this->Result->show("danger", _("Database error: ").$e->getMessage());}

		# try to fetch all subnet and vlan informations for all zones
		try { $networkInformation =  $this->Database->getObjectsQuery('SELECT
						firewallZoneSubnet.zoneId AS zoneId,
						firewallZoneSubnet.subnetId AS subnetId,
						subnets.sectionId AS sectionId,
						subnets.subnet AS subnet,
						subnets.mask AS subnetMask,
						subnets.description AS subnetDescription,
						subnets.isFolder AS subnetIsFolder,
						vlans.vlanId AS vlanId,
						vlans.domainId AS domainId,
						vlans.number AS vlan,
						vlans.name AS vlanName
						FROM firewallZoneSubnet
						LEFT JOIN subnets ON subnetId = subnets.id
						LEFT JOIN vlans ON subnets.vlanId = vlans.vlanId ORDER BY subnet ASC;');}
		# throw exception
		catch (Exception $e) {$this->Result->show("danger", _("Database error: ").$e->getMessage());}

		# modify the zone output values
		foreach ($mappings as $key => $val) {
			# transform the zone name from decimal to hex
			if($mappings[$key]->generator == 1 ){
				$mappings[$key]->zone = dechex($mappings[$key]->zone);
			}
			# add some padding if it is activated and the zone generatore is not text
			if($mappings[$key]->padding == 1 && $mappings[$key]->generator != 2){
			# remove leading zeros (padding) and raise the value in case of any zone name length changes
			# add some padding to reach the maximum zone name lenght
			$mappings[$key]->zone = str_pad(ltrim($mappings[$key]->zone,0),$mappings[$key]->length,"0",STR_PAD_LEFT);
			}
			# inject network informations
			foreach ($networkInformation as $nkey => $nval) {
				if($mappings[$key]->id == $nval->zoneId) {
					# add each network and vlan information to the object
					$mappings[$key]->network[] = $networkInformation[$nkey];
				}
			}
		}
		# return the values
		return sizeof($mappings)>0 ? $mappings : false;
	}


	/**
	 * Fetches zone mapping from database, depending on id
	 *
	 * @access public
	 * @param mixed $id
	 * @return void
	 */
	public function get_zone_mapping ($id) {
		# try to fetch id specific zone mapping
		try { $mapping =  $this->Database->getObjectsQuery('SELECT
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
						firewallZoneMapping.interface AS interface
						FROM firewallZoneMapping
						RIGHT JOIN firewallZones ON zoneId = firewallZones.id
						LEFT JOIN devices ON deviceId = devices.id
						having  deviceId is not NULL AND mappingId = ?;', $id);}
		# throw exception
		catch (Exception $e) {$this->Result->show("danger", _("Database error: ").$e->getMessage());}

		# try to fetch all subnet and vlan informations for all zones
		try { $networkInformation =  $this->Database->getObjectsQuery('SELECT
						firewallZoneSubnet.zoneId AS zoneId,
						firewallZoneSubnet.subnetId AS subnetId,
						subnets.sectionId AS sectionId,
						subnets.subnet AS subnet,
						subnets.mask AS subnetMask,
						subnets.description AS subnetDescription,
						subnets.isFolder AS subnetIsFolder,
						vlans.vlanId AS vlanId,
						vlans.domainId AS domainId,
						vlans.number AS vlan,
						vlans.name AS vlanName
						FROM firewallZoneSubnet
						LEFT JOIN subnets ON subnetId = subnets.id
						LEFT JOIN vlans ON subnets.vlanId = vlans.vlanId
						HAVING zoneId = ? ORDER BY subnet ASC;', $mapping[0]->id);}
		# throw exception
		catch (Exception $e) {$this->Result->show("danger", _("Database error: ").$e->getMessage());}

		# modify the zone output values
		foreach ($mapping as $key => $val) {
			# transform the zone name from decimal to hex
			if($mapping[$key]->generator == 1 ){
				$mapping[$key]->zone = dechex($mapping[$key]->zone);
			}
			# add some padding if it is activated and the zone generatore is not text
			if($mapping[$key]->padding == 1 && $mapping[$key]->generator != 2){
			# remove leading zeros (padding) and raise the value in case of any zone name length changes
			# add some padding to reach the maximum zone name lenght
			$mapping[$key]->zone = str_pad(ltrim($mapping[$key]->zone,0),$mapping[$key]->length,"0",STR_PAD_LEFT);
			}
			# inject network informations
			foreach ($networkInformation as $nkey => $nval) {
				if($mapping[$key]->id == $nval->zoneId) {
					# remove the zoneId, we don't need it anymore
					unset($networkInformation[$nkey]->zoneId);
					# add each network and vlan information to the object
					$mapping[$key]->network[] = $networkInformation[$nkey];
				}
			}
		}
		# return the values
		return sizeof($mapping)>0 ? $mapping[0] : false;
	}


	/**
	 * Fetches zone mapping informations for subnet detaul from database, depending on id
	 *
	 * @access public
	 * @param mixed $id
	 * @return void
	 */
	public function get_zone_subnet_info ($id) {
		# try to fetch id specific zone information
		try { $info =  $this->Database->getObjectsQuery('SELECT
						firewallZones.zone as zone,
						firewallZoneMapping.alias as alias,
						firewallZones.description as description,
						firewallZoneSubnet.subnetId,
						firewallZoneMapping.interface as interface,
						devices.hostname as deviceName
						FROM firewallZoneMapping
						RIGHT JOIN firewallZones on firewallZoneMapping.zoneId = firewallZones.id
						LEFT JOIN firewallZoneSubnet on firewallZoneMapping.zoneId = firewallZoneSubnet.zoneId
						LEFT JOIN devices ON deviceId = devices.id
						HAVING firewallZoneSubnet.subnetId = ?;', $id);}
		# throw exception
		catch (Exception $e) {$this->Result->show("danger", _("Database error: ").$e->getMessage());}
		# return the values
		return sizeof($info)>0 ? $info[0] : false;
	}


	/**
	 * Fetches all zones from database
	 *
	 * @access public
	 * @return void
	 */
	public function get_zones () {
		# try to fetch all zones
		try { $zones =  $this->Database->getObjectsQuery('SELECT * FROM firewallZones;');}
		# throw exception
		catch (Exception $e) {$this->Result->show("danger", _("Database error: ").$e->getMessage());}

		# try to fetch all subnet and vlan informations for all zones
		try { $networkInformation =  $this->Database->getObjectsQuery('SELECT
						firewallZoneSubnet.zoneId AS zoneId,
						firewallZoneSubnet.subnetId AS subnetId,
						subnets.sectionId AS sectionId,
						subnets.subnet AS subnet,
						subnets.mask AS subnetMask,
						subnets.description AS subnetDescription,
						subnets.isFolder AS subnetIsFolder,
						vlans.vlanId AS vlanId,
						vlans.domainId AS domainId,
						vlans.number AS vlan,
						vlans.name AS vlanName
						FROM firewallZoneSubnet
						LEFT JOIN subnets ON subnetId = subnets.id
						LEFT JOIN vlans ON subnets.vlanId = vlans.vlanId ORDER BY subnet ASC;');}
		# throw exception
		catch (Exception $e) {$this->Result->show("danger", _("Database error: ").$e->getMessage());}

		# modify the zone output values
		foreach ($zones as $key => $val) {
			# transform the zone name from decimal to hex
			if($zones[$key]->generator == 1 ){
				$zones[$key]->zone = dechex($zones[$key]->zone);
			}
			# add some padding if it is activated and the zone generatore is not text
			if($zones[$key]->padding == 1 && $zones[$key]->generator != 2){
			# remove leading zeros (padding) and raise the value in case of any zone name length changes
			# add some padding to reach the maximum zone name lenght
			$zones[$key]->zone = str_pad(ltrim($zones[$key]->zone,0),$zones[$key]->length,"0",STR_PAD_LEFT);
			}
			# inject network informations
			foreach ($networkInformation as $nkey => $nval) {
				if($zones[$key]->id == $nval->zoneId) {
					# remove the zoneId, we don't need it anymore
					unset($networkInformation[$nkey]->zoneId);
					# add each network and vlan information to the object
					$zones[$key]->network[] = $networkInformation[$nkey];
				}
			}
		}
		# return the values
		return sizeof($zones)>0 ? $zones : false;
	}


	/**
	 * Fetches single zone from database, depending on zone id
	 *
	 * @access public
	 * @param mixed $id
	 * @return void
	 */
	public function get_zone ($id) {
		# try to fetch zone with ID $id
		try { $zone = $this->Database->getObjectsQuery('SELECT * FROM firewallZones WHERE id = ?;', $id);}

		# throw exception
		catch (Exception $e) {$this->Result->show("danger", _("Database error: ").$e->getMessage());}

		# try to fetch all subnet and vlan informations for this zone
		try { $networkInformation =  $this->Database->getObjectsQuery('SELECT
						firewallZoneSubnet.zoneId AS zoneId,
						firewallZoneSubnet.subnetId AS subnetId,
						subnets.sectionId AS sectionId,
						subnets.subnet AS subnet,
						subnets.mask AS subnetMask,
						subnets.description AS subnetDescription,
						subnets.isFolder AS subnetIsFolder,
						vlans.vlanId AS vlanId,
						vlans.domainId AS domainId,
						vlans.number AS vlan,
						vlans.name AS vlanName
						FROM firewallZoneSubnet
						LEFT JOIN subnets ON subnetId = subnets.id
						LEFT JOIN vlans ON subnets.vlanId = vlans.vlanId HAVING zoneId = ? ORDER BY subnet ASC;', $id);}
		# throw exception
		catch (Exception $e) {$this->Result->show("danger", _("Database error: ").$e->getMessage());}

		# modify the zone output values
		foreach ($zone as $key => $val) {
			# transform the zone name from decimal to hex
			if($zone[$key]->generator == 1 ){
				$zone[$key]->zone = dechex($zone[$key]->zone);
			}
			# add some padding if it is activated and the zone generatore is not text
			if($zone[$key]->padding == 1 && $zone[$key]->generator != 2){
			# remove leading zeros (padding) and raise the value in case of any zone name length changes
			# add some padding to reach the maximum zone name lenght
			$zone[$key]->zone = str_pad(ltrim($zone[$key]->zone,0),$zone[$key]->length,"0",STR_PAD_LEFT);
			}
			# inject network informations
			foreach ($networkInformation as $nkey => $nval) {
				if($zone[$key]->id == $nval->zoneId) {
					# remove the zoneId, we don't need it anymore
					unset($networkInformation[$nkey]->zoneId);
					# add each network and vlan information to the object
					$zone[$key]->network[] = $networkInformation[$nkey];
				}
			}
		}

		# return the values
		return sizeof($zone)>0 ? $zone[0] : false;
	}


	/**
	 * display formated zone data
	 *
	 * @access public
	 * @param mixed $id
	 * @return void
	 */
	public function get_zone_detail ($id) {
		# get zone informations
		$zoneInformation = $this->get_zone($id);

		# build html output
		print '<table class="table table-auto table-condensed" style="margin-bottom:0px;">';
		print "<tr><td colspan='2'><h4>Zone details</h4><hr></td></tr>";
		print '<tr>';
		print '<td style="width:110px;">'._('Zone Name').'</td>';
		print '<td>'.$zoneInformation->zone.'</td>';
		print '</tr>';
		print '<tr>';
		print '<td>'._('Indicator').'</td>';
		$title = $zoneInformation->indicator == 0 ? 'Own Zone' : 'Customer Zone';
		print '<td><span class="fa fa-home"  title="'._($title).'"></span></td>';
		print '</tr>';
		print '<tr>';
		print '<td>'._('Description').'</td>';
		print '<td>'.$zoneInformation->description.'</td>';
		print '</tr>';
		print "</table>";
		// networks
		if ($zoneInformation->network) {
			print "<table class='table table-condensed' style='margin-bottom:30px;'>";
			print "<tr><td colspan='2'><br><h4>Subnets</h4><hr></td></tr>";
			print '<tr>';
			print '<th>'._('Subnet').'</th>';
			print '<th>'._('VLAN').'</th>';
			print '</tr>';
			foreach ($zoneInformation->network as $network) {
				print '<tr>';
				// description fix
				$network->subnetDescription = $network->subnetDescription ? " (".$network->subnetDescription.")" : "/";
				$network->vlanName = $network->vlanName ? "(".$network->vlanName.")" : "";

				if (!$network->subnetIsFolder) {
					print '<td>'.$this->Subnets->transform_to_dotted($network->subnet).'/'.$network->subnetMask.$network->subnetDescription.'</td>';
				}
				else{
					print '<td>Folder '.$network->subnetDescription.'</td>';
				}
				print '<td>'.$network->vlan.$network->vlanName.'</td>';
				print '</tr>';
			}
		}
	}


	/**
	 * display formated zone network(s)
	 *
	 * @access public
	 * @param mixed $id
	 * @return void
	 */
	public function get_zone_network ($id) {
		# try to fetch all subnet and vlan informations for this zone
		try { $networkInformation =  $this->Database->getObjectsQuery('SELECT
						firewallZoneSubnet.zoneId AS zoneId,
						firewallZoneSubnet.subnetId AS subnetId,
						subnets.sectionId AS sectionId,
						subnets.subnet AS subnet,
						subnets.mask AS subnetMask,
						subnets.description AS subnetDescription,
						subnets.isFolder AS subnetIsFolder,
						vlans.vlanId AS vlanId,
						vlans.domainId AS domainId,
						vlans.number AS vlan,
						vlans.name AS vlanName
						FROM firewallZoneSubnet
						LEFT JOIN subnets ON subnetId = subnets.id
						LEFT JOIN vlans ON subnets.vlanId = vlans.vlanId HAVING zoneId = ? ORDER BY subnet ASC;', $id);}
		# throw exception
		catch (Exception $e) {$this->Result->show("danger", _("Database error: ").$e->getMessage());}

		$rowspan = count($networkInformation);
		$i = 1;
		print '<table class="table table-noborder table-condensed" style="padding-bottom:20px;">';
		foreach ($networkInformation as $network) {
			print '<tr>';
			if ($i === 1) {
				print '<td rowspan="'.$rowspan.'" style="width:150px;">Network</td>';
			}
			print '<td>';
			print '<a class="btn btn-xs btn-danger editNetwork" style="margin-right:5px;" alt="'._('Delete Network').'" title="'._('Delete Network').'" data-action="delete" data-zoneId="'.$id.'" data-subnetId="'.$network->subnetId.'">';
			print '<span><i class="fa fa-close"></i></span>';
			print "</a>";

			if ($network->subnetIsFolder == 1 ) {
				print 'Folder: '.$network->subnetDescription;
			} else {
				# display network information with or without description
				if ($network->subnetDescription) 	{	print $this->Subnets->transform_to_dotted($network->subnet).'/'.$network->subnetMask.' ('.$network->subnetDescription.')</td>';	}
				else 								{	print $this->Subnets->transform_to_dotted($network->subnet).'/'.$network->subnetMask.'</td>';	}
			}
			print '</tr>';
			$i++;
		}
		print '</table>';
	}


	/**
	 * validate if a network is suitable to map to a zone
	 *
	 * @access public
	 * @param mixed $subnetId
	 * @return void
	 */
	public function check_zone_network ($subnetId) {
		# check if the subnet is already bound to this or any other zone
		try { $networkInformation =  $this->Database->getObjectsQuery('SELECT * FROM firewallZoneSubnet WHERE subnetId = ?;', $subnetId);}

		# throw exception
		catch (Exception $e) {$this->Result->show("danger", _("Database error: ").$e->getMessage());}

		if(!sizeof($networkInformation)>0 ) {
			# return dummy value
			return 'success';
		} else {
			$this->Result->show("danger","<strong>"._('Error').":</strong><br>"._("This network is already bound to this or another zone.<br>The binding must be unique."), false);
			return false;
		}
		# return dummy value
		return 'success';
	}


	/**
	 * add a network to a zone
	 *
	 * @access public
	 * @param mixed $zoneId
	 * @param mixed $subnetId
	 * @return void
	 */
	public function add_zone_network ($zoneId,$subnetId) {
		# check if the subnet is already bound to this or any other zone
		try { $networkInformation =  $this->Database->getObjectsQuery('SELECT * FROM firewallZoneSubnet WHERE subnetId = ?;', $subnetId);}

		# throw exception
		catch (Exception $e) {$this->Result->show("danger", _("Database error: ").$e->getMessage());}

		if(!sizeof($networkInformation)>0 ) {
			$query = 'INSERT INTO firewallZoneSubnet (zoneId, subnetId) VALUES (?,?);';
			$params = array('zoneId' => $zoneId, 'subnetId' => $subnetId);

			# try to fetch all subnet and vlan informations for this zone
			try { $addRow =  $this->Database->insertObject("firewallZoneSubnet", $params);}

			# throw exception
			catch (Exception $e) {$this->Result->show("danger", _("Database error: ").$e->getMessage());}
		} else {
			$this->Result->show("danger","<strong>"._('Error').":</strong><br>"._("This network is already bound to this or another zone.<br>The binding must be unique."), false);
			return false;
		}
		# return dummy value
		return 'success';
	}


	/**
	 * delete a network of a zone
	 *
	 * @access public
	 * @param mixed $zoneId
	 * @param mixed $subnetId
	 * @return void
	 */
	public function delete_zone_network ($zoneId,$subnetId) {
		# try to fetch all subnet and vlan informations for this zone
		try { $deleteRow =  $this->Database->deleteRow("firewallZoneSubnet", "zoneId", $zoneId, "subnetId", $subnetId); }

		# throw exception
		catch (Exception $e) {$this->Result->show("danger", _("Database error: ").$e->getMessage());}

		# return dummy value or false
		if ($deleteRow) {
			return 'success';
		} else {
			return false;
		}
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
		# initialize user
		$this->User = new User ($this->Database);

		# separate network informations if available
		$network = $values['network'];
		unset($values['network']);

		# null empty values
		$values = $this->reformat_empty_array_fields ($values, null);
		if ($network) {
			$network = $this->reformat_empty_array_fields ($network, null);
		}

		# execute based on action
		if($action=="add")			{ return $this->zone_add ($values,$network); }
		elseif($action=="edit")		{ return $this->zone_edit ($values); }
		elseif($action=="delete")	{ return $this->zone_delete ($values['id']); }
		else						{ return $this->Result->show("danger", _("Invalid action"), true); }
	}


	/**
	 * Create new zone method
	 *
	 * @access private
	 * @param mixed $values
	 * @param mixed $network
	 * @return void
	 */
	private function zone_add ($values,$network) {
		# get the settings
		$firewallZoneSettings = json_decode($this->settings->firewallZoneSettings,true);

		# push the zone name length into the values array
		$values['length'] = $firewallZoneSettings['zoneLength'];

		# execute insert
		try { $this->Database->insertObject("firewallZones", $values); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			$this->Log->write( "Firewall zone created", "Failed to add new firewall zone<hr>".$e->getMessage(), 2, $this->User->username);
			return false;
		}

		# fetch the highest inserted id, matching the zone name
		try { $lastId=$this->Database->getObjectsQuery("SELECT MAX(id) AS id FROM firewallZones WHERE zone = ? ;", $values['zone']);}
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}

		if ($network) {
			foreach ($network as $subnetId) {
				$values = array('zoneId' => $lastId[0]->id, 'subnetId' => $subnetId);
				# add the network bindings if there are any
				try { $this->Database->insertObject("firewallZoneSubnet", $values); }
				catch (Exception $e) {
					$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
					return false;
				}
			}
			# ok
			return true;
		}
		# ok
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
		# execute
		try { $this->Database->updateObject("firewallZones", $values, "id"); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			$this->Log->write( "Firewall zone edited", "Failed to edit firewall zone<hr>".$e->getMessage(), 2, $this->User->username);
			return false;
		}
		# ok
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
		# save old values
		$old_zone = $this->get_zone($id);

		# delete mappings
		try { $this->Database->deleteRow("firewallZoneMapping", "zoneId", $id); }
		catch (Exception $e) {
			$this->Log->write( "Firewall zone and mappings delete", "Failed to delete firewall zone mappfings of $old_zone->zone<hr>".$e->getMessage(), 2, $this->User->username);
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}

		# delete zone
		try { $this->Database->deleteRow("firewallZones", "id", $id); }
		catch (Exception $e) {
			$this->Log->write( "Firewall zone delete", "Failed to delete firewall zone $old_zone->zone<hr>".$e->getMessage(), 2, $this->User->username);
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}
		# ok
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
		# initialize user
		$this->User = new User ($this->Database);

		# null empty values
		$values = $this->reformat_empty_array_fields ($values, null);

		# execute based on action
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
		# get the settings
		$firewallZoneSettings = json_decode($this->settings->firewallZoneSettings,true);

		# execute
		try { $this->Database->insertObject("firewallZoneMapping", $values); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			$this->Log->write( "Firewall zone mapping created", "Failed to add new firewall zone mapping<hr>".$e->getMessage(), 2, $this->User->username);
			return false;
		}
		# ok
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
		# execute
		try { $this->Database->updateObject("firewallZoneMapping", $values, "id"); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			$this->Log->write( "Firewall zone mapping edited", "Failed to edit firewall zone mapping<hr>".$e->getMessage(), 2, $this->User->username);
			return false;
		}
		# ok
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
		# save old values
		$old_mapping = $this->get_zone_mapping($id);

		# delete mapping
		try { $this->Database->deleteRow("firewallZoneMapping", "id", $id); }
		catch (Exception $e) {
			$this->Log->write( "Firewall zone mapping delete", "Failed to delete firewall zone mapping $old_zone->zone<hr>".$e->getMessage(), 2, $this->User->username);
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}
		# ok
		$this->Log->write( "Firewall zone mapping deleted", "Firewall zone mapping ".$old_zone->zone." deleted<hr>".$this->array_to_log($old_subnet), 0, $this->User->username);

		return true;
	}

}