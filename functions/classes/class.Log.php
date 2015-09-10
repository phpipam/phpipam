<?php

/**
 *	phpIPAM log class
 *
 *
 *	It will log to any of:
 * 		* internal database;
 *		* file;
 *		* syslog;
 */

class Logging extends Common_functions {

	/**
	 * public variables
	 */
	public $settings = null;				//(object) phpipam settings

	/**
	 * protected variables
	 */
	protected $debugging = false;			//(bool) debugging flag
	protected $log_type;					//(varchar) log type

	protected $log_command 	= null;			//(varchar) log command
	protected $log_details 	= null;			//(varchar) log details
	protected $log_severity = 0;			//(int) log severity : 0: informational, 1: warning, 2: error
	protected $log_username	= null;			//username
	protected $user_id	= null;				//users id

	/**
	 * object holders
	 */
	protected $Database;					//for Database connection




	/**
	 * __construct function
	 *
	 * @access public
	 * @return void
	 */
	public function __construct (Database_PDO $database, $settings = null) {
		# Save database object
		$this->Database = $database;
		# Result
		$this->Result = new Result ();
		# User
  		$this->log_username = @$_SESSION['ipamusername'];

		# settings
		$this->settings = $settings===null ? $this->get_settings () : (object) $settings;
		# debugging
		$this->set_debugging();
		# set log type
		$this->set_log_type ();
	}






	/**
	 * Sets log type based on phpipam settings
	 *
	 *	available options:
	 *		Database (default)
	 *		syslog
	 *
	 * @access private
	 * @return void
	 */
	private function set_log_type () {
		# check settings
		$this->log_type = $this->settings->log;
	}

	/**
	 * fetches settings from database
	 *
	 * @access private
	 * @return none
	 */
	private function get_settings () {
		# cache check
		if($this->settings === null) {
			try { $settings = $this->Database->getObject("settings", 1); }
			catch (Exception $e) { $this->Result->show("danger", _("Database error: ").$e->getMessage()); }
		}
		# ok
		return $settings;
	}

	/**
	 * Gets id of active user
	 *
	 * @access private
	 * @return void
	 */
	private function get_active_user_id () {
		# cache
		if ($this->user_id===null) {
			# null
			if (!isset($_SESSION['ipamusername'])) { $this->user_id = null; }
			else {
				try { $user_id = $this->Database->getObjectQuery("select * from `users` where `username` = ? limit 1", array($_SESSION['ipamusername'])); }
				catch (Exception $e) { $this->Result->show("danger", _("Database error: ").$e->getMessage()); }
			}
			# save
			$this->user_id = $user_id->id;
		}

	}






	/**
	 * write log function
	 *
	 * @access public
	 * @param mixed $command
	 * @param mixed $details (default: NULL)
	 * @param int $severity (default: 0)
	 * @param mixed $username (default: NULL)
	 * @return void
	 */
	public function write ($command, $details = NULL, $severity = 0, $username = null) {
		// save provided values
		$this->log_command 	= $command;
		$this->log_details 	= $details;
		$this->log_severity = $severity;
		$this->log_username	= $username===null ? $this->log_username : $username;

		// validate
		!is_null($this->log_command) ? : $this->Result->show("danger", _("Invalid log command"));

		// execute
		$this->log_type == "syslog" ? $this->write_syslog () : $this->write_database_log ();
	}






	/**
	 *	@syslog log methods
	 *	--------------------------------
	 */

	/**
	 * Generates new syslog message
	 *
	 * @access private
	 * @return void
	 */
	private function write_syslog () {
	}

	private function write_changelog_syslog ($changelog) {

	}





	/**
	 *	@database log methods
	 *	--------------------------------
	 */

	/**
	 * Writes log to local database
	 *
	 * @access private
	 * @return void
	 */
	private function write_database_log () {
	    # set values
	    $values = array(
	    			"command"=>$this->log_command,
	    			"severity"=>$this->log_severity,
	    			"date"=>$this->Database->toDate(),
	    			"username"=>$this->log_username,
	    			"ipaddr"=>@$_SERVER['REMOTE_ADDR'],
	    			"details"=>$this->log_details
					);
		# null empty values
		$values = $this->reformat_empty_array_fields ($values, null);

		# execute
		try { $this->Database->insertObject("logs", $values); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}
		# ok
		return true;
	}

	/**
	 * fetches logs for specified parameters
	 *
	 * @access public
	 * @param mixed $logCount
	 * @param mixed $direction (default: NULL)
	 * @param mixed $lastId (default: NULL)
	 * @param mixed $highestId (default: NULL)
	 * @param mixed $informational
	 * @param mixed $notice
	 * @param mixed $warning
	 * @return void
	 */
	public function fetch_logs ($logCount, $direction = NULL, $lastId = NULL, $highestId = NULL, $informational, $notice, $warning) {

		# query start
		$query  = 'select * from ('. "\n";
		$query .= 'select * from logs '. "\n";
		# append severities
		$query .= 'where (`severity` = "'. $informational .'" or `severity` = "'. $notice .'" or `severity` = "'. $warning .'" )'. "\n";
		# set query based on direction */
		if( ($direction == "next") && ($lastId != $highestId) ) {
			$query .= 'and `id` < '. $lastId .' '. "\n";
			$query .= 'order by `id` desc limit '. $logCount . "\n";
		}
		elseif( ($direction == "prev") && ($lastId != $highestId)) {
			$query .= 'and `id` > '. $lastId .' '. "\n";
			$query .= 'order by `id` asc limit '. $logCount . "\n";
		}
		else {
			$query .= 'order by `id` desc limit '. $logCount . "\n";
		}
		# append limit and order
		$query .= ') as test '. "\n";
		$query .= 'order by `id` desc limit '. $logCount .';'. "\n";


	    # fetch
	    try { $logs = $this->Database->getObjectsQuery($query); }
		catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), false);	return false; }

	    # return results
	    return $logs;
	}

	/**
	 * Returns highest (last) log id
	 *
	 * @access public
	 * @return void
	 */
	public function log_fetch_highest_id () {
		# fetch
	    try { $id = $this->Database->getObjectQuery("select id from logs order by id desc limit 1;"); }
		catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), false);	return false; }
		# return result
		return $id->id;
	}










	/**
	 *	@changelog methods
	 *	--------------------------------
	 */

	/**
	 * Write new changelog to db or send to syslog
	 *
	 * @access public
	 * @param mixed $object_type
	 * @param mixed $action
	 * @param mixed $result
	 * @param array $old (default: array())
	 * @param array $new (default: array())
	 * @return void
	 */
	public function write_changelog ($object_type, $action, $result, $old = array(), $new = array()) {
		//set values
		$this->object_type 	 = $object_type;
		$this->object_action = $action;
		$this->object_result = $result;
		//cast diff objects as array
		$this->object_old = (array) $old;		// new object
		$this->object_new = (array) $new;		// old object

		// validate - if object should write changelog or not
		if ($this->changelog_validate_object () === false) {
			return true;
		}

		// check if syslog globally enabled and write log
	    if($this->settings->enableChangelog==1) {

		    # get user details and initialize required objects
		    $this->Addresses 	= new Addresses ($this->Database);
		    $this->Subnets 		= new Subnets ($this->Database);
		    $this->Sections 	= new Sections ($this->Database);
		    $this->Tools	 	= new Tools ($this->Database);


		    # unset unneeded values and format
		    $this->changelog_unset_unneeded_values ();

		    # calculate diff
		    if($action == "edit") {
				$log = $this->changelog_calculate_edit_diff ();
			}
			elseif($action == "add") {
				//booleans
				foreach ($this->object_new as $k=>$v) {
					$this->object_new[$k] = $this->changelog_make_booleans ($k, $v);
				}
				$log['[create]'] = $this->object_type." created";
				$log['[details]'] = "<br>".$this->array_to_log ($this->object_new);
			}
			elseif($action == "delete") {
				//booleans
				foreach ($this->object_old as $k=>$v) {
					$this->changelog_make_booleans ($k, $v);
				}
				$log['[delete]']  = $this->object_type." deleted";
				$log['[details]'] = "<br>".$this->array_to_log ($this->object_old);
			}
			elseif($action == "truncate") {
				$log['[truncate]'] = "Subnet truncated";
			}
			elseif($action == "resize") {
				$log['[resize]'] = "Subnet Resized";
				$log['[New mask]'] = "/".$this->object_new['mask'];
			}
			elseif($action == "perm_change") {
				$log = $this->changelog_format_permission_change ();
			}

			//if change happened write it!
			if(isset($log)) {
				// execute
				return $this->log_type == "syslog" ? $this->write_changelog_syslog ($log) : $this->write_changelog_to_db ($log);
			}
		}
		# not enabled
		else {
			return true;
		}
	}

	/**
	 * Writes changelog to database
	 *
	 * @access private
	 * @param mixed $changelog
	 * @return void
	 */
	private function write_changelog_to_db ($changelog) {
		# log to array
		$changelog = str_replace("<br>", "\n", $this->array_to_log ($changelog));
		# fetch user id
		$this->get_active_user_id ();

		# set update id based on action
		if ($this->object_action=="add")	{ $obj_id = $this->object_new['id']; }
		else								{ $obj_id = $this->object_old['id']; }
	    # set values
	    $values = array(
	    			"ctype"	 => $this->object_type,
	    			"coid"	 => $obj_id,
	    			"cuser"	 => $this->user_id,
	    			"caction"=> $this->object_action,
	    			"cresult"=> $this->object_result,
	    			"cdate"	 => date("Y-m-d H:i:s"),
	    			"cdiff"	 => $changelog
					);
		# null empty values
		$values = $this->reformat_empty_array_fields ($values, null);

		# execute
		try { $this->Database->insertObject("changelog", $values); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}
		# ok
		return true;
	}

	/**
	 * Checks if object should write changelog
	 *
	 * @access private
	 * @return void
	 */
	private function changelog_validate_object () {
		# set valid objects
		$objects = array(
						"ip_addr",
						"subnet",
						"section"
						);
		# check
		return in_array($this->object_type, $objects) ? true : false;
	}

	/**
	 * Calculate possible chages on edit
	 *
	 * @access private
	 * @return void
	 */
	private function changelog_calculate_edit_diff () {
		//old object - checkboxes that are not present, set them as 0
		foreach($this->object_old as $k=>$v) {
			if(!isset($this->object_new[$k]) && $v=="1") {
				$this->object_new[$k] = 0;
			}
		}
		foreach ($this->object_new as $k=>$v) {
			if(!isset($this->object_old[$k]) && $v=="1") {
				$this->object_old[$k] = 0;
			}
		}
		// ip address - old needs to be transformed to dotted format
		$this->object_old['ip_addr'] = $this->Subnets->transform_to_dotted($this->object_old['ip_addr']);
		// check each value
		foreach($this->object_new as $k=>$v) {
			//change
			if($this->object_old[$k]!=$v && ($this->object_old[$k] != str_replace("\'", "'", $v)))	{
				//empty
				if(strlen(@$this->object_old[$k])==0)	{ $this->object_old[$k] = "NULL"; }
				if(strlen(@$v)==0)						{ $v = "NULL"; }

				//tag change
				if($k == 'state') 				{ $v = $this->changelog_format_tag_diff ($k, $v); }
				//section change
				elseif($k == 'sectionIdNew') 	{ $v = $this->changelog_format_section_diff ($k, $v); }
				//master subnet change
				elseif($k == "masterSubnetId") 	{ $v = $this->changelog_format_master_subnet_diff ($k, $v); }
				//device change
				elseif($k == 'switch') 			{ $v = $this->changelog_format_device_diff ($k, $v); }
				//vlan
				elseif($k == 'vlanId') 			{ $v = $this->changelog_format_vlan_diff ($k, $v); }
				//vrf
				elseif($k == 'vrfId') 			{ $v = $this->changelog_format_vrf_diff ($k, $v); }
				//master section change
				elseif($k == 'masterSection') 	{ $v = $this->changelog_format_master_section_diff ($k, $v); }
				//permission change
				elseif($k == "permissions") 	{ $v = $this->changelog_format_permission_diff ($k, $v); }
				// make booleans
				$v = $this->changelog_make_booleans ($k, $v);
				//set log
				if ($k!=="id")
				$log["[$k]"] = $this->object_old[$k]." => $v";
			}
		}
		// result
		return $log;
	}

	/**
	 * Removes unneeded values from changelog based on log type
	 *
	 * @access private
	 * @return void
	 */
	private function changelog_unset_unneeded_values () {
		# remove ip address fields
		if($this->object_type == "ip_addr") {
			unset(	$this->object_new['subnet'],
					$this->object_new['type'],
					$this->object_new['section'],
					$this->object_new['ip_addr_old'],
					$this->object_new['nostrict']
					);
		}
		# remove subnet fields
		elseif($this->object_type == "subnet")	{
			// remove unneeded values
			unset(	$this->object_new['subnetId'],
					$this->object_new['location'],
					$this->object_new['vrfIdOld'],
					$this->object_new['permissions'],
					$this->object_new['state'],
					$this->object_new['sectionId'],
					$this->object_new['ip']
				);
			# if section does not change
			if($this->object_new['sectionId']==$this->object_new['sectionIdNew']) {
				unset(	$this->object_new['sectionIdNew'],
						$this->object_new['sectionId']);
			}
			else {
				$this->object_old['sectionIdNew'] = $this->object_old['sectionId'];
			}
			//transform subnet to IP address format
			if(strlen($new['subnet'])>0) {
		    	$this->object_new['subnet'] = $this->Subnets->transform_address (substr($this->object_new['subnet'], 0, strpos($this->object_new['subnet'], "/")), "decimal");
			}
		}
		# remove order fields
		elseif($this->object_type == "section") {
			unset($this->object_old['order']);
		}

		# common
		unset($this->object_new['action']);
		unset($this->object_new['editDate'], $this->object_old['editDate']);
	}

	/**
	 * Formats tag from int to nam.
	 *
	 * @access private
	 * @param mixed $k
	 * @param mixed $v
	 * @return void
	 */
	private function changelog_format_tag_diff ($k, $v) {
		$this->object_old[$k] 	= $this->Addresses->address_type_index_to_type($this->object_old[$k]);
		$v 						= $this->Addresses->address_type_index_to_type($v);
		//result
		return $v;
	}

	/**
	 * Formats section if change
	 *
	 * @access private
	 * @param mixed $k
	 * @param mixed $v
	 * @return void
	 */
	private function changelog_format_section_diff ($k, $v) {
		//get old and new device
		if($this->object_old[$k] != "NULL") {
			$section = $this->Sections->fetch_section ("id", $this->object_old[$k]);
			$this->object_old[$k] = $section->name;
		}
		if($v != "NULL")	{
			$section = $this->Sections->fetch_section ("id", $v);
			$v = $section->name;
		}
		//result
		return $v;
	}

	/**
	 * Formats master subnet if change
	 *
	 * @access private
	 * @param mixed $k
	 * @param mixed $v
	 * @return void
	 */
	private function changelog_format_master_subnet_diff ($k, $v) {
		//Old root or not
		if($this->object_old[$k]==0){
			$this->object_old[$k] = "Root";
		} else {
			$subnet = $this->Subnets->fetch_subnet("id", $this->object_old[$k]);
			$this->object_old[$k] = $this->Subnets->transform_address($subnet->subnet, "dotted")."/$subnet->mask [$subnet->description]";
		}
		//New root or not
		if($v==0) {
			$v = "Root";
		} else {
			$subnet = $this->Subnets->fetch_subnet("id", $v);
			$v  = $this->Subnets->transform_address($subnet->subnet, "dotted")."/$subnet->mask [$subnet->description]";
		}
		//result
		return $v;
	}

	/**
	 * Format device if change
	 *
	 * @access private
	 * @param mixed $k
	 * @param mixed $v
	 * @return void
	 */
	private function changelog_format_device_diff ($k, $v) {
		// old none
		if($this->object_old[$k] == 0)	{
			$this->object_old[$k] = "None";
		}
		elseif($this->object_old[$k] != "NULL") {
			$dev = $this->Tools->fetch_object("devices", "id", $this->object_old[$k]);
			$this->object_old[$k] = $dev->hostname;
		}
		// new none
		if($v == 0)	{
			$v = "None";
		}
		if($v != "NULL") {
			$dev = $this->Tools->fetch_object("devices", "id", $v);
			$v = $dev->hostname;
		}
		//result
		return $v;
	}

	/**
	 * Format vlan if change
	 *
	 * @access private
	 * @param mixed $k
	 * @param mixed $v
	 * @return void
	 */
	private function changelog_format_vlan_diff ($k, $v) {
		//old none
		if($this->object_old[$k] == 0)	{
			$this->object_old[$k] = "None";
		}
		elseif($this->object_old[$k] != "NULL") {
			$vlan = $this->Tools->fetch_object("vlans", "vlanId", $this->object_old[$k]);
			$this->object_old[$k] = $vlan->name." [$vlan->number]";
		}
		//new none
		if($v == 0)	{
			$v = "None";
		}
		elseif($v != "NULL") {
			$vlan = $this->Tools->fetch_object("vlans", "vlanId", $v);
			$v = $vlan->name." [$vlan->number]";
		}
		//result
		return $v;
	}

	/**
	 * Format vrf if change
	 *
	 * @access private
	 * @param mixed $k
	 * @param mixed $v
	 * @return void
	 */
	private function changelog_format_vrf_diff ($k, $v) {
		//old none
		if($this->object_old[$k] == 0)	{
			$this->object_old[$k] = "None";
		}
		elseif($this->object_old[$k] != "NULL") {
			$vrf = $this->Tools->fetch_object("vrf", "vrfId", $this->object_old[$k]);
			$this->object_old[$k] = $vrf->name." [$vrf->description]";
		}
		// new none
		if($v == 0)	{
			$v = "None";
		}
		elseif($v != "NULL") {
			$vrf = $this->Tools->fetch_object("vrf", "vrfId", $v);
			$v = $vrf->name." [$vrf->description]";
		}
		//result
		return $v;
	}

	/**
	 * Format master section ifchange
	 *
	 * @access private
	 * @param mixed $k
	 * @param mixed $v
	 * @return void
	 */
	private function changelog_format_master_section_diff ($k, $v) {
		// old root
		if($this->object_old[$k]==0) {
			$this->object_old[$k] = "Root";
		}
		else {
			$section = $this->Sections->fetch_section ("id", $this->object_old[$k]);
			$this->object_old[$k] = $section->name;
		}
		// nwe root
		if($v==0) {
			$v = "Root";
		}
		else {
			$section = $this->Sections->fetch_section ("id", $v);
			$v = $section->name;
		}
		//result
		return $v;
	}

	/**
	 * Format permissions on change - EDIT
	 *
	 * @access private
	 * @param mixed $k
	 * @param mixed $v
	 * @return void
	 */
	private function changelog_format_permission_diff ($k, $v) {
		// get old and compare
		$this->object_new['permissions'] = json_decode(str_replace("\\", "", $this->object_new['permissions']), true);		//Remove /
		$this->object_old['permissions'] = json_decode(str_replace("\\", "", $this->object_old['permissions']), true);		//Remove /

		# Get all groups:
		$groups = (array) $this->Tools->fetch_all_objects("userGroups", "g_id");
		// rekey
		foreach($groups as $g) {
			$out[$g->g_id]['g_name'] = $g->g_name;
		}
		$groups = $out;

		// loop
		foreach($this->object_new['permissions'] as $group_id=>$p) {
			$val[] = $groups[$group_id]['g_name'] ." : ".$this->Subnets->parse_permissions($p);
		}
		$this->object_old[$k] = "";

		//result
		return implode(" ; ", $val);
	}

	/**
	 * Make true / false from 0/1
	 *
	 * @access private
	 * @param mixed $k
	 * @param mixed $v
	 * @return void
	 */
	private function changelog_make_booleans ($k, $v) {
		// list of keys to be changed per object
		$keys['section'] = array("strictMode", "showVLAN", "showVRF");
		$keys['subnet']  = array("allowRequests", "showName", "pingSubnet", "discoverSubnet", "DNSrecursive", "DNSrecords", "isFolder", "isFull");
		$keys['ip_addr'] = array("is_gateway", "excludePing", "PTRignore");

		// check
		if (array_key_exists($this->object_type, $keys)) {
			if (in_array($k, $keys[$this->object_type])) {
				if ($v=="0") { $this->object_old[$k] = "True";	return "False"; }
				else 		 { $this->object_old[$k] = "False"; return "True"; }
			}
			else {
				return $v;
			}
		}
		else {
			return $v;
		}
	}

	/**
	 * Format permission on permission only change
	 *
	 * @access private
	 * @return void
	 */
	private function changelog_format_permission_change () {
		# get old and compare
		$this->object_new['permissions_change'] = json_decode(str_replace("\\", "", $this->object_new['permissions_change']), true);		//Remove /

		# Get all groups:
		$groups = (array) $this->Tools->fetch_all_objects("userGroups", "g_id");
		// rekey
		foreach($groups as $k=>$g) {
			// save
			$out[$g->g_id]['g_name'] = $g->g_name;
		}
		$groups = $out;

		# reformat
		if($this->object_new['permissions_change']!="null") {
			$new_permissions = json_decode($this->object_new['permissions_change']);
			foreach($new_permissions as $group_id=>$p) {
				$log['[Permissions]'] .= "<br>". $groups[$group_id]['g_name'] ." : ".$this->Subnets->parse_permissions($p);
			}
		}
		//result
		return $log;
	}

	/**
	 * fetches all changelogs
	 *
	 * @access public
	 * @param bool $filter (default: false)
	 * @param mixed $expr
	 * @param int $limit (default: 100)
	 * @return void
	 */
	public function fetch_all_changelogs ($filter = false, $expr, $limit = 100) {
	    # set query
		if(!$filter) {
		    $query = "select * from (
						select `cid`, `coid`,`ctype`,`real_name`,`caction`,`cresult`,`cdate`,`cdiff`,`ip_addr`,'mask',`sectionId`,`subnetId`,`ip`.`id` as `tid`,`u`.`id` as `userid`,`su`.`isFolder` as `isFolder`,`su`.`description` as `sDescription`
						from `changelog` as `c`, `users` as `u`,`ipaddresses` as `ip`,`subnets` as `su`
						where `c`.`ctype` = 'ip_addr' and `c`.`cuser` = `u`.`id` and `c`.`coid`=`ip`.`id` and `ip`.`subnetId` = `su`.`id`
						union all
						select `cid`, `coid`,`ctype`,`real_name`,`caction`,`cresult`,`cdate`,`cdiff`,`subnet`,`mask`,`sectionId`,'subnetId',`su`.`id` as `tid`,`u`.`id` as `userid`,`su`.`isFolder` as `isFolder`,`su`.`description` as `sDescription`
						from `changelog` as `c`, `users` as `u`,`subnets` as `su`
						where `c`.`ctype` = 'subnet' and  `c`.`cuser` = `u`.`id` and `c`.`coid`=`su`.`id`
						union all
						select `cid`, `coid`,`ctype`,`real_name`,`caction`,`cresult`,`cdate`,`cdiff`,`name` ,'empty','empty','empty',`su`.`id` as `tid`,`u`.`id` as `userid`,'empty',`su`.`description` as `sDescription`
						from `changelog` as `c`, `users` as `u`,`sections` as `su`
						where `c`.`ctype` = 'section' and `c`.`cuser` = `u`.`id` and `c`.`coid`=`su`.`id`

					) as `ips` order by `cid` desc limit $limit;";
		}
		# filter
		else {
			/* replace * with % */
			if(substr($expr, 0, 1)=="*")								{ $expr[0] = "%"; }
			if(substr($expr, -1, 1)=="*")								{ $expr = substr_replace($expr, "%", -1);  }
			if(substr($expr, 0, 1)!="*" && substr($expr, -1, 1)!="*")	{ $expr = "%".$expr."%"; }

		    $query = "select * from (
						select `cid`, `coid`,`ctype`,`real_name`,`caction`,`cresult`,`cdate`,`cdiff`,`ip_addr`,'mask',`sectionId`,`subnetId`,`ip`.`id` as `tid`,`u`.`id` as `userid`,`su`.`isFolder` as `isFolder`,`su`.`description` as `sDescription`
						from `changelog` as `c`, `users` as `u`,`ipaddresses` as `ip`,`subnets` as `su`
						where `c`.`ctype` = 'ip_addr' and `c`.`cuser` = `u`.`id` and `c`.`coid`=`ip`.`id` and `ip`.`subnetId` = `su`.`id`
						union all
						select `cid`, `coid`,`ctype`,`real_name`,`caction`,`cresult`,`cdate`,`cdiff`,`subnet`,`mask`,`sectionId`,'subnetId',`su`.`id` as `tid`,`u`.`id` as `userid`,`su`.`isFolder` as `isFolder`,`su`.`description` as `sDescription`
						from `changelog` as `c`, `users` as `u`,`subnets` as `su`
						where `c`.`ctype` = 'subnet' and  `c`.`cuser` = `u`.`id` and `c`.`coid`=`su`.`id`
						union all
						select `cid`, `coid`,`ctype`,`real_name`,`caction`,`cresult`,`cdate`,`cdiff`,`name` ,'empty','empty','empty',`su`.`id` as `tid`,`u`.`id` as `userid`,'empty',`su`.`description` as `sDescription`
						from `changelog` as `c`, `users` as `u`,`sections` as `su`
						where `c`.`ctype` = 'section' and `c`.`cuser` = `u`.`id` and `c`.`coid`=`su`.`id`
					) as `ips`
					where `coid`='$expr' or `ctype`='$expr' or `real_name` like '$expr' or `cdate` like '$expr' or `cdiff` like '$expr' or INET_NTOA(`ip_addr`) like '$expr'
					order by `cid` desc limit $limit;";
		}

	    # fetch
	    try { $logs = $this->Database->getObjectsQuery($query); }
		catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), false);	return false; }

	    # return results
	    return $logs;
	}

	/**
	 * Fetches changelog for addresses in subnet for all slave subnets
	 *
	 * @access public
	 * @param mixed $subnetId
	 * @param int $limit (default: 50)
	 * @return void
	 */
	public function fetch_subnet_addresses_changelog_recursive ($subnetId, $limit = 50) {
	    # get all addresses ids
	    $ips  = array();
		$Addresses = new Addresses ($this->Database);
	    $ips = $Addresses->fetch_subnet_addresses_recursive ($subnetId, false);

	    # fetch changelog for IPs
	    if(sizeof($ips) > 0) {
		    # query
		    $query  = "select
		    			`u`.`real_name`,`o`.`id`,`o`.`ip_addr`,`o`.`description`,`o`.`id`,`o`.`subnetId`,`c`.`caction`,`c`.`cresult`,`c`.`cdate`,`c`.`cdiff`
						from `changelog` as `c`, `users` as `u`, `ipaddresses` as `o`
						where `c`.`cuser` = `u`.`id` and `c`.`coid`=`o`.`id`
						and (";
			foreach($ips as $ip) {
			$query .= "`c`.`coid` = ? or ";
			$args[] = $ip->id;
			}
			$query  = substr($query, 0, -3);
			$query .= ") and `c`.`ctype` = 'ip_addr' order by `c`.`cid` desc limit $limit;";

			# fetch
		    try { $logs = $this->Database->getObjectsQuery($query, $args); }
			catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), false);	return false; }

		    # return result
		    return $logs;
	    }
		else {
			return false;
		}
	}

	/**
	 * fetch changelog entries for specified type entry
	 *
	 * @param $object_type = 'ip_addr','subnet','section'
	 * @param $coid = objectId from ctype definition
	 * @param $long (default: false)
	 * @param $limit (default: 50)
	 */
	public function fetch_changlog_entries($object_type, $coid, $long = false, $limit = 50) {
	    # change ctype to match table
		if($object_type=="ip_addr")	$object_typeTable = "ipaddresses";
		elseif($object_type=="subnet")$object_typeTable = "subnets";
		else					$object_typeTable = $object_type;

	    # query
	    if($long) {
		    $query = "select *
						from `changelog` as `c`, `users` as `u`, `$object_typeTable` as `o`
						where `c`.`cuser` = `u`.`id` and `c`.`coid`=`o`.`id`
						and `c`.`coid` = ? and `c`.`ctype` = ? order by `c`.`cid` desc limit $limit;";
		} else {
		    $query = "select *
						from `changelog` as `c`, `users` as `u`
						where `c`.`cuser` = `u`.`id`
						and `c`.`coid` = ? and `c`.`ctype` = ? order by `c`.`cid` desc limit $limit;";
		}
	    # fetch
	    try { $logs = $this->Database->getObjectsQuery($query, array($coid, $object_type)); }
		catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), false);	return false; }

	    # return result
	    return $logs;
	}

	/**
	 * Fetches changelog entries for all slave subnets recursive
	 *
	 * @access public
	 * @param mixed $subnetId
	 * @param int $limit (default: 50)
	 * @return void
	 */
	public function fetch_subnet_slaves_changlog_entries_recursive($subnetId, $limit = 50) {
		# fetch all slave subnet ids
		$Subnets = new Subnets ($this->Database);
		$Subnets->reset_subnet_slaves_recursive ();
		$Subnets->fetch_subnet_slaves_recursive ($subnetId);
		# remove master subnet ID
		$key = array_search($subnetId, $Subnets->slaves);
		unset($Subnets->slaves[$key]);
		$Subnets->slaves = array_unique($Subnets->slaves);

	    # if some slaves are present get changelog
	    if(sizeof($Subnets->slaves) > 0) {
		    # set query
		    $query  = "select
						`u`.`real_name`,`o`.`sectionId`,`o`.`subnet`,`o`.`mask`,`o`.`isFolder`,`o`.`description`,`o`.`id`,`c`.`caction`,`c`.`cresult`,`c`.`cdate`,`c`.`cdiff`  from `changelog` as `c`, `users` as `u`, `subnets` as `o`
						where `c`.`cuser` = `u`.`id` and `c`.`coid`=`o`.`id`
						and (";
			foreach($Subnets->slaves as $slaveId) {
			$query .= "`c`.`coid` = ? or ";
			$args[] = $slaveId;							//set keys
			}
			$query  = substr($query, 0, -3);
			$query .= ") and `c`.`ctype` = 'subnet' order by `c`.`cid` desc limit $limit;";

			# fetch
		    try { $logs = $this->Database->getObjectsQuery($query, $args); }
			catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), false);	return false; }

		    # return result
		    return $logs;
	    }
		else {
			return false;
		}
	}
}