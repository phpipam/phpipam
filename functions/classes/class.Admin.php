<?php

/**
 *	phpIPAM Admin class
 */

class Admin extends Common_functions {


	/**
	 * (array of objects) to store users, user id is array index
	 *
	 * @var mixed
	 * @access public
	 */
	public $users;

	/**
	 * (array of objects) to store groups, group id is array index
	 *
	 * @var mixed
	 * @access public
	 */
	public $groups;

	/**
	 * id of last edited/added table
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access public
	 */
	public $lastId = null;

	/**
	 * if admin user is required to connect. Can be overridden
	 *
	 * (default value: true)
	 *
	 * @var bool
	 * @access private
	 */
	private $admin_required = true;

	/**
	 * User
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $User;






	/**
	 * __construct function.
	 *
	 * @access public
	 * @param Database_PDO $database
	 * @param bool $admin_required (default: true)
	 */
	public function __construct (Database_PDO $database, $admin_required = true) {
		parent::__construct();

		# initialize database object
		$this->Database = $database;
		# initialize Result
		$this->Result = new Result ();
		# set admin flag
		$this->set_admin_required ($admin_required);
		# verify that user is admin
		$this->is_admin ();
		# Log object
		$this->Log = new Logging ($this->Database);
	}

	/**
	 * Saves last insert ID on object modification.
	 *
	 * @access public
	 * @return void
	 */
	public function save_last_insert_id () {
		$this->lastId = $this->Database->lastInsertId();
	}

	/**
	 * Sets admin required flag if needed
	 *
	 * @access public
	 * @param boolean $bool
	 * @return void
	 */
	public function set_admin_required ($bool) {
		$this->admin_required = is_bool($bool) ? $bool : true;
	}

	/**
	 * Checks if current user is admin
	 *
	 * @access public
	 * @return void
	 */
	public function is_admin () {
		// user not required for cli
		if (php_sapi_name()!="cli") {
			# initialize user class
			$this->User = new User ($this->Database);
    		# save settings
    		$this->settings = $this->User->settings;
    		# if required die !
    		if($this->User->is_admin(false)!==true && $this->admin_required===true) {
    			// popup ?
    			if(@$_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest") 	{ $this->Result->show("danger", _("Administrative privileges required"),true, true); }
    			else 														{ $this->Result->show("danger", _("Administrative privileges required"),true); }
    		}
		}
	}













	/**
	 *	@general update methods
	 *	--------------------------------
	 */

	/**
	 * Modify database object
	 *
	 * @param string $table
	 * @param string $action
	 * @param string|array $field
	 * @param array $values
	 * @param array $values_log
	 * @return void
	 */
	public function object_modify ($table, $action=null, $field="id", $values = [], $values_log = []) {
		if (!is_string($table) || strlen($table) == 0) return false;
		# strip tags
		$values     = $this->strip_input_tags ($values);
		$values_log = $this->strip_input_tags ($values_log);

		# if empty values_log inherit from values to preserve old functionality
		if(sizeof($values_log)==0)	{ $values_log = $values; }

		# execute based on action
		if($action=="add")					{ return $this->object_add ($table, $values, $values_log); }
		elseif($action=="edit")				{ return $this->object_edit ($table, $field, $values, $values_log); }
		elseif($action=="edit-multiple")	{ return $this->object_edit_multiple ($table, $field, $values); }		//$field = array of ids
		elseif($action=="delete")			{ return $this->object_delete ($table, $field, $values[$field]); }
		else								{ return $this->Result->show("danger", _("Invalid action"), true); }
	}

	/**
	 * Create new database object
	 *
	 *		$values are all values that should be passed to create object
	 *
	 * @access private
	 * @param mixed $table
	 * @param mixed $values
	 * @param array $values_log		//log variables
	 * @return boolean
	 */
	private function object_add ($table, $values, $values_log) {
		# null empty values
		$values = $this->reformat_empty_array_fields ($values, null);

		# execute
		try { $this->Database->insertObject($table, $values); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			$this->Log->write( $table." "._("object creation"), _("Failed to create new")." ".$table." "._("database object").".<hr>".$e->getMessage()."<hr>".$this->array_to_log($this->reformat_empty_array_fields ($values_log, "NULL")), 2);
			return false;
		}
		# save ID
		$this->save_last_insert_id ();
		# ok
		$this->Log->write( $table." "._("Object creation"), _("A new")." ".$table." "._("database object created").".<hr>".$this->array_to_log($this->reformat_empty_array_fields ($values_log, "NULL")), 0);
		return true;
	}

	/**
	 * Edit object in table by specified object id
	 *
	 *		$values are all values that should be passed to edit object,
	 *		id will be used to match field to update.
	 *
	 * @access private
	 * @param mixed $table			//name of table to update
	 * @param mixed $key
	 * @param array $values			//update variables
	 * @param array $values_log		//log variables
	 * @return boolean
	 */
	private function object_edit ($table, $key, $values, $values_log = []) {
		# null empty values
		$values = $this->reformat_empty_array_fields ($values, null);

		# execute
		try { $this->Database->updateObject($table, $values, $key); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			$this->Log->write( $table." "._("object")." ".$values[$key]." "._("edit"), _("Failed to edit object")." ".$key=$values[$key]." "._("in")." $table.<hr>".$e->getMessage()."<hr>".$this->array_to_log($this->reformat_empty_array_fields ($values_log, "NULL")), 2);
			return false;
		}
		# save ID
		$this->save_last_insert_id ();
		# ok
		$this->Log->write( $table." "._("object")." ".$values[$key]." "._("edit"), _("Object")." ".$key=$values[$key]." "._("in")." ".$table." "._("edited").".<hr>".$this->array_to_log($this->reformat_empty_array_fields ($values_log, "NULL")), 0);
		return true;
	}

	/**
	 * Edit multiple objects in table by specified object id
	 *
	 *		$values are all values that should be passed to edit object,
	 *		ids will be used to match fields to update.
	 *
	 * @access private
	 * @param mixed $table			//name of table to update
	 * @param array $values			//update variables
	 * @param string $ids
	 * @return boolean
	 */
	private function object_edit_multiple ($table, $ids, $values) {
		# null empty values
		$values = $this->reformat_empty_array_fields ($values, null);

		# execute
		try { $this->Database->updateMultipleObjects($table, $ids, $values); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			$this->Log->write( $table." "._("multiple objects edit"), _("Failed to edit multiple objects in")." $table.<hr>".$e->getMessage()."<hr>".$this->array_to_log($ids)."<hr>".$this->array_to_log($this->reformat_empty_array_fields ($values, "NULL")), 2);
			return false;
		}
		# save ID
		$this->save_last_insert_id ();
		# ok
		$this->Log->write( $table." "._("multiple objects edit"), _("Multiple objects in")." ".$table." "._("edited").".<hr>".$this->array_to_log($ids)."<hr>".$this->array_to_log($this->reformat_empty_array_fields ($values, "NULL")), 0);
		return true;
	}

	/**
	 * Delete object in table by specified object id
	 *
	 * @access private
	 * @param mixed $table		//table to update
	 * @param string $field		//field selection (where $field = $id)
	 * @param mixed $id			//field identifier
	 * @return boolean
	 */
	private function object_delete ($table, $field, $id) {
		# execute
		try { $this->Database->deleteRow($table, $field, $id); }
		catch (Exception $e) {
			$this->Log->write( $table." "._("object")." ".$id." "._("delete"), _("Failed to delete object")." ".$field=$id." "._("in")." ".$table.".<hr>".$e->getMessage(), 2);
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}
		# save ID
		$this->save_last_insert_id ();
		# ok
		$this->Log->write( $table." "._("object")." ".$id." "._("edit"), _("Object")." ".$field=$id." "._("in")." ".$table." "._("deleted").".", 0);
		return true;
	}

	/**
	 * Removes or replaces all old object references
	 *
	 * @access public
	 * @param mixed $table
	 * @param mixed $field
	 * @param mixed $old_value
	 * @param mixed $new_value (Default: NULL)
	 * @return null|false
	 */
	public function remove_object_references ($table, $field, $old_value, $new_value = NULL) {
		$table = $this->Database->escape($table);
		$field = $this->Database->escape($field);

		try { $this->Database->runQuery("update `$table` set `$field` = ? where `$field` = ?;", array($new_value, $old_value)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}
	}

	/**
	 * Resets or replaces all old object references
	 *
	 * @access public
	 * @param mixed $table
	 * @param mixed $field
	 * @param mixed $old_value
	 * @param mixed $new_value
	 * @return null|false
	 */
	public function update_object_references ($table, $field, $old_value, $new_value) {
		$table = $this->Database->escape($table);
		$field = $this->Database->escape($field);

		try { $this->Database->runQuery("update `$table` set `$field` = ? where `$field` = ?;", array($new_value, $old_value)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}
	}

	/**
	 * Empties table
	 *
	 * @access public
	 * @param mixed $table (default: null)
	 * @return boolean
	 */
	public function truncate_table ($table = null) {
		# null table
		if(is_null($table)||strlen($table)==0) return false;
		else {
			try { $this->Database->emptyTable($table); }
			catch (Exception $e) {
				$this->Result->show("danger", _("Error: ").$e->getMessage());
				return false;
			}
			# result
			return true;
		}
	}






	/**
	 *	@group methods
	 *	--------------------------------
	 */

	/**
	 * Parse user groups
	 *
	 *	input:  array of group ids
	 *	output: array of groups ( "id"=>array($group) )
	 *
	 * @access public
	 * @param mixed $group_ids
	 * @return void
	 */
	public function groups_parse ($group_ids) {
		$out = array ();
		// check
		if(!is_null($group_ids)) {
			if(sizeof($group_ids)>0) {
		    	foreach($group_ids as $g_id) {
		    		$group = $this->fetch_object ("userGroups", "g_id", $g_id);
		    		$out[$group->g_id] = (array) $group;
		    	}
		    }
		}
	    # return array of groups
	    return $out;
	}


	/**
	 * Parse user groups
	 *
	 *	input:  array of group ids
	 *	output: array of ids (  "id"=>id )
	 *
	 * @access public
	 * @param mixed $group_ids
	 * @return void
	 */
	public function groups_parse_ids ($group_ids) {
		$out = array ();
		// check
		if(!is_null($group_ids)) {
			if(sizeof($group_ids) >0) {
			    foreach($group_ids as $g_id) {
		    		$group = $this->fetch_object ("userGroups", "g_id", $g_id);
		    		$out[$group->g_id] = $group->g_id;
		    	}
		    }
		}
	    # return array of group ids
	    return $out;
	}

	/**
	 * Fetches all users that are in group
	 *
	 * @access public
	 * @return array of user ids
	 */
	public function group_fetch_users ($group_id) {
		$out = array ();
		# get all users
		$users = $this->fetch_all_objects("users");
		# check if $gid in array
		if($users!==false) {
			foreach($users as $u) {
				$group_array = json_decode($u->groups, true);
				$group_array = $this->groups_parse($group_array);

				if(sizeof($group_array)>0) {
					foreach($group_array as $group) {
						if(in_array($group_id, $group)) {
							$out[] = $u->id;
						}
					}
				}
			}
		}
		# return
		return isset($out) ? $out : array();
	}

	/**
	 * Fetches all users that are not admins and are not in group
	 *
	 * @access public
	 * @param mixed $group_id
	 * @return void
	 */
	public function group_fetch_missing_users ($group_id) {
		$out = array ();
		# get all users
		$users = $this->fetch_all_objects("users");

		# check if $gid in array
		if($users!==false) {
			foreach($users as $u) {
				if($u->role != "Administrator") {
					$g = json_decode($u->groups, true);
					if(!@in_array($group_id, $g)) { $out[] = $u->id; }
				}
			}
		}
		# return
		return $out;
	}

	/**
	 * This function adds new group access to user account
	 *
	 * @access private
	 * @param mixed $gid
	 * @param mixed $uid
	 * @return boolean
	 */
	public function add_group_to_user ($gid, $uid) {
		# get old groups
		$user = $this->fetch_object ("users", "id", $uid);

		# append new group
		$g = json_decode($user->groups, true);
		$g[$gid] = $gid;
		$g = json_encode($g);

		# update
		if(!$this->update_user_groups($uid, $g)) { return false; }
		else									 { return true; }
	}

	/**
	 * This function removes group from users account.
	 *
	 * @access public
	 * @param mixed $gid
	 * @param mixed $uid
	 * @return boolean
	 */
	public function remove_group_from_user($gid, $uid) {
		# get old groups
		$user = $this->fetch_object ("users", "id", $uid);

		# remove group
		$g = json_decode($user->groups, true);
		unset($g[$gid]);
		$g = json_encode($g);

		# update
		if(!$this->update_user_groups($uid, $g)) 	{ return false; }
		else										{ return true; }
	}


	/**
	 * Update groups for specified user
	 *
	 * @access public
	 * @param mixed $uid
	 * @param string $groups
	 * @return void
	 */
	public function update_user_groups ($uid, $groups) {
	    return $this->object_modify ("users", "edit", "id", array("id"=>$uid, "groups"=>$groups));
	}

	/**
	 * Update group permissions for section
	 *
	 * @access public
	 * @param mixed $sid
	 * @param string $groups
	 * @return void
	 */
	public function update_section_groups($sid, $groups) {
	    return $this->object_modify ("sections", "edit", "id", array("id"=>$sid, "permissions"=>$groups));
	}

	/**
	 * Removes all users from specified group on group delete
	 *
	 * @access public
	 * @param int $gid	//group id
	 * @return boolean
	 */
	public function remove_group_from_users($gid) {
		# get all users
		$users = $this->fetch_all_objects("users");
		# check if $gid in array
		if($users!==false) {
			foreach($users as $u) {
				$g  = json_decode($u->groups, true);
				$go = $g;
				$g  = $this->groups_parse($g);
				# check
				if(sizeof($g)>0) {
					foreach($g as $gr) {
						if(in_array($gid, $gr)) {
							unset($go[$gid]);
							$ng = json_encode($go);
							$this->update_user_groups($u->id,$ng);
						}
					}
				}
			}
		}
		return true;
	}

	/**
	 * Removes group ID from all section permissions
	 *
	 * @access public
	 * @param mixed $gid
	 * @return boolean
	 */
	public function remove_group_from_sections ($gid) {
		# get all sections
		$sections = $this->fetch_all_objects ("sections", "id");
		# check if $gid in array
		foreach($sections as $s) {
			$g = json_decode($s->permissions, true);

			if(sizeof($g)>0) {
				if(array_key_exists($gid, $g)) {
					unset($g[$gid]);
					$ng = json_encode($g);
					$this->update_section_groups($s->id,$ng);
				}
			}
		}
		return true;
	}












	/**
	 *	@search/replace fields
	 *	--------------------------------
	 */

	/**
	 * Replace fields
	 *
	 * @access public
	 * @param mixed $field
	 * @param mixed $search
	 * @param mixed $replace
	 * @return void
	 */
	public function replace_fields ($field, $search, $replace) {
		$field = $this->Database->escape($field);

		# check number of items
		$count = $this->count_database_objects ("ipaddresses", $field, "%$search%", true);
		# if some exist update
		if($count>0) {
			# update
		    try { $this->Database->runQuery("update `ipaddresses` set `$field` = replace(`$field`, ?, ?);", array($search, $replace)); }
		    catch (Exception $e) {
			    $this->Result->show("danger alert-absolute", _("Error: ").$e->getMessage(), true);
		    }
		    # ok, print count
		    $this->Result->show("success alert-absolute", $count .' '._('items replaced successfully').'!', false);
		}
		else {
			$this->Result->show("info alert-absolute", _("No records found to replace."), false);
		}
	}












	/**
	 *	@custom field methods
	 *	--------------------------------
	 */

	/**
	 * Valid custom field database types
	 * @return array
	 */
	public function valid_custom_field_types() {
		return ["varchar"  =>"varchar",
				"integer"  =>"int",
				"boolean"  =>"bool",
				"text"     =>"text",
				"date"     =>"date",
				"datetime" =>"datetime",
				"set"      =>"set",
				"enum"     =>"enum"];
	}

	/**
	 * Updates custom field definition
	 *
	 * @access public
	 * @param array $field
	 * @return bool
	 */
	public function update_custom_field_definition ($field) {
		if (!in_array($field['fieldType'], $this->valid_custom_field_types())) {
			$this->Result->show("danger", _("Error: ")._("Invalid custom field type"));
			return false;
		}

	    # set type definition and size of needed
	    if($field['fieldType']=="bool" || $field['fieldType']=="text" || $field['fieldType']=="date" || $field['fieldType']=="datetime")	{ $field['ftype'] = $field['fieldType']; }
	    else																																{ $field['ftype'] = $field['fieldType']."(".$field['fieldSize'].")"; }

	    # default value null
	    $field['fieldDefault'] = strlen($field['fieldDefault'])==0 ? NULL : $field['fieldDefault'];

	    # character set if needed
	    if($field['fieldType']=="varchar" || $field['fieldType']=="text" || $field['fieldType']=="set" || $field['fieldType']=="enum")	{ $charset = "CHARACTER SET utf8"; }
	    else																															{ $charset = ""; }

	    # escape fields
	    $field['table'] 		= $this->Database->escape($field['table']);
	    $field['name'] 			= $this->Database->escape($field['name']);
	    $field['oldname'] 		= $this->Database->escape($field['oldname']);
	    # strip values
	    $field['action'] 		= $this->strip_input_tags($field['action']);
	    $field['Comment'] 		= $this->strip_input_tags($field['Comment']);

	    # add name prefix to distinguish custom fields
	    if($field['action']=="edit" || $field['action']=="add") {
		    if(strpos($field['name'], "custom_")!==0) { $field['name'] = "custom_".$field['name']; }
		}

	    # set update query
	    if($field['action']=="delete") 								{ $query  = "ALTER TABLE `$field[table]` DROP `$field[oldname]`;"; }
	    else if ($field['action']=="edit"&&@$field['NULL']=="NO") 	{ $query  = "ALTER TABLE `$field[table]` CHANGE COLUMN `$field[oldname]` `$field[name]` $field[ftype] $charset DEFAULT :default NOT NULL COMMENT :comment;"; }
	    else if ($field['action']=="edit") 							{ $query  = "ALTER TABLE `$field[table]` CHANGE COLUMN `$field[oldname]` `$field[name]` $field[ftype] $charset DEFAULT :default COMMENT :comment;"; }
	    else if ($field['action']=="add"&&@$field['NULL']=="NO") 	{ $query  = "ALTER TABLE `$field[table]` ADD COLUMN 	`$field[name]` 					$field[ftype] $charset DEFAULT :default NOT NULL COMMENT :comment;"; }
	    else if ($field['action']=="add")							{ $query  = "ALTER TABLE `$field[table]` ADD COLUMN 	`$field[name]` 					$field[ftype] $charset DEFAULT :default NULL COMMENT :comment;"; }
	    else {
		    return false;
	    }

	    # set parametized values
	    $params = array();
	    if (strpos($query, ":default")>0)	$params['default'] = $field['fieldDefault'];
	    if (strpos($query, ":comment")>0)	$params['comment'] = $field['Comment'];

		# execute
		try { $res = $this->Database->runQuery($query, $params); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
	        $this->Log->write( _("Custom field")." ".$field["action"], _("Custom field")." ".$field["action"]." "._("failed")." (".$field["name"].").<hr>".$this->array_to_log($field), 2);
			return false;
		}
		# field updated
        $this->Log->write( _("Custom field")." ".$field["action"], _("Custom field")." ".$field["action"]." "._("success")." (".$field["name"].").<hr>".$this->array_to_log($field), 0);
	    return true;
	}

	/**
	 * Save custom fields that should be hidden from normal display
	 *
	 * @access public
	 * @param mixed $table				//name of custom fields table
	 * @param mixed $filtered_fields	//array of field to hide for this table
	 * @return boolean
	 */
	public function save_custom_fields_filter ($table, $filtered_fields) {
		# old custom fields, save them to array
		$hidden_array = strlen($this->settings->hiddenCustomFields)>0 ? json_decode($this->settings->hiddenCustomFields, true) : array();

		# set new array for table
		if(is_null($filtered_fields))	{ unset($hidden_array[$table]); }
		else							{ $hidden_array[$table]=$filtered_fields; }

		# encode to json
		$hidden_json = json_encode($hidden_array, JSON_UNESCAPED_UNICODE);

		# update database
	    try { $this->object_edit ("settings", $key="id", array("id"=>1,"hiddenCustomFields"=>$hidden_json)); }
	    catch (Exception $e) {
		    $this->Result->show("danger", _("Error: ").$e->getMessage(), true);
	    }
	    # ok
		return true;
	}

	/**
	 * Reorders custom fields
	 *
	 * @access public
	 * @param mixed $table
	 * @param mixed $next
	 * @param mixed $current
	 * @return boolean
	 */
	public function reorder_custom_fields ($table, $next, $current) {
	    $table = $this->Database->escape($table);
	    $next = $this->Database->escape($next);
	    $current = $this->Database->escape($current);
	    # get current field details
	    $Tools = new Tools ($this->Database);
	    $old = (array) $Tools->fetch_full_field_definition ($table, $current);

	    # set update request
	    if($old['Null']=="NO")	{ $query  = 'ALTER TABLE `'.$table.'` MODIFY COLUMN `'. $current .'` '.$old['Type'].' NOT NULL COMMENT "'.$old['Comment'].'" AFTER `'. $next .'`;'; }
	    else					{ $query  = 'ALTER TABLE `'.$table.'` MODIFY COLUMN `'. $current .'` '.$old['Type'].' DEFAULT NULL COMMENT "'.$old['Comment'].'" AFTER `'. $next .'`;'; }

		# execute
		try { $res = $this->Database->runQuery($query); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}
		# ok
	    return true;
	}

}
