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
	 * debugging flag
	 *
	 * (default value: false)
	 *
	 * @var bool
	 * @access public
	 */
	public $debugging = false;

	/**
	 * log_type
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $log_type;

	/**
	 * log_command
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $log_command = null;

	/**
	 * log_details
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $log_details = null;

	/**
	 * Syslog facility
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $syslog_facility;

	/**
	 * syslog_priority
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $syslog_priority;

	/**
	 * log severity
	 *
	 *  0: informational
	 *  1: warning
	 *  2: error
	 *
	 * (default value: 0)
	 *
	 * @var int
	 * @access protected
	 */
	protected $log_severity = 0;

	/**
	 * New object (changed)
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $object_new;

	/**
	 * Old object (before change)
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $object_old;

	/**
	 * object_type
	 *
	 * @var mixed
	 * @access public
	 */
	public $object_type;

	/**
	 * Object action
	 *
	 *  add, edit, delete
	 *
	 * @var mixed
	 * @access public
	 */
	public $object_action;

	/**
	 * Result - success, failure
	 *
	 * @var mixed
	 * @access public
	 */
	public $object_result;

	/**
	 * mail_changelog
	 *
	 * @var mixed
	 * @access public
	 */
	public $mail_changelog;

	/**
	 * log_username
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $log_username	= null;

	/**
	 * user details
	 *
	 * @var mixed
	 * @access public
	 */
	public $user;

	/**
	 * id of user
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $user_id	= null;

	/**
	 * Database object
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Database;

	/**
	 * Result object
	 *
	 * @var mixed
	 * @access public
	 */
	public $Result;

	/**
	 * Addresses object
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Addresses;

	/**
	 * Sections object
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Sections;

	/**
	 * Subnets object
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Subnets;

	/**
	 * Tools object
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Tools;

	/**
	 * settings
	 *
	 * @var mixed
	 * @access public
	 */
	public $settings;





	/**
	 * __construct function.
	 *
	 * @access public
	 * @param Database_PDO $database
	 * @param mixed $settings (default: null)
	 */
	public function __construct (Database_PDO $database, $settings = null) {
		# Save database object
		$this->Database = $database;
		# Result
		$this->Result = new Result ();
		# User
		$this->log_username = @$_SESSION['ipamusername'];

		# settings
		if ($settings===null || $settings===false) {
			$this->get_settings(); #assigns $this->settings internally
		}
		else {
			$this->settings = (object) $settings;
		}
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
	 * Gets id of active user
	 *
	 * @access private
	 * @return void
	 */
	private function get_active_user_id () {
		# cache
		if ($this->user_id===null) {
			# null
			$user_id = null;
			if (!isset($_SESSION['ipamusername'])) {
				// when API calls subnet_create we get:
				// Error: SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'cuser' cannot be null
				// so let's get a user_id
				if (array_key_exists("HTTP_PHPIPAM_TOKEN", $_SERVER)) {
					$admin = new Admin($this->Database, False);
					$token = $admin->fetch_object ("users", "token", $_SERVER['HTTP_PHPIPAM_TOKEN']);
					if ($token === False) {
						$this->user_id = null;
					}
					else {
						$user_id = $token;
					}
				}
				else {
					$this->user_id = null;
				}
			}
			else {
				try { $user_id = $this->Database->getObjectQuery("select * from `users` where `username` = ? limit 1", array($_SESSION['ipamusername'])); }
				catch (Exception $e) { $this->Result->show("danger", _("Database error: ").$e->getMessage()); }
			}
			# save id
			$this->user_id = $user_id->id;
			# save user
			$this->user = $user_id;
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
		$this->log_command = $command;
		$this->log_details = $details;
		$this->log_severity = $severity;
		$this->log_username	= $username===null ? $this->log_username : $username;

		// validate
		!is_null($this->log_command) ? : $this->Result->show("danger", _("Invalid log command"));

		// execute
		if ($this->log_type == "syslog")	{ $this->syslog_write (); }
		elseif ($this->log_type == "both")	{ $this->database_write_log (); $this->syslog_write (); }
		else								{ $this->database_write_log (); }
	}






	/**
	 *	@syslog log methods
	 *	--------------------------------
	 */

	/**
	 * Generates new syslog message
	 *
	 *		# > syslogd example:
	 *
	 *		# phpipam syslog messages setup
	 *		# user.alert;user.warning;user.debug            /var/log/messages
	 *		auth.alert;auth.warning;auth.debug              /var/log/auth.log
	 *
	 *		# log all phpipam messages
	 *		!phpipam
	 *		*.*                                             /var/log/phpipam.log
	 *		!*
	 *
	 *		# changelog
	 *		!phpipam-changelog
	 *		*.*                                             /var/log/phpipam-changelog.log
	 *		!*
	 *
	 *		# > rysylog example
	 *		auth.alert;auth.warning;auth.debug              /var/log/auth.log
	 *		if $programname == 'phpipam' then /var/log/phpipam.log
	 *		if $programname == 'phpipam-changelog' then /var/log/phpipam-changelog.log
	 *
	 * @access private
	 * @return void
	 */
	private function syslog_write () {
		# set facility
		$this->syslog_set_facility ();
		# set priority
		$this->syslog_set_priority ();
		# format details
		$this->syslog_format_details ();

		# add username if present
		$username = $this->log_username!==null ? $this->log_username." | " : "";

		# open syslog and write log
		openlog('phpipam', LOG_NDELAY | LOG_PID, $this->syslog_facility);
		syslog($this->syslog_priority, "$_SERVER[REMOTE_ADDR] | ".$username.$this->log_command." | ".$this->log_details);

		# close
		closelog();
	}

	/**
	 * Sets facility for syslog
	 *
	 * @access private
	 * @return void
	 */
	private function syslog_set_facility () {
		# for windows we can only use LOG_USER
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')		{ $facility = "LOG_USER"; }
		else {
			//login, logout
			if (strpos($this->log_command, "login")>0 ||
				strpos($this->log_command, "logged out")>0) { $facility = "LOG_AUTH"; }
			else 											{ $facility = "LOG_USER"; }
		}
		# save
		$this->syslog_facility = constant($facility);
	}

	/**
	 * Sets priority
	 *
	 * @access private
	 * @return void
	 */
	private function syslog_set_priority () {
    	// init
    	$priorities = array();
		# definitions
		$priorities[] = "LOG_EMERG";
		$priorities[] = "LOG_ALERT";
		$priorities[] = "LOG_CRIT";
		$priorities[] = "LOG_ERR";
		$priorities[] = "LOG_WARNING";
		$priorities[] = "LOG_NOTICE";
		$priorities[] = "LOG_INFO";
		$priorities[] = "LOG_DEBUG";
		# set
		if ($this->log_severity == "2")		{ $priority = "LOG_ALERT"; }
		elseif ($this->log_severity == "1")	{ $priority = "LOG_WARNING"; }
		else								{ $priority = "LOG_DEBUG"; }
		# set
		$this->syslog_priority = constant($priority);
	}

	/**
	 * Reformat syslog details
	 *
	 * @access private
	 * @return void
	 */
	private function syslog_format_details () {
		// replace <br>
		$this->log_details = str_replace("<br>", ",",$this->log_details);
		$this->log_details = str_replace("<hr>", ",",$this->log_details);
		// replace spaces
		$this->log_details = trim($this->log_details, ",");
	}

	/**
	 * Writes changelog to syslog
	 *
	 * @access private
	 * @param mixed $changelog
	 * @return void
	 */
	private function syslog_write_changelog ($changelog) {
		# fetch user id
		$this->get_active_user_id ();
		# set update id based on action
		if ($this->object_action=="add")	{ $obj_id = $this->object_new['id']; }
		else								{ $obj_id = $this->object_old['id']; }

		# format
		$changelog = str_replace("<br>", ",",$changelog);
		$changelog = str_replace("<hr>", ",",$changelog);

		# formulate
		$log = array();
		foreach($changelog as $k=>$l) {
			$log[] = "$k: $l";
		}

		# open syslog and write log
		openlog('phpipam-changelog', LOG_NDELAY | LOG_PID, LOG_USER);
		syslog(LOG_DEBUG, "changelog | $this->log_username | $this->object_type | $obj_id | $this->object_action | ".date("Y-m-d H:i:s")." | ".implode(", ",$log));
		# close
		closelog();
	}











	/**
	 *	@database log methods
	 *	--------------------------------
	 */

	/**
	 * Writes log to local database
	 *
	 * @access private
	 * @return boolean
	 */
	private function database_write_log () {
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
		$values = $this->reformat_empty_array_fields($values, null);
		$values = $this->strip_input_tags($values);

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
	 * @param string $object_type
	 * @param string $action
	 * @param string $result
	 * @param array $old (default: array())
	 * @param array $new (default: array())
	 * @param bool $mail_changelog (default: true)
	 * @return boolean|null
	 */
	public function write_changelog ($object_type, $action, $result, $old = array(), $new = array(), $mail_changelog = true) {
		//set values
		$this->object_type 	  = $object_type;
		$this->object_action  = $action;
		$this->object_result  = $result;
		$this->mail_changelog = $mail_changelog;
		//cast diff objects as array
		$this->object_old = (array) $old;		// new object
		$this->object_new = (array) $new;		// old object

		// validate - if object should write changelog or not
		if ($this->changelog_validate_object () === false) {
			return true;
		}

		// make sure we have settings
		$this->get_settings ();

		// check if syslog globally enabled and write log
	    if($this->settings->enableChangelog==1) {
		    # get user details and initialize required objects
		    $this->Addresses = new Addresses ($this->Database);
		    $this->Subnets 		= new Subnets ($this->Database);
		    $this->Sections = new Sections ($this->Database);
		    $this->Tools	 = new Tools ($this->Database);

		    # unset unneeded values and format
		    $this->changelog_unset_unneeded_values ();

		    # default log
		    $log = array();

		    # calculate diff
		    if($action == "edit") {
				$log = $this->changelog_calculate_edit_diff ();
			}
			elseif($action == "add") {
				//booleans
				foreach ($this->object_new as $k=>$v) {
					$this->object_new[$k] = $this->changelog_make_booleans ($k, $v);
				}
				$log['details'] = "<br>".$this->array_to_log ($this->object_new, true);
			}
			elseif($action == "delete") {
				//booleans
				foreach ($this->object_old as $k=>$v) {
					$this->changelog_make_booleans ($k, $v);
				}
				$log['details'] = "<br>".$this->array_to_log ($this->object_old, true);
			}
			elseif($action == "truncate") {
				$log['truncate'] = "Subnet truncated";
			}
			elseif($action == "resize") {
				$log['resize'] = "Subnet Resized";
				$log['mask'] = $this->object_old['mask']."/".$this->object_new['mask'];
			}
			elseif($action == "perm_change") {
				$log = $this->changelog_format_permission_change ();
			}

			# reformat null values
			$log =str_replace(": <br>", ": / <br>", $log);

			//if change happened write it!
			if(isset($log) && sizeof($log)>0) {
				// execute
				if ($this->log_type == "syslog")	{ $this->syslog_write_changelog ($log); }
				elseif ($this->log_type == "both")	{ $this->changelog_write_to_db ($log); $this->syslog_write_changelog ($log); }
				else								{ $this->changelog_write_to_db ($log); }
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
	private function changelog_write_to_db ($changelog) {
		# log to array
		$changelog = str_replace("<br>", "\r\n", $this->array_to_log ($changelog, true));
		# fetch user id
		$this->get_active_user_id ();

		# null and from cli, set admin user
		if ($this->user===null && php_sapi_name()=="cli") { $this->user_id = 1; }

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
		# mail
		if ($this->mail_changelog)
		$this->changelog_send_mail ($changelog);
		# ok
		return true;
	}

	/**
	 * Checks if object should write changelog
	 *
	 * @access private
	 * @return boolean
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
		$this->object_old['ip_addr'] = $this->Subnets->transform_address($this->object_old['ip_addr'], "dotted");
		$this->object_new['ip_addr'] = $this->Subnets->transform_address($this->object_new['ip_addr'], "dotted");

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
				$log["$k"] = $this->object_old[$k]." => $v";
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
					$this->object_new['nostrict'],
					$this->object_new['start'],
					$this->object_new['stop']
					);
			unset(	$this->object_old['subnet'],
					$this->object_old['type'],
					$this->object_old['section'],
					$this->object_old['ip_addr_old'],
					$this->object_old['nostrict'],
					$this->object_old['start'],
					$this->object_old['stop']
					);
			# reformat ip
			if (isset($this->object_old['ip_addr']))	{ $this->object_old['ip_addr'] = $this->Subnets->transform_address ($this->object_old['ip_addr'],"dotted"); }
			if (isset($this->object_new['ip_addr']))	{ $this->object_new['ip_addr'] = $this->Subnets->transform_address ($this->object_new['ip_addr'],"dotted"); }

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
			unset(	$this->object_old['subnetId'],
					$this->object_old['location'],
					$this->object_old['vrfIdOld'],
					$this->object_old['permissions'],
					$this->object_old['state'],
					$this->object_old['sectionId'],
					$this->object_old['ip']
				);

			# if section does not change
			if($this->object_new['sectionId']==$this->object_new['sectionIdNew']) {
				unset(	$this->object_new['sectionIdNew'],
						$this->object_new['sectionId'],
						$this->object_old['sectionId']);
			}
			else {
				$this->object_old['sectionIdNew'] = $this->object_old['sectionId'];
			}

			//transform subnet to IP address format
			if(strlen($this->object_new['subnet'])>0) 	{ $this->object_new['subnet'] = $this->Subnets->transform_address ($this->object_new['subnet'], "dotted");}
			if(strlen($this->object_old['subnet'])>0) 	{ $this->object_old['subnet'] = $this->Subnets->transform_address ($this->object_old['subnet'], "dotted");}

			//remove subnet/mask for folders
			if (@$this->object_new['isFolder']=="1")	{ unset($this->object_new['subnet'], $this->object_new['mask']); }
			if (@$this->object_old['isFolder']=="1")	{ unset($this->object_old['subnet'], $this->object_old['mask']); }
		}
		# remove order fields
		elseif($this->object_type == "section") {
			unset($this->object_old['order']);
		}

		# common
		unset($this->object_new['action']);
		unset($this->object_new['editDate'], $this->object_old['editDate']);
		unset($this->object_new['csrf_cookie']);
	}

	/**
	 * Formats tag from int to nam.
	 *
	 * @access private
	 * @param string $k
	 * @param mixed $v
	 * @return void
	 */
	private function changelog_format_tag_diff ($k, $v) {
		$this->object_old[$k] = $this->Addresses->address_type_index_to_type($this->object_old[$k]);
		$v 						= $this->Addresses->address_type_index_to_type($v);
		//result
		return $v;
	}

	/**
	 * Formats section if change
	 *
	 * @access private
	 * @param string $k
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
	 * @param string $k
	 * @param mixed $v
	 * @return string
	 */
	private function changelog_format_master_subnet_diff ($k, $v) {
		//Old root or not
		if($this->object_old[$k]==0){
			$this->object_old[$k] = "Root";
		}
		else {
			$subnet = $this->Subnets->fetch_subnet("id", $this->object_old[$k]);
			$this->object_old[$k] = $this->Subnets->transform_address($subnet->subnet, "dotted")."/$subnet->mask [$subnet->description]";
		}
		//New root or not
		if($v==0) {
			$v = "Root";
		}
		else {
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
	 * @param string $k
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
	 * @param string $k
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
	 * @param string $k
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
	 * @param string $k
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
	 * @param string $k
	 * @param mixed $v
	 * @return string
	 */
	private function changelog_format_permission_diff ($k, $v) {
		// get old and compare
		$this->object_new['permissions'] = json_decode(str_replace("\\", "", $this->object_new['permissions']), true);		//Remove /
		$this->object_old['permissions'] = json_decode(str_replace("\\", "", $this->object_old['permissions']), true);		//Remove /

		# Get all groups:
		$groups = (array) $this->Tools->fetch_all_objects("userGroups", "g_id");
		// rekey
		$out = array();
		foreach($groups as $g) {
			$out[$g->g_id]['g_name'] = $g->g_name;
		}
		$groups = $out;

		// loop
		$val = array();
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
    	// init
    	$keys = array();
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
		$out = array();
		$log = array();

		foreach($groups as $k=>$g) {
			// save
			$out[$g->g_id]['g_name'] = $g->g_name;
		}
		$groups = $out;

		# reformat
		if($this->object_new['permissions_change']!="null") {
			$new_permissions = json_decode($this->object_new['permissions_change']);
			foreach($new_permissions as $group_id=>$p) {
				$log['Permissions'] .= "<br>". $groups[$group_id]['g_name'] ." : ".$this->Subnets->parse_permissions($p);
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
					where `coid`=:expr or `ctype`=:expr or `real_name` like :expr or `cdate` like :expr or `cdiff` like :expr or INET_NTOA(`ip_addr`) like :expr
					order by `cid` desc limit $limit;";
		}

	    # fetch
	    try { $logs = $this->Database->getObjectsQuery($query, array("expr"=>$expr)); }
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

            $args = array();
			foreach($ips as $ip) {
			$query .= "`c`.`coid` = ? or ";
			$args[] = $ip->id;
			}
			$query  = substr($query, 0, -3);
			$query .= ") and `c`.`ctype` = 'ip_addr' order by `c`.`cid` desc limit $limit;";

			# fetch
		    try { $logs = $this->Database->getObjectsQuery($query, array_filter($args)); }
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
		}
		else {
		    $query = "select *
						from `changelog` as `c`, `users` as `u`
						where `c`.`cuser` = `u`.`id`
						and `c`.`coid` = ? and `c`.`ctype` = ? order by `c`.`cid` desc limit $limit;";
		}
	    # fetch
	    try { $logs = $this->Database->getObjectsQuery($query, array($coid, $object_type)); }
		catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), false); return false; }

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
			if(!isset($args)) $args = array();
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












	/**
	 *	@changelog mail methods
	 *	--------------------------------
	 */

	/**
	 * Send mail on new changelog
	 *
	 * @access public
	 * @param string $changelog
	 * @return boolean
	 */
	public function changelog_send_mail ($changelog) {

		# initialize tools class
		$this->Tools = new Tools ($this->Database);

		# set object
		$obj_details = $this->object_action == "add" ? $this->object_new : $this->object_old;

		# change ip_addr
		$this->object_type = str_replace("ip_addr", "address", $this->object_type);
		$this->object_type = str_replace("ip_range", "address range", $this->object_type);

		# folder
		if ( $this->object_new['isFolder']=="1"	||$this->object_old['isFolder']=="1")	{ $this->object_type = "folder"; }

		# set subject
		$subject = string;
		if($this->object_action == "add") 		{ $subject = ucwords($this->object_type)." create notification"; }
		elseif($this->object_action == "edit") 	{ $subject = ucwords($this->object_type)." change notification"; }
		elseif($this->object_action == "delete"){ $subject = ucwords($this->object_type)." delete notification"; }

		// if address we need subnet details !
		$address_subnet = array();
		if ($this->object_type=="address")		{ $address_subnet = (array) $this->Tools->fetch_object("subnets", "id", $obj_details['subnetId']); }

		# set object details
		$details = string;
		if ($this->object_type=="section") 		{ $details = "<a href='".$this->createURL().create_link("subnets",$obj_details['id'])."'>".$obj_details['name'] . "(".$obj_details['description'].") - id ".$obj_details['id']."</a>"; }
		elseif ($this->object_type=="subnet")	{ $details = "<a href='".$this->createURL().create_link("subnets",$obj_details['sectionId'],$obj_details['id'])."'>".$this->Subnets->transform_address ($obj_details['subnet'], "dotted")."/".$obj_details['mask']." (".$obj_details['description'].") - id ".$obj_details['id']."</a>"; }
		elseif ($this->object_type=="folder")	{ $details = "<a href='".$this->createURL().create_link("folder",$obj_details['sectionId'],$obj_details['id'])."'>".$obj_details['description']." - id ".$obj_details['id']."</a>"; }
		elseif ($this->object_type=="address")	{ $details = "<a href='".$this->createURL().create_link("subnets",$address_subnet['sectionId'],$obj_details['subnetId'],"address-details",$obj_details['id'])."'>".$this->Subnets->transform_address ($obj_details['ip_addr'], "dotted")." ( hostname ".$obj_details['dns_name'].", subnet: ".$this->Subnets->transform_address ($address_subnet['subnet'], "dotted")."/".$address_subnet['mask'].")- id ".$obj_details['id']."</a>"; }
		elseif ($this->object_type=="address range")	{ $details = $changelog; }

		# set content
		$style = "face='Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;'";
		$content = array();
		$content[] = "<div style='padding:10px;'>";
		$content[] = "<table>";
		$content[] = "<tr><td><font $style>Object type:</font><td><font $style>".$this->object_type."</font></td></tr>";
		$content[] = "<tr><td><font $style>Object details:</font><td><font $style>".$details."</font></td></tr>";
		$content[] = "<tr><td><font $style>User:</font><td><font $style>".$this->user->real_name." (".$this->user->username.")"."</font></td></tr>";
		$content[] = "<tr><td><font $style>Action:</font><td><font $style>".$this->object_action."</font></td></tr>";
		$content[] = "<tr><td><font $style>Date:</font><td><font $style>".date("Y-m-d H:i:s")."</font></td></tr>";
		$content[] = "<tr><td colspan='2'><hr></td></tr>";
		$content[] = "<tr><td colspan='2'><font $style>Changes:<br>";
		$content[] = "<tr><td colspan='2'><font $style>&nbsp;<br>";
		$changelog = str_replace("\r\n", "<br>",$changelog);
		$content[] = "$changelog</font></td></tr>";
		$content[] = "</table>";
		$content[] = "</div>";

		# set plain content
		$content_plain = array();
		$content_plain[] = "Object type: ".$this->object_type;
		$content_plain[] = "Object details: ".$details;
		$content_plain[] = "User: ".$this->user->real_name." (".$this->user->username.")";
		$content_plain[] = "Action: ".$this->object_action;
		$content_plain[] = "Date: ".date("Y-m-d H:i:s");
		$content_plain[] = "\r\n--------------------\r\n";
		$content_plain[] = str_replace("<br>", "\r\n",$changelog);


		# get all admins and check who to end mail to
		$recipients = $this->changelog_mail_get_recipients ();
		if($recipients ===false) 				{ return true; }

		# fetch mailer settings
		$mail_settings = $this->Tools->fetch_object("settingsMail", "id", 1);

		# initialize mailer
		$phpipam_mail = new phpipam_mail($this->settings, $mail_settings);
		$phpipam_mail->initialize_mailer();

		// set content
		$content 		= $phpipam_mail->generate_message (implode("\r\n", $content));
		$content_plain = implode("\r\n",$content_plain);

		# try to send
		try {
			$phpipam_mail->Php_mailer->setFrom($mail_settings->mAdminMail, $mail_settings->mAdminName);
			foreach($recipients as $r) {
			$phpipam_mail->Php_mailer->addAddress(addslashes(trim($r->email)));
			}
			$phpipam_mail->Php_mailer->Subject = $subject;
			$phpipam_mail->Php_mailer->msgHTML($content);
			$phpipam_mail->Php_mailer->AltBody = $content_plain;
			//send
			$phpipam_mail->Php_mailer->send();
		} catch (phpmailerException $e) {
			$this->Result->show("danger", "Mailer Error: ".$e->errorMessage(), true);
		} catch (Exception $e) {
			$this->Result->show("danger", "Mailer Error: ".$e->errorMessage(), true);
		}

		# ok
		return true;
	}


	/**
	 * Get all admins that are set to receive changelog
	 *
	 * @access private
	 * @return void
	 */
	private function changelog_mail_get_recipients () {
		// get all admins and check who to end mail to
		$recipients = $this->Tools->fetch_multiple_objects ("users", "role", "Administrator", "id", true);
		//check recipients
		if ($recipients!==false) {
			// check
			$m = 0;
			foreach($recipients as $k=>$r) {
				if($r->mailChangelog!="Yes") {
					unset($recipients[$k]);
				}
				else {
					$m++;
				}
			}
			// if none return false
			if ($m==0) 	{ return false; }
			else 		{ return $recipients; }
		}
		else {
			return false;
		}
	}
}
