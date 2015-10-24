<?php

/**
 *	phpIPAM IP addresses class
 */

class Addresses extends Common_functions {

	/**
	 * public variables
	 */
	public $addresses;						//(array of objects) to store addresses, address ID is array index
	public $address_types;					//(array) address types
	public $mail_changelog = true;

	/**
	 * protected variables
	 */
	protected $debugging = false;			//(bool) debugging flag

	/**
	 * object holders
	 */
	protected $Net_IPv4;					//PEAR NET IPv4 object
	protected $Net_IPv6;					//PEAR NET IPv6 object
	public $Result;							//for Result printing
	protected $Database;					//for Database connection
	protected $Subnets;						//for Subnets object
	public $Log;							//for Logging connection




	/**
	 * __construct function
	 *
	 * @access public
	 */
	public function __construct (Database_PDO $database) {
		# Save database object
		$this->Database = $database;
		# initialize Result
		$this->Result = new Result ();
		# debugging
		$this->set_debugging();

		# Log object
		$this->Log = new Logging ($this->Database);
	}









	/**
	* @address tag methods
	* -------------------------------
	*/

	/**
	 * Returns array of address types.
	 *
	 * @access public
	 * @return array of address types and parameters
	 */
	public function addresses_types_fetch () {
		# check cache
		if($this->address_types!=null) {
			return $this->address_types;
		}
		else {
			try { $types = $this->Database->getObjects("ipTags", 'id', true); }
			catch (Exception $e) {
				$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
				return false;
			}
			# save to array
			foreach($types as $t) {
				$types_out[$t->id] = (array) $t;
			}
			# save to cache
			$this->address_types = $types_out;
			# return
			return $types_out;
		}
	}

	/**
	 * Sets address tag
	 *
	 * @access public
	 * @param int $state
	 * @return mixed tag
	 */
	public function address_type_format_tag ($state) {
		# fetch address states
		$this->addresses_types_fetch();
		# result
		if(!isset($this->address_types[$state]))	{
			return $state;
		}
		else {
			if($this->address_types[$state]['showtag']==1) {
				return "<i class='fa fa-".$this->address_types[$state]['type']." fa-tag state' rel='tooltip' style='color:".$this->address_types[$state]['bgcolor']."' title='"._($this->address_types[$state]['type'])."'></i>";
			}
		}
	}

	/**
	 * returns address type from index
	 *
	 *		1 > Offline
	 *
	 * @access public
	 * @param int $index
	 * @return mixed address type
	 */
	public function address_type_index_to_type ($index) {
		# fetch address states
		$this->addresses_types_fetch();
		# return
		if(isset($this->address_types[$index])) {
			return $this->address_types[$index]['type'];
		}
		else {
			return $index;
		}
	}

	/**
	 * Returns address index from type
	 *
	 *	Offline > 1
	 *
	 * @access public
	 * @param mixed $type
	 * @return void
	 */
	public function address_type_type_to_index ($type = "Used") {
		# null of no length
		$type = strlen($type)==0 || is_null($type) ? "Used" : $type;
		# fetch address states
		$this->addresses_types_fetch();
		# reindex
		foreach($this->address_types as $s) {
			$states_assoc[$s['type']] = $s;
		}
		# return
		if(isset($states_assoc[$type])) {
			return $states_assoc[$type]['id'];
		}
		else {
			return $type;
		}
	}












	/**
	* @address methods
	* -------------------------------
	*/

	/**
	 * Fetches address by specified method
	 *
	 * @access public
	 * @param string $method (default: "id")
	 * @param mixed $id
	 * @return object address
	 */
	public function fetch_address ($method, $id) {
		# null method
		$method = is_null($method) ? "id" : $this->Database->escape($method);
		# check cache first
		if(isset($this->addresses[$id]))	{
			return $this->addresses[$id];
		}
		else {
			try { $address = $this->Database->getObjectQuery("SELECT * FROM `ipaddresses` where `$method` = ? limit 1;", array($id)); }
			catch (Exception $e) {
				$this->Result->show("danger", _("Error: ").$e->getMessage());
				return false;
			}
			# save to addresses cache
			if(sizeof($address)>0) {
				# add decimal format
				$address->ip = $this->transform_to_dotted ($address->ip_addr);
				# save to subnets
				$this->addresses[$id] = (object) $address;
			}
			#result
			return sizeof($address)>0 ? $address : false;
		}
	}

	/**
	 * Fetch addresses on int ip_addr and subnetId
	 *
	 * @access public
	 * @param mixed $ip_addr
	 * @param mixed $subnetId
	 * @return void
	 */
	public function fetch_address_multiple_criteria ($ip_addr, $subnetId) {
		try { $address = $this->Database->getObjectQuery("SELECT * FROM `ipaddresses` where `ip_addr` = ? and `subnetId` = ? limit 1;", array($ip_addr, $subnetId)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# save to addresses cache
		if(sizeof($address)>0) {
			# add decimal format
			$address->ip = $this->transform_to_dotted ($address->ip_addr);
			# save to subnets
			$this->addresses[$address->id] = (object) $address;
		}
		#result
		return sizeof($address)>0 ? $address : false;
	}

	/**
	 * Address modification
	 *
	 * @access public
	 * @param mixed $address
	 * @param bool $mail_changelog (default: true)
	 * @return void
	 */
	public function modify_address ($address, $mail_changelog = true) {
		# save changelog
		$this->mail_changelog  = $mail_changelog;
		# null empty values
		$address = $this->reformat_empty_array_fields ($address, null);
		# strip tags
		$address = $this->strip_input_tags ($address);
		# execute based on action
		if($address['action']=="add")			{ return $this->modify_address_add ($address); }							//create new address
		elseif($address['action']=="edit")		{ return $this->modify_address_edit ($address); }							//modify existing address
		elseif($address['action']=="delete")	{ return $this->modify_address_delete ($address); }							//delete address
		elseif($address['action']=="move")		{ return $this->modify_address_move ($address); }							//move to new subnet
		else									{ return $this->Result->show("danger", _("Invalid action"), true); }
	}

	/**
	 * Inserts new IP address to table
	 *
	 * @access protected
	 * @param array $address
	 * @return boolean success/failure
	 */
	protected function modify_address_add ($address) {
		# set insert array
		$insert = array("ip_addr"=>$this->transform_address($address['ip_addr'],"decimal"),
						"subnetId"=>$address['subnetId'],
						"description"=>@$address['description'],
						"dns_name"=>@$address['dns_name'],
						"mac"=>@$address['mac'],
						"owner"=>@$address['owner'],
						"state"=>@$address['state'],
						"switch"=>@$address['switch'],
						"port"=>@$address['port'],
						"note"=>@$address['note'],
						"is_gateway"=>@$address['is_gateway'],
						"excludePing"=>@$address['excludePing'],
						"PTRignore"=>@$address['PTRignore'],
						"lastSeen"=>@$address['lastSeen']
						);
		# custom fields, append to array
		foreach($this->set_custom_fields() as $c) {
			$insert[$c['name']] = strlen(@$address[$c['name']])>0 ? @$address[$c['name']] : null;
		}

		# null empty values
		$insert = $this->reformat_empty_array_fields ($insert, null);

		# remove gateway
		if($address['is_gateway']==1)	{ $this->remove_gateway ($address['subnetId']); }

		# execute
		try { $this->Database->insertObject("ipaddresses", $insert); }
		catch (Exception $e) {
			$this->Log->write( "Address create", "Failed to create new address<hr>".$e->getMessage()."<hr>".$this->array_to_log($this->reformat_empty_array_fields ($address, "NULL")), 2);
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}
		# save id
		$this->lastId = $this->Database->lastInsertId();

		# log and changelog
		$address['id'] = $this->lastId;
		$this->Log->write( "Address created", "New address created<hr>".$this->array_to_log($this->reformat_empty_array_fields ($address, "NULL")), 0);
		$this->Log->write_changelog('ip_addr', "add", 'success', array(), $address, $this->mail_changelog);

		# edit DNS PTR record
		$this->ptr_modify ("add", $insert);

		# ok
		return true;
	}

	/**
	 * Modifies address in table or whole range if requested
	 *
	 * @access protected
	 * @param array $address
	 * @return boolean success/failure
	 */
	protected function modify_address_edit ($address) {
		# fetch old details for logging
		$address_old = $this->fetch_address (null, $address['id']);
		# set update array
		$insert = array("id"=>$address['id'],
						"subnetId"=>$address['subnetId'],
						"ip_addr"=>$this->transform_address($address['ip_addr'], "decimal"),
						"description"=>@$address['description'],
						"dns_name"=>@$address['dns_name'],
						"mac"=>@$address['mac'],
						"owner"=>@$address['owner'],
						"state"=>@$address['state'],
						"switch"=>@$address['switch'],
						"port"=>@$address['port'],
						"note"=>@$address['note'],
						"is_gateway"=>@$address['is_gateway'],
						"excludePing"=>@$address['excludePing'],
						"PTRignore"=>@$address['PTRignore']
						);
		# custom fields, append to array
		foreach($this->set_custom_fields() as $c) {
			$insert[$c['name']] = strlen(@$address[$c['name']])>0 ? @$address[$c['name']] : null;
		}

		# set primary key for update
		if($address['type']=="series") {
			$id1 = "subnetId";
			$id2 = "ip_addr";
			unset($insert['id']);
		} else {
			$id1 = "id";
			$id2 = null;
		}

		# remove gateway
		if($address['is_gateway']==1)	{ $this->remove_gateway ($address['subnetId']); }

		# execute
		try { $this->Database->updateObject("ipaddresses", $insert, $id1, $id2); }
		catch (Exception $e) {
			$this->Log->write( "Address edit", "Failed to edit address $address[ip_addr]<hr>".$e->getMessage()."<hr>".$this->array_to_log($this->reformat_empty_array_fields ($address, "NULL")), 2);
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}

		# log and changelog
		$this->Log->write( "Address updated", "Address $address[ip_addr] updated<hr>".$this->array_to_log($this->reformat_empty_array_fields ($address, "NULL")), 0);
		$this->Log->write_changelog('ip_addr', "edit", 'success', (array) $address_old, $address, $this->mail_changelog);

		# edit DNS PTR record
		$insert['PTR']=@$address['PTR'];
		$this->ptr_modify ("edit", $insert);

		# ok
		return true;
	}

	/**
	 * Deletes address or address range.
	 *
	 * @access protected
	 * @param array $address
	 * @return boolean success/failure
	 */
	protected function modify_address_delete ($address) {
		# fetch old details for logging
		$address_old = $this->fetch_address (null, $address['id']);
		# series?
		if($address['type']=="series") {
			$field  = "subnetId";	$value  = $address['subnetId'];
			$field2 = "ip_addr";	$value2 = $this->transform_address ($address['ip_addr'], "decimal");
		} else {
			$field  = "id";			$value  = $address['id'];
			$field2 = null;			$value2 = null;
		}
		# execute
		try { $this->Database->deleteRow("ipaddresses", $field, $value, $field2, $value2); }
		catch (Exception $e) {
			$this->Log->write( "Address delete", "Failed to delete address $address[ip_addr]<hr>".$e->getMessage()."<hr>".$this->array_to_log((array) $address_old), 2);
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}

		# log and changelog
		$this->Log->write( "Address deleted", "Address $address[ip_addr] deleted<hr>".$this->array_to_log((array) $address_old), 0);
		$this->Log->write_changelog('ip_addr', "delete", 'success', (array) $address_old, array(), $this->mail_changelog);

		# edit DNS PTR record
		$this->ptr_modify ("delete", $address);

		# ok
		return true;
	}

	/**
	 * Moves address to new subnet
	 *
	 * @access protected
	 * @param array $address
	 * @return boolean success/failure
	 */
	protected function modify_address_move ($address) {
		# execute
		try { $this->Database->updateObject("ipaddresses", array("subnetId"=>$address['newSubnet'], "id"=>$address['id'])); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}
		# ok
		return true;
	}
	/**
	 * Removes gateway if it exists
	 *
	 * @access public
	 * @param mixed $subnetId
	 * @return void
	 */
	public function remove_gateway ($subnetId) {
		try { $this->Database->updateObject("ipaddresses", array("subnetId"=>$subnetId, "is_gateway"=>0), "subnetId"); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
	}

	/**
	 * Fetches custom IP address fields
	 *
	 * @access public
	 * @return object custom address fields
	 */
	public function set_custom_fields () {
		# Tools object
		$Tools = new Tools ($this->Database);
		# fetch
		return $Tools->fetch_custom_fields ('ipaddresses');
	}

	/**
	 * Checks if address already exists in subnet
	 *
	 *	if cnt is false we will return id if it exists and false ifnot
	 *
	 * @access public
	 * @param int $address
	 * @param int $subnetId
	 * @param int $subnetId
	 * @return boolean success/failure
	 */
	public function address_exists ($address, $subnetId, $cnt = true) {
		# make sure it is in decimal format
		$address = $this->transform_address($address, "decimal");
		# check
		if($cnt===true) { $query = "select count(*) as `cnt` from `ipaddresses` where `subnetId`=? and `ip_addr`=?;"; }
		else			{ $query = "select `id` from `ipaddresses` where `subnetId`=? and `ip_addr`=?;";  }
		# fetch
		try { $count = $this->Database->getObjectQuery($query, array($subnetId, $address)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# result
		if ($cnt===true)	{ return $count->cnt==0 ? false : true; }
		else				{ return is_null($count->id) ? false : $count->id; }
	}

	/**
	 * Calculates diff between two IP addresses
	 *
	 * @access public
	 * @param int $ip1
	 * @param int $ip2
	 * @return void
	 */
	public function calculate_address_diff ($ip1, $ip2) {
		return gmp_strval(gmp_sub($ip2, $ip1));
	}


	/**
	 * Returns first available subnet address, false if none
	 *
	 * @access public
	 * @param int $subnetId
	 * @param obj $Subnets
	 * @return int / false
	 */
	public function get_first_available_address ($subnetId, $Subnets) {

		# fetch all addresses in subnet and subnet
		$addresses = $this->fetch_subnet_addresses ($subnetId, "ip_addr", "asc");
		$subnet = (array) $Subnets->fetch_subnet(null, $subnetId);

	    # get max hosts
	    $max_hosts = $Subnets->get_max_hosts ($subnet['mask'], $this->identify_address($subnet['subnet']));

		# full subnet?
		if(sizeof($addresses)>=$max_hosts)																{ return false; } 	//full subnet

		# set type
		$ip_version = $this->identify_address ($subnet['subnet']);
	    # get first diff > 1
	    if(sizeof($addresses)>0) {
		    foreach($addresses as $k=>$ipaddress) {
			    # check subnet and first IP
			    if($k==0) {
				    # /31 fix
				    if($subnet['mask']==31)	{
					    if(gmp_strval(gmp_sub($addresses[$k]->ip_addr, $subnet['subnet']))>0) 			{ return gmp_strval($subnet['subnet']); }
				    } else {
					    if(gmp_strval(gmp_sub($addresses[$k]->ip_addr, $subnet['subnet']))>1) 			{ return gmp_strval(gmp_add($subnet['subnet'], 1)); }
					    elseif($ip_version=="IPv6") {
						    if(sizeof($addresses)==1) {
							    if(gmp_strval(gmp_sub($addresses[$k]->ip_addr, $subnet['subnet']))==0)	{ return gmp_strval(gmp_add($subnet['subnet'], 1)); }
						    }
					    }
				    }
			    }
			    else {
				    if(gmp_strval(gmp_sub($addresses[$k]->ip_addr, $addresses[$k-1]->ip_addr))>1) 		{ return gmp_strval(gmp_add($addresses[$k-1]->ip_addr, 1)); }
			    }
		    }
		    # all consecutive, last + 1
		    																							{ return gmp_strval(gmp_add($addresses[$k]->ip_addr, 1)); }
	    }
	    # no addresses
	    else {
		    # /32, /31
		    if($subnet['mask']==32 || $subnet['mask']==31 || $ip_version=="IPv6") 						{ return $subnet['subnet']; }
		    else																						{ return gmp_strval(gmp_add($subnet['subnet'], 1)); }
	    }

	}









	/**
	 * @powerDNS
	 * -------------------------------
	 */

	/**
	 * Modifes powerDNS PTR record
	 *
	 * @access public
	 * @param mixed $action
	 * @param mixed $address
	 * @return void
	 */
	public function ptr_modify ($action, $address, $print_error = true) {
		// first check if subnet selected for PTR records
		$this->initialize_subnets_object ();
		$subnet = $this->Subnets->fetch_subnet ("id", $address['subnetId']);
		if ($subnet->DNSrecursive!="1") { return false; }

		// ignore if PTRignore set
		if ($address['PTRignore']=="1")	{
				// validate db
				$this->pdns_validate_connection ();
				// remove if it exists
				if ($this->ptr_exists ($address['PTR'])) {
					$this->ptr_delete ($address, false);
										{ return false; }
				}
				else {
										{ return true; }
				}
		}
		// validate db
		$this->pdns_validate_connection ();
		// to object
		$address = (object) $address;
		# execute based on action
		if($action=="add")				{ return $this->ptr_add ($address, $print_error); }							//create new PTR
		elseif($action=="edit")			{ return $this->ptr_edit ($address, $print_error); }						//modify existing PTR
		elseif($action=="delete")		{ return $this->ptr_delete ($address, $print_error); }						//delete PTR
		else							{ return $this->Result->show("danger", _("Invalid PDNS action"), true); }
	}

	/**
	 *  Validates pdns database connection
	 *
	 * @access public
	 * @param bool $die (default: false)
	 * @return void
	 */
	public function pdns_validate_connection ($die = true) {
		# powerDNS class
		$this->PowerDNS = new PowerDNS ($this->Database);
		# check connection
		if($this->PowerDNS->db_check()===false && $die) { $this->Result->show("danger", _("Cannot connect to powerDNS database"), true); }
		# get settings
		$this->get_settings ();
	}

	/**
	 * Set zone name and fetch domain details
	 *
	 * @access private
	 * @param mixed $ip
	 * @param mixed $mask
	 * @return void
	 */
	private function pdns_fetch_domain ($subnet_id) {
		# initialize subnets
		$this->initialize_subnets_object ();
		// fetch subnet
		$subnet = $this->Subnets->fetch_subnet ("id", $subnet_id);
		if($subnet===false)							{  $this->Result->show("danger", _("Invalid subnet Id"), true); }

		// set PTR zone name from IP/mash
		$zone = $this->PowerDNS->get_ptr_zone_name ($this->transform_address ($subnet->subnet, "dotted"), $subnet->mask);
		// try to fetch
		return  $this->PowerDNS->fetch_domain_by_name ($zone);
	}

	/**
	 * Create new PTR record when adding new IP address
	 *
	 * @access public
	 * @param mixed $address
	 * @param mixed $print_error
	 * @param mixed $id (default: NULL)
	 * @return void
	 */
	public function ptr_add ($address, $print_error, $id = null) {
		// validate hostname
		if ($this->validate_hostname ($address->dns_name)===false)		{ return false; }
		// fetch domain
		$domain = $this->pdns_fetch_domain ($address->subnetId);
		// decode values
		$values = json_decode($this->settings->powerDNS);

		// formulate new record
		$record = $this->PowerDNS->formulate_new_record ($domain->id, $this->PowerDNS->get_ip_ptr_name ($this->transform_address ($address->ip_addr, "dotted")), "PTR", $address->dns_name, $values->ttl);
		// insert record
		$this->PowerDNS->add_domain_record ($record, false);
		// link to address
		$id = $id===null ? $this->lastId : $id;
		$this->ptr_link ($id, $this->PowerDNS->lastId);
		// ok
		if ($print_error && php_sapi_name()!="cli")
		$this->Result->show("success", "PTR record created", false);

		return true;
	}

	/**
	 * Edits PTR
	 *
	 * @access public
	 * @param mixed $address
	 * @param mixed $print_error
	 * @return void
	 */
	public function ptr_edit ($address, $print_error) {
		// validate hostname
		if ($this->validate_hostname ($address->dns_name)===false)	{
			// remove pointer if it exists!
			if ($this->ptr_exists ($address->PTR)===true)	{ $this->ptr_delete ($address, $print_error); }
			else											{ return false; }
		}

		// new record
 		if ($this->ptr_exists ($address->PTR)===false) {
	 		// fake lastid
	 		$this->lastId = $address->id;
	 		// new ptr record
	 		$this->ptr_add ($address, true);
 		}
 		// update PTR
 		else {
			// fetch domain
			$domain = $this->pdns_fetch_domain ($address->subnetId);

			// fetch old
			$old_record = $this->PowerDNS->fetch_record ($address->PTR);

			// create insert array
			$update = $this->PowerDNS->formulate_update_record ($this->PowerDNS->get_ip_ptr_name ($this->transform_address ($address->ip_addr, "dotted")), null, $address->dns_name, null, null, null, $old_record->change_date);
			$update['id'] = $address->PTR;

			// update
			$this->PowerDNS->update_domain_record ($domain->id, $update);
			// ok
			$this->Result->show("success", "PTR record updated", false);
 		}
	}

	/**
	 * Remove PTR from database
	 *
	 * @access public
	 * @param mixed $address
	 * @param mixed $print_error
	 * @return void
	 */
	public function ptr_delete ($address, $print_error) {
		$address = (object) $address;

		// remove link from ipaddresses
		$this->ptr_unlink ($address->id);

		// exists
		if ($this->ptr_exists ($address->PTR)!==false)	{
			// fetch domain
			$domain = $this->pdns_fetch_domain ($address->subnetId);
			//remove
			$this->PowerDNS->remove_domain_record ($domain->id, $address->PTR);
			// ok
			$this->Result->show("success", "PTR record removed", false);
		}
	}

	/**
	 * Links PTR record with address record
	 *
	 * @access public
	 * @param mixed $address_id
	 * @param mixed $ptr_id
	 * @return void
	 */
	public function ptr_link ($address_id, $ptr_id) {
		# execute
		try { $this->Database->updateObject("ipaddresses", array("id"=>$address_id, "PTR"=>$ptr_id)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}
	}

	/**
	 * Remove PTR link if it exists
	 *
	 * @access private
	 * @param mixed $address_id
	 * @return void
	 */
	private function ptr_unlink ($address_id) {
		# execute
		try { $this->Database->updateObject("ipaddresses", array("id"=>$address_id, "PTR"=>0)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}
	}

	/**
	 * Removes all PTR references for all hosts in subnet
	 *
	 * @access public
	 * @param mixed $subnet_id
	 * @return void
	 */
	public function ptr_unlink_subnet_addresses ($subnet_id) {
		try { $address = $this->Database->runQuery("update `ipaddresses` set `PTR` = 0 where `subnetId` = ?;", array($subnet_id)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		#result
		return true;
	}

	/**
	 * Checks if PTR record exists
	 *
	 * @access private
	 * @param mixed $ptr_id (default: 0)
	 * @return void
	 */
	private function ptr_exists ($ptr_id = 0) {
		return $this->PowerDNS->record_id_exists ($ptr_id);
	}














	/**
	* @import address methods
	* -------------------------------
	*/

	/**
	 * Import single line from csv to database
	 *
	 * @access public
	 * @param array $address
	 * @param int $subnetId
	 * @return void
	 */
	public function import_address_from_csv ($address, $subnetId) {
		# Subnets object
		$this->initialize_subnets_object ();

	    # fetch subnet details
	    $subnet = (array) $this->Subnets->fetch_subnet(null, $subnetId);

	    # verify address
	    if($this->verify_address( $address[0], $this->transform_to_dotted($subnet['subnet'])."/".$subnet['mask'], false, false)!==false) { return false; }
	    # check for duplicates
	    if ($this->address_exists($address[0], $subnetId)) { return _('IP address already exists').' - '.$address[0]; }

		# format insert array
		$address_insert = array("subnetId"=>$subnetId,
								"ip_addr"=>$address[0],
								"state"=>$address[1],
								"description"=>$address[2],
								"dns_name"=>$address[3],
								"mac"=>$address[4],
								"owner"=>$address[5],
								"switch"=>$address[6],
								"port"=>$address[7],
								"note"=>$address[8]
								);

		# switch to 0, state to active
		$address_insert['switch'] = strlen($address_insert['switch'])==0 ? 0 : $address_insert['switch'];
		$address_insert['state']  = strlen($address_insert['state'])==0 ?  1 : $address_insert['state'];

		# custom fields, append to array
		$m=9;
		$custom_fields = $this->set_custom_fields();
		if(sizeof($custom_fields) > 0) {
			foreach($custom_fields as $c) {
				$address_insert[$c['name']] = $address[$m];
				$m++;
			}
		}

		# insert
		return $this->modify_address_add ($address_insert);
	}










	/**
	* @address subnet methods
	* -------------------------------
	*/

	/**
	 * Opens new Subnets connection if not already opened
	 *
	 * @access private
	 * @return void
	 */
	private function initialize_subnets_object () {
		if(!is_object($this->Subnets)) { $this->Subnets = new Subnets ($this->Database); }
	}

	/**
	 * Fetches all IP addresses in subnet
	 *
	 * @access public
	 * @param int $subnetId
	 * @param mixed $order
	 * @param mixed $order_direction
	 * @return objects addresses
	 */
	public function fetch_subnet_addresses ($subnetId, $order=null, $order_direction=null) {
		# set order
		if(!is_null($order)) 	{ $order = array($order, $order_direction); }
		else 					{ $order = array("ip_addr", "asc"); }

		# escape ordering
		$order[0] = $this->Database->escape ($order[0]);
		$order[1] = $this->Database->escape ($order[1]);

		try { $addresses = $this->Database->getObjectsQuery("SELECT * FROM `ipaddresses` where `subnetId` = ? order by `$order[0]` $order[1];", array($subnetId)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# save to addresses cache
		if(sizeof($addresses)>0) {
			foreach($addresses as $k=>$address) {
				# add decimal format
				$address->ip = $this->transform_to_dotted ($address->ip_addr);
				# save to subnets
				$this->addresses[$address->id] = (object) $address;
				$addresses[$k]->ip = $address->ip;
			}
		}
		# result
		return sizeof($addresses)>0 ? $addresses : array();
	}

	/**
	 * Count number of IP addresses in subnet
	 *
	 * @access public
	 * @param int $subnetId
	 * @return int number of addresses in subnet
	 */
	public function count_subnet_addresses ($subnetId) {
		try { $count = $this->Database->numObjectsFilter("ipaddresses", "subnetId", $subnetId); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# result
		return (int) $count;
	}

	/**
	 * Count number of addresses in multiple subnets
	 *
	 *	we provide array of all subnet ids
	 *
	 * @access public
	 * @param mixed $subnets
	 * @return void
	 */
	public function count_addresses_in_multiple_subnets ($subnets) {
		# empty
		if(empty($subnets)) { return 0; }

		# create query
		foreach($subnets as $k=>$s) {
			if (is_object($s))	{ $tmp[] = " `subnetId`=$s->id "; }
			else				{ $tmp[] = " `subnetId`=$s "; }
		}
		$query  = "select count(*) as `cnt` from `ipaddresses` where ".implode("or", $tmp).";";

		# fetch
		try { $addresses = $this->Database->getObjectsQuery($query); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# return count
	    return $addresses[0]->cnt;
	}


	/**
	 * Fetch IP addresses for all recursive slaves
	 *
	 *	count returns count only, else whole subnets
	 *
	 * @access public
	 * @param int $subnetId
	 * @param {bool} $count
	 * @return count or addresses
	 */
	public function fetch_subnet_addresses_recursive ($subnetId, $count = false, $order=null, $order_direction=null ) {
		# initialize subnets
		$this->initialize_subnets_object ();
		$this->Subnets = new Subnets ($this->Database);
		$this->Subnets->reset_subnet_slaves_recursive();				//reset array of slaves before continuing
	    $this->Subnets->fetch_subnet_slaves_recursive($subnetId);		//fetch array of slaves
	    $this->Subnets->slaves = array_unique($this->Subnets->slaves);	//remove possible duplicates

		# ip address order
		if(!is_null($order)) 	{ $order_addr = array($order, $order_direction); }
		else 					{ $order_addr = array("ip_addr", "asc"); }

		# escape ordering
		$order[0] = $this->Database->escape ($order[0]);
		$order[1] = $this->Database->escape ($order[1]);

	    # set query to fetch all ip addresses for specified subnets or just count
		if($count) 	{ $query = 'select count(*) as cnt from `ipaddresses` where `subnetId` = "" '; }
		else	 	{ $query = 'select * from `ipaddresses` where `subnetId` = "" '; }
	    foreach($this->Subnets->slaves as $subnetId2) {
		    # ignore orphaned
	    	if($subnetId2 != $subnetId) {
				$query  .= " or `subnetId` = ? ";
		    	$ids[] = $subnetId2;
			}
		}

	    $query      .= "order by `$order_addr[0]` $order_addr[1];";
		# fetch
		try { $addresses = $this->Database->getObjectsQuery($query, $ids); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
	    # return ip address array or just count
	    return $count ? (int) $addresses[0]->cnt : $addresses;
	}

	/**
	 * Search for unused address space between 2 IP addresses
	 *
	 * @access public
	 * @param int $address1
	 * @param int $address2
	 * @param int $netmask
	 * @return possible unused addresses by type
	 */
	public function find_unused_addresses ($address1, $address2, $netmask, $empty=false) {
		# make sure addresses are in decimal format
		$address1 = $this->transform_address ($address1, "decimal");
		$address2 = $this->transform_address ($address2, "decimal");
		# check for space
		return $this->identify_address($address1)=="IPv6" ? $this->find_unused_addresses_IPv6 ($address1, $address2, $netmask, $empty) : $this->find_unused_addresses_IPv4 ($address1, $address2, $netmask, $empty);
	}

	/**
	 * Search for unused address space between 2 IPv4 addresses.
	 *
	 * @access protected
	 * @param int $address1
	 * @param int $address2
	 * @param int $netmask
	 * @return unused address range or false if none available
	 */
	protected function find_unused_addresses_IPv4 ($address1, $address2, $netmask, $empty) {
		# calculate diff
		$diff = $this->calculate_address_diff ($address1, $address2);
		# 32 subnets
		if($netmask==32) {
			if($empty) {
				return array("ip"=>$this->transform_to_dotted($address1), "hosts"=>1);
			}
			else {
				return false;
			}
		}
		# 31 subnets
		elseif($netmask==31) {

			if($empty) {
				return array("ip"=>$this->transform_to_dotted($address1), "hosts"=>2);
			}
			elseif($diff==1) {
				if($this->is_network($address1, $netmask)) {
					return array("ip"=>$this->transform_to_dotted($address2), "hosts"=>1);
				}
				elseif($this->is_broadcast($address2, $netmask)) {
					return array("ip"=>$this->transform_to_dotted($address1), "hosts"=>1);
				}
				else {
					return false;
				}
			}
			else {
				return false;
			}
		}
		# if diff is less than 2 return false */
		elseif ( $diff < 2 ) {
        		return false;
    	}
		# if diff is 2 return 1 IP address in the middle */
		elseif ( $diff == 2 ) {
				return array("ip"=>$this->transform_to_dotted($address1+1), "hosts"=>1);
    	}
		# if diff is more than 2 return pool */
		else {
            	return array("ip"=>$this->transform_to_dotted($address1+1)." - ".$this->transform_to_dotted(($address2-1)), "hosts"=>gmp_strval(gmp_sub($diff, 1)));
    	}
    	# default false
    	return false;
	}

	/**
	 * Search for unused address space between 2 IPv6 addresses
	 *
	 * @access protected
	 * @param int $address1
	 * @param int $address2
	 * @param int $netmask
	 * @return unused address range or false if none available
	 */
	protected function find_unused_addresses_IPv6 ($address1, $address2, $netmask, $empty) {
		# calculate diff
		$diff = $this->calculate_address_diff ($address1, $address2);

		# /128
		if($netmask == 128) {
				return array("ip"=>$this->transform_to_dotted(gmp_strval($address1)), "hosts"=>1);
    	}
		# /127
	    elseif($netmask == 127) {
			if($diff==1 && $this->is_network($address1, $netmask)) {
				return array("ip"=>$this->transform_to_dotted($address2), "hosts"=>1);
			}
			elseif($diff==1 && $this->is_broadcast($address2, $netmask)) {
				return array("ip"=>$this->transform_to_dotted($address1), "hosts"=>1);
			}
			else {
				return array("ip"=>$this->transform_to_dotted($address1), "hosts"=>2);
			}
	    }
	    # if diff is less than 2 return false */
	    elseif ($diff < 2) {
	        	return false;
	    }
	    # if diff is 2 return 1 IP address in the middle */
	    elseif ($diff == 2) {
	            return array("ip"=>$this->transform_to_dotted(gmp_strval($address1)), "hosts"=>1);
	    }
		# if diff is more than 2 return pool */
		else {
            	return array("ip"=>$this->transform_to_dotted(gmp_strval($address1))." - ".$this->transform_to_dotted(gmp_strval($address2)), "hosts"=>$this->reformat_number(gmp_strval($diff)));
    	}
    	# default false
    	return false;
	}











	/**
	* @address verification methods
	* -------------------------------
	*/

	/**
	 * Verify IP address
	 *
	 * @access public
	 * @param int $address
	 * @param mixed $subnet (CIDR)
	 * @param bool $no_strict (default: false)
	 * @param bool $die (default: false)
	 * @return boolean
	 */
	public function verify_address( $address, $subnet, $no_strict = false, $die=true ) {
		# subnet should be in CIDR format
		$this->initialize_subnets_object ();
		if(strlen($error = $this->Subnets->verify_cidr ($subnet))>1)				{ $this->Result->show("danger", $error, $die); return true; }

		# make checks
		return $this->identify_address ($address)=="IPv6" ? $this->verify_address_IPv6 ($address, $subnet, $die) : $this->verify_address_IPv4 ($address, $subnet, $no_strict, $die);
	}

	/**
	 * Verify IPv4 address
	 *
	 * @access public
	 * @param int $address
	 * @param mixed $subnet (CIDR)
	 * @param bool $no_strict
	 * @param bool $die
	 * @return boolean
	 */
	public function verify_address_IPv4 ($address, $subnet, $no_strict, $die) {
		# Initialize PEAR NET object
		$this->initialize_pear_net_IPv4 ();
        # fetch mask part
        $mask = explode("/", $subnet);

		# is address valid?
		if (!$this->Net_IPv4->validateIP($address)) 						{ $this->Result->show("danger", _("IP address not valid")."! ($ip)", $die); return true; }
		# is address in provided subnet
		elseif (!$this->Net_IPv4->ipInNetwork($address, $subnet)) 			{ $this->Result->show("danger", _("IP address not in selected subnet")."! ($address)", $die); return true; }
		# ignore  /31 and /32 subnet broadcast and subnet checks!
		elseif ($mask[1] == 31 || $mask[1] == 32 || $no_strict == true) 	{ }
		# It cannot be subnet or broadcast
		else {
            $net = $this->Net_IPv4->parseAddress($subnet);

            if ($net->network == $address) 									{ $this->Result->show("danger", _("Cannot add subnet as IP address!"), $die); return true; }
            elseif ($net->broadcast == $address)							{ $this->Result->show("danger", _("Cannot add broadcast as IP address!"), $die); return true; }
		}
		# default
		return false;
	}

	/**
	 * Verify IPv6 address
	 *
	 * @access public
	 * @param int $address
	 * @param mixed $subnet (CIDR)
	 * @param bool $die
	 * @return boolean
	 */
	public function verify_address_IPv6 ($address, $subnet, $die) {
		# Initialize PEAR NET object
		$this->initialize_pear_net_IPv6 ();

        # remove /xx from subnet
        $subnet_short = $this->Net_IPv6->removeNetmaskSpec($subnet);

		# is it valid?
		if (!$this->Net_IPv6->checkIPv6($address)) 							{ $this->Result->show("danger", _("IP address not valid")."! ($address)", $die); return true; }
		# it must be in provided subnet
		elseif (!$this->Net_IPv6->isInNetmask($address, $subnet)) 			{ $this->Result->show("danger", _("IP address not in selected subnet")."! ($address)", $die); return true; }
		# default
		return false;
	}

	/**
	 * Validates IP address
	 *
	 * @access public
	 * @param mixed $address
	 * @return void
	 */
	public function validate_address ($address) {
		# Initialize PEAR NET object
		$this->initialize_pear_net_IPv4 ();
		$this->initialize_pear_net_IPv6 ();

		// transform
		$address = $this->transform_address ($address, "dotted");
		// ipv6
		if($this->identify_address ($address)=="IPv6") {
			return $this->Net_IPv6->checkIPv6($address) ?  true : false;
		}
		// ipv4
		else {
			return $this->Net_IPv4->validateIP($address) ? true : false;
		}
	}

	/**
	 * Checks if address is subnet for IPv4 addresses
	 *
	 * @access public
	 * @param mixed $address
	 * @param int $mask
	 * @return boolean
	 */
	public function is_network ($address, $netmask) {
		$this->initialize_subnets_object ();
		$boundaries = $this->Subnets->get_network_boundaries ($address, $netmask);
		return $this->transform_address($address,"dotted")==$boundaries['network'] ? true : false;
	}

	/**
	 * Checks if address is broadcast for IPv4 addresses
	 *
	 * @access public
	 * @param mixed $address
	 * @param int $mask
	 * @return boolean
	 */
	public function is_broadcast ($address, $netmask) {
		$this->initialize_subnets_object ();
		$boundaries = $this->Subnets->get_network_boundaries ($address, $netmask);
		return $this->transform_address($address,"dotted")==$boundaries['broadcast'] ? true : false;
	}

	/**
	 * Checks if hostname in database is unique
	 *
	 * @access public
	 * @param mixed $hostname
	 * @return boolean
	 */
	public function is_hostname_unique ($hostname) {
		try { $cnt = $this->Database->numObjectsFilter("ipaddresses", "dns_name", $hostname); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		return $cnt==0 ? true : false;
	}













	/**
	* @transform address methods
	* -------------------------------
	*/

	/**
	 * This function compresses all ranges
	 *
	 *	input is array of ip addresses
	 *	output compresses address range
	 *
	 * @access public
	 * @param array $addresses
	 * @return void
	 */
	public function compress_address_ranges ($addresses, $state=4) {
		# loop through IP addresses
		for($c=0; $c<sizeof($addresses); $c++) {
			# ignore already comressed range
			if($addresses[$c]->class!="compressed-range") {
				# gap between this and previous
				if(gmp_strval( @gmp_sub($addresses[$c]->ip_addr, $addresses[$c-1]->ip_addr)) != 1) {
					# remove index flag
					unset($fIndex);
					# save IP address
					$addresses_formatted[$c] = $addresses[$c];
					$addresses_formatted[$c]->class = "ip";

					# no gap this -> next
					if(gmp_strval( @gmp_sub($addresses[$c]->ip_addr, $addresses[$c+1]->ip_addr)) == -1 && $addresses[$c]->state==$state) {
						//is state the same?
						if($addresses[$c]->state==$addresses[$c+1]->state) {
							$fIndex = $c;
							$addresses_formatted[$fIndex]->startIP = $addresses[$c]->ip_addr;
							$addresses_formatted[$c]->class = "compressed-range";
						}
					}
				}
				# no gap between this and previous
				else {
					# is state same as previous?
					if($addresses[$c]->state==$addresses[$c-1]->state && $addresses[$c]->state==$state) {
						$addresses_formatted[$fIndex]->stopIP = $addresses[$c]->ip_addr;	//adds dhcp state
						$addresses_formatted[$fIndex]->numHosts = gmp_strval( gmp_add(@gmp_sub($addresses[$c]->ip_addr, $addresses_formatted[$fIndex]->ip_addr),1));	//add number of hosts
					}
					# different state
					else {
						# remove index flag
						unset($fIndex);
						# save IP address
						$addresses_formatted[$c] = $addresses[$c];
						$addresses_formatted[$c]->class = "ip";
						# check if state is same as next to start range
						if($addresses[$c]->state==@$addresses[$c+1]->state &&  gmp_strval( @gmp_sub($addresses[$c]->ip_addr, $addresses[$c+1]->ip_addr)) == -1 && $addresses[$c]->state==$state) {
							$fIndex = $c;
							$addresses_formatted[$fIndex]->startIP = $addresses[$c]->ip_addr;
							$addresses_formatted[$c]->class = "compressed-range";
						}
					}
				}
			}
			else {
				# save already compressed
				$addresses_formatted[$c] = $addresses[$c];
			}
		}
		# overrwrite ipaddresses and rekey
		$addresses = @array_values($addresses_formatted);
		# return
		return $addresses;
	}

	/**
	 * Finds invalid addresses - that have subnetId that does not exist
	 *
	 * @access public
	 * @return void
	 */
	public function find_invalid_addresses () {
		// find unique ids
		$ids = $this->find_unique_subnetids ();
		if ($ids===false)										{ return false; }
		// validate
		foreach ($ids as $id) {
			if ($this->verify_subnet_id ($id->subnetId)===0) {
				$false[] = $this->fetch_subnet_addresses ($id->subnetId);
			}
		}
		// return
		return isset($false) ? $false : false;
	}

	/**
	 * Finds all unique master subnet ids
	 *
	 * @access private
	 * @return void
	 */
	private function find_unique_subnetids () {
		try { $res = $this->Database->getObjectsQuery("select distinct(`subnetId`) from `ipaddresses` order by `subnetId` asc;"); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# return
		return sizeof($res)>0 ? $res : false;
	}

	/**
	 * Verifies that subnetid exists
	 *
	 * @access private
	 * @param mixed $id
	 * @return void
	 */
	private function verify_subnet_id ($id) {
		try { $res = $this->Database->getObjectQuery("select count(*) as `cnt` from `subnets` where `id` = ?;", array($id)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# return
		return (int) $res->cnt;
	}











	/**
	* @permission address methods
	* -------------------------------
	*/

	/**
	 * Checks permission for specified subnet
	 *
	 *	we provide user details and subnetId
	 *
	 * @access public
	 * @param object $user
	 * @param int $subnetid
	 * @return int permission level
	 */
	public function check_permission ($user, $subnetid) {

		# get all user groups
		$groups = json_decode($user->groups);

		# if user is admin then return 3, otherwise check
		if($user->role == "Administrator")	{ return 3; }

		# set subnet permissions
		$subnet  = $this->fetch_subnet ("id", $subnetId);
		$subnetP = json_decode($subnet['permissions']);

		# set section permissions
		$Section = new Section ($this->Database);
		$section = $Section->fetch_section ("id", $subnet['sectionId']);
		$sectionP = json_decode($section->permissions);

		# default permission
		$out = 0;

		# for each group check permissions, save highest to $out
		if(sizeof($sectionP) > 0) {
			foreach($sectionP as $sk=>$sp) {
				# check each group if user is in it and if so check for permissions for that group
				foreach($groups as $uk=>$up) {
					if($uk == $sk) {
						if($sp > $out) { $out = $sp; }
					}
				}
			}
		}
		else {
			return 0;
		}

		# if section permission == 0 then return 0
		if($out == 0) {
			return 0;
		}
		else {
			$out = 0;
			# ok, user has section access, check also for any higher access from subnet
			if(sizeof($subnetP) > 0) {
				foreach($subnetP as $sk=>$sp) {
					# check each group if user is in it and if so check for permissions for that group
					foreach($groups as $uk=>$up) {
						if($uk == $sk) {
							if($sp > $out) { $out = $sp; }
						}
					}
				}
			}
		}

		# return result
		return $out;
	}










	/**
	* @misc address methods
	* -------------------------------
	*/

	/**
	 * Prints pagination if subnet has multiple pages of IP addresses
	 *
	 * @access public
	 * @param int $page	//current page number
	 * @param int $pages	//number of all subpages
	 * @return mixed
	 */
	public function print_pagination ($page, $pages) {

		print "<hr>";
		print "<div class='text-right'>";
		print "<ul class='pagination pagination-sm'>";

		//previous - disabled?
		if($page == 1)			{ print "<li class='disabled'><a href='#'>&laquo;</a></li>"; }
		else					{ print "<li>				<a href='".create_link("subnets",$_GET['section'],$_GET['subnetId'],"page".($page-1))."'>&laquo;</a></li>"; }

		# less than 8
		if($pages<8) {
			for($m=1; $m<=$pages; $m++) {
				//active?
				if($page==$m)	{ print "<li class='active'><a href='".create_link("subnets",$_GET['section'],$_GET['subnetId'],"page$m")."'>$m</a></li>"; }
				else			{ print "<li>				<a href='".create_link("subnets",$_GET['section'],$_GET['subnetId'],"page$m")."'>$m</a></li>"; }
			}
		}
		# more than seven
		else {
			//first page
			if($page<=3) {
				for($m=1; $m<=5; $m++) {
					//active?
					if($page==$m)	{ print "<li class='active'><a href='".create_link("subnets",$_GET['section'],$_GET['subnetId'],"page$m")."'>$m</a></li>"; }
					else			{ print "<li>				<a href='".create_link("subnets",$_GET['section'],$_GET['subnetId'],"page$m")."'>$m</a></li>"; }
				}
				print "<li class='disabled'><a href='#'>...</a></li>";
				print "<li>				    <a href='".create_link("subnets",$_GET['section'],$_GET['subnetId'],"page$pages")."'>$pages</a></li>";
			}
			//last pages
			elseif($page>$pages-4) {
				print "<li>				    <a href='".create_link("subnets",$_GET['section'],$_GET['subnetId'],"page1")."'>1</li>";
				print "<li class='disabled'><a href='#'>...</a></li>";
				for($m=$pages-4; $m<=$pages; $m++) {
					//active?
					if($page==$m)	{ print "<li class='active'><a href='".create_link("subnets",$_GET['section'],$_GET['subnetId'],"page$m")."'>$m</a></li>"; }
					else			{ print "<li>				<a href='".create_link("subnets",$_GET['section'],$_GET['subnetId'],"page$m")."'>$m</a></li>"; }
				}
			}
			//page more than 2
			else {
				print "<li>				    <a href='".create_link("subnets",$_GET['section'],$_GET['subnetId'],"page1")."'>1</li>";
				print "<li class='disabled'><a href='#'>...</a></li>";
				for($m=$page-1; $m<=$page+1; $m++) {
					//active?
					if($page==$m)	{ print "<li class='active'><a href='".create_link("subnets",$_GET['section'],$_GET['subnetId'],"page$m")."'>$m</a></li>"; }
					else			{ print "<li>				<a href='".create_link("subnets",$_GET['section'],$_GET['subnetId'],"page$m")."'>$m</a></li>"; }
				}
				print "<li class='disabled'><a href='#'>...</a></li>";
				print "<li><a href='".create_link("subnets",$_GET['section'],$_GET['subnetId'],"page$pages")."'>$pages</li>";
			}
		}

		//next - disabled?
		if($page == $pages)		{ print "<li class='disabled'><a href='#'>&raquo;</a></li>"; }
		else					{ print "<li>				  <a href='".create_link("subnets",$_GET['section'],$_GET['subnetId'],"page".($page+1))."'>&raquo;</a></li>"; }

		print "</ul>";
		print "</div>";
	}

	/**
	 * Present numbers in pow 10, only for IPv6
	 *
	 * @access public
	 * @param mixed $number
	 * @return void
	 */
	public function reformat_number ($number) {
		$length = strlen($number);
		$pos	= $length - 3;

		if ($length > 8) {
			$number = "~". substr($number, 0, $length - $pos) . "&middot;10^<sup>". $pos ."</sup>";
		}
		return $number;
	}
}
