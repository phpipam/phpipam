<?php

/**
 *	phpIPAM Section class
 */

class Tools extends Common_functions {

	/**
	 * public variables
	 */
	public $vlans;							//to store vlans, vlanId is array index (array of objects)
	public $vrfs;							//to store vrfs, vrfId is array index (array of objects)
	public $devices;						//to store devices, id is array index (array of objects)
	public $settings = null;				//settings

	/**
	 * object holders
	 */
	protected $Net_IPv4;					//PEAR NET IPv4 object
	protected $Net_IPv6;					//PEAR NET IPv6 object
	protected $Result;						//for Result printing
	protected $debugging = false;			//debugging flag (bool)
	protected $Database;					//for Database connection





	/**
	 * __construct method
	 *
	 * @access public
	 * @return void
	 */
	public function __construct (Database_PDO $database) {
		# set database object
		$this->Database = $database;
		# initialize Result
		$this->Result = new Result ();
		# set debugging
		$this->set_debugging ();
	}

	/**
	 * Initializes PEAR Net IPv4 object
	 *
	 * @access private
	 * @return void
	 */
	private function initialize_pear_net_IPv4 () {
		//initialize NET object
		if(!is_object($this->Net_IPv4)) {
			require_once( dirname(__FILE__) . '/../../functions/PEAR/Net/IPv4.php' );
			//initialize object
			$this->Net_IPv4 = new Net_IPv4();
		}
	}
	/**
	 * Initializes PEAR Net IPv6 object
	 *
	 * @access private
	 * @return void
	 */
	private function initialize_pear_net_IPv6 () {
		//initialize NET object
		if(!is_object($this->Net_IPv6)) {
			require_once( dirname(__FILE__) . '/../../functions/PEAR/Net/IPv6.php' );
			//initialize object
			$this->Net_IPv6 = new Net_IPv6();
		}
	}









	/**
	 *	@general fetch methods
	 *	--------------------------------
	 */

	/**
	 * Fetch all objects from specified table in database
	 *
	 * @access public
	 * @param mixed $table
	 * @param mixed $sortField (default:id)
	 * @return void
	 */
	public function fetch_all_objects ($table=null, $sortField="id") {
		# null table
		if(is_null($table)||strlen($table)==0) return false;
		# fetch
		try { $res = $this->Database->getObjects($table, $sortField); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# result
		return sizeof($res)>0 ? $res : false;
	}

	/**
	 * Fetches specified object specified table in database
	 *
	 * @access public
	 * @param mixed $table
	 * @param mixed $method (default: null)
	 * @param mixed $id
	 * @return void
	 */
	public function fetch_object ($table=null, $method=null, $id) {
		# null table
		if(is_null($table)||strlen($table)==0) return false;
		# null method
		$method = is_null($method) ? "id" : $this->Database->escape($method);

		# ignore 0
		if($id===0 || is_null($id)) {
			return false;
		}
		# check cache
		elseif(isset($this->table[$table][$method][$id]))	{
			return $this->table[$table][$method][$id];
		}
		else {
			try { $res = $this->Database->getObjectQuery("SELECT * from `$table` where `$method` = ? limit 1;", array($id)); }
			catch (Exception $e) {
				$this->Result->show("danger", _("Error: ").$e->getMessage());
				return false;
			}
			# save to cache array
			if(sizeof($res)>0) {
				$this->table[$table][$method][$id] = (object) $res;
				return $res;
			}
			else {
				return false;
			}
		}
	}

	/**
	 * Fetches multiple objects in specified table in database
	 *
	 *	doesnt cache
	 *
	 * @access public
	 * @param mixed $table (default: null)
	 * @param mixed $method (default: null)
	 * @param mixed $id
	 * @return void
	 */
	public function fetch_multiple_objects ($table, $field, $value, $sortField = 'id', $sortAsc = true) {
		# null table
		if(is_null($table)||strlen($table)==0) return false;
		else {
			try { $res = $this->Database->findObjects($table, $field, $value, $sortField, $sortAsc); }
			catch (Exception $e) {
				$this->Result->show("danger", _("Error: ").$e->getMessage());
				return false;
			}
			# result
			return sizeof($res)>0 ? $res : false;
		}
	}











	/**
	 *	@VLAN specific methods
	 *	--------------------------------
	 */

	/**
	 * Fetch vlans and subnets for tools vlan display. Joined query
	 *
	 * @access public
	 * @param int $domainId (default: 1)
	 * @return void
	 */
	public function fetch_vlans_and_subnets ($domainId=1) {
	    # custom fields
	    $custom_fields = $this->fetch_custom_fields("vlans");
		# if set add to query
	    if(sizeof($custom_fields)>0) {
			foreach($custom_fields as $myField) {
				$custom_fields_query  .= ',`vlans`.`'.$myField['name'].'`';
			}
		}
	    # set query
	    $query = 'SELECT vlans.vlanId,vlans.number,vlans.name,vlans.description,subnets.subnet,subnets.mask,subnets.id AS subnetId,subnets.sectionId'.@$custom_fields_query.' FROM vlans LEFT JOIN subnets ON subnets.vlanId = vlans.vlanId where vlans.`domainId` = ? ORDER BY vlans.number ASC;';
		# fetch
		try { $vlans = $this->Database->getObjectsQuery($query, array($domainId)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}

		# reorder
		foreach ($vlans as $vlan) {
			$out[$vlan->vlanId][] = $vlan;
		}
		# result
		return is_array($out) ? array_values($out) : false;
	}

	/**
	 * Validates VLAN
	 *
	 *	not 1
	 *	integer
	 *	not higher that maxVLAN from settings
	 *
	 * @access public
	 * @param int $number
	 * @return void
	 */
	public function validate_vlan ($number) {
		# fetch highest vlan id
		$settings = $this->get_settings();

		if(empty($number)) 							{ return true; }
		elseif(!is_numeric($number)) 				{ return _('VLAN must be numeric value!'); }
		elseif ($number > $settings['vlanMax']) 	{ return _('Vlan number can be max 4094'); }
		else 										{ return true; }
	}











	/**
	 *	@VRF specific methods
	 *	--------------------------------
	 */

	/**
	 * Fetches all VRFs.
	 *
	 * @access public
	 * @return void
	 */
	public function fetch_vrfs () {
		try { $vrfs = $this->Database->getObjectsQuery("SELECT * FROM `vrf`;"); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# save to vrfs array
		if(sizeof($vrfs)>0) {
			foreach($vrfs as $vrf) {
				$this->vrfs[$id] = (object) $vrf;
			}
		}
		# result
		return $vrfs;
	}

	/**
	 * Fetches VRF by specified method
	 *
	 * @access public
	 * @param string $method (default: "null")
	 * @param mixed $id
	 * @return void
	 */
	public function fetch_vrf ($method=null, $id) {
		# null method
		$method = is_null($method) ? "vrfId" : $this->Database->escape($method);
		# check cache first
		if(isset($this->vrfs[$id]))	{
			return $this->vrfs[$id];
		}
		# ignore 0
		elseif($id==0 || is_null($id)) {
			return false;
		}
		else {
			try { $vrf = $this->Database->getObjectQuery("SELECT * FROM `vrf` where `$method` = ? limit 1;", array($id)); }
			catch (Exception $e) {
				$this->Result->show("danger", _("Error: ").$e->getMessage());
				return false;
			}
			# save to vlans array
			if(sizeof($vrf)>0) {
				$this->vrfs[$id] = (object) $vrf;
				return $vrf;
			}
			else {
				return false;
			}
		}
	}










	/**
	 *	@device methods
	 *	--------------------------------
	 */

	/**
	 * Fetches all available devices
	 *
	 * @access public
	 * @param mixed $field
	 * @param mixed $val
	 * @param mixed $order_field (default: "id")
	 * @param mixed $order_direction (default: "asc")
	 * @return array
	 */
	public function fetch_devices ($field=null, $val=null, $order_field="id", $order_direction="asc") {
		# set query
		if(!is_null($field)) {
			# escape method/table
			$field = $this->Database->escape($field);

			$query  = "SELECT * FROM `devices` where `$field` like ? order by ? ?;";
			$params = array("%$val%", $order_field, $order_direction);
		}
		else {
			$query  = "SELECT * FROM `devices` order by ? ?;";
			$params = array($order_field, $order_direction);
		}
		# fetch
		try { $devices = $this->Database->getObjectsQuery($query, $params); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# save to devices array
		if(sizeof($devices)>0) {
			foreach($devices as $device) {
				$this->devices[$device->id] = (object) $device;
			}
		}
		# return
		return sizeof($devices)>0 ? $devices : array();
	}

	/**
	 * Fetches device by specified method
	 *
	 * @access public
	 * @param string $method (default: "vrfId")
	 * @param mixed $id
	 * @return void
	 */
	public function fetch_device ($method=null, $id) {
		# null method
		$method = is_null($method) ? "id" : $this->Database->escape($method);
		# check cache first
		if(isset($this->devices[$id]))	{
			return $this->devices[$id];
		}
		# ignore 0
		elseif($id==0 || is_null($id)) {
			return false;
		}
		else {
			try { $device = $this->Database->getObjectQuery("SELECT * FROM `devices` where `$method` = ? limit 1;", array($id)); }
			catch (Exception $e) {
				$this->Result->show("danger", _("Error: ").$e->getMessage());
				return false;
			}
			# save to vlans array
			if(sizeof($device)>0) {
				$this->devices[$id] = (object) $device;
				return $device;
			}
			else {
				return false;
			}
		}
	}

	/**
	 * Fetch number of addresses associated with device id
	 *
	 * @access public
	 * @param int $id
	 * @return int (count)
	 */
	public function count_device_addresses ($id=null) {
		# if null
		try { $cnt = $this->Database->numObjectsFilter("ipaddresses", "switch", $id); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		return $cnt;
	}

	/**
	 * Fetch all addresses assigned to some device
	 *
	 * @access public
	 * @param int $id (device id)
	 * @return array
	 */
	public function fetch_device_addresses ($id) {
		# fetch
		try { $addresses = $this->Database->getObjectsQuery("select * from `ipaddresses` where `switch` = ?;", array($id)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# return
		return $addresses;
	}

	/**
	 * Fetch all device types
	 *
	 * @access public
	 * @return array
	 */
	public function fetch_device_types () {
		# fetch
		try { $device_types = $this->Database->getObjectsQuery("SELECT * FROM `deviceTypes`;", $params); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# reindex
		$device_types = $this->rekey_device_types ($device_types);
		# return
		return $device_types;
	}

	/**
	 * Reindexes device types
	 *
	 * @access private
	 * @param array $device_types
	 * @return array
	 */
	private function rekey_device_types ($device_types) {
		foreach($device_types as $t) {
			$out[$t->tid] = $t;
		}
		# return
		return $out;
	}

	/**
	 * Fetch specific device type
	 *
	 * @access public
	 * @param mixed $method
	 * @param mixed $id
	 * @return void
	 */
	public function fetch_device_type ($method, $id) {
		# null method
		$method = is_null($method) ? "tid" : $this->Database->escape($method);
		# ignore 0
		if($id==0 || is_null($id)) {
			return false;
		}
		else {
			try { $device_type = $this->Database->getObjectQuery("SELECT * FROM `deviceTypes` where `$method` = ? limit 1;", array($id)); }
			catch (Exception $e) {
				$this->Result->show("danger", _("Error: ").$e->getMessage());
				return false;
			}
		}
		# return
		return $device_type;
	}















	/**
	 *	@search methods
	 *	--------------------------------
	 */

	/**
	 * Search database for addresses
	 *
	 * @access public
	 * @param mixed $search_term
	 * @param mixed $high
	 * @param mixed $low
	 * @return void
	 */
	public function search_addresses($search_term, $high, $low) {
		# fetch custom fields
		$custom_fields = $this->fetch_custom_fields ("ipaddresses");

		# set search query
		$query[] = "select * from `ipaddresses` ";
		$query[] = "where `ip_addr` between :low and :high ";	//ip range
		$query[] = "or `dns_name` like :search_term ";			//hostname
		$query[] = "or `owner` like :search_term ";				//owner
		# custom fields
		if(sizeof($custom_fields) > 0) {
			foreach($custom_fields as $myField) {
				$myField['name'] = $this->Database->escape($myField['name']);
				$query[] = "or `$myField[name]` like :search_term ";
			}
		}
		$query[] = "or `switch` like :search_term ";
		$query[] = "or `port` like :search_term ";				//port search
		$query[] = "or `description` like :search_term ";		//descriptions
		$query[] = "or `note` like :search_term ";				//note
		$query[] = "or `mac` like :search_term ";				//mac
		$query[] = "order by `ip_addr` asc;";

		# join query
		$query = implode("\n", $query);

		# fetch
		try { $result = $this->Database->getObjectsQuery($query, array("low"=>$low, "high"=>$high, "search_term"=>"%$search_term%")); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# result
		return $result;
	}

	/**
	 * Search subnets for provided range
	 *
	 *	First search range
	 *	If host provided search also inside subnet ranges
	 *
	 * @access public
	 * @param mixed $search_term
	 * @param number $high
	 * @param number $low
	 * @return array
	 */
	public function search_subnets($search_term, $high, $low) {
		# first search if range provided
		$result1 = $this->search_subnets_range  ($search_term, $high, $low);
		# search inside subnets even if IP does not exist!
		$result2 = $this->search_subnets_inside ($high, $low);
		# merge arrays
		if(sizeof($result1)>0 && sizeof($result2)>0)	{ $result = array_merge($result1, $result2); }
		elseif(sizeof($result1)>0)						{ $result = $result1; }
		elseif(sizeof($result2)>0)						{ $result = $result2; }
		else											{ $result = array(); }
	    # result
	    return array_filter($result);
	}

	/**
	 * Search for subnets inside range
	 *
	 * @access private
	 * @param mixed $search_term
	 * @param number $high
	 * @param number $low
	 * @return array
	 */
	private function search_subnets_range ($search_term, $high, $low) {
		# fetch custom fields
		$custom_fields = $this->fetch_custom_fields ("subnets");

		# reformat low/high
		if($high==0 && $low==0)	{ $high = "1"; $low="1"; }

		# set search query
		$query[] = "select * from `subnets` where `description` like :search_term ";
		$query[] = "or `subnet` between :low and :high ";
		# custom
	    if(sizeof($custom_fields) > 0) {
			foreach($custom_fields as $myField) {
				$myField['name'] = $this->Database->escape($myField['name']);
				$query[] = " or `$myField[name]` like :search_term ";
			}
		}
		$query[] = ";";

		# join query
		$query = implode("\n", $query);

		# fetch
		try { $result = $this->Database->getObjectsQuery($query, array("low"=>$low, "high"=>$high, "search_term"=>"%$search_term%")); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# result
		return $result;
	}

	/**
	 * Search inside subnets if host address is provided!
	 *
	 * @access private
	 * @param mixed $search_term
	 * @param number $high
	 * @param number $low
	 * @return array
	 */
	private function search_subnets_inside ($high, $low) {
		if($low==$high) {
			# subnets class
			$Subnets = new Subnets ($this->Database);
			# fetch all subnets
			$subnets = $Subnets->fetch_all_subnets_search();
			# loop and search
			foreach($subnets as $s) {
				# cast
				$s = (array) $s;
				//first verify address type
				$type = $this->identify_address($s['subnet']);
				if($type == "IPv4") {
					# Initialize PEAR NET object
					$this->initialize_pear_net_IPv4 ();
					# parse address
					$net = $this->Net_IPv4->parseAddress($Subnets->transform_to_decimal($s['subnet']).'/'.$s['mask']);

					if($low>$Subnets->transform_to_decimal(@$net->network) && $low<$Subnets->transform_to_decimal($net->broadcast)) {
						$ids[] = $s['id'];
					}
				}
			}
			# filter
			$ids = sizeof(@$ids)>0 ? array_filter($ids) : array();
			# search
			if(sizeof($ids)>0) {
				foreach($ids as $id) {
					$result[] = $Subnets->fetch_subnet(null, $id);
				}
			}
			# return
			return sizeof(@$result)>0 ? array_filter($result) : array();
		}
		else {
			return array();
		}
	}

	/**
	 * Function to search vlans
	 *
	 * @access public
	 * @param mixed $search_term
	 * @return void
	 */
	public function search_vlans($search_term) {
		# fetch custom fields
		$custom_fields = $this->fetch_custom_fields ("vlans");
		# query
		$query[] = "select * from `vlans` where `name` like :search_term or `description` like :search_term or `number` like :search_term ";
		# custom
	    if(sizeof($custom_fields) > 0) {
			foreach($custom_fields as $myField) {
				$myField['name'] = $this->Database->escape($myField['name']);
				$query[] = " or `$myField[name]` like :search_term ";
			}
		}
		$query[] = ";";
		# join query
		$query = implode("\n", $query);

		# fetch
		try { $search = $this->Database->getObjectsQuery($query, array("search_term"=>"%$search_term%")); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}

	    # return result
	    return $search;

	}

	/**
	 * Reformat possible nun-full IPv4 address for search
	 *
	 *	e.g. 10.10.10 -> 10.10.10.0 - 10.10.10.255
	 *
	 * @access public
	 * @param mixed $address
	 * @return array high/low decimal address
	 */
	public function reformat_IPv4_for_search ($address) {
		# remove % sign if present
		$address = str_replace("%", "", $address);
		# we need Addresses class
		$Addresses = new Addresses ($this->Database);

		# if subnet is provided we have all data
		if(strpos($address, "/")>0) {
			# Initialize PEAR NET object
			$this->initialize_pear_net_IPv4 ();
			$net = $this->Net_IPv4->parseAddress($ip);

			$result['low']   = $Addresses->transform_to_decimal($net->network);
			$result['high']	 = $Addresses->transform_to_decimal($net->broadcast);
		}
		# else calculate options
		else {
			# if subnet is not provided maye wildcard is, so explode it to array
			$address = array_filter(explode(".", $address));

			# 4 pieces is ok, host
			if (sizeof($address) == 4) {
				$result['low'] = $result['high'] = $Addresses->transform_to_decimal(implode(".", $address));
			}
			# 3 pieces, we need to modify > check whole subnet
			elseif (sizeof($address) == 3) {
				$result['low']  = $Addresses->transform_to_decimal(implode(".", array_merge($address, array(0))));
				$result['high'] = $Addresses->transform_to_decimal(implode(".", array_merge($address, array(255))));
			}
			# 2 pieces also
			elseif (sizeof($address) == 2) {
				$result['low']  = $Addresses->transform_to_decimal(implode(".", array_merge($address, array(0,0))));
				$result['high'] = $Addresses->transform_to_decimal(implode(".", array_merge($address, array(255,255))));
			}
			# 1 piece also
			elseif (sizeof($address) == 1) {
				$result['low']  = $Addresses->transform_to_decimal(implode(".", array_merge($address, array(0,0,0))));
				$result['high'] = $Addresses->transform_to_decimal(implode(".", array_merge($address, array(255,255,255))));
			}
			# else return same value
			else {
				$result['low']  = implode(".", $address);
				$result['high'] = implode(".", $address);
			}
		}
		# return result array low/high
		return $result;
	}

	/**
	 * Reformat possible nun-full IPv6 address for search
	 *
	 * @access public
	 * @param mixed $address
	 * @return void
	 */
	public function reformat_IPv6_for_search ($address) {
		# we need Subnets class
		$Subnets = new Subnets ($this->Database);
		# split network and subnet part
		$address = explode("/", $address);

		# if subnet is not provided we are looking for host
		if (sizeof($address) < 2) {
			$return['low']  = $Subnets->transform_to_decimal($address[0]);
			$return['high'] = $Subnets->transform_to_decimal($address[0]);
		}

		//if network part ends with :: we must search the complete provided subnet!
		$lastChars = substr($address[0], -2);

		if ($lastChars == "::") {
			$return['low']  = $Subnets->transform_to_decimal ($address[0]);

			//set highest IP address
			$subnet = substr($ip[0], 0, -2);
			$subnet = $Subnets->transform_to_decimal ($subnet);

			//calculate all possible hosts in subnet mask
			$maxHosts = $Subnets->get_max_hosts ($address[1], "IPv6");

			$return['high'] = gmp_strval(gmp_add($return['low'], $maxHosts));
		}
		# return result array low/high
		return $return;
	}













	/**
	 *	@custom fields methods
	 *	--------------------------------
	 */

	/**
	 * Fetches all custom fields
	 *
	 * @access public
	 * @param mixed $table
	 * @return void
	 */
	public function fetch_custom_fields ($table) {
    	# fetch columns
		$fields = $this->fetch_columns ($table);

		# save Field values only
		foreach($fields as $field) {
			# cast
			$field = (array) $field;

			$res[$field['Field']]['name'] 	 = $field['Field'];
			$res[$field['Field']]['type'] 	 = $field['Type'];
			$res[$field['Field']]['Comment'] = $field['Comment'];
			$res[$field['Field']]['Null'] 	 = $field['Null'];
			$res[$field['Field']]['Default'] = $field['Default'];
		}

		# fetch standard fields
		$standard = $this->fetch_standard_fields ($table);

		# remove them
		foreach($standard as $st) {
			unset($res[$st]);
		}
		# return array
		return sizeof($res)==0 ? array() : $res;
	}

	/**
	 * Fetches all custom fields and reorders them into numeric array
	 *
	 * @access public
	 * @param mixed $table
	 * @return void
	 */
	public function fetch_custom_fields_numeric ($table) {
		# fetch all custom fields
		$custom_fields = $this->fetch_custom_fields ($table);
		# make numberic array
		if(sizeof($custom_fields>0)) {
			foreach($custom_fields as $f) {
				$out[] = $f;
			}
			# result
			return isset($out) ? $out : array();
		}
		else {
			return array();
		}
	}

	/**
	 * Fetch all fields configured in table - standard + custom
	 *
	 * @access private
	 * @param mixed $table
	 * @return void
	 */
	private function fetch_columns ($table) {
		# escape method/table
		$table = $this->Database->escape($table);
    	# fetch columns
		$query    = "show full columns from `$table`;";
		# fetch
	    try { $fields = $this->Database->getObjectsQuery($query); }
		catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), false);	return false; }

		return (array) $fields;
	}

	/**
	 * Fetches standard database fields from SCHEMA.sql file
	 *
	 * @access public
	 * @param mixed $table
	 * @return void
	 */
	public function fetch_standard_fields ($table) {
		# get SCHEMA.SQL file
		$schema = fopen(dirname(__FILE__) . "/../../db/SCHEMA.sql", "r");
		$schema = fread($schema, 100000);

		# get definition
		$definition = strstr($schema, "CREATE TABLE `$table` (");
		$definition = trim(strstr($definition, ";\n", true));

		# get each line to array
		$definition = explode("\n", $definition);

		# go through,if it begins with ` use it !
		foreach($definition as $d) {
			$d = trim($d);
			if(strpos(trim($d), "`")==0) {
				$d = strstr(trim($d, "`"), "`", true);
				$out[] = substr($d, strpos($d, "`"));
			}
		}
		# return array of fields
		return is_array($out) ? array_filter($out) : array();
	}

	/**
	 * Fetches standard tables from SCHEMA.sql file
	 *
	 * @access private
	 * @return void
	 */
	private function fetch_standard_tables () {
		# get SCHEMA.SQL file
		$schema = fopen(dirname(__FILE__) . "/../../db/SCHEMA.sql", "r");
		$schema = fread($schema, 100000);

		# get definitions to array, explode with CREATE TABLE `
		$creates = explode("CREATE TABLE `", $schema);
		# fill tables array
		foreach($creates as $k=>$c) {
			if($k>0)	{ $tables[] = strstr($c, "`", true); }	//we exclude first !
		}

		# return array of tables
		return $tables;
	}

	/**
	 * This functions fetches all columns for specified Field
	 *
	 * Array (Field, Type, Collation, Null, Comment)
	 *
	 * @access public
	 * @param mixed $table
	 * @param mixed $field
	 * @return void
	 */
	public function fetch_full_field_definition ($table, $field) {
		# escape field
		$table = $this->Database->escape($table);
		# fetch
	    try { $field_data = $this->Database->getObjectQuery("show full columns from `$table` where `Field` = ?;", array($field)); }
		catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), false);	return false; }
		# result
	    return($field_data);
	}












	/**
	 *	@widget methods
	 *	--------------------------------
	 */

	/**
	 * Fetches all widgets
	 *
	 * @access public
	 * @param bool $admin (default: false)
	 * @param bool $inactive (default: false)
	 * @return void
	 */
	public function fetch_widgets ($admin = false, $inactive = false) {

		# inactive also - only for administration
		if($inactive) 			{ $query = "select * from `widgets`; "; }
		else {
			# admin?
			if($admin) 			{ $query = "select * from `widgets` where `wactive` = 'yes'; "; }
			else				{ $query = "select * from `widgets` where `wadminonly` = 'no' and `wactive` = 'yes'; "; }
		}
	    # fetch
	    try { $widgets = $this->Database->getObjectsQuery($query); }
		catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), false);	return false; }

	    # reindex
	    foreach($widgets as $w) {
			$wout[$w->wfile] = $w;
	    }

	    # return results
	    return $wout;
	}

	/**
	 * Fetches widget by specified method
	 *
	 * @access public
	 * @param string $method (default: "wid")
	 * @param mixed $id
	 * @return void
	 */
	public function fetch_widget ($method, $id) {
		# null method
		$method = is_null($method) ? "wid" : $this->Database->escape($method);
		# fetch
		try { $widget = $this->Database->getObjectQuery("SELECT * FROM `widgets` where `$method` = ? limit 1;", array($id)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}

		# return result
		return sizeof($widget)>0 ? (array) $widget : NULL;
	}

	/**
	 * Verify that widget file exists
	 *
	 * @access public
	 * @return void
	 */
	public function verify_widget ($file) {
		return file_exists("app/dashboard/widgets/$file.php")==false ? false : true;
	}










	/**
	 *	@request methods (for IP request)
	 *	--------------------------------
	 */

	/**
	 * fetches all IP requests and saves them to $requests
	 *
	 * @access public
	 * @return void
	 */
	public function requests_fetch ($num = true) {
		return $num ? $this->requests_fetch_num () : $this->requests_fetch_objects ();
	}

	/**
	 * Fetches number of active IP requests
	 *
	 * @access private
	 * @return void
	 */
	private function requests_fetch_num () {
		try { return $this->Database->numObjectsFilter("requests", "processed", "0"); }
		catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), false);	return false; }
	}

	/**
	 * Fetches all requests and saves them to $requests
	 *
	 * @access private
	 * @return void
	 */
	private function requests_fetch_objects () {
		try { return $this->Database->getObjectsQuery("SELECT * FROM `requests` where `processed`=0;"); }
		catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), false);	return false; }
	}

	/**
	 * Fetches all subnets that are set to allow requests
	 *
	 * @access public
	 * @return void
	 */
	public function requests_fetch_available_subnets () {
		try { $subnets = $this->Database->getObjectsQuery("SELECT * FROM `subnets` where `allowRequests`=1;"); }
		catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), false);	return false; }

		# save
		return sizeof($subnets)>0 ? (array) $subnets : NULL;
	}

	/**
	 * Sends mail for IP request
	 *
	 * @access public
	 * @param string $action (default: "new")
	 * @param mixed $values
	 * @return void
	 */
	public function ip_request_send_mail ($action="new", $values) {

		# get all admins and check who to end mail to
		$recipients = $this->ip_request_get_mail_recipients ();

		# add requester to cc
		$recipients_requester = $values['requester'];

		# reformat key / vaues
		$values = $this->ip_request_reformat_mail_values ($values);
		#reformat empty
		$values = $this->reformat_empty_array_fields ($values, "/");

		# generate content
		if ($action=="new")			{ $subject	= "New IP address request"; }
		elseif ($action=="accept")	{ $subject	= "IP address request accepted"; }
		elseif ($action=="reject")	{ $subject	= "IP address request rejected"; }
		else						{ $this->Result->show("danger", _("Invalid request action"), true); }

		// set html content
		$content[] = "<table style='margin-left:10px;margin-top:5px;width:auto;padding:0px;border-collapse:collapse;'>";
		$content[] = "<tr><td colspan='2' style='padding:5px;margin:0px;color:#333;font-size:16px;text-shadow:1px 1px 1px white;border-bottom:1px solid #eeeeee;'><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:16px;'>$subject</font></td></tr>";
		foreach($values as $k=>$v) {
		// title search
		if (preg_match("/s_title_/", $k)) {
		$content[] = "<tr><td colspan='2' style='padding:5px;margin:0px;color:#333;font-size:16px;text-shadow:1px 1px 1px white;border-bottom:1px solid #eeeeee;'><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:16px;'>$v</font></td></tr>";
		}
		else {
		//content
		$content[] = "<tr>";
		$content[] = "<td style='padding:3px;padding-left:15px;margin:0px;'><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;'>$k</font></td>";
		$content[] = "<td style='padding:3px;padding-left:15px;margin:0px;'><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;'>$v</font></td>";
		$content[] = "</tr>";
		}
		}
		$content[] = "<tr><td style='padding:5px;padding-left:15px;margin:0px;font-style:italic;padding-bottom:3px;text-align:right;color:#ccc;text-shadow:1px 1px 1px white;border-top:1px solid white;'><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:11px;'>Sent by user ".$User->user->real_name." at ".date('Y/m/d H:i')."</font></td></tr>";
		//set alt content
		$content_plain[] = "$subject"."\r\n------------------------------\r\n";
		foreach($values as $k=>$v) {
		$content_plain[] = $k." => ".$v;
		}
		$content_plain[] = "\r\n\r\n"._("Sent by user")." ".$User->user->real_name." at ".date('Y/m/d H:i');
		$content[] = "</table>";


		# fetch mailer settings
		$mail_settings = $this->fetch_object("settingsMail", "id", 1);

		# initialize mailer
		$this->get_settings ();
		$phpipam_mail = new phpipam_mail($this->settings, $mail_settings);
		$phpipam_mail->initialize_mailer();

		// set content
		$content 		= $phpipam_mail->generate_message (implode("\r\n", $content));
		$content_plain 	= implode("\r\n",$content_plain);

		# try to send
		try {
			$phpipam_mail->Php_mailer->setFrom($mail_settings->mAdminMail, $mail_settings->mAdminName);
			if ($recipients!==false) {
			foreach($recipients as $r) {
			$phpipam_mail->Php_mailer->addAddress(trim($r->email));
			}
			$phpipam_mail->Php_mailer->AddCC(trim($recipients_requester));
			}
			else {
			$phpipam_mail->Php_mailer->addAddress(trim($recipients_requester));
			}
			$phpipam_mail->Php_mailer->Subject = $subject;
			$phpipam_mail->Php_mailer->msgHTML($content);
			$phpipam_mail->Php_mailer->AltBody = $content_plain;
			//send
			$phpipam_mail->Php_mailer->send();
		} catch (phpmailerException $e) {
			$Result->show("danger", "Mailer Error: ".$e->errorMessage(), true);
		} catch (Exception $e) {
			$Result->show("danger", "Mailer Error: ".$e->errorMessage(), true);
		}

		# ok
		return true;

	}

	/**
	 * Returns list of recipients to get new
	 *
	 * @access private
	 * @return void
	 */
	private function ip_request_get_mail_recipients () {
		// get all admins and check who to end mail to
		$recipients = $this->fetch_multiple_objects ("users", "role", "Administrator", "id", true);
		//check recepients
		if ($recipients!==false) {
			// check
			$m = 0;
			foreach($recipients as $k=>$r) {
				if($r->mailNotify!="Yes") {
					unset($recipients[$k]);
				} else {
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

	/**
	 * Reformats request value/key pairs for request mailing
	 *
	 * @access private
	 * @param mixed $values
	 * @return void
	 */
	private function ip_request_reformat_mail_values ($values) {
		// no array
		if (!is_array($values)) { return $values; }

		// addresses
		$this->Addresses = new Addresses ($this->Database);

		// change fields for mailings
		foreach ($values as $k=>$v) {
			// subnetId
			if ($k=="subnetId")	{
				// add title
				$mail["s_title_1"] = "<br>Subnet details";

				$subnet = $this->fetch_object("subnets", "id", $v);
				$mail["Subnet"]  = $this->transform_address ($subnet->subnet, "dotted")."/".$subnet->mask;
				$mail["Subnet"] .= strlen($subnet->description)>0 ? " - ".$subnet->description : "";
			}
			// ip_addr
			elseif ($k=="ip_addr") {
				// add title
				$mail["s_title_2"] = "<br>Address details";

				if (strlen($v)>0) {
					$mail['IP address'] = $this->transform_address($v, "dotted");
				} else {
					$mail['IP address'] = "Automatic";
				}
			}
			// state
			elseif ($k=="state") {
				$mail['State'] = $this->Addresses-> address_type_index_to_type ($v);
			}
			// description
			elseif ($k=="descriotion") {
				$mail['Description'] = $v;
			}
			// dns_name
			elseif ($k=="dns_name") {
				$mail['Hostname'] = $v;
			}
			// owner
			elseif ($k=="owner") {
				$mail['Address owner'] = $v;
			}
			// requester
			elseif ($k=="requester") {
				$mail['Requested by'] = $v;
			}
			// comment
			elseif ($k=="comment") {
				$mail['Request comment'] = $v;
			}
			// admin comment
			elseif ($k=="adminComment") {
				// add title
				$mail["s_title_3"] = "<br>Admin comment";

				$mail['Admin comment'] = $v;
			}
			// admin comment
			elseif ($k=="gateway") {
				$mail['Gateway'] = $v;
			}
			// nameservers
			elseif ($k=="dns") {
				if (strlen($v)>0) {
				$mail['DNS servers'] = $v;
				}
			}
			// vlans
			elseif ($k=="vlan") {
				if (strlen($v)>0) {
				$mail['VLAN'] = $v;
				}
			}
		}
		// response
		return $mail;
	}












	/**
	 *	@database verification methods
	 *	------------------------------
	 */

	/**
	 * Checks if all database fields are installed ok
	 *
	 * @access public
	 * @return void
	 */
	public function verify_database () {

		# required tables from SCHEMA.sql
		$tables = $this->fetch_standard_tables();

		# fetch required fields
		foreach($tables as $t) {
			$fields[$t] = $this->fetch_standard_fields ($t);
		}

		/**
		 * check that each database exist - if it does check also fields
		 *		2 errors -> $tableError, $fieldError[table] = field
		 ****************************************************************/
		foreach($tables as $table) {

			//check if table exists
			if(!$this->table_exists($table)) {
				$error['tableError'][] = $table;
			}
			//check for each field
			else {
				foreach($fields[$table] as $field) {
					//if it doesnt exist store error
					if(!$this->field_exists($table, $field)) {
						$error['fieldError'][$table] = $field;
					}
				}
			}
		}

		# return array
		if(isset($error)) {
			return $error;
		} else 	{
			# update check field
			$this->update_db_verify_field ();
			# return empty array
			return array();
		}
	}

	/**
	 * Checks if specified table exists in database
	 *
	 *	true = exists
	 *	false = doesnt exist
	 *
	 * @access public
	 * @param mixed $tablename
	 * @param bool $quit
	 * @return bool
	 */
	public function table_exists ($tablename, $quit = false) {
	    # query
	    $query = 'SELECT COUNT(*) AS count FROM information_schema.tables WHERE table_schema = "'.$this->Database->dbname.'" AND table_name = ?;';
		try { $count = $this->Database->getObjectQuery($query, array($tablename)); }
		catch (Exception $e) { !$quit ? : $this->Result->show("danger", $e->getMessage(), true);	return false; }
		# return
		return $count->count ==1 ? true : false;
	}

	/**
	 * Checks if specified field exists in table
	 *
	 *	true = exists
	 *	false = doesnt exist
	 *
	 * @access public
	 * @param mixed $fieldname
	 * @return bool
	 */
	public function field_exists ($tablename, $fieldname) {
	    # escape
	    $tableName = $this->Database->escape($tablename);
	    $tableName = $this->Database->escape($tablename);
		# check
	    $query = "DESCRIBE `$tablename` `$fieldname`;";
		try { $count = $this->Database->getObjectQuery($query); }
		catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), true);	return false; }
		# return true if it exists
		return sizeof($count)>0 ? true : false;
	}

	/**
	 * Updates DB check flag in database
	 *
	 * @access private
	 * @return void
	 */
	private function update_db_verify_field () {
		# query
		$query = "update `settings` set `dbverified`=1 where `id` = 1; ";
		try { $this->Database->runQuery($query); }
		catch (Exception $e) { !$this->debugging ? : $this->Result->show("danger", $e->getMessage(), false); }
	}

	/**
	 * Get fix for missing table.
	 *
	 * @access public
	 * @param mixed $table
	 * @return void
	 */
	public function get_table_fix ($table) {
		$res = fopen(dirname(__FILE__) . "/../../db/SCHEMA.sql", "r");
		$file = fread($res, 100000);

		//go from delimiter on
		$file = strstr($file, "DROP TABLE IF EXISTS `$table`;");
		$file = trim(strstr($file, "# Dump of table", true));

		# check
		if(strpos($file, "DROP TABLE IF EXISTS `$table`;") > 0 )	return false;
		else														return $file;
	}

	/**
	 * Get fix for missing field.
	 *
	 * @access public
	 * @param mixed $table
	 * @param mixed $field
	 * @return void
	 */
	public function get_field_fix ($table, $field) {
		$res = fopen(dirname(__FILE__) . "/../../db/SCHEMA.sql", "r");
		$file = fread($res, 100000);

		//go from delimiter on
		$file = strstr($file, "DROP TABLE IF EXISTS `$table`;");
		$file = trim(strstr($file, "# Dump of table", true));

		//get proper line
		$file = explode("\n", $file);
		foreach($file as $k=>$l) {
			if(strpos(trim($l), "$field`")==1) {
				//get previous
				$prev = trim($file[$k-1]);
				$prev = explode("`", $prev);
				$prev = "`$prev[1]`";

				$res = trim($l, ",");
				$res .= " after $prev;";

				return $res;
			}
		}
		return false;
	}

	/**
	 * Fix missing table - create
	 *
	 * @access public
	 * @param mixed $table
	 * @return void
	 */
	public function fix_table ($table) {
		# first fetch fix query
		$query = $this->get_table_fix($table);
		# fix
		try { $this->Database->runQuery($query); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Update: ").$e->getMessage()."<br>query: ".$query, true);
			return false;
		}
		return true;
	}

	/**
	 * Fix missing field in table
	 *
	 * @access public
	 * @param mixed $table
	 * @param mixed $field
	 * @return void
	 */
	public function fix_field ($table, $field) {

		# set fix query
		$query  = "alter table `$table` add ";
		$query .= trim($this->get_field_fix ($table, $field), ",");
		$query .= ";";

		# fix
		try { $this->Database->runQuery($query); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Update: ").$e->getMessage()."<br>query: ".$query, true);
			return false;
		}
		return true;
	}











	/**
	 *	@version check methods
	 *	------------------------------
	 */

	/**
	 * Checks for latest phpipam version from phpipam webpage
	 *
	 * @access public
	 * @return void
	 */
	public function check_latest_phpipam_version () {
		# fetch webpage
		$handle = @fopen("http://phpipam.net/phpipamversion.php", "r");
		if($handle) {
			while (!feof($handle)) {
				$version = fgets($handle);
			}
			fclose($handle);
		}

		# replace dots for check
		$versionT = str_replace(".", "", $version);

		# return version or false
		return is_numeric($versionT) ? $version : false;
	}

	/**
	 * Updates DB version check flag in database
	 *
	 * @access public
	 * @return void
	 */
	public function update_phpipam_checktime () {
		# query
		$query = "update `settings` set `vcheckDate`='".date("Y-m-d H:i:s")."';";
		try { $this->Database->runQuery($query); }
		catch (Exception $e) { !$this->debugging ? : $this->Result->show("danger", $e->getMessage(), false); }
	}









	/**
	 * @ipcalc @calculator methods
	 * ------------------------------
	 */

	/**
	 * Calculates IP calculator result per IP type
	 *
	 * @access public
	 * @param mixed $cidr
	 * @return void
	 */
	public function calculate_ip_calc_results ($cidr) {
		# addresses class
		$Addresses = new Addresses ($this->Database);
		# detect address and calculate
		return $this->identify_address($cidr)=="IPv6" ? $this->calculate_IPv6_calc_results($cidr) : $this->calculate_IPv4_calc_results($cidr);
	}

	/**
	 * Calculates IPv4 results from provided CIDR address
	 *
	 * @access private
	 * @param mixed $cidr
	 * @return array
	 */
	private function calculate_IPv4_calc_results ($cidr) {
		# initialize subnets Class
		$Subnets = new Subnets($this->Database);
		# Initialize PEAR NET object
		$this->initialize_pear_net_IPv4 ();

		# parse address
        $net = $this->Net_IPv4->parseAddress( $cidr );

        # set ip address type
        $out['Type']            = 'IPv4';

        # calculate network details
        $out['IP address']      = $net->ip;        // 192.168.0.50
        $out['Network']         = $net->network;   // 192.168.0.0
        $out['Broadcast']       = $net->broadcast; // 192.168.255.255
        $out['Subnet bitmask']  = $net->bitmask;   // 16
        $out['Subnet netmask']  = $net->netmask;   // 255.255.0.0
        $out['Subnet wildcard'] = long2ip(~ip2long($net->netmask));	//0.0.255.255

        # calculate min/max IP address
        $out['Min host IP']     = long2ip(ip2long($net->network) + 1);
        $out['Max host IP']     = long2ip(ip2long($net->broadcast) - 1);
        $out['Number of hosts'] = $Subnets->get_max_hosts ($net->bitmask, "IPv4");;

        # subnet class
        $out['Subnet Class']    = $this->get_ipv4_address_type($net->network, $net->broadcast);

        # if IP == subnet clear the Host fields
        if ($out['IP address'] == $out['Network']) {
            $out['IP address'] = "/";
        }
        # /32 and /32 fixes
        if($net->bitmask==31 || $net->bitmask==32) {
			$out['Min host IP'] = $out['Network'];
			$out['Max host IP'] = $out['Broadcast'];
        }
		# result
		return $out;
	}

	/**
	 * Returns IPv4 address type from cidr
	 *
	 * @access private
	 * @param $network
	 * @param $broadcast
	 * @return void
	 */
	private function get_ipv4_address_type ($network, $broadcast) {
		# get all possible classes
		$classes = $this->define_ipv4_address_types ();
		# Initialize PEAR NET object
		$this->initialize_pear_net_IPv4 ();
		# check for each if member
	    foreach( $classes as $key=>$class ) {
	        if ($this->Net_IPv4->ipInNetwork($network, $class)) {
	            if ($this->Net_IPv4->ipInNetwork($broadcast, $class)) {
	                return($key);
	            }
	        }
	    }
	    # no match
	    return false;
	}

	/**
	 * Defines all possible IPv4 address types
	 *
	 * @access private
	 * @return array
	 */
	private function define_ipv4_address_types () {
	    # define classes
	    $classes['private A']          = '10.0.0.0/8';
	    $classes['private B']          = '172.16.0.0/12';
	    $classes['private C']          = '192.168.0.0/16';
	    $classes['Loopback']           = '127.0.0.0/8';
	    $classes['Link-local']         = '169.254.0.0/16';
	    $classes['Reserved (IANA)']    = '192.0.0.0/24';
	    $classes['TEST-NET-1']         = '192.0.2.0/24';
	    $classes['IPv6 to IPv4 relay'] = '192.88.99.0/24';
	    $classes['Network benchmark']  = '198.18.0.0/15';
	    $classes['TEST-NET-2']         = '198.51.100.0/24';
	    $classes['TEST-NET-3']         = '203.0.113.0/24';
	    $classes['Multicast']          = '224.0.0.0/4';
	    $classes['Reserved']           = '240.0.0.0/4';
	    # result
	    return $classes;
	}

	/**
	 * Calculates IPv6 from cidr
	 *
	 * @access private
	 * @param mixed $cidr
	 * @return void
	 */
	private function calculate_IPv6_calc_results ($cidr) {
		# initialize subnets Class
		$Subnets = new Subnets($this->Database);
		# Initialize PEAR NET object
		$this->initialize_pear_net_IPv6 ();

        # set ip address type
        $out['Type']                      = 'IPv6';

        # calculate network details
        $out['Host address']              = $cidr;
        $out['Host address']              = $this->Net_IPv6->compress ( $out['Host address'], 1 );
        $out['Host address (uncompressed)'] = $this->Net_IPv6->uncompress ( $out['Host address'] );

        $mask                             = $this->Net_IPv6->getNetmaskSpec( $cidr );
        $subnet                           = $this->Net_IPv6->getNetmask( $cidr );
        $out['Subnet prefix']             = $this->Net_IPv6->compress ( $subnet ) .'/'. $mask;
        $out['Prefix length']             = $this->Net_IPv6->getNetmaskSpec( $cidr );

        # get reverse DNS entries
        $out['Host Reverse DNS']   = $this->reverse_IPv6($out['Host address (uncompressed)']);
        $out['Subnet Reverse DNS'] = $this->reverse_IPv6($subnet, $mask);

        # if IP == subnet clear the Host fields and Host Reverse DNS
         if ($out['Host address'] == $out['Subnet prefix']) {
             $out['Host address']                = '/';
             $out['Host address (uncompressed)'] = '/';
             unset($out['Host Reverse DNS']);
        }

        # /min / max hosts
        $maxIp = gmp_strval(gmp_add(gmp_pow(2, 128 - $mask),$this->ip2long6 ($subnet)));
		$maxIp = gmp_strval(gmp_sub($maxIp, 1));

        $out['Min host IP']               = $subnet;
        $out['Max host IP']               = $this->long2ip6 ($maxIp);
        $out['Number of hosts']           = $Subnets->get_max_hosts ($mask, "IPv6");

        # set address type
        $out['Address type']              = $this->get_ipv6_address_type( $cidr );
		# result
		return $out;
	}

	/**
	 * Calculate reverse DNS entry for IPv6 addresses
	 *
	 *	If a prefix length is given, generate only up to this length (ie. for zone definitions)
	 *
	 * @access public
	 * @param mixed $addresses
	 * @param int $pflen (default: 128)
	 * @return void
	 */
	public function reverse_IPv6 ($addresses, $pflen=128) {
		# Initialize PEAR NET object
		$this->initialize_pear_net_IPv6 ();
		//uncompress
	    $uncompressed = $this->Net_IPv6->removeNetmaskSpec($this->Net_IPv6->uncompress($addresses));
	    $len = $pflen / 4;
	    $parts = explode(':', $uncompressed);
	    $res = '';
	    foreach($parts as $part) {
	        $res .= str_pad($part, 4, '0', STR_PAD_LEFT);
	    }
	    $res = implode('.', str_split(strrev(substr($res, 0, $len)))) . '.ip6.arpa';
	    if ($pflen % 4 != 0) {
	        $res .= " "._("(closest parent)");
	    }
	    return $res;
	}

	/**
	 * Returns IPv6 address type from cidr
	 *
	 * @access private
	 * @param CIDR $cidr
	 * @return void
	 */
	private function get_ipv6_address_type ($cidr) {
		# Initialize PEAR NET object
		$this->initialize_pear_net_IPv6 ();
		# get type in number
		$type = $this->Net_IPv6->getAddressType($cidr);
		# fetch types
		$all_types = $this->define_ipv6_address_types ();
		# translate
		return array_key_exists($type, $all_types) ? $all_types[$type] : false;
	}

	/**
	 * Defines all IPv6 address types
	 *
	 * @access private
	 * @return array
	 */
	private function define_ipv6_address_types () {
        $all_types[10] = "NET_IPV6_NO_NETMASK";
        $all_types[1]  = "NET_IPV6";
        $all_types[11] = "NET_IPV6_RESERVED";
        $all_types[12] = "NET_IPV6_RESERVED_NSAP";
        $all_types[13] = "NET_IPV6_RESERVED_IPX";
        $all_types[14] = "NET_IPV6_RESERVED_UNICAST_GEOGRAPHIC";
        $all_types[22] = "NET_IPV6_UNICAST_PROVIDER";
        $all_types[31] = "NET_IPV6_MULTICAST";
        $all_types[42] = "NET_IPV6_LOCAL_LINK";
        $all_types[43] = "NET_IPV6_LOCAL_SITE";
        $all_types[51] = "NET_IPV6_IPV4MAPPING";
        $all_types[51] = "NET_IPV6_UNSPECIFIED";
        $all_types[51] = "NET_IPV6_LOOPBACK";
        $all_types[51] = "NET_IPV6_UNKNOWN_TYPE";
		# response
        return $all_types;
	}














	/**
	 *	@misc methods
	 *	------------------------------
	 */

	/**
	 * Fetches instructions from database
	 *
	 * @access public
	 * @return void
	 */
	public function fetch_instructions () {
		try { $instructions = $this->Database->getObject("instructions", 1); }
		catch (Exception $e) {
			$this->Result->show("danger", $e->getMessage(), false);
			return false;
		}
		return $instructions;
	}

	/**
	 * Counts number of IP addresses for statistics
	 *
	 * @access public
	 * @param string $type (default: "IPv4")
	 * @return void
	 */
	public function count_subnets ($type="IPv4") {
		# set proper query
		if($type=="IPv4")		{ $query = 'select count(cast(`ip_addr` as UNSIGNED)) as count from `ipaddresses` where cast(`ip_addr` as UNSIGNED) < "4294967295";'; }
		elseif($type=="IPv6")	{ $query = 'select count(cast(`ip_addr` as UNSIGNED)) as count from `ipaddresses` where cast(`ip_addr` as UNSIGNED) > "4294967295";'; }

		try { $count = $this->Database->getObjectQuery($query); }
		catch (Exception $e) { !$quit ? : $this->Result->show("danger", $e->getMessage(), true);	return false; }

		/* return true if it exists */
		return $count->count;
	}

	/**
	 * Fetches top subnets for dashboard graphs
	 *
	 * @access public
	 * @param mixed $type
	 * @param string $limit (default: "10")
	 * @param bool $perc (default: false)
	 * @return void
	 */
	public function fetch_top_subnets ($type, $limit = "10", $perc = false)
	{
	    # set limit
	    $limit = $limit==0 ? "" : "limit $limit";

	    # set query
	    if($perc) {
			$query = "select SQL_CACHE *,round(`usage`/(pow(2,32-`mask`)-2)*100,2) as `percentage` from (
						select `sectionId`,`id`,`subnet`,cast(`subnet` as UNSIGNED) as cmp,`mask`,IF(char_length(`description`)>0, `description`, 'No description') as description, (
							SELECT COUNT(*) FROM `ipaddresses` as `i` where `i`.`subnetId` = `s`.`id`
						)
						as `usage` from `subnets` as `s`
						where `mask` < 31 and cast(`subnet` as UNSIGNED) < '4294967295'
						order by `usage` desc
						) as `d` where `usage` > 0 order by `usage` desc $limit;";
	    }
		# ipv4 stats
		elseif($type == "IPv4") {
			$query = "select SQL_CACHE * from (
					select `sectionId`,`id`,`subnet`,cast(`subnet` as UNSIGNED) as cmp,`mask`,IF(char_length(`description`)>0, `description`, 'No description') as description, (
						SELECT COUNT(*) FROM `ipaddresses` as `i` where `i`.`subnetId` = `s`.`id`
					)
					as `usage` from `subnets` as `s`
					where cast(`subnet` as UNSIGNED) < '4294967295'
					order by `usage` desc $limit
					) as `d` where `d`.`usage` > 0;";
		}
		# IPv6 stats
		else {
			$query = "select SQL_CACHE * from (
					select `sectionId`,`id`,`subnet`,cast(`subnet` as UNSIGNED) as cmp,`mask`, IF(char_length(`description`)>0, `description`, 'No description') as description, (
						SELECT COUNT(*) FROM `ipaddresses` as `i` where `i`.`subnetId` = `s`.`id`
					)
					as `usage` from `subnets` as `s`
					where cast(`subnet` as UNSIGNED) > '4294967295'
					order by `usage` desc $limit
					) as `d` where `d`.`usage` > 0;";
		}

		# fetch
		try { $stats = $this->Database->getObjectsQuery($query); }
		catch (Exception $e) { !$debugging ? : $this->Result->show("danger", $e->getMessage(), true);	return false; }

	    # return subnets array
	    return (array) $stats;
	}

	/**
	 * Validate posted action on scripts
	 *
	 * @access public
	 * @param mixed $action
	 * @return void
	 */
	public function validate_action ($action) {
		# get valid actions
		$valid_actions = $this->get_valid_actions ();
		# check
		in_array($action, $valid_actions) ?: $this->Result->show("danger", _("Invalid action!"), true);
	}

	/**
	 * Sets valid actions
	 *
	 * @access private
	 * @return void
	 */
	private function get_valid_actions () {
		return array("add", "all-add", "edit", "all-edit", "delete", "truncate", "split", "resize", "move");
	}

	/**
	 * Fetches all addresses to export to hosts file
	 *
	 * @access public
	 * @return void
	 */
	public function fetch_addresses_for_export () {
		# fetch
	    try { $addresses = $this->Database->getObjectsQuery("select `id`,`subnetId`,`ip_addr`,`dns_name` from `ipaddresses` where length(`dns_name`)>1 order by `subnetId` asc;"); }
		catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), false);	return false; }
		# return result
		return $addresses;
	}

	/**
	 * Verify that translation exists
	 *
	 * @access public
	 * @param mixed $code		//lang code
	 * @return void
	 */
	public function verify_translation ($code) {
		//verify that proper files exist
		return !file_exists("functions/locale/$code/LC_MESSAGES/phpipam.mo") ? false : true;
	}

	/**
	 * Fetches translation version from code
	 *
	 * @access public
	 * @param mixed $code		//lang code
	 * @return void
	 */
	public function get_translation_version ($code) {
		//check for version
		$ver = shell_exec("grep 'Project-Id-Version:' ".dirname(__FILE__)."/../locale/$code/LC_MESSAGES/phpipam.po");
		//parse
		$ver = str_replace(array("Project-Id-Version:", " ", '"', "#",'\n', ":"), "", $ver);
		//return version
		return $ver;
	}

}