<?php

/**
 *	phpIPAM Section class
 */

class ipGroups extends Common_functions {

	/**
	 * (array of objects) to store groups, group ID is array index
	 *
	 * @var mixed
	 * @access public
	 */
	public $groups;

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
		$this->Database = $database;
		$this->Result   = new Result ();
		$this->Log      = new Logging ($this->Database);
	}

	/**
	 *	@update group methods
	 *	--------------------------------
	 */

	/**
	 * Modify group
	 *
	 * @access public
	 * @param  string $action
	 * @param  array  $values
	 * @return bool
	 */
	public function modify_group ($action, $values) {
		$values = $this->strip_input_tags ($values);

        switch ($action) {
            case 'add':
                return $this->group_add($values);
            case 'edit':
                return $this->group_edit($values);
            case 'delete':
                return $this->group_delete ($values);
            default:
                return $this->Result->show("danger", _("Invalid action"), true);
        }
	}

	/**
	 * Creates new group
	 *
	 * @access private
	 * @param mixed $values
	 * @return bool
	 */
	private function group_add($values) {
		# null empty values
		$values = $this->reformat_empty_array_fields ($values, null);

		unset($values['id']);

		try {
		    $this->Database->insertObject("ipGroups", $values);
		} catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			// write log and changelog
			$this->Log->write(
			    "Groups creation",
                "Failed to create new group<hr>".$e->getMessage()."<hr>" . $this->array_to_log($this->reformat_empty_array_fields ($values, "NULL")),
                2
            );

			return false;
		}

		$this->lastInsertId = $this->Database->lastInsertId();
		# ok
		$values['id'] = $this->lastInsertId;
		$this->Log->write(
		    "Section created",
            "New group created<hr>".$this->array_to_log($this->reformat_empty_array_fields ($values, "NULL")),
            0
        );
		# write changelog
		$this->Log->write_changelog('group', "add", 'success', array(), $values);

		return true;
	}

	/**
	 * Edit existing group
	 *
	 * @access private
	 * @param mixed $values
	 * @return bool
	 */
	private function group_edit($values) {
		$values    = $this->reformat_empty_array_fields ($values, NULL);
		$old_group = $this->fetch_group ("id", $values['id']);

		try {
		    $this->Database->updateObject("ipGroups", $values, "id");
		} catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);

			$this->Log->write(
                "Section $old_group->name edit",
                "Failed to edit group $old_group->name<hr>".$e->getMessage()."<hr>".$this->array_to_log($this->reformat_empty_array_fields ($values, "NULL")),
                2
            );

            return false;
		}

		$this->Log->write_changelog('group', "edit", 'success', $old_group, $values);
		$this->Log->write(
		    "Section $old_group->name edit",
            "Section $old_group->name edited<hr>".$this->array_to_log($this->reformat_empty_array_fields ($values, "NULL")),
            0
        );

		return true;
	}

	/**
	 * Delete group, subgroups, subnets and ip addresses
	 *
	 * @access private
	 * @param mixed $values
	 * @return bool
	 */
	private function group_delete($values) {
		$old_group = $this->fetch_group ("id", $values['id']);

        # delete all groups
        try {
            $this->Database->deleteRow("ipGroups", "id", $old_group->id);
        } catch (Exception $e) {
            $this->Log->write(
                "Section $old_group->name delete",
                "Failed to delete group $old_group->name<hr>".$e->getMessage()."<hr>".$this->array_to_log($this->reformat_empty_array_fields ($values, "NULL")),
                2
            );

            $this->Result->show("danger", _("Error: ").$e->getMessage(), false);

            return false;
        }

		# write changelog
		$this->Log->write_changelog('group', "delete", 'success', $old_group, array());
		# log
		$this->Log->write(
		    "Section $old_group->name delete",
            "Section $old_group->name deleted<hr>" . $this->array_to_log($this->reformat_empty_array_fields((array) $old_group)),
            0
        );
		return true;
	}

	/**
	 *	@fetch group methods
	 *	--------------------------------
	 */

	/**
	 * fetches all available groups
	 *
	 * @access public
	 * @param string $order_by (default: "order")
	 * @param bool $sort_asc (default: true)
	 * @return array|bool
	 */
	public function fetch_all_groups($order_by="id", $sort_asc=true) {
    	return $this->fetch_all_objects ("ipGroups", $order_by, $sort_asc);
	}

	/**
	 * Alias for fetch_all_groups
	 *
	 * @param string $order_by (default: "order")
	 * @param bool $sort_asc (default: true)
	 * @return array|bool
	 */
	public function fetch_groups($order_by="id", $sort_asc=true) {
		return $this->fetch_all_objects ("ipGroups", $order_by, $sort_asc);
	}

	/**
	 * fetches group by specified method
	 *
	 * @access public
	 * @param string $method (default: "id")
	 * @param mixed $value
	 * @return object|bool
	 */
	public function fetch_group($method = "id", $value) {
    	if (is_null($method))   $method = "id";
        return $this->fetch_object ("ipGroups", $method, $value);
	}
}