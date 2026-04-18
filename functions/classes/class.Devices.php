<?php

/**
 *	phpIPAM Section class
 */

class Devices extends Common_functions {

	/**
	 * (array of objects) to store sections, section ID is array index
	 *
	 * @var mixed
	 * @access public
	 */
	public $sections;

	/**
	 * id of last insert
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access public
	 */
	public $lastInsertId = null;

	/**
	 * (object) for User profile
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $user = null;



	/**
	 * __construct function.
	 *
	 * @access public
	 * @param Database_PDO $database
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
	 * Fetches all devices that are not in group
	 *
	 * @access public
	 * @param int $group_id
	 * @return array
	 */
	public function group_fetch_missing_devices($group_id) {
		$out = [];

		# get all devices
		$devices = $this->fetch_all_objects("devices");
		$devices = ($devices !== false) && isset($devices) ? (array)$devices : [];
		
		// get current group members
		$current_group_members = $this->fetch_multiple_objects("device_to_group", "g_id", $group_id, 'g_id', result_fields: "d_id");
		$current_group_members = ($current_group_members !== false) && isset($current_group_members) ? (array)$current_group_members : [];

		foreach ($devices as $d) {
			$found = false;

			foreach ($current_group_members as $member) {
				if ($d->id === $member->d_id) {
					$found = true;
					break;
				}
			}

			if (!$found) {
				array_push($out, $d->id);
			}
		}
		return $out;
	}

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
	public function object_modify ($table, $action=null, $field="id", $values = [], $values_log = [], $field2 = null, $values2 = null) {
		if (!is_string($table) || is_blank($table)) return false;
		# strip tags
		$values     = $this->strip_input_tags ($values);
		$values_log = $this->strip_input_tags ($values_log);

		# if empty values_log inherit from values to preserve old functionality
		if(sizeof($values_log)==0)	{ $values_log = $values; }

		# execute based on action
		if($action=="add")					{ return $this->object_add ($table, $values, $values_log); }
		elseif($action=="delete")			{ return $this->object_delete ($table, $field, $values[$field], $field2, $values2[$field2]); }
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
	 * Delete object in table by specified object id
	 *
	 * @access private
	 * @param mixed $table		//table to update
	 * @param string $field		//field selection (where $field = $id)
	 * @param mixed $id			//field identifier
	 * @return boolean
	 */
	private function object_delete ($table, $field, $id, $field2, $id2) {
		# execute
		try { $this->Database->deleteRow($table, $field, $id, $field2, $id2); }
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

	public $lastId = null;

	/**
	 * Saves last insert ID on object modification.
	 *
	 * @access public
	 * @return void
	 */
	public function save_last_insert_id () {
		$this->lastId = $this->Database->lastInsertId();
	}
}
