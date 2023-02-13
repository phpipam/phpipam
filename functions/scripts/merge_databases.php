<?php

/**
 * This script will import data from other phpipam to this phpipam
 *
 * Please note:
 * 		- Database versions must match exactly !
 *   	- Following tables are not merged:
 *    		- "lang"
 *      	- "loginAttempts"
 *       	- "instructions"
 *        	- "settings"
 *         	- "settingsDomain"
 *          - "settingsMail"
 *          - "widgets"
 *          - "deviceTypes"
 *          - "requests"
 *
 */

// script can only be run from cli
if(php_sapi_name()!="cli") 						{ die("This script can only be run from cli!"); }

# include required scripts
require_once( dirname(__FILE__) . '/../functions.php' );

# New database details
$db_new['host'] = "localhost";
$db_new['user'] = "phpipam";
$db_new['pass'] = "phpipam_password";
$db_new['name'] = "phpipam_1";
$db_new['port'] = 3306;

# Old database details
$db_old['host'] = "localhost";
$db_old['user'] = "phpipam";
$db_old['pass'] = "phpipam_password";
$db_old['name'] = "phpipam_2";
$db_old['port'] = 3306;




# initialize new objects
$Database     = new Database_PDO ($db_new['user'], $db_new['pass'], $db_new['host'], $db_new['port'], $db_new['name']);
$Tools        = new Tools ($Database);
$Admin        = new Admin ($Database, false);
$Result		  = new Result ();

# initialize objects from merging (old) database
$Database_old = new Database_PDO ($db_old['user'], $db_old['pass'], $db_old['host'], $db_old['port'], $db_old['name']);
$Tools_old    = new Tools ($Database_old);


// fetch and check settings
$settings     = $Tools->get_settings();
$settings_old = $Tools_old->get_settings();

if($settings->version !== $settings_old->version)	{ $Result->show("danger", "Versions do not match ($settings->version vs $settings_old->version) !"); }



/**
 * Setting special parameters, arrays etc
 */

// set special identifiers
$special_identifiers = array (
								"changelog"          =>"cid",
								"deviceTypes"        =>"tid",
								"firewallZoneSubnet" =>"zoneId",
								"userGroups"         =>"g_id",
								"vlans"              =>"vlanId",
								"vrf"                =>"vrfId",
								"widgets"            =>"wid"
                              );
// ignored tables
$ignored_tables = array("lang", "loginAttempts", "instructions", "settings", "settingsMail", "widgets", "deviceTypes", "requests", "changelog", "logs", "firewallZoneMapping", "firewallZones", "firewallZoneSubnet");
// result array
$highest_ids        = array();		// current highest ids
$highest_ids_old    = array();		// new highest ids
$highest_ids_append = array();		// diff, to append to importing indexes
$old_data			= array();		// old content data
$new_data			= array();		// old content data - we will change those values



/**
 * We fetch all tables, check highest ids and save them to array
 */

// fetch all tables
$tables = $Tools->fetch_standard_tables ();
// loop and set highest ids
foreach ($tables as $table) {
	// ignored databases
	if(!in_array($table, $ignored_tables)) {
		// set id
		$identifier = array_key_exists($table, $special_identifiers) ? $special_identifiers[$table] : "id";
		// fetch old and new
		$highest_id     = $Database->getObjectQuery     ("SELECT `$identifier` FROM `$table` ORDER BY `$identifier` DESC LIMIT 0, 1;");
		$lowest_id_old  = $Database_old->getObjectQuery ("SELECT `$identifier` FROM `$table` ORDER BY `$identifier` ASC LIMIT 0, 1;");

		// only if something is present in merging table, otherwise no need to import !
		if($lowest_id_old->{$identifier} != "") {
			$highest_ids[$table]        = $highest_id->{$identifier};
			$highest_ids_old[$table]    = $lowest_id_old->{$identifier};
			$highest_ids_append[$table] = $highest_id->{$identifier} +1 - $lowest_id_old->{$identifier};

			// fetch and save all data
			$old_data[$table] = $Database_old->getObjectsQuery ("SELECT * FROM `$table` ORDER BY `$identifier` ASC;");
			$new_data[$table] = $Database_old->getObjectsQuery ("SELECT * FROM `$table` ORDER BY `$identifier` ASC;");
		}

		// fetch custom fields
		$cfields_old = $Tools_old->fetch_custom_fields ($table);
		$cfields 	 = $Tools->fetch_custom_fields ($table);
		if(sizeof($cfields_old)>0) {
			foreach ($cfields_old as $k=>$cf) {
				if(!array_key_exists($k, $cfields)) {
					$new_custom_fields[$table] = $cfields_old;
				}
			}
		}
	}
}


// go through all old data and add diff to indexes indexes
foreach ($old_data as $table => $table_content) {
	// update sections
	if ($table == "sections") {
		// go through each table and update
		foreach ($table_content as $lk=>$value_obj) {
			$new_data[$table][$lk]->id    = $highest_ids_append[$table] + $value_obj->id;
			$new_data[$table][$lk]->order = $highest_ids_append[$table] + $value_obj->order;
			// sections
			if ($value_obj->masterSection > 0) {
				$new_data[$table][$lk]->masterSection = $highest_ids_append["sections"] + $value_obj->masterSection;
			}
			// permissions
			if(!is_blank($value_obj->permissions) && $value_obj->permissions!="null") {
				$permissions = pf_json_decode($value_obj->permissions);
				$permissions_new = new StdClass ();
				foreach ($permissions as $k=>$v) {
					$permissions_new->{$highest_ids_append["userGroups"] + $k} = $v;
				}
				$new_data[$table][$lk]->permissions = json_encode($permissions_new);
			}
		}
	}
	// update subnets
	elseif ($table == "subnets") {
		// go through each table and update
		foreach ($table_content as $lk=>$value_obj) {
			$new_data[$table][$lk]->id        = $highest_ids_append[$table] + $value_obj->id;
			$new_data[$table][$lk]->sectionId = $highest_ids_append["sections"] + $value_obj->sectionId;
			// master subnet
			if ($value_obj->masterSubnetId > 0) {
				$new_data[$table][$lk]->masterSubnetId = $highest_ids_append["subnets"] + $value_obj->masterSubnetId;
			}
			// linked subnet
			if ($value_obj->linked_subnet > 0) {
				$new_data[$table][$lk]->linked_subnet = $highest_ids_append["subnets"] + $value_obj->linked_subnet;
			}
			// vlan
			if ($value_obj->vlanId > 0) {
				$new_data[$table][$lk]->vlanId = $highest_ids_append["vlans"] + $value_obj->vlanId;
			}
			// vrf
			if ($value_obj->vrfId > 0) {
				$new_data[$table][$lk]->vrfId = $highest_ids_append["vrf"] + $value_obj->vrfId;
			}
			// device
			if ($value_obj->device > 0) {
				$new_data[$table][$lk]->device = $highest_ids_append["devices"] + $value_obj->device;
			}
			// ns
			if ($value_obj->nameserverId > 0) {
				$new_data[$table][$lk]->nameserverId = $highest_ids_append["nameservers"] + $value_obj->nameserverId;
			}
			// location
			if ($value_obj->location > 0) {
				$new_data[$table][$lk]->location = $highest_ids_append["locations"] + $value_obj->location;
			}
			// scanAgent
			if ($value_obj->scanAgent > 1) {
				$new_data[$table][$lk]->scanAgent = $highest_ids_append["scanAgents"] + $value_obj->scanAgent;
			}
			// permissions
			if(!is_blank($value_obj->permissions) && $value_obj->permissions!="null") {
				$permissions = pf_json_decode($value_obj->permissions);
				$permissions_new = new StdClass ();
				foreach ($permissions as $k=>$v) {
					$permissions_new->{$highest_ids_append["userGroups"] + $k} = $v;
				}
				$new_data[$table][$lk]->permissions = json_encode($permissions_new);
			}
		}
	}
	// update ipaddresses
	elseif ($table == "ipaddresses") {
		// go through each table and update
		foreach ($table_content as $lk=>$value_obj) {
			$new_data[$table][$lk]->id        = $highest_ids_append[$table] + $value_obj->id;
			$new_data[$table][$lk]->subnetId  = $highest_ids_append["subnets"] + $value_obj->subnetId;
			// state
			if ($value_obj->state > 4) {
				$new_data[$table][$lk]->state = $highest_ids_append["ipTags"] + $value_obj->state;
			}
			// device
			if ($value_obj->switch > 0) {
				$new_data[$table][$lk]->switch = $highest_ids_append["devices"] + $value_obj->switch;
			}
		}
	}
	// update devices
	elseif ($table == "devices") {
		// go through each table and update
		foreach ($table_content as $lk=>$value_obj) {
			$new_data[$table][$lk]->id = $highest_ids_append[$table] + $value_obj->id;
			// type
			if ($value_obj->type > 9) {
				$new_data[$table][$lk]->type = $highest_ids_append["deviceTypes"] + $value_obj->type;
			}
			// sections
			if(strlen($value_obj->sections)>1) {
				$sections     = pf_explode(";", $value_obj->sections);
				$sections_new = array();
				foreach ($sections as $k=>$v) {
					$sections_new[$highest_ids_append["sections"] + $k] = $v;
				}
				$new_data[$table][$lk]->sections = implode(";", $sections_new);
			}
			// rack
			if ($value_obj->rack > 0) {
				$new_data[$table][$lk]->rack = $highest_ids_append["racks"] + $value_obj->rack;
			}
			// location
			if ($value_obj->location > 0) {
				$new_data[$table][$lk]->location = $highest_ids_append["locations"] + $value_obj->location;
			}
		}
	}
	// update userGroups
	elseif ($table == "userGroups") {
		// go through each table and update
		foreach ($table_content as $lk=>$value_obj) {
			$new_data[$table][$lk]->g_id = $highest_ids_append[$table] + $value_obj->g_id;
		}
	}
	// update users
	elseif ($table == "users") {
		// go through each table and update
		foreach ($table_content as $lk=>$value_obj) {
			// make sure it doesnt exist already !
			if($Database->numObjectsFilter("users", "username", $value_obj->username)==0 && $Database->numObjectsFilter("users", "email", $value_obj->email)==0) {
				$new_data[$table][$lk]->id = $highest_ids_append[$table] + $value_obj->id;
				// authmethod
				if($value_obj->authMethod>2) {
					$new_data[$table][$lk]->authMethod = $highest_ids_append["usersAuthMethod"] + $value_obj->authMethod;
				}
				// groups
				if($value_obj->role!="Administrator") {
					$groups_tmp = pf_json_decode($value_obj->groups, true);
					$groups_new = array();
					foreach ($groups_tmp as $gid=>$gid2) {
						$groups_new[$gid+$highest_ids_append["userGroups"]] = $gid+$highest_ids_append["userGroups"];
					}
					$new_data[$table][$lk]->groups = json_encode($groups_new);
				}
				// favourite subnets
				if(!is_blank($value_obj->favourite_subnets)) {
					$fs_tmp = pf_explode(";", $value_obj->favourite_subnets);
					$fs_new = array();
					foreach ($fs_tmp as $gid) {
						$fs_new[] = $gid+$highest_ids_append["subnets"];
					}
					$new_data[$table][$lk]->favourite_subnets = implode(";", $fs_new);
				}
			}
			else {
				unset($new_data[$table][$lk]);
			}
		}
	}
	// update vlans
	elseif ($table == "vlans") {
		// go through each table and update
		foreach ($table_content as $lk=>$value_obj) {
			$new_data[$table][$lk]->vlanId   = $highest_ids_append[$table] + $value_obj->vlanId;
			$new_data[$table][$lk]->domainId = $highest_ids_append["vlanDomains"] + $value_obj->domainId;
		}
	}
	// update vlanDomains
	elseif ($table == "vlanDomains") {
		// go through each table and update
		foreach ($table_content as $lk=>$value_obj) {
			$new_data[$table][$lk]->id = $highest_ids_append[$table] + $value_obj->id;
			// permissions
			if(!is_blank($value_obj->permissions)) {
				$fs_tmp = pf_explode(";", $value_obj->permissions);
				$fs_new = array();
				foreach ($fs_tmp as $gid) {
					$fs_new[] = $gid+$highest_ids_append["sections"];
				}
				$new_data[$table][$lk]->permissions = implode(";", $fs_new);
			}
		}
	}
	// update vrf
	elseif ($table == "vrf") {
		// go through each table and update
		foreach ($table_content as $lk=>$value_obj) {
			$new_data[$table][$lk]->vrfId = $highest_ids_append[$table] + $value_obj->vrfId;
			// sections
			if(!is_blank($value_obj->sections)) {
				$fs_tmp = pf_explode(";", $value_obj->sections);
				$fs_new = array();
				foreach ($fs_tmp as $gid) {
					$fs_new[] = $gid+$highest_ids_append["sections"];
				}
				$new_data[$table][$lk]->sections = implode(";", $fs_new);
			}
		}
	}
	// update nameservers
	elseif ($table == "nameservers") {
		// go through each table and update
		foreach ($table_content as $lk=>$value_obj) {
			$new_data[$table][$lk]->id = $highest_ids_append[$table] + $value_obj->id;
			// permissions
			if(!is_blank($value_obj->permissions)) {
				$fs_tmp = pf_explode(";", $value_obj->permissions);
				$fs_new = array();
				foreach ($fs_tmp as $gid) {
					$fs_new[] = $gid+$highest_ids_append["sections"];
				}
				$new_data[$table][$lk]->permissions = implode(";", $fs_new);
			}
		}
	}
	// update api
	elseif ($table == "api") {
		// go through each table and update
		foreach ($table_content as $lk=>$value_obj) {
			$new_data[$table][$lk]->id = $highest_ids_append[$table] + $value_obj->id;
		}
	}
	// update vlans
	elseif ($table == "vlans") {
		// go through each table and update
		foreach ($table_content as $lk=>$value_obj) {
			$new_data[$table][$lk]->id = $highest_ids_append[$table] + $value_obj->id;
		}
	}
	// update deviceTypes
	elseif ($table == "deviceTypes") {
		// go through each table and update
		foreach ($table_content as $lk=>$value_obj) {
			// more than default
			if($value_obj->tid > 12) {
				$new_data[$table][$lk]->tid = $highest_ids_append[$table] + $value_obj->tid;
			}
			else {
				unset($new_data[$table][$lk]);
			}
		}
	}
	// update usersAuthMethod
	elseif ($table == "usersAuthMethod") {
		// go through each table and update
		foreach ($table_content as $lk=>$value_obj) {
			// more than default
			if($value_obj->id > 2) {
				$new_data[$table][$lk]->id = $highest_ids_append[$table] + $value_obj->id;
			}
			else {
				unset($new_data[$table][$lk]);
			}
		}
	}
	// update ipTags
	elseif ($table == "ipTags") {
		// go through each table and update
		foreach ($table_content as $lk=>$value_obj) {
			// more than default
			if($value_obj->id > 4) {
				$new_data[$table][$lk]->id = $highest_ids_append[$table] + $value_obj->id;
			}
			else {
				unset($new_data[$table][$lk]);
			}
		}
	}
	// update scanAgents
	elseif ($table == "scanAgents") {
		// go through each table and update
		foreach ($table_content as $lk=>$value_obj) {
			// more than default
			if($value_obj->id > 1) {
				$new_data[$table][$lk]->id = $highest_ids_append[$table] + $value_obj->id;
			}
			else {
				unset($new_data[$table][$lk]);
			}
		}
	}
	// update nat
	elseif ($table == "nat") {
		// go through each table and update
		foreach ($table_content as $lk=>$value_obj) {
			$new_data[$table][$lk]->id = $highest_ids_append[$table] + $value_obj->id;
			// src
			if (!is_blank($value_obj->src)) {
				$arr     = json_encode($value_obj->src, true);
				$arr_new = array();
				if (is_array($arr)) {
					foreach ($arr as $type=>$objects) {
						$arr_new[$type] = array();
						if(sizeof($objects)>0) {
							foreach($objects as $ok=>$object) {
								$arr_new[$type][] = $highest_ids_append[$type] + $object;
							}
						}
					}
				}
				$new_data[$table][$lk]->src = json_encode($arr_new);
			}
			// dst
			if (!is_blank($value_obj->dst)) {
				$arr     = json_encode($value_obj->dst, true);
				$arr_new = array();
				if (is_array($arr)) {
					foreach ($arr as $type=>$objects) {
						$arr_new[$type] = array();
						if(sizeof($objects)>0) {
							foreach($objects as $ok=>$object) {
								$arr_new[$type][] = $highest_ids_append[$type] + $object;
							}
						}
					}
				}
				$new_data[$table][$lk]->dst = json_encode($arr_new);
			}
			// device
			if($value_obj->device > 0) {
				$value_obj->device = $highest_ids_append["devices"] + $value_obj->device;
			}
		}
	}
	// update racks
	elseif ($table == "racks") {
		// go through each table and update
		foreach ($table_content as $lk=>$value_obj) {
			$new_data[$table][$lk]->id = $highest_ids_append[$table] + $value_obj->id;
		}
	}
	// update locations
	elseif ($table == "locations") {
		// go through each table and update
		foreach ($table_content as $lk=>$value_obj) {
			$new_data[$table][$lk]->id = $highest_ids_append[$table] + $value_obj->id;
		}
	}
	// update pstnPrefixes
	elseif ($table == "pstnPrefixes") {
		// go through each table and update
		foreach ($table_content as $lk=>$value_obj) {
			$new_data[$table][$lk]->id       = $highest_ids_append[$table] + $value_obj->id;
			$new_data[$table][$lk]->deviceId = $highest_ids_append["devices"] + $value_obj->deviceId;
		}
	}
	// update pstnPrefixes
	elseif ($table == "pstnNumbers") {
		// go through each table and update
		foreach ($table_content as $lk=>$value_obj) {
			$new_data[$table][$lk]->id       = $highest_ids_append[$table] + $value_obj->id;
			$new_data[$table][$lk]->prefix   = $highest_ids_append["pstnPrefixes"] + $value_obj->prefix;
			$new_data[$table][$lk]->deviceId = $highest_ids_append["devices"] + $value_obj->deviceId;
			$new_data[$table][$lk]->state    = $highest_ids_append["ipTags"] + $value_obj->state;
		}
	}
}


// create new custom fields
if(isset($new_custom_fields)) {
	foreach ($new_custom_fields as $table=>$field) {
		// each field
		foreach ($field as $fname=>$fval) {
			$null = $fval['Null']=="YES" ? "" : "NOT NULL";
			$default = !is_blank($fval['Default']) ? "DEFAULT '$fval[Default]'" : "";
			// update teable definition
			$query = "ALTER TABLE `$table` ADD COLUMN `$fval[name]` $fval[type] $default $null COMMENT '$fval[Comment]';";
			// update
			try {
				$Database->runQuery ($query);
			} catch (Exception $e) {
				print $e->getMessage ();
				die("\nExit");
			}
		}
	}
}



// insert new data
foreach ($new_data as $table=>$field) {
	foreach ($field as $k=>$var) {
		try { $Database->insertObject($table, $var, false, false, false); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
		}
	}
}

?>