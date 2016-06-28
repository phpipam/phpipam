<?php

/**
 *	phpIPAM Section class
 */

class Sections extends Common_functions {

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
	 * Result
	 *
	 * @var mixed
	 * @access public
	 */
	public $Result;

	/**
	 * Database
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Database;

	/**
	 * Log
	 *
	 * @var mixed
	 * @access public
	 */
	public $Log;





	/**
	 * __construct function.
	 *
	 * @access public
	 * @param Database_PDO $database
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
	 *	@update section methods
	 *	--------------------------------
	 */

	/**
	 * Modify section
	 *
	 * @access public
	 * @param mixed $action
	 * @param mixed $values
	 * @return void
	 */
	public function modify_section ($action, $values) {
		# strip tags
		$values = $this->strip_input_tags ($values);

		# execute based on action
		if($action=="add")			{ return $this->section_add ($values); }
		elseif($action=="edit")		{ return $this->section_edit ($values); }
		elseif($action=="delete")	{ return $this->section_delete ($values); }
		elseif($action=="reorder")	{ return $this->section_reorder ($values); }
		else						{ return $this->Result->show("danger", _("Invalid action"), true); }
	}

	/**
	 * Creates new section
	 *
	 * @access private
	 * @param mixed $values
	 * @return void
	 */
	private function section_add ($values) {
		# null empty values
		$values = $this->reformat_empty_array_fields ($values, null);

		# unset id
		unset($values['id']);

		# execute
		try { $this->Database->insertObject("sections", $values); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			// write log and changelog
			$this->Log->write( "Sections creation", "Failed to create new section<hr>".$e->getMessage()."<hr>".$this->array_to_log($this->reformat_empty_array_fields ($values, "NULL")), 2);
			return false;
		}
		# save id
		$this->lastInsertId = $this->Database->lastInsertId();
		# ok
		$values['id'] = $this->lastInsertId;
		$this->Log->write( "Section created", "New section created<hr>".$this->array_to_log($this->reformat_empty_array_fields ($values, "NULL")), 0);
		# write changelog
		$this->Log->write_changelog('section', "add", 'success', array(), $values);
		return true;
	}

	/**
	 * Edit existing section
	 *
	 * @access private
	 * @param mixed $values
	 * @return void
	 */
	private function section_edit ($values) {
		# null empty values
		$values = $this->reformat_empty_array_fields ($values, NULL);

		# save old values
		$old_section = $this->fetch_section ("id", $values['id']);

		# execute
		try { $this->Database->updateObject("sections", $values, "id"); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			$this->Log->write( "Section $old_section->name edit", "Failed to edit section $old_section->name<hr>".$e->getMessage()."<hr>".$this->array_to_log($this->reformat_empty_array_fields ($values, "NULL")), 2);
			return false;
		}

		# write changelog
		$this->Log->write_changelog('section', "edit", 'success', $old_section, $values);
		# ok
		$this->Log->write( "Section $old_section->name edit", "Section $old_section->name edited<hr>".$this->array_to_log($this->reformat_empty_array_fields ($values, "NULL")), 0);
		return true;
	}

	/**
	 * Delete section, subsections, subnets and ip addresses
	 *
	 * @access private
	 * @param mixed $values
	 * @return void
	 */
	private function section_delete ($values) {
		# subnets class
		$Subnets = new Subnets ($this->Database);

		# save old values
		$old_section = $this->fetch_section ("id", $values['id']);

		# check for subsections and store all ids
		$all_ids = $this->get_all_section_and_subsection_ids ($values['id']);		//array of section + all subsections

		# truncate and delete all subnets in all sections, than delete sections
		foreach($all_ids as $id) {
			$section_subnets = $Subnets->fetch_section_subnets ($id);
			if(sizeof($section_subnets)>0) {
				foreach($section_subnets as $ss) {
					//delete subnet
					$Subnets->modify_subnet("delete", array("id"=>$ss->id));
				}
			}
			# delete all sections
			try { $this->Database->deleteRow("sections", "id", $id); }
			catch (Exception $e) {
				$this->Log->write( "Section $old_section->name delete", "Failed to delete section $old_section->name<hr>".$e->getMessage()."<hr>".$this->array_to_log($this->reformat_empty_array_fields ($values, "NULL")), 2);
				$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
				return false;
			}
		}

		# write changelog
		$this->Log->write_changelog('section', "delete", 'success', $old_section, array());
		# log
		$this->Log->write( "Section $old_section->name delete", "Section $old_section->name deleted<hr>".$this->array_to_log($this->reformat_empty_array_fields((array) $old_section)), 0);
		return true;
	}

	/**
	 * Updates section order
	 *
	 * @access private
	 * @param mixed $order
	 * @return void
	 */
	private function section_reorder ($order) {
		# update each section
		foreach($order as $key=>$o) {
			# execute
			try { $this->Database->updateObject("sections", array("order"=>$o, "id"=>$key), "id"); }
			catch (Exception $e) {
				$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
				return false;
			}
		}
	    return true;
	}










	/**
	 *	@fetch section methods
	 *	--------------------------------
	 */

	/**
	 * fetches all available sections
	 *
	 * @access public
	 * @param string $order_by (default: "order")
	 * @param bool $sort_asc (default: true)
	 * @return void
	 */
	public function fetch_all_sections ($order_by="order", $sort_asc=true) {
    	return $this->fetch_all_objects ("sections", $order_by, $sort_asc);
	}

	/**
	 * Alias for fetch_all_sections
	 *
	 * @param string $order_by (default: "order")
	 * @param bool $sort_asc (default: true)
	 * @return void
	 */
	public function fetch_sections ($order_by="order", $sort_asc=true) {
		return $this->fetch_all_objects ("sections", $order_by, $sort_asc);
	}

	/**
	 * fetches section by specified method
	 *
	 * @access public
	 * @param string $method (default: "id")
	 * @param mixed $value
	 * @return void
	 */
	public function fetch_section ($method = "id", $value) {
    	if (is_null($method))   $method = "id";
        return $this->fetch_object ("sections", $method, $value);
	}

	/**
	 * Fetch subsections for specified sectionid
	 *
	 * @access public
	 * @param mixed $sectionId
	 * @return void
	 */
	public function fetch_subsections ($sectionId) {
		try { $subsections = $this->Database->getObjectsQuery("SELECT * FROM `sections` where `masterSection` = ?;", array($sectionId)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# result
		return sizeof($subsections)>0 ? $subsections : array();
	}

	/**
	 * Fetches ids of section and possible subsections for deletion
	 *
	 * @access private
	 * @param int $id
	 * @return void
	 */
	private function get_all_section_and_subsection_ids ($id) {
		# check for subsections and store all ids
		$subsections = $this->fetch_subsections ($id);
		if(sizeof($subsections)>0) {
			foreach($subsections as $ss) {
				$subsections_ids[] = $ss->id;
			}
		}
		else {
				$subsections_ids = array();
		}
		//array of section + all subsections
		return  array_filter(array_merge($subsections_ids, array($id)));
	}

	/**
	 * Fetches all vlans in section
	 *
	 * @access public
	 * @param mixed $sectionId
	 * @return void
	 */
	public function fetch_section_vlans ($sectionId) {
		# set query
		$query = "select distinct(`v`.`vlanId`),`v`.`name`,`v`.`number`,`v`.`domainId`, `v`.`description` from `subnets` as `s`,`vlans` as `v` where `s`.`sectionId` = ? and `s`.`vlanId`=`v`.`vlanId` order by `v`.`number` asc;";
		# fetch
		try { $vlans = $this->Database->getObjectsQuery($query, array($sectionId)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# result
		return sizeof($vlans)>0 ? $vlans : false;
	}

	/**
	 * Fetches all vrfs in section
	 *
	 * @access public
	 * @param mixed $sectionId
	 * @return void
	 */
	public function fetch_section_vrfs ($sectionId) {
		# set query
		$query = "select distinct(`v`.`vrfId`),`v`.`name`,`v`.`description` from `subnets` as `s`,`vrf` as `v` where `s`.`sectionId` = ? and `s`.`vrfId`=`v`.`vrfId` order by `v`.`name` asc;";
		# fetch
		try { $vrfs = $this->Database->getObjectsQuery($query, array($sectionId)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# result
		return sizeof($vrfs)>0 ? $vrfs : false;
	}


	/**
	 * Fetches section domains
	 *
	 * @access public
	 * @param mixed $sectionId
	 * @return void
	 */
	public function fetch_section_domains ($sectionId) {
		# first fetch all domains
		$Admin = new Admin ($this->Database, false);
		$domains = $Admin->fetch_all_objects ("vlanDomains");
		# loop and check
		foreach($domains as $d) {
			//default
			if($d->id==1) {
					$permitted[] = $d->id;
			}
			else {
				//array
				if(in_array($sectionId, explode(";", $d->permissions))) {
					$permitted[] = $d->id;
				}
			}
		}
		# return permitted
		return $permitted;
	}

	/**
	 * Fetches nameserver sets to belong to section
	 *
	 * @access public
	 * @param mixed $sectionId
	 * @return void
	 */
	public function fetch_section_nameserver_sets ($sectionId) {
		# first fetch all nameserver sets
		$Admin = new Admin ($this->Database, false);
		$nameservers = $Admin->fetch_all_objects ("nameservers");
		# loop and check
		if ($nameservers!==false) {
			foreach($nameservers as $n) {
				//default
				if($n->id==1) {
						$permitted[] = $n->id;
				}
				else {
					//array
					if(in_array($sectionId, explode(";", $n->permissions))) {
						$permitted[] = $n->id;
					}
				}
			}
			# return permitted
			return $permitted;
		}
		else {
			return false;
		}
	}





	/**
	 *	@permission section methods
	 *	--------------------------------
	 */

	/**
	 * Checks section permissions and returns group privilege for each section
	 *
	 * @access public
	 * @param mixed $permissions
	 * @return void
	 */
	public function parse_section_permissions($permissions) {
		# save to array
		$permissions = json_decode($permissions, true);
		# start Tools object
		$Tools = new Tools ($this->Database);
		if(sizeof($permissions)>0) {
	    	foreach($permissions as $key=>$p) {
	    		$group = $Tools->fetch_object("userGroups", "g_id", $key);
	    		$out[$group->g_id] = $p;
	    	}
	    }
	    # return array of groups
		return isset($out) ? $out : array();
	}

	/**
	 * returns permission level for specified section
	 *
	 *	3 = read/write/admin
	 *	2 = read/write
	 *	1 = read
	 *	0 = no access
	 *
	 * @access public
	 * @param obj $user
	 * @param int $sectionid
	 * @return void
	 */
	public function check_permission ($user, $sectionid) {
		# decode groups user belongs to
		$groups = json_decode($user->groups);

		# admins always has permission rwa
		if($user->role == "Administrator")		{ return 3; }
		else {
			# fetch section details and check permissions
			$section  = $this->fetch_section ("id", $sectionid);
			$sectionP = json_decode($section->permissions);

			# default permission is no access
			$out = 0;

			# for each group check permissions, save highest to $out
			if(sizeof($sectionP)>0) {
				foreach($sectionP as $sk=>$sp) {
					# check each group if user is in it and if so check for permissions for that group
					if(sizeof($groups)>0) {
						foreach($groups as $uk=>$up) {
							if($uk == $sk) {
								if($sp > $out) { $out = $sp; }
							}
						}
					}
				}
			}
			# return permission level
			return $out;
		}
	}

	/**
	 * This function returns permissions of group_id for each section
	 *
	 * @access public
	 * @param int $gid						//id of group to verify permissions
	 * @param bool $name (default: true)	//should index be name or id?
	 * @return array
	 */
	public function get_group_section_permissions ($gid, $name = true) {
		# fetch all sections
		$sections = $this->fetch_all_sections();

		# loop through sections and check if group_id in permissions
        if ($sections !== false) {
    		foreach($sections as $section) {
    			$p = json_decode($section->permissions, true);
    			if(sizeof($p)>0) {
    				if($name) {
    					if(array_key_exists($gid, $p)) {
    						$out[$section->name] = $p[$gid];
    					}
    				}
    				else {
    					if(array_key_exists($gid, $p)) {
    						$out[$section->id] = $p[$gid];
    					}
    				}
    			}
    			# no permissions
    			else {
    				$out[$section->name] = 0;
    			}
    		}
		}
		# return
		return $out;
	}

	/**
	 * Delegates section permissions to all belonging subnets
	 *
	 * @access public
	 * @param mixed $sectionId
	 * @param array $removed_permissions
	 * @param array $changed_permissions
	 * @return void
	 */
	public function delegate_section_permissions ($sectionId, $removed_permissions, $changed_permissions) {
    	// init subnets class
    	$Subnets = new Subnets ($this->Database);
    	// fetch section subnets
    	$section_subnets = $this->fetch_multiple_objects ("subnets", "sectionId", $sectionId);
    	// loop
    	if ($section_subnets!==false) {
        	foreach ($section_subnets as $s) {
                // to array
                $s_old_perm = json_decode($s->permissions, true);
                // removed
                if (sizeof($removed_permissions)>0) {
                    foreach ($removed_permissions as $k=>$p) {
                        unset($s_old_perm[$k]);
                    }
                }
                // added
                if (sizeof($changed_permissions)>0) {
                    foreach ($changed_permissions as $k=>$p) {
                        $s_old_perm[$k] = $p;
                    }
                }

                // set values
                $values = array(
                            "id" => $s->id,
                            "permissions" => json_encode($s_old_perm)
                            );

                // update
                if($Subnets->modify_subnet ("edit", $values)===false)       { $Result->show("danger",  _("Failed to set subnet permissons for subnet")." $s->name!", true); }
        	}
        	// ok
        	$this->Result->show("success", _("Subnet permissions recursively set")."!", true);
    	}


		try { $this->Database->updateObject("subnets", array("permissions"=>$permissions, "sectionId"=>$sectionId), "sectionId"); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		return true;
	}
}
?>
