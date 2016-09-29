<?php

/**
 *	phpIPAM Section class
 */

class Tools extends Common_functions {

	/**
	 * settings
	 *
	 * (default value: null)
	 *
	 * @var object
	 * @access public
	 */
	public $settings = null;

	/**
	 * (array) IP address types from Addresses object
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access public
	 */
	public $address_types = null;

	/**
	 * PEAR NET IPv4 object
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Net_IPv4;

	/**
	 * PEAR NET IPv6 object
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Net_IPv6;

	/**
	 * Addresses object
	 *
	 * (default value: false)
	 *
	 * @var bool|object
	 * @access protected
	 */
	protected $Addresses = false;

	/**
	 * for Result printing
	 *
	 * @var mixed
	 * @access public
	 */
	public $Result;

	/**
	 * debugging flag
	 *
	 * (default value: false)
	 *
	 * @var bool
	 * @access protected
	 */
	protected $debugging = false;

	/**
	 * Database connection
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Database;





	/**
	 * __construct method
	 *
	 * @access public
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
	 *	@VLAN specific methods
	 *	--------------------------------
	 */

	/**
	 * Fetch vlans and subnets for tools vlan display. Joined query
	 *
	 * @access public
	 * @param int $domainId (default: 1)
	 * @return array|bool
	 */
	public function fetch_vlans_and_subnets ($domainId=1) {
	    # custom fields
	    $custom_fields = $this->fetch_custom_fields("vlans");
		# if set add to query
		$custom_fields_query = "";
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
		$out = array();
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
	 * @return mixed|bool
	 */
	public function validate_vlan ($number) {
		# fetch highest vlan id
		$settings = $this->get_settings();

		if(empty($number)) 							{ return true; }
		elseif(!is_numeric($number)) 				{ return _('VLAN must be numeric value!'); }
		elseif ($number > $settings['vlanMax']) 	{ return _('Vlan number can be max '.$settings['vlanMax']); }
		else 										{ return true; }
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
	 * @param string $high (default: "")
	 * @param string $low (default: "")
	 * @param array $custom_fields (default: array())
	 * @return array
	 */
	public function search_addresses($search_term, $high = "", $low = "", $custom_fields = array()) {

    	$tags = $this->fetch_all_objects ("ipTags", "id");
    	foreach ($tags as $t) {
        	if(strtolower($t->type)==strtolower($search_term)) {
            	$tags = $t->id;
            	break;
        	}
        	$tags = false;
    	}

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
		//tag
		if($tags!==false)
		$query[] = "or `state` like :tags ";				//tag
		$query[] = "order by `ip_addr` asc;";

		# join query
		$query = implode("\n", $query);

		# fetch
		try { $result = $this->Database->getObjectsQuery($query, array("low"=>$low, "high"=>$high, "search_term"=>"%$search_term%", "tags"=>$tags)); }
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
	 * @param string $high (default: "")
	 * @param string $low (default: "")
	 * @param mixed $search_req
	 * @param mixed $custom_fields (default: array())
	 * @return array
	 */
	public function search_subnets($search_term, $high = "", $low = "", $search_req, $custom_fields = array()) {
		# first search if range provided
		$result1 = $this->search_subnets_range  ($search_term, $high, $low, $custom_fields);
		# search inside subnets even if IP does not exist!
		$result2 = $this->search_subnets_inside ($high, $low);
		# search inside subnets even if IP does not exist - IPv6
		$result3 = $this->search_subnets_inside_v6 ($high, $low, $search_req);
		# merge arrays
		$result = array_merge($result1, $result2, $result3);
	    # result
	    return array_filter($result);
	}

	/**
	 * Search for subnets inside range
	 *
	 * @access private
	 * @param mixed $search_term
	 * @param string $high
	 * @param string $low
	 * @param mixed $custom_fields (default: array())
	 * @return array
	 */
	private function search_subnets_range ($search_term, $high, $low, $custom_fields = array()) {
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
		$query[] = "order by `subnet` asc, `mask` asc;";

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
	 * @param string $high
	 * @param string $low
	 * @return array
	 */
	private function search_subnets_inside ($high, $low) {
		if($low==$high) {
			# subnets class
			$Subnets = new Subnets ($this->Database);
			# fetch all subnets
			$subnets = $Subnets->fetch_all_subnets_search();
			# loop and search
			$ids = array();
			foreach($subnets as $s) {
				# cast
				$s = (array) $s;

				//first verify address type
				$type = $this->identify_address($s['subnet']);

				if($type == "IPv4") {
					# Initialize PEAR NET object
					$this->initialize_pear_net_IPv4 ();
					# parse address
					$net = $this->Net_IPv4->parseAddress($this->transform_address($s['subnet']).'/'.$s['mask'], "dotted");

					if($low>$this->transform_to_decimal(@$net->network) && $low<$this->transform_address($net->broadcast, "decimal")) {
						$ids[] = $s['id'];
					}
				}
			}
			# filter
			$ids = sizeof(@$ids)>0 ? array_filter($ids) : array();

			$result = array();

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
	 * Search inside subnets if host address is provided! ipv6
	 *
	 * @access private
	 * @param string $high
	 * @param string $low
	 * @return array
	 */
	private function search_subnets_inside_v6 ($high, $low, $search_req) {
		// same
		if($low==$high) {
			# Initialize PEAR NET object
			$this->initialize_pear_net_IPv6 ();

			// validate
			if ($this->Net_IPv6->checkIPv6($search_req)) {
				# subnets class
				$Subnets = new Subnets ($this->Database);
				# fetch all subnets
				$subnets = $Subnets->fetch_all_subnets_search("IPv6");
				# loop and search
				$ids = array();
				foreach($subnets as $s) {
					# cast
					$s = (array) $s;
					# parse address
					$net = $this->Net_IPv6->parseAddress($this->transform_address($s['subnet'], "dotted").'/'.$s['mask']);

					if(gmp_cmp($low, $this->transform_address(@$net['start'], "decimal")) == 1 && gmp_cmp($low, $this->transform_address(@$net['end'], "decimal")) == -1) {
						$ids[] = $s['id'];

					}
				}
				# filter
				$ids = sizeof(@$ids)>0 ? array_filter($ids) : array();
				# search
				if(sizeof($ids)>0) {
    				$result = array();
					foreach($ids as $id) {
						$result[] = $Subnets->fetch_subnet(null, $id);
					}
				}
				# return
				return sizeof(@$result)>0 ? array_filter($result) : array();
			}
			// empty
			else {
				return array();
			}
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
	 * @param array $custom_fields (default: array())
	 * @return array
	 */
	public function search_vlans($search_term, $custom_fields = array()) {
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
	 * Function to search vrf
	 *
	 * @access public
	 * @param mixed $search_term
	 * @param array $custom_fields (default: array())
	 * @return array
	 */
	public function search_vrfs ($search_term, $custom_fields = array()) {
		# query
		$query[] = "select * from `vrf` where `name` like :search_term or `description` like :search_term or `rd` like :search_term ";
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
	 * Search for PSTN prefixes.
	 *
	 * @access public
	 * @param mixed $search_term
	 * @param array $custom_prefix_fields (default: array())
	 * @return array
	 */
	public function search_pstn_refixes ($search_term, $custom_prefix_fields = array()) {
		# query
		$query[] = "select *,concat(prefix,start) as raw from `pstnPrefixes` where `prefix` like :search_term or `name` like :search_term or `description` like :search_term ";
		# custom
	    if(sizeof($custom_prefix_fields) > 0) {
			foreach($custom_prefix_fields as $myField) {
				$myField['name'] = $this->Database->escape($myField['name']);
				$query[] = " or `$myField[name]` like :search_term ";
			}
		}
		$query[] = "order by  raw asc;";
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
	 * Search for PSTN numbers.
	 *
	 * @access public
	 * @param mixed $search_term
	 * @param array $custom_prefix_fields (default: array())
	 * @return array
	 */
	public function search_pstn_numbers ($search_term, $custom_prefix_fields = array()) {
		# query
		$query[] = "select * from `pstnNumbers` where `number` like :search_term or `name` like :search_term or `description` like :search_term or `owner` like :search_term ";
		# custom
	    if(sizeof($custom_prefix_fields) > 0) {
			foreach($custom_prefix_fields as $myField) {
				$myField['name'] = $this->Database->escape($myField['name']);
				$query[] = " or `$myField[name]` like :search_term ";
			}
		}
		$query[] = "order by number asc;";
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
			$net = $this->Net_IPv4->parseAddress($address);

			$result['low']   = $Addresses->transform_to_decimal($net->network);
			$result['high']	 = $Addresses->transform_to_decimal($net->broadcast);
		}
		# else calculate options
		else {
			# if subnet is not provided maybe wildcard is, so explode it to array
			$address = explode(".", $address);
            # remove empty
            foreach($address as $k=>$a) {
                if (strlen($a)==0)  unset($address[$k]);
            }

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
	 * Reformat possible non-full IPv6 address for search - set lowest and highest IPs
	 *
	 *	we can have
	 *		a:a:a:a:a:a:a
	 *		a:a:a::a
	 *		a:a:a:a:a:a:a:a/mask
	 *
	 * @access public
	 * @param mixed $address
	 * @return array
	 */
	public function reformat_IPv6_for_search ($address) {
		# parse address
		$this->initialize_pear_net_IPv6 ();

		$return = array();

		# validate
		if ($this->Net_IPv6->checkIPv6($address)==false) {
			// return 0
			return array("high"=>0, "low"=>0);
		}
		else {
			# fake mask
			if (strpos($address, "/")==0)	{ $address .= "/128"; }

			# parse address
			$parsed = $this->Net_IPv6->parseAddress($address);

			# result
			$return['low']  = gmp_strval($this->transform_address($parsed['start'], "decimal"));
			$return['high'] = gmp_strval($this->transform_address($parsed['end'], "decimal"));

			# return result array low/high
			return $return;
		}
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
	 * @return array
	 */
	public function fetch_custom_fields ($table) {
    	# fetch columns
		$fields = $this->fetch_columns ($table);

		$res = array();

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
	 * @return array
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
	 * @return array
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
	 * @return array
	 */
	public function fetch_standard_fields ($table) {
		# get SCHEMA.SQL file
		$schema = fopen(dirname(__FILE__) . "/../../db/SCHEMA.sql", "r");
		$schema = fread($schema, 100000);
		$schema = str_replace("\r\n", "\n", $schema);

		# get definition
		$definition = strstr($schema, "CREATE TABLE `$table` (");
		$definition = trim(strstr($definition, ";" . "\n", true));

		# get each line to array
		$definition = explode("\n", $definition);

		# go through,if it begins with ` use it !
		$out = array();
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
	 * @return array
	 */
	private function fetch_standard_tables () {
		# get SCHEMA.SQL file
		$schema = fopen(dirname(__FILE__) . "/../../db/SCHEMA.sql", "r");
		$schema = fread($schema, 100000);

		# get definitions to array, explode with CREATE TABLE `
		$creates = explode("CREATE TABLE `", $schema);
		# fill tables array
		$tables = array();
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
	 * @return array
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
	 * @return array
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
	    $wout = array();
	    foreach($widgets as $w) {
			$wout[$w->wfile] = $w;
	    }

	    # return results
	    return $wout;
	}

	/**
	 * Verify that widget file exists
	 *
	 * @access public
	 * @return bool
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
	 * @return int|array
	 */
	public function requests_fetch ($num = true) {
		return $num ? $this->requests_fetch_num () : $this->requests_fetch_objects ();
	}

	/**
	 * Fetches number of active IP requests
	 *
	 * @access private
	 * @return int
	 */
	private function requests_fetch_num () {
    	return $this->count_database_objects ("requests", "processed", 0);
	}

	/**
	 * Fetches all requests and saves them to $requests
	 *
	 * @access private
	 * @return array
	 */
	private function requests_fetch_objects () {
    	return $this->fetch_multiple_objects ("requests", "processed", 0);
	}

	/**
	 * Fetches all subnets that are set to allow requests
	 *
	 * @access public
	 * @return array|null
	 */
	public function requests_fetch_available_subnets () {
		try { $subnets = $this->Database->getObjectsQuery("SELECT * FROM `subnets` where `allowRequests`=1 and `isFull` != 1;"); }
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
	 * @return bool
	 */
	public function ip_request_send_mail ($action="new", $values) {

		# fetch mailer settings
		$mail_settings = $this->fetch_object("settingsMail", "id", 1);

		# initialize mailer
		$this->get_settings ();
		$phpipam_mail = new phpipam_mail($this->settings, $mail_settings);
		$phpipam_mail->initialize_mailer();


		# get all users and check who to end mail to
		$recipients = $this->ip_request_get_mail_recipients ($values['subnetId']);

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
		$content[] = "<table style='margin-left:10px;margin-top:20px;width:auto;padding:0px;border-collapse:collapse;'>";
		$content[] = "<tr><td colspan='2' style='margin:0px;>$this->mail_font_style <strong>$subject</strong></font></td></tr>";
		foreach($values as $k=>$v) {
		// title search
		if (preg_match("/s_title_/", $k)) {
		$content[] = "<tr><td colspan='2' style='margin:0px;border-bottom:1px solid #eeeeee;'>$this->mail_font_style<strong>$v</strong></font></td></tr>";
		}
		else {
		//content
		$content[] = "<tr>";
		$content[] = "<td style='padding-left:15px;margin:0px;'>$this->mail_font_style $k</font></td>";
		$content[] = "<td style='padding-left:15px;margin:0px;'>$this->mail_font_style $v</font></td>";
		$content[] = "</tr>";
		}
		}
		$content[] = "<tr><td style='padding-top:15px;padding-bottom:3px;text-align:right;color:#ccc;'>$this->mail_font_style Sent at ".date('Y/m/d H:i')."</font></td></tr>";
		//set alt content
		$content_plain[] = "$subject"."\r\n------------------------------\r\n";
		foreach($values as $k=>$v) {
		$content_plain[] = $k." => ".$v;
		}
		$content_plain[] = "\r\n\r\nSent at ".date('Y/m/d H:i');
		$content[] = "</table>";

		// set content
		$content 		= $phpipam_mail->generate_message (implode("\r\n", $content));
		$content_plain 	= implode("\r\n",$content_plain);

		# try to send
		try {
			$phpipam_mail->Php_mailer->setFrom($mail_settings->mAdminMail, $mail_settings->mAdminName);
			if ($recipients!==false) {
			foreach($recipients as $r) {
			$phpipam_mail->Php_mailer->addAddress(addslashes(trim($r->email)));
			}
			$phpipam_mail->Php_mailer->AddCC(addslashes(trim($recipients_requester)));
			}
			else {
			$phpipam_mail->Php_mailer->addAddress(addslashes(trim($recipients_requester)));
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
	 * Returns list of recipients to get new
	 *
	 * @access private
	 * @param bool|mixed $subnetId
	 * @return array|bool
	 */
	private function ip_request_get_mail_recipients ($subnetId = false) {
    	// fetch all users with mailNotify
        $notification_users = $this->fetch_multiple_objects ("users", "mailNotify", "Yes", "id", true);
        // recipients array
        $recipients = array();
        // any ?
        if ($notification_users!==false) {
         	// if subnetId is set check who has permissions
        	if (isset($subnetId)) {
             	foreach ($notification_users as $u) {
                	// inti object
                	$Subnets = new Subnets ($this->Database);
                	//check permissions
                	$subnet_permission = $Subnets->check_permission($u, $subnetId);
                	// if 3 than add
                	if ($subnet_permission==3) {
                    	$recipients[] = $u;
                	}
            	}
        	}
        	else {
            	foreach ($notification_users as $u) {
                	if($u->role=="Administrator") {
                    	$recipients[] = $u;
                	}
            	}
        	}
        	return sizeof($recipients)>0 ? $recipients : false;
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
	 * @return array
	 */
	private function ip_request_reformat_mail_values ($values) {
		// no array
		if (!is_array($values)) { return $values; }

		// addresses
		$this->Addresses = new Addresses ($this->Database);

		$mail = array();

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
	 * @return array
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
	 * @return false|string
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
	 * @return string|false
	 */
	public function get_field_fix ($table, $field) {
		$res = fopen(dirname(__FILE__) . "/../../db/SCHEMA.sql", "r");
		$file = fread($res, 100000);
		$file = str_replace("\r\n", "\n", $file);

		//go from delimiter on
		$file = strstr($file, "DROP TABLE IF EXISTS `$table`;");
		$file = trim(strstr($file, "# Dump of table", true));

		//get proper line
		$file = explode("\n", $file);
		foreach($file as $k=>$l) {
			if(strpos(trim($l), "$field`")==1) {
				$res = trim($l, ",");
				$res .= ";";

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
	 * @return bool
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
	 * @return bool
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
	 * Check for latest version
	 *
	 * @access public
	 * @return mixed|bool
	 */
	public function check_latest_phpipam_version () {
		# fetch settings
		$this->get_settings ();
		# check for release
		return $this->settings->version >= "1.2" ? $this->check_latest_phpipam_version_github () : $this->check_latest_phpipam_version_phpipamnet ();
	}

	/**
	 * Checks for latest phpipam version from phpipam webpage
	 *
	 * @access public
	 * @return string|false
	 */
	public function check_latest_phpipam_version_phpipamnet () {
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
	 * Fetch latest version form Github for phpipam > 1.2
	 *
	 * @access public
	 * @return mixed|bool
	 */
	public function check_latest_phpipam_version_github () {
		# set releases href
		$feed = implode(file('https://github.com/phpipam/phpipam/releases.atom'));
		// fetch
		$xml = simplexml_load_string($feed);

		// if ok
		if ($xml!==false) {
			// encode to json
			$json = json_decode(json_encode($xml));
			// save all releases
			$this->phpipam_releases = $json->entry;
			// check for latest release
			foreach ($json->entry as $e) {
				// releases will be named with numberic values
				if (is_numeric(str_replace("Version", "", $e->title))) {
					// save
					$this->phpipam_latest_release = $e;
					// return
					return str_replace("Version", "", $e->title);
				}
			}
			// none
			return false;
		}
		else {
			return false;
		}
	}

	/**
	 * Updates DB version check flag in database
	 *
	 * @access public
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
	 * @return mixed
	 */
	public function calculate_ip_calc_results ($cidr) {
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
        $out = array();
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
	 * @return string|false
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
	    $classes = array();
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
	 * @return array
	 */
	private function calculate_IPv6_calc_results ($cidr) {
		# initialize subnets Class
		$Subnets = new Subnets($this->Database);
		# Initialize PEAR NET object
		$this->initialize_pear_net_IPv6 ();

        # set ip address type
        $out = array();
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
	 * @return string
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
	 * @return string|false
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
	 * @return string[]
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
	 *	@nat methods
	 *	------------------------------
	 */

    /**
     * Translates NAT objects to be shown on page
     *
     * @access public
     * @param json $json_objects
     * @param int|bool $nat_id (default: false)
     * @param bool $json_objects (default: false)
     * @param bool $object_type (default: false) - to bold it (ipaddresses / subnets)
     * @param int|bool object_id (default: false) - to bold it
     * @return array|bool
     */
    public function translate_nat_objects_for_display ($json_objects, $nat_id = false, $admin = false, $object_type = false, $object_id=false) {
        // to array "subnets"=>array(1,2,3)
        $objects = json_decode($json_objects, true);
        // init out array
        $out = array();
        // set ping statuses for warning and offline
        $this->get_settings();
        $statuses = explode(";", $this->settings->pingStatus);
        // check
        if(is_array($objects)) {
            if(sizeof($objects)>0) {
                foreach ($objects as $ot=>$ids) {
                    if (sizeof($ids)>0) {
                        foreach ($ids as $id) {
                            // fetch
                            $item = $this->fetch_object($ot, "id", $id);
                            if($item!==false) {
                                // bold
                                $bold = $item->id==$object_id && $ot==$object_type ? "<span class='strong'>" : "<span>";
                                // remove
                                $remove = $admin&&$nat_id ? "<span class='remove-nat-item-wrapper_".$ot."_".$item->id."'><a class='btn btn-xs btn-danger removeNatItem' data-id='$nat_id' data-type='$ot' data-item-id='$item->id' rel='tooltip' title='"._('Remove')."'><i class='fa fa-times'></i></a>" : "<span>";
                                // subnets
                                if ($ot=="subnets") {
                                    $out[] = "$remove $bold<a href='".create_link("subnets", $item->sectionId, $item->id)."'>".$this->transform_address($item->subnet, "dotted")."/".$item->mask."</a></span></span>";
                                }
                                // addresses
                                else {
                                    // subnet
                                    $snet = $this->fetch_object("subnets", "id", $item->subnetId);
                                    // append status
                                    if ($snet->pingSubnet=="1") {
                                        //calculate
                                        $tDiff = time() - strtotime($item->lastSeen);
                                        if($item->excludePing=="1" )    { $hStatus = "padded"; $hTooltip = ""; }
                                        elseif(is_null($item->lastSeen)) { $hStatus = "neutral"; $hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Address was never online")."'"; }
                                        elseif($tDiff < $statuses[0])	{ $hStatus = "success";	$hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Address is alive")."<hr>"._("Last seen").": ".$item->lastSeen."'"; }
                                        elseif($tDiff < $statuses[1])	{ $hStatus = "warning"; $hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Address warning")."<hr>"._("Last seen").": ".$item->lastSeen."'"; }
                                        elseif($tDiff > $statuses[1])	{ $hStatus = "error"; 	$hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Address is offline")."<hr>"._("Last seen").": ".$item->lastSeen."'";}
                                        elseif($item->lastSeen == "0000-00-00 00:00:00") { $hStatus = "neutral"; 	$hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Address is offline")."<hr>"._("Last seen").": "._("Never")."'";}
                                        elseif($item->lastSeen == "1970-01-01 00:00:01") { $hStatus = "neutral"; 	$hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Address is offline")."<hr>"._("Last seen").": "._("Never")."'";}
                                        else							{ $hStatus = "neutral"; $hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Address status unknown")."'";}
                                    }
                                    else {
                                        $hStatus = "hidden";
                                        $hTooltip = "";
                                    }
                                    if($remove=="<span>") {
                                        $remove .= "<span class='status status-$hStatus' $hTooltip></span>";
                                    }

                                    $out[] = "$remove $bold <a href='".create_link("subnets", $snet->sectionId, $item->subnetId, "address-details", $item->id)."'>".$this->transform_address($item->ip_addr, "dotted")."</a></span>";
                                }
                            }
                        }
                    }
                }
            }
        }
        // result
        return sizeof($out)>0 ? $out : false;
    }

    /**
     * This function will reindex all nat object to following structure:
     *
     *  ipaddresses => array (
     *                  [address_id] => array (nat_id1, nat_id2)
     *              )
     *  subnets => array (
     *                  [subnet_id] => array (nat_id1, nat_id2)
     *              )
     *
     * @access public
     * @param array $all_nats (default: array())
     * @return array
     */
    public function reindex_nat_objects ($all_nats = array()) {
        // out array
        $out = array(
            "ipaddresses"=>array(),
            "subnets"=>array()
        );
        // loop
        if(is_array($all_nats)) {
            if (sizeof($all_nats)>0) {
                foreach ($all_nats as $n) {
                    $src = json_decode($n->src, true);
                    $dst = json_decode($n->dst, true);

                    // src
                    if(is_array($src)) {
                        if(is_array(@$src['subnets'])) {
                            foreach ($src['subnets'] as $s) {
                                $out['subnets'][$s][] = $n->id;
                            }
                        }
                        if(is_array(@$src['ipaddresses'])) {
                            foreach ($src['ipaddresses'] as $s) {
                                $out['ipaddresses'][$s][] = $n->id;
                            }
                        }
                    }
                    // dst
                    if(is_array($dst)) {
                        if(is_array(@$dst['subnets'])) {
                            foreach ($dst['subnets'] as $s) {
                                $out['subnets'][$s][] = $n->id;
                            }
                        }
                        if(is_array(@$dst['ipaddresses'])) {
                            foreach ($dst['ipaddresses'] as $s) {
                                $out['ipaddresses'][$s][] = $n->id;
                            }
                        }
                    }
                }
            }
        }
        // return
        return $out;
    }

    /**
     * Prints single NAT for display in devices, subnets, addresses.
     *
     * @access public
     * @param mixed $n
     * @param bool $is_admin (default: false)
     * @param bool|int $nat_id (default: false)
     * @param bool $admin (default: false) > shows remove links
     * @param bool|mixed $object_type (default: false)
     * @param bool $object_id (default: false)
     * @return string
     */
    public function print_nat_table ($n, $is_admin = false, $nat_id = false, $admin = false, $object_type = false, $object_id=false) {
        // cast to object to be sure if array provided
        $n = (object) $n;

        // translate json to array, links etc
        $sources      = $this->translate_nat_objects_for_display ($n->src, $nat_id, $admin, $object_type, $object_id);
        $destinations = $this->translate_nat_objects_for_display ($n->dst, $nat_id, $admin, $object_type, $object_id);

        // no src/dst
        if ($sources===false)
            $sources = array("<span class='badge badge1 badge5 alert-danger'>"._("None")."</span>");
        if ($destinations===false)
            $destinations = array("<span class='badge badge1 badge5 alert-danger'>"._("None")."</span>");

        // description
        $n->description = str_replace("\n", "<br>", $n->description);
        $n->description = strlen($n->description)>0 ? "($n->description)" : "";

        // device
        if (strlen($n->device)) {
            if($n->device !== 0) {
                $device = $this->fetch_object ("devices", "id", $n->device);
                $description = strlen($device->description)>0 ? " ($device->description)" : "";
                $n->device = $device===false ? "/" : "<a href='".create_link("tools", "devices", $device->id)."'>$device->hostname</a> ($device->ip_addr) <span class='text-muted'>$description</span>";
            }
        }
        else {
            $n->device = "/";
        }

        // icon
        $icon =  $n->type=="static" ? "fa-arrows-h" : "fa-long-arrow-right";

        // to html
        $html = array();
        $html[] = "<tr>";
        $html[] = "<td colspan='4'>";
        $html[] = "<span class='badge badge1 badge5'>".ucwords($n->type)."</span> <strong>$n->name</strong> <span class='text-muted'>$n->description</span>";
        $html[] = "	<div class='btn-group pull-right'>";
        $html[] = "		<a href='' class='btn btn-xs btn-default editNat' data-action='edit'   data-id='$n->id'><i class='fa fa-pencil'></i></a>";
        $html[] = "		<a href='' class='btn btn-xs btn-default editNat' data-action='delete' data-id='$n->id'><i class='fa fa-times'></i></a>";
        $html[] = "	</div>";
        $html[] = "</td>";
        $html[] = "</tr>";

        // append ports
        if(($n->type=="static" || $n->type=="destination") && (strlen($n->src_port)>0 && strlen($n->dst_port)>0)) {
            $sources      = implode("<br>", $sources)." /".$n->src_port;
            $destinations = implode("<br>", $destinations)." /".$n->dst_port;
        }
        else {
            $sources      = implode("<br>", $sources);
            $destinations = implode("<br>", $destinations);
        }

        $html[] = "<tr>";
        $html[] = "<td style='width:80px;'></td>";
        $html[] = "<td>$sources</td>";
        $html[] = "<td><i class='fa $icon'></i></td>";
        $html[] = "<td>$destinations</td>";
        $html[] = "</tr>";

        $html[] = "<tr>";
        $html[] = "<td></td>";
        $html[] = "<td colspan='3'><span class='text-muted'>";
        $html[] = _('Device').": $n->device";
        $html[] = "</span></td>";
        $html[] = "</tr>";

        // actions
        if($is_admin) {
        $html[] = "<tr>";
        $html[] = "<td colspan='4'><hr></td>";
        $html[] = "</tr>";
        }
        // return
        return implode("\n", $html);
    }













	/**
	 *	@pstn methods
	 *	------------------------------
	 *
	 *  !pstn
	 */

    /**
     * Returns all prefixes in correct order
     *
     * @access public
     * @return void
     * @param bool|int $master (default: false)
     * @param bool $recursive (default: false)
     * @return array|bool
     */
    public function fetch_all_prefixes ($master = false, $recursive = false) {
	    if($master && !$recursive) {
    	    $query = 'select *,concat(prefix,start) as raw from pstnPrefixes where master = ? order by raw asc;';
    	    $params = array($master);
	    }
	    else {
    	    $query = 'select *,concat(prefix,start) as raw from pstnPrefixes order by raw asc;';
    	    $params = array();
        }
		# fetch
		try { $prefixes = $this->Database->getObjectsQuery($query, $params); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
        // for master + recursive we need to go from master id to next 0 (root
        if($master && $recursive && $prefixes) {
            $master_set = false;
            $out = array();

            foreach ($prefixes as $k=>$p) {
        	    if($p->id == $master) {
            	    $out[] = $p;
            	    $master_set = true;
        	    }
        	    elseif ($master_set && $p->master!=0) {
            	    $out[] = $p;
        	    }
        	    elseif ($master_set && $p->master!=0) {
            	    break;
        	    }
            }
            $prefixes = $out;
        }
		# result
		return sizeof($prefixes)>0 ? array_values($prefixes) : false;
    }

    /**
     * Normalize prefix / number.
     *
     * @access public
     * @param mixed $number
     * @return mixed
     */
    public function prefix_normalize ($number) {
        return str_replace(array("+", " ", "-"), "", $number);
    }

	/**
	 * fetch whole tree path for prefix - from slave to parents
	 *
	 * @access public
	 * @param mixed $id
	 * @return array
	 */
	public function fetch_prefix_parents_recursive ($id) {
		$parents = array();
		$root = false;

		while($root === false) {
			$subd = $this->fetch_object("pstnPrefixes", "id", $id);		# get subnet details

			if($subd!==false) {
    			$subd = (array) $subd;
				# not root yet
				if(@$subd['master']!=0) {
					array_unshift($parents, $subd['master']);
					$id  = $subd['master'];
				}
				# root
				else {
					array_unshift($parents, $subd['master']);
					$root = true;
				}
			}
			else {
				$root = true;
			}
		}
		# remove 0
		unset($parents[0]);
		# return array
		return $parents;
	}

	/**
	 * Checks for duplicate number.
	 *
	 * @access public
	 * @param bool $prefix (default: false)
	 * @param bool $number (default: false)
	 * @return null|boolean
	 */
	public function check_number_duplicates ($prefix = false, $number = false) {
    	if($prefix===false && $number===false) {
        	$this->Result->show("danger", "Duplicate chck failed", true);
    	}
    	else {
        	$query = "select count(*) as cnt from pstnNumbers where prefix = ? and number = ?;";
    		# fetch
    		try { $cnt = $this->Database->getObjectQuery($query, array($prefix, $number)); }
    		catch (Exception $e) {
    			$this->Result->show("danger", _("Error: ").$e->getMessage());
    			return false;
    		}
    		# result
    		return $cnt->cnt>0 ? true : false;
    	}
	}

	/**
	 * Checks permission for specified prefix
	 *
	 *	we provide user details and subnetId
	 *
	 * @access public
	 * @param object $user
	 * @return int
	 */
	public function check_prefix_permission ($user) {
        return $user->role=="Administrator" ? 3 : $user->pstn;
	}

	/**
	 * Prints structured menu of prefixes
	 *
	 * @access public
	 * @param mixed $user
	 * @param mixed $prefixes
	 * @param mixed $custom_fields
	 * @return mixed
	 */
	public function print_menu_prefixes ( $user, $prefixes, $custom_fields ) {

		# set hidden fields
		$this->get_settings ();
		$hidden_fields = json_decode($this->settings->hiddenCustomFields, true);
		$hidden_fields = is_array($hidden_fields['subnets']) ? $hidden_fields['subnets'] : array();

		# set html array
		$html = array();
		# root is 0
		$rootId = 0;

		# remove all not permitted!
		if(sizeof($prefixes)>0) {
		foreach($prefixes as $k=>$s) {
			$permission = $this->check_prefix_permission ($user);
			if($permission == 0) { unset($prefixes[$k]); }
		}
		}

		# create loop array
		if(sizeof($prefixes) > 0) {
        $children_prefixes = array();
		foreach ( $prefixes as $item ) {
			$item = (array) $item;
			$children_prefixes[$item['master']][] = $item;
		}
		}
		else {
			return false;
		}

		# loop will be false if the root has no children (i.e., an empty menu!)
		$loop = !empty( $children_prefixes[$rootId] );

		# initializing $parent as the root
		$parent = $rootId;
		$parent_stack = array();

		# old count
		$old_count = 0;

		# return table content (tr and td's)
		while ( $loop && ( ( $option = each( $children_prefixes[$parent] ) ) || ( $parent > $rootId ) ) )
		{
			if(count($parent_stack) == 0) {
				$margin = "0px";
				$padding = "0px";
			}
			else {
				# padding
				$padding = "10px";

				# margin
				$margin  = (count($parent_stack) * 10) -10;
				$margin  = $margin *1.5;
				$margin  = $margin."px";
			}

			# count levels
			$count = count( $parent_stack ) + 1;

			# description
			$name = strlen($option['value']['name'])==0 ? "/" : $option['value']['name'];

			# print table line
			if(strlen($option['value']['prefix']) > 0) {
    			# count change?
    			if ($count != $old_count) { $html[] = "</tbody><tbody>"; }

				$html[] = "<tr class='level$count'>";

				//which level?
				if($count==1) {
					# last?
					if(!empty( $children_prefixes[$option['value']['id']])) {
						$html[] = "	<td class='level$count'><span class='structure-last' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open-o'></i><a href='".create_link($_GET['page'],"pstn-prefixes",$option['value']['id'])."'>".$option['value']['prefix']." </a></td>";
						$html[] = "	<td class='level$count'><span class='structure-last' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open-o'></i> <strong>$name</strong></td>";
					} else {
						$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-angle-right'></i><a href='".create_link($_GET['page'],"pstn-prefixes",$option['value']['id'])."'>".$option['value']['prefix']." </a></td>";
						$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-angle-right'></i> <strong>$name</strong></td>";
					}
				}
				else {
					# last?
					if(!empty( $children_prefixes[$option['value']['id']])) {
						$html[] = "	<td class='level$count'><span class='structure-last' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open-o'></i> <a href='".create_link($_GET['page'],"pstn-prefixes",$option['value']['id'])."'>  ".$option['value']['prefix']."</a></td>";
						$html[] = "	<td class='level$count'><span class='structure-last' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open-o'></i> <strong>$name</strong></td>";
					}
					else {
						$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-angle-right'></i> <a href='".create_link($_GET['page'],"pstn-prefixes",$option['value']['id'])."'>  ".$option['value']['prefix']."</a></td>";
						$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-angle-right'></i> <strong>$name</strong></td>";
					}
				}

				// range
				$html[] = " <td class='level$count'><span class='structure-last' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-angle-right'></i> ".$option['value']['prefix'].$option['value']['start']." ".$option['value']['prefix'].$option['value']['stop']."</td>";

				//start/stop
				$html[] = "	<td>".$option['value']['start']."</td>";
				$html[] = "	<td>".$option['value']['stop']."</td>";

				//count
                $cnt = $this->count_database_objects("pstnNumbers", "prefix", $option['value']['id']);

                $html[] = "	<td><span class='badge badge1 badge5'>".$cnt."</span></td>";

				//device
				$device = ( $option['value']['deviceId']==0 || empty($option['value']['deviceId']) ) ? false : true;

				if($device===false) { $html[] ='	<td>/</td>' . "\n"; }
				else {
					$device = $this->fetch_object ("devices", "id", $option['value']['deviceId']);
					if ($device!==false) {
						$html[] = "	<td><a href='".create_link("tools","devices",$device->id)."'>".$device->hostname .'</a></td>' . "\n";
					}
					else {
						$html[] ='	<td>/</td>' . "\n";
					}
				}

				//custom
				if(sizeof($custom_fields) > 0) {
			   		foreach($custom_fields as $field) {
				   		# hidden?
				   		if(!in_array($field['name'], $hidden_fields)) {

				   			$html[] =  "<td class='hidden-xs hidden-sm hidden-md'>";

				   			//booleans
							if($field['type']=="tinyint(1)")	{
								if($option['value'][$field['name']] == "0")			{ $html[] = _("No"); }
								elseif($option['value'][$field['name']] == "1")		{ $html[] = _("Yes"); }
							}
							//text
							elseif($field['type']=="text") {
								if(strlen($option['value'][$field['name']])>0)		{ $html[] = "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", $option['value'][$field['name']])."'>"; }
								else												{ $html[] = ""; }
							}
							else {
								$html[] = $option['value'][$field['name']];

							}

				   			$html[] =  "</td>";
			   			}
			    	}
			    }

				# set permission
				$permission = $this->check_prefix_permission ($user);

				$html[] = "	<td class='actions' style='padding:0px;'>";
				$html[] = "	<div class='btn-group'>";

				if($permission>2) {
					$html[] = "		<button class='btn btn-xs btn-default editPSTN' data-action='edit'   data-id='".$option['value']['id']."'><i class='fa fa-pencil'></i></button>";
					$html[] = "		<button class='btn btn-xs btn-default editPSTN' data-action='delete' data-id='".$option['value']['id']."'><i class='fa fa-times'></i></button>";
				}
				else {
					$html[] = "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-pencil'></i></button>";
					$html[] = "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-times'></i></button>";
				}
				$html[] = "	</div>";
				$html[] = "	</td>";

				$html[] = "</tr>";

                # save old level count
                $old_count = $count;
			}

			if ( $option === false ) { $parent = array_pop( $parent_stack ); }
			# Has slave subnets
			elseif ( !empty( $children_prefixes[$option['value']['id']] ) ) {
				array_push( $parent_stack, $option['value']['master'] );
				$parent = $option['value']['id'];
			}
		}
		# print
		return $html;
	}




	/**
	 * Prints dropdown menu for master prefix selection in prefix editing
	 *
	 * @access public
	 * @param bool $prefixId (default: false)
	 * @return mixed
	 */
	public function print_masterprefix_dropdown_menu ($prefixId = false) {

		# initialize vars
		$children_prefixes = array();
		$parent_stack_prefixes = array();
		$html = array();
		$rootId = 0;			// root is 0
		$parent = $rootId;      // initializing $parent as the root

		# fetch all prefixes in section
		$all_prefixes = $this->fetch_all_prefixes ();
		# folder or subnet?
		foreach($all_prefixes as $s) {
			$children_prefixes[$s->master][] = (array) $s;
		}

		# loop will be false if the root has no children (i.e., an empty menu!)
		$loop  = !empty( $children_prefixes[$rootId] );

		# structure
		$html[] = "<select name='master' class='form-control input-sm input-w-auto input-max-200'>";

		# root subnet
		$html[] = "<option value='0'>"._("Root subnet")."</option>";

		# return table content (tr and td's) - subnets
		if(sizeof($children_prefixes)>0) {
		while ( $loop && ( ( $option = each( $children_prefixes[$parent] ) ) || ( $parent > $rootId ) ) )
		{
			# repeat
			$repeat  = str_repeat( " &nbsp;&nbsp; ", ( count($parent_stack_prefixes)) );

			# selected
			$selected = $option['value']['id'] == $prefixId ? "selected='selected'" : "";
			if($option['value']['id'])
            $html[] = "<option value='".$option['value']['id']."' $selected>$repeat ".$option['value']['prefix']." (".$option['value']['name'].")</option>";

			if ( $option === false ) { $parent = array_pop( $parent_stack_prefixes ); }
			# Has slave subnets
			elseif ( !empty( $children_prefixes[$option['value']['id']] ) ) {
				array_push( $parent_stack_prefixes, $option['value']['master'] );
				$parent = $option['value']['id'];
			}		}
		}
		$html[] = "</select>";
		# join and print
		print implode( "\n", $html );
	}




	/**
	 * This function compresses all pstn
	 *
	 *	input is array of pstn ranges
	 *	output compresses pstn range
	 *
	 * @access public
	 * @param array $numbers
	 * @return array
	 */
	public function compress_pstn_ranges ($numbers, $state=4) {
    	# set size
    	$size = sizeof($numbers);
    	// vars
    	$numbers_formatted = array();
    	$fIndex = int;

		# loop through IP addresses
		for($c=0; $c<$size; $c++) {
			# ignore already comressed range
			if($numbers[$c]->class!="compressed-range") {
				# gap between this and previous
				if(gmp_strval( @gmp_sub($numbers[$c]->number, $numbers[$c-1]->number)) != 1) {
					# remove index flag
					unset($fIndex);
					# save IP address
					$numbers_formatted[$c] = $numbers[$c];
					$numbers_formatted[$c]->class = "ip";

					# no gap this -> next
					if(gmp_strval( @gmp_sub($numbers[$c]->number, $numbers[$c+1]->number)) == -1 && $numbers[$c]->state==$state) {
						//is state the same?
						if($numbers[$c]->state==$numbers[$c+1]->state) {
							$fIndex = $c;
							$numbers_formatted[$fIndex]->startIP = $numbers[$c]->number;
							$numbers_formatted[$c]->class = "compressed-range";
						}
					}
				}
				# no gap between this and previous
				else {
					# is state same as previous?
					if($numbers[$c]->state==$numbers[$c-1]->state && $numbers[$c]->state==$state) {
						$numbers_formatted[$fIndex]->stopIP = $numbers[$c]->number;	//adds dhcp state
						$numbers_formatted[$fIndex]->numHosts = gmp_strval( gmp_add(@gmp_sub($numbers[$c]->number, $numbers_formatted[$fIndex]->number),1));	//add number of hosts
					}
					# different state
					else {
						# remove index flag
						unset($fIndex);
						# save IP address
						$numbers_formatted[$c] = $numbers[$c];
						$numbers_formatted[$c]->class = "ip";
						# check if state is same as next to start range
						if($numbers[$c]->state==@$numbers[$c+1]->state &&  gmp_strval( @gmp_sub($numbers[$c]->number, $numbers[$c+1]->number)) == -1 && $numbers[$c]->state==$state) {
							$fIndex = $c;
							$numbers_formatted[$fIndex]->startIP = $numbers[$c]->number;
							$numbers_formatted[$c]->class = "compressed-range";
						}
					}
				}
			}
			else {
				# save already compressed
				$numbers_formatted[$c] = $numbers[$c];
			}
		}
		# overrwrite ipaddresses and rekey
		$addresses = @array_values($numbers_formatted);
		# return
		return $addresses;
	}

	/**
	 * Calculates pstn usage - dhcp, active, ...
	 *
	 * @access public
	 * @param obj $prefix        //subnet in decimal format
	 * @param obj $numbers	     //netmask in decimal format
	 * @return array
	 */
	public function calculate_prefix_usege ($prefix, $numbers) {
	    # calculate max number of hosts
	    $details = array();
	    $details['maxhosts'] = ($prefix->stop - $prefix->start + 1);

		# get IP address count per address type
		if($numbers!==false) {
		    $details_p = $this->calculate_prefix_usage_sort_numbers ($numbers);
    	    foreach($this->address_types as $t) {
    		    $details[$t['type']."_percent"] = round( ( ($details_p[$t['type']] * 100) / $details['maxhosts']), 2 );
    	    }

            # calculate free hosts
            $details['freehosts'] =  $details['maxhosts'] - sizeof($numbers);
        }
        else {
            $details['freehosts'] =  $details['maxhosts'];
        }
	    # calculate use percentage for each type
	    $details['freehosts_percent'] = round( ( ($details['freehosts'] * 100) / $details['maxhosts']), 2 );

	    # result
	    return $details;
	}

	/**
	 * Calculates number usage per host type
	 *
	 * @access public
	 * @param mixed $numbers
	 * @return array
	 */
	public function calculate_prefix_usage_sort_numbers ($numbers) {
		$count = array();
		$count['used'] = 0;				//initial sum count
		# fetch address types
		$this->get_addresses_types();
		# create array of keys with initial value of 0
		foreach($this->address_types as $a) {
			$count[$a['type']] = 0;
		}
		# count
		if($numbers!==false) {
			foreach($numbers as $n) {
				$count[$this->translate_address_type($n->state)]++;
				$count['used'] = gmp_strval(gmp_add($count['used'], 1));
			}
		}
		# result
		return $count;
	}

	/**
	 * Returns array of address types
	 *
	 * @access public
	 */
	public function get_addresses_types () {
		# from cache
		if($this->address_types == null) {
        	# fetch
        	$types = $this->fetch_all_objects ("ipTags", "id");

            # save to array
			$types_out = array();
			foreach($types as $t) {
				$types_out[$t->id] = (array) $t;
			}
			# save to cache
			$this->address_types = $types_out;
		}
	}

	/**
	 * Translates address type from index (int) to type
	 *
	 *	e.g.: 0 > offline
	 *
	 * @access public
	 * @param mixed $index
	 * @return array
	 */
	public function translate_address_type ($index) {
		# fetch
		$this->get_addresses_types();
		# return
		return $this->address_types[$index]["type"];
	}











	/**
	 *	@location methods
	 *	------------------------------
	 *
	 *  !location
	 */

    /**
     * Fetches all location objects.
     *
     * @access public
     * @param bool|int $id (default: false)
     * @param bool count (default: false)
     * @return array|bool
     */
    public function fetch_location_objects ($id = false, $count = false) {
        // check
        if(is_numeric($id)) {
            // count ?
            $select = $count ? "count(*) as cnt " : "*";
            // query
            $query = "select $select from
                        (
                        SELECT d.id, d.hostname as name, '' as mask, 'devices' as type, '' as sectionId, d.location, d.description
                        FROM devices d
                        JOIN locations l
                        ON d.location = l.id
                        UNION ALL
                        SELECT r.id, r.name, '' as mask, 'racks' as type, '' as sectionId, r.location, r.description
                        FROM racks r
                        JOIN locations l
                        ON r.location = l.id
                        UNION ALL
                        SELECT s.id, s.subnet as name, s.mask, 'subnets' as type, s.sectionId, s.location, s.description
                        FROM subnets s
                        JOIN locations l
                        ON s.location = l.id
                        UNION ALL
                        SELECT a.id, a.ip_addr as name, 'mask', 'addresses' as type, a.subnetId as sectionId, a.location, a.dns_name as description
                        FROM ipaddresses a
                        JOIN locations l
                        ON a.location = l.id
                        )
                        as linked where location = ?;";

     		// fetch
    		try { $objects = $this->Database->getObjectsQuery($query, array($id)); }
    		catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), true); }

    		// return
    		return sizeof($objects)>0 ? $objects : false;
        }
        else {
            return false;
        }
    }











	/**
	 *	@misc methods
	 *	------------------------------
	 */

	/**
	 * Fetch all l2 domans and vlans
	 *
	 * @access public
	 * @param string $search (default: false)
	 * @return array|bool
	 */
	public function fetch_all_domains_and_vlans ($search = false) {
		// set query
		$query[] = "select `d`.`name` as `domainName`,";
		$query[] = "	`d`.`description` as `domainDescription`,";
		$query[] = "	`v`.`domainId` as `domainId`,";
		$query[] = "	`v`.`name` as `name`,";
		$query[] = "	`d`.`name` as `domainName`,";
		$query[] = "	`v`.`number` as `number`,";
		$query[] = "	`v`.`description` as `description`,";
		// fetch custom fields
		$custom_vlan_fields = $this->fetch_custom_fields ("vlans");
		if ($custom_vlan_fields != false) {
    		foreach ($custom_vlan_fields as $f) {
        		$query[] = "  `v`.`$f[name]` as `$f[name]`,";
    		}

		}
		$query[] = "	`v`.`vlanId` as `id`";
		$query[] = "	from";
		$query[] = "	`vlans` as `v`,";
		$query[] = "	`vlanDomains` as `d`";
		$query[] = "	where `v`.`domainId` = `d`.`id`";
		$query[] = "	order by `v`.`number` asc;";

		// fetch
		try { $domains = $this->Database->getObjectsQuery(implode("\n",$query)); }
		catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), true); }
		// filter if requested
		if ($search !== false && sizeof($domains)>0) {
			foreach ($domains as $k=>$d) {
				if (strpos($d->number, $search)===false && strpos($d->name, $search)===false && strpos($d->description, $search)===false) {
					unset($domains[$k]);
				}
			}
		}
		// return
		return sizeof($domains)>0 ? $domains : false;
	}

	/**
	 * Parses import file
	 *
	 * @access public
	 * @param string $filetype (default: "xls")
	 * @param object $subnet
	 * @param array $custom_address_fields
	 * @return array
	 */
	public function parse_import_file ($filetype = "xls", $subnet = object, $custom_address_fields) {
    	# start object and get settings
    	$this->settings ();
    	$this->Subnets = new Subnets ($this->Database);

        # CSV
        if (strtolower($filetype) == "csv")     { $outFile = $this->parse_import_file_csv (); }
        # XLS
        elseif(strtolower($filetype) == "xls")  { $outFile = $this->parse_import_file_xls ($subnet, $custom_address_fields); }
        # error
        else                                    { $this->Result->show("danger", _('Invalid filetype'), true); }

        # validate
        return $this->parse_validate_file ($outFile, $subnet);
	}

	/**
	 * Parses xls import file
	 *
	 * @access private
	 * @param object $subnet
	 * @param array $custom_address_fields
	 * @return mixed
	 */
	private function parse_import_file_xls ($subnet, $custom_address_fields) {
     	# get excel object
    	require_once(dirname(__FILE__).'/../../functions/php-excel-reader/excel_reader2.php');				//excel reader 2.21
    	$data = new Spreadsheet_Excel_Reader(dirname(__FILE__) . '/../../app/subnets/import-subnet/upload/import.xls', false);

    	//get number of rows
    	$numRows = $data->rowcount(0);
    	$numRows++;

    	$outFile = array();

    	//get all to array!
    	for($m=0; $m < $numRows; $m++) {

    		//IP must be present!
    		if(filter_var($data->val($m,'A'), FILTER_VALIDATE_IP)) {
        		//for multicast
        		if ($this->settings-enableMulticast=="1") {
            		if (strlen($data->val($m,'F'))==0 && $this->Subnets->is_multicast($data->val($m,'A')))    {
                		$mac = $this->Subnets->create_multicast_mac ($data->val($m,'A'));
                    }
                    else {
                        $mac = $data->val($m,'F');
                    }
                }

    			$outFile[$m]  = $data->val($m,'A').','.$data->val($m,'B').','.$data->val($m,'C').','.$data->val($m,'D').',';
    			$outFile[$m] .= $data->val($m,'E').','.$mac.','.$data->val($m,'G').','.$data->val($m,'H').',';
    			$outFile[$m] .= $data->val($m,'I').','.$data->val($m,'J');
    			//add custom fields
    			if(sizeof($custom_address_fields) > 0) {
    				$currLett = "K";
    				foreach($custom_address_fields as $field) {
    					$outFile[$m] .= ",".$data->val($m,$currLett++);
    				}
    			}
    		}
    	}
    	// return
    	return $outFile;
	}

	/**
	 * Parses CSV import file
	 *
	 * @access private
	 * @return array
	 */
	private function parse_import_file_csv () {
    	/* get file to string */
    	$outFile = file_get_contents(dirname(__FILE__) . '/../../app/subnets/import-subnet/upload/import.csv') or die ($this->Result->show("danger", _('Cannot open upload/import.csv'), true));

    	/* format file */
    	$outFile = str_replace( array("\r\n","\r") , "\n" , $outFile);	//replace windows and Mac line break
    	$outFile = explode("\n", $outFile);

    	/* validate IP */
    	foreach($outFile as $k=>$v) {
        	//put it to array
        	$field = explode(",", $v);

        	if(!filter_var($field[0], FILTER_VALIDATE_IP)) {
            	unset($outFile[$k]);
        	}
        	else {
            	# mac
        		if ($this->settings-enableMulticast=="1") {
            		if (strlen($field[5])==0 && $this->Subnets->is_multicast($field[0]))  {
                		$field[5] = $this->Subnets->create_multicast_mac ($field[0]);
                    }
        		}
        	}

        	# save
        	$outFile[$k] = implode(",", $field);
    	}

    	# return
    	return $outFile;
	}

	/**
	 * Validates each import line from provided array
	 *
	 *      append class to array
	 *
	 * @access private
	 * @param mixed $outFile
	 * @param object $subnet
	 * @return void
	 */
	private function parse_validate_file ($outFile = array(), $subnet = object) {
    	$result = array();
    	# present ?
    	if (sizeof($outFile)>0) {
            foreach($outFile as $k=>$line) {
            	//put it to array
            	$field = explode(",", $line);

            	//verify IP address
            	if(!filter_var($field[0], FILTER_VALIDATE_IP)) 	{ $class = "danger";	$errors++; }
            	else											{ $class = ""; }

            	// verify that address is in subnet for subnets
            	if($subnet->isFolder!="1") {
            	    if ($this->Subnets->is_subnet_inside_subnet ($field[0]."/32", $this->transform_address($subnet->subnet, "dotted")."/".$subnet->mask)==false)    { $class = "danger"; $errors++; }
                }
            	// make sure mac does not exist
                if ($this->settings-enableMulticast=="1" && strlen($class)==0) {
                    if (strlen($field[5])>0 && $this->Subnets->is_multicast($field[0])) {
                        if($this->Subnets->validate_multicast_mac ($field[5], $subnet->sectionId, $subnet->vlanId, MCUNIQUE)!==true) {
                            $errors++; $class = "danger";
                        }
                    }
                }

                // set class
                $field['class'] = $class;

                // save outfile
                $result[] = $field;
            }
        }

        # return
        return $result;
	}

	/**
	 * Counts number of IP addresses for statistics
	 *
	 * @access public
	 * @param string $type (default: "IPv4")
	 * @return int
	 */
	public function count_subnets ($type="IPv4") {
		# set proper query
		if($type=="IPv4")		{ $query = 'select count(cast(`ip_addr` as UNSIGNED)) as count from `ipaddresses` where cast(`ip_addr` as UNSIGNED) < "4294967295";'; }
		elseif($type=="IPv6")	{ $query = 'select count(cast(`ip_addr` as UNSIGNED)) as count from `ipaddresses` where cast(`ip_addr` as UNSIGNED) > "4294967295";'; }

		try { $count = $this->Database->getObjectQuery($query); }
		catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), true); }

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
	 * @return array
	 */
	public function fetch_top_subnets ($type, $limit = "10", $perc = false) {
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
						) as `d` where `usage` > 0 order by `percentage` desc $limit;";
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
		catch (Exception $e) { !$this->debugging ? : $this->Result->show("danger", $e->getMessage(), true);	return false; }

	    # return subnets array
	    return (array) $stats;
	}

	/**
	 * Fetches all addresses to export to hosts file
	 *
	 * @access public
	 * @return array
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
	 * @return bool
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
	 * @return string
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