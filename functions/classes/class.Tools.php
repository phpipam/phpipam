<?php

/**
 *	phpIPAM Section class
 */

class Tools extends Common_functions {

	/**
	 * CSV delimiter
	 *
	 * @var string
	 */
	public $csv_delimiter = ",";

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
	 * Available phpIPAM releases
	 * @var array
	 */
	public $phpipam_releases = [];

	/**
	 * Latest phpIPAM release
	 * @var mixed
	 */
	private $phpipam_latest_release;



	/**
	 * __construct method
	 *
	 * @access public
	 */
	public function __construct (Database_PDO $database) {
		parent::__construct();

		# set database object
		$this->Database = $database;
		# initialize Result
		$this->Result = new Result ();
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
	    $query = 'SELECT vlans.vlanId,vlans.number,vlans.name,vlans.description,vlans.customer_id,subnets.subnet,subnets.mask,subnets.id AS subnetId,subnets.sectionId'.@$custom_fields_query.' FROM vlans LEFT JOIN subnets ON subnets.vlanId = vlans.vlanId where vlans.`domainId` = ? ORDER BY vlans.number ASC;';
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
		$query[] = "or `hostname` like :search_term ";			//hostname
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
	 * @param string $high
	 * @param string $low
	 * @param mixed $search_req
	 * @param mixed $custom_fields (default: array())
	 * @return array
	 */
	public function search_subnets($search_term, $high, $low, $search_req, $custom_fields = array()) {
		# first search if range provided
		$result1 = $this->search_subnets_range  ($search_term, $high, $low, $custom_fields);
		# search inside subnets even if IP does not exist!
		$result2 = $this->search_subnets_inside ($high, $low);
		# search inside subnets even if IP does not exist - IPv6
		$result3 = $this->search_subnets_inside_v6 ($high, $low, $search_req);
		# filter results based on id
		$results = [];
		foreach (array_merge($result1, $result2, $result3) as $result) {
			$results[$result->id] = $result;
		}
		# result
		return $results;
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
		if($high==0 && $low==0)	{ $high = "1"; $low = "1"; }

		# set search query
		$query[] = "select * from `subnets` where `description` like :search_term ";
		$query[] = "or (`subnet` >= :low and `subnet` <= :high )";
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

					if($low>=$this->transform_to_decimal(@$net->network) && $low<=$this->transform_address($net->broadcast, "decimal")) {
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
				$result = array();
				if(sizeof($ids)>0) {
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
	 * Search for circuits.
	 *
	 * @access public
	 * @param mixed $search_term
	 * @param array $custom_circuit_fields (default: array())
	 * @return array
	 */
	public function search_circuits ($search_term, $custom_circuit_fields = array()) {
		# query
		$query[] = "select c.*,p.name,p.description,p.contact,p.id as pid ";
		$query[] = "from circuits as c, circuitProviders as p ";
		$query[] = "where c.provider = p.id";
		$query[] = "and (`cid` like :search_term or `type` like :search_term or `capacity` like :search_term or `comment` like :search_term or `name` like :search_term";
		# custom
	    if(sizeof($custom_circuit_fields) > 0) {
			foreach($custom_circuit_fields as $myField) {
				$myField['name'] = $this->Database->escape($myField['name']);
				$query[] = " or `$myField[name]` like :search_term ";
			}
		}

		$query[] = ") order by c.cid asc;";
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
	 * Search for circuit providers
	 *
	 * @access public
	 * @param mixed $search_term
	 * @param array $custom_circuit_fields (default: array())
	 * @return array
	 */
	public function search_circuit_providers ($search_term, $custom_circuit_fields = array()) {
		# query
		$query[] = "select * from `circuitProviders` where `name` like :search_term or `description` like :search_term or `contact` like :search_term ";
		# custom
	    if(sizeof($custom_circuit_fields) > 0) {
			foreach($custom_circuit_fields as $myField) {
				$myField['name'] = $this->Database->escape($myField['name']);
				$query[] = " or `$myField[name]` like :search_term ";
			}
		}
		$query[] = "order by name asc;";
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
	 * Function to search customers
	 *
	 * @access public
	 * @param mixed $search_term
	 * @param array $custom_fields (default: array())
	 * @return array
	 */
	public function search_customers ($search_term, $custom_fields = array()) {
		# query
		$query[] = "select * from `customers` where `title` like :search_term or `address` like :search_term or `postcode` like :search_term or `city` like :search_term or `state` like :search_term ";
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
		$table = $this->Database->escape($table);

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
		if(sizeof($custom_fields)>0) {
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
	 * Read the SCHEMA.sql file and enforce UNIX LF
	 *
	 * @access private
	 * @return array
	 */
	private function read_db_schema() {
		$fh = fopen(dirname(__FILE__) . '/../../db/SCHEMA.sql', 'r');
		$schema = str_replace("\r\n", "\n", fread($fh, 100000));
		return $schema;
	}

	/**
	 * Fetch the db/SCHEMA.sql DBVERSION
	 *
	 * @return int
	 */
	public function fetch_schema_version() {
		# get SCHEMA.SQL file
		$schema = $this->read_db_schema();

		$dbversion = strstr($schema, 'UPDATE `settings` SET `dbversion` =');
		$dbversion = strstr($dbversion, ';', true);
		$dbversion = explode("=", $dbversion);

		return intval($dbversion[1]);
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
		$schema = $this->read_db_schema();

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
	 * @return array
	 */
	public function fetch_standard_tables () {
		# get SCHEMA.SQL file
		$schema = $this->read_db_schema();

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
		return file_exists(dirname(__FILE__)."/../../app/dashboard/widgets/$file.php")||file_exists(dirname(__FILE__)."/../../app/dashboard/widgets/custom/$file.php") ? true : false;
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
		// All subnets where allowRequests=1, isFull=0 and subnet has no children.
		$query = "SELECT s1.* FROM subnets AS s1
			LEFT JOIN subnets AS s2 ON s2.masterSubnetId = s1.id
			WHERE s1.allowRequests=1
			AND s1.isFull!=1
			AND s2.masterSubnetId IS NULL
			ORDER BY LPAD(s1.subnet,39,0);";
		try { $subnets = $this->Database->getObjectsQuery($query); }
		catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), false);	return NULL; }

		# save
		return sizeof($subnets)>0 ? (array) $subnets : NULL;
	}

	/**
	 * Sends mail for IP request
	 *
	 * @access public
	 * @param string $action
	 * @param mixed $values
	 * @return bool
	 */
	public function ip_request_send_mail ($action, $values) {

		$this->get_settings ();

		# try to send
		try {
			# fetch mailer settings
			$mail_settings = $this->fetch_object("settingsMail", "id", 1);

			# initialize mailer
			$phpipam_mail = new phpipam_mail($this->settings, $mail_settings);

			# get all users and check who to end mail to
			$recipients = $this->ip_request_get_mail_recipients ($values['subnetId']);

			# add requester to cc
			$recipients_requester = $values['requester'];

			# reformat key / vaues
			$values = $this->ip_request_reformat_mail_values ($values);
			#reformat empty
			$values = $this->reformat_empty_array_fields ($values, "/");

			# generate content
			if ($action=="new")			{ $subject	= _("New IP address request"); }
			elseif ($action=="accept")	{ $subject	= _("IP address request accepted"); }
			elseif ($action=="reject")	{ $subject	= _("IP address request rejected"); }
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
			$content[] = "<tr><td style='padding-top:15px;padding-bottom:3px;text-align:right;color:#ccc;'>$this->mail_font_style "._("Sent at")." ".date('Y/m/d H:i')."</font></td></tr>";
			//set alt content
			$content_plain[] = "$subject"."\r\n------------------------------\r\n";
			foreach($values as $k=>$v) {
			$content_plain[] = $k." => ".$v;
			}
			$content_plain[] = "\r\n\r\n".("Sent at")." ".date('Y/m/d H:i');
			$content[] = "</table>";

			// set content
			$content 		= $phpipam_mail->generate_message (implode("\r\n", $content));
			$content_plain 	= implode("\r\n",$content_plain);

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
			$this->Result->show("danger", _("Mailer Error").": ".$e->errorMessage(), true);
		} catch (Exception $e) {
			$this->Result->show("danger", _("Mailer Error").": ".$e->getMessage(), true);
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
				$mail["s_title_1"] = "<br>"._("Subnet details");

				$subnet = $this->fetch_object("subnets", "id", $v);
				$mail["Subnet"]  = $this->transform_address ($subnet->subnet, "dotted")."/".$subnet->mask;
				$mail["Subnet"] .= strlen($subnet->description)>0 ? " - ".$subnet->description : "";
			}
			// ip_addr
			elseif ($k=="ip_addr") {
				// add title
				$mail["s_title_2"] = "<br>"._("Address details");

				if (strlen($v)>0) {
					$mail['IP address'] = $this->transform_address($v, "dotted");
				} else {
					$mail['IP address'] = _("Automatic");
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
			// hostname
			elseif ($k=="hostname") {
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
				$mail["s_title_3"] = "<br>"._("Admin comment");

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
						$error['fieldError'][$table][] = $field;
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
	    $tablename = $this->Database->escape($tablename);
		# check
	    $query = "DESCRIBE `$tablename` `$fieldname`;";
		try { $count = $this->Database->getObjectQuery($query); }
		catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), true);	return false; }
		# return true if it exists
		return $count!== null ? true : false;
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
		$file = $this->read_db_schema();

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
		$file = $this->read_db_schema();

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
			$this->Result->show("danger", _("Update: ").$e->getMessage()."<br>"._("query").": ".$query, true);
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
		$table = $this->Database->escape($table);
		$field = $this->Database->escape($field);

		# set fix query
		$query  = "alter table `$table` add ";
		$query .= trim($this->get_field_fix ($table, $field), ",");
		$query .= ";";

		# fix
		try { $this->Database->runQuery($query); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Update: ").$e->getMessage()."<br>"._("query").": ".$query, true);
			return false;
		}
		return true;
	}

	/**
	 * Verify that all required indexes are present in database
	 *
	 * @method verify_database_indexes
	 * @return bool
	 */
	public function verify_database_indexes () {
		// get indexes from schema
		$schema_indexes = $this->get_schema_indexes();
		// get existing indexes
		$missing = $this->get_missing_database_indexes($schema_indexes);

		// if false all indexes are ok, otherwise fix
		if ($missing===false) {
			return true;
		}
		else {
			foreach ($missing as $table=>$index_id) {
				foreach ($index_id as $index_name) {
					$this->fix_missing_index ($table, $index_name);
				}
			}
		}
		return false;
	}

	/**
	 * Get all indexes required for phpipam
	 *
	 * ignoring primary keys
	 *
	 * @method get_schema_indexes
	 * @return array
	 */
	private function get_schema_indexes () {
		// Discover indexes required for phpipam
		$schema = $this->read_db_schema();

		# get definitions to array, explode with CREATE TABLE `
		$creates = explode("CREATE TABLE `", $schema);

		$indexes = array ();
		foreach($creates as $k=>$c) {
			if($k == 0) continue;
			$c = trim(strstr($c, ";" . "\n", true));

			$table = strstr($c, "`", true);

			$definitions = explode("\n", $c);
			foreach($definitions as $definition) {
				if (preg_match('/(KEY|UNIQUE KEY) +`(.*)` +\(/', $definition, $matches)) {
					$indexes[$table][] = $matches[2];
				}
			}
		}
		return $indexes;
	}

	/**
	 * Get list of table indexes
	 *
	 * @param  string $table
	 * @return mixed
	 */
	private function get_table_indexes($table) {
		try { return $indexes = $this->Database->getObjectsQuery("SHOW INDEX from `$table` where `Key_name` != 'PRIMARY';"); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Invalid query for")." `.$table.` "._("database index check : ").$e->getMessage(), true);
		}
	}

	/**
	 * Using required database indexes remove all that are existing and return array of missing indexes
	 *
	 * @method get_missing_database_indexes
	 * @param array $schema_indexes
	 * @return array|null
	 */
	private function get_missing_database_indexes ($schema_indexes) {
		// loop
		foreach ($schema_indexes as $table=>$index) {
			$indexes = $this->get_table_indexes($table);
			// remove existing
			if ($indexes!==false) {
				foreach ($indexes as $i) {
					// remove indexes
					if(($key = array_search($i->Key_name, $schema_indexes[$table])) !== false) {
						unset($schema_indexes[$table][$key]);
					}
				}
			}
			// remove also empty table
			if(sizeof($schema_indexes[$table])==0) {
				unset($schema_indexes[$table]);
			}
		}
		// return diff
		return sizeof($schema_indexes)==0 ? false : $schema_indexes;
	}

	/**
	 * Fix missing indexes
	 *
	 * @method fix_missing_index
	 * @param  string $table
	 * @param  string $index_name
	 * @return void
	 */
	private function fix_missing_index ($table, $index_name) {
		$table = $this->Database->escape($table);
		$index_name = $this->Database->escape($index_name);

		// get definition
		$file = $this->read_db_schema();

		//go from delimiter on
		$file = strstr($file, "DROP TABLE IF EXISTS `$table`;");
		$file = trim(strstr($file, "# Dump of table", true));

		//get proper line
		$file = explode("\n", $file);

		$line = false;
		foreach($file as $k=>$l) {
			// trim
			$l = trim($l);
			if(strpos($l, "KEY `".$index_name."`")!==false) {
				// remove last ,
				if(substr($l, -1)==",") {
					$l = substr($l, 0, -1);
				}
				// set query and run
				$query = "ALTER TABLE `$table` ADD ".$l;

				try { $this->Database->runQuery($query); }
				catch (Exception $e) {
					$this->Result->show("danger", _("Creating index failed: ").$e->getMessage()."<br><pre>".$query."</pre>", true);
					return false;
				}
				// add warning that index was created
				$this->Result->show("warning", _("Created index for table")." `$table` "._("named")." `$index_name`.", false);
			}
		}
	}

	/**
	 * Manage indexes for linked addresses
	 *
	 * @param  string $linked_field
	 * @return void
	 */
	public function verify_linked_field_indexes ($linked_field) {
		$valid_fields = $this->fetch_custom_fields ('ipaddresses');
		$valid_fields = array_merge(['ip_addr','hostname','mac','owner'], array_keys($valid_fields));

		// get indexes from schema and table
		$schema_indexes = $this->get_schema_indexes();
		$table_indexes  = $this->get_table_indexes('ipaddresses');

		if (!is_array($schema_indexes) || !is_array($table_indexes))
			return;

		$linked_field_index_found = false;

		foreach ($table_indexes as $i) {
			// check for valid linked_field candidates
			if (!in_array($i->Key_name, $valid_fields))
				continue;
			// skip permanent indexes defined in schema
			if (in_array($i->Key_name, $schema_indexes['ipaddresses']))
				continue;
			// skip selected linked_field
			if ($i->Key_name == $linked_field) {
				$linked_field_index_found = true;
				continue;
			}

			// Remove un-necessary linked_field indexes.
			try { $this->Database->runQuery("ALTER TABLE `ipaddresses` DROP INDEX $i->Key_name;"); }
			catch (Exception $e) {
				$this->Result->show("danger", $e->getMessage(), true);
			}
			$this->Result->show("info", _("Removing link addresses index : ").$i->Key_name);
		}

		if ($linked_field_index_found || !in_array($linked_field, $valid_fields))
			return;

		$schema = $this->getTableSchemaByField('ipaddresses');
		$data_type = $schema[$linked_field]->DATA_TYPE;

		if( in_array($data_type, ['text', 'blob']) ) {
			// The prefix length must be specified when indexing TEXT/BLOB datatypes.
			// Max prefix length and behaviour varies with strict mode, MySQL/MariaDB versions and configured collation.
			//
			// Too complex: Avoid creating an index for this datatype and warn of possible poor performance.

			$this->Result->show("warning",
				_("Warning: ")._("Unable to create index for MySQL TEXT/BLOB data types.")."<br>".
				_("Reduced performance when displaying linked addresses by ").escape_input($linked_field)." ($data_type)"."<br>".
				_("Change custom field data type to VARCHAR and re-save to enable indexing.")
			);
			return;
		}

		// Create selected linked_field index if not exists.
		try { $this->Database->runQuery("ALTER TABLE `ipaddresses` ADD INDEX ($linked_field);"); }
		catch (Exception $e) {
			$this->Result->show("danger", $e->getMessage(), true);
		}
		$this->Result->show("info", _("Adding link addresses index : ").$linked_field);
	}










	/**
	 *	@version check methods
	 *	------------------------------
	 */

	/**
	 * Check for latest version on gitHub
	 *
	 * @access public
	 * @param bool $print_error (default: false)
	 * @return string|bool
	 */
	public function check_latest_phpipam_version ($print_error = false) {
		# fetch settings
		$this->get_settings ();
		# check for release
		# try to fetch
		$curl = $this->curl_fetch_url('https://github.com/phpipam/phpipam/releases.atom');
		# check
		if ($curl['result']===false) {
			if($print_error) {
				$this->Result->show("danger", _("Cannot fetch https://github.com/phpipam/phpipam/releases.atom : ").$curl['error_msg'], false);
			}
			return false;
		}
		# set releases href
		$xml = simplexml_load_string($curl['result']);

		// if ok
		if ($xml!==false) {
			// encode to json
			$json = json_decode(json_encode($xml));
			// save all releases
			$this->phpipam_releases = $json->entry;
			// check for latest release
			foreach ($json->entry as $e) {
				// releases will be named with numberic values
				if (is_numeric(str_replace(array("Version", "."), "", $e->title))) {
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
        $out['Number of hosts'] = $Subnets->max_hosts(['subnet'=>$net->network, 'mask'=>$net->bitmask]);

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
        $longIp = $this->ip2long6($subnet);
        $minIp = $Subnets->decimal_network_address($longIp, $mask);
        $maxIp = $Subnets->decimal_broadcast_address($longIp, $mask);

        $out['Min host IP']               = $this->transform_to_dotted ($minIp);
        $out['Max host IP']               = $this->transform_to_dotted ($maxIp);
        $out['Number of hosts']           = $Subnets->max_hosts(['subnet'=>$subnet, 'mask'=>$mask]);

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
     * @param string $actions_menu
     * @return string
     */
    public function print_nat_table ($n, $is_admin = false, $nat_id = false, $admin = false, $object_type = false, $object_id=false, $actions_menu = "") {
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
        $n->description = strlen($n->description)>0 ? "<br>$n->description" : "";

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
        $html[] = "<span class='pull-right'>";
        $html[] = $actions_menu;
        $html[] = "</span>";
        $html[] = "</td>";
        $html[] = "</tr>";

        // append ports
        if(($n->type=="static" || $n->type=="destination") && (strlen($n->src_port)>0 && strlen($n->dst_port)>0)) {
            $sources      = implode("<br>", $sources)." :".$n->src_port;
            $destinations = implode("<br>", $destinations)." :".$n->dst_port;
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
	 * Fetch parents recursive - generic function
	 *
	 * Fetches all parents for specific table in id / parent relations
	 *
	 * It will return array, keys will be id's and values as defined in return_field
	 *
	 * @param string $table
	 * @param string $parent_field
	 * @param string $return_field
	 * @param int $false
	 * @param bool $reverse (default: true)
	 *
	 * @return array
	 */
	public function fetch_parents_recursive ($table, $parent_field, $return_field, $id, $reverse = false) {
		$parents = array();
		$root = false;

		while($root === false) {
			$subd = $this->fetch_object($table, "id", $id);

			if($subd!==false) {
    			$subd = (array) $subd;
				# not root yet
				if(@$subd[$parent_field]!=0) {
					// array_unshift($parents, $subd[$parent_field]);
					$parents[$subd['id']] = $subd[$return_field];
					$id  = $subd[$parent_field];
				}
				# root
				else {
					$parents[$subd['id']] = $subd[$return_field];
					$root = true;
				}
			}
			else {
				$root = true;
			}
		}
		# return array
		return $reverse ? array_reverse($parents, true) :$parents;
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
        	$this->Result->show("danger", _("Duplicate check failed"), true);
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
	 * Prints structured menu of prefixes
	 *
	 * @access public
	 * @param mixed $user
	 * @param mixed $prefixes
	 * @param mixed $custom_fields
	 * @return mixed
	 */
	public function print_menu_prefixes ( $user, $prefixes, $custom_fields ) {

		# user class for permissions
		$User = new User ($this->Database);

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
			if($User->get_module_permissions ("pstn")==User::ACCESS_NONE) { unset($prefixes[$k]); }
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
		reset( $children_prefixes[$parent] );
		while ( $loop && ( ( $option = current( $children_prefixes[$parent] ) ) || ( $parent > $rootId ) ) )
		{
			next( $children_prefixes[$parent] );

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
			$name = strlen($option['name'])==0 ? "/" : $option['name'];

			# print table line
			if(strlen($option['prefix']) > 0) {
    			# count change?
				$html[] = "<tr class='level$count'>";

				//which level?
				if($count==1) {
					# last?
					if(!empty( $children_prefixes[$option['id']])) {
						$html[] = "	<td class='level$count'><span class='structure-last' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open-o'></i><a href='".create_link($_GET['page'],"pstn-prefixes",$option['id'])."'>".$option['prefix']." </a></td>";
						$html[] = "	<td class='level$count'><span class='structure-last' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open-o'></i> <strong>$name</strong></td>";
					} else {
						$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-angle-right'></i><a href='".create_link($_GET['page'],"pstn-prefixes",$option['id'])."'>".$option['prefix']." </a></td>";
						$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-angle-right'></i> <strong>$name</strong></td>";
					}
				}
				else {
					# last?
					if(!empty( $children_prefixes[$option['id']])) {
						$html[] = "	<td class='level$count'><span class='structure-last' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open-o'></i> <a href='".create_link($_GET['page'],"pstn-prefixes",$option['id'])."'>  ".$option['prefix']."</a></td>";
						$html[] = "	<td class='level$count'><span class='structure-last' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open-o'></i> <strong>$name</strong></td>";
					}
					else {
						$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-angle-right'></i> <a href='".create_link($_GET['page'],"pstn-prefixes",$option['id'])."'>  ".$option['prefix']."</a></td>";
						$html[] = "	<td class='level$count'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-angle-right'></i> <strong>$name</strong></td>";
					}
				}

				// range
				$html[] = " <td class='level$count'><span class='structure-last' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-angle-right'></i> ".$option['prefix'].$option['start']." ".$option['prefix'].$option['stop']."</td>";

				//start/stop
				$html[] = "	<td>".$option['start']."</td>";
				$html[] = "	<td>".$option['stop']."</td>";

				//count
                $cnt = $this->count_database_objects("pstnNumbers", "prefix", $option['id']);

                $html[] = "	<td><span class='badge badge1 badge5'>".$cnt."</span></td>";

				//device
				if($User->get_module_permissions ("devices")>=User::ACCESS_RW) {
					$device = ( $option['deviceId']==0 || empty($option['deviceId']) ) ? false : true;

					if($device===false) { $html[] ='	<td>/</td>' . "\n"; }
					else {
						$device = $this->fetch_object ("devices", "id", $option['deviceId']);
						if ($device!==false) {
							$html[] = "	<td><a href='".create_link("tools","devices",$device->id)."'>".$device->hostname .'</a></td>' . "\n";
						}
						else {
							$html[] ='	<td>/</td>' . "\n";
						}
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
								if($option[$field['name']] == "0")			{ $html[] = _("No"); }
								elseif($option[$field['name']] == "1")		{ $html[] = _("Yes"); }
							}
							//text
							elseif($field['type']=="text") {
								if(strlen($option[$field['name']])>0)		{ $html[] = "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", $option[$field['name']])."'>"; }
								else												{ $html[] = ""; }
							}
							else {
								$html[] = $option[$field['name']];

							}

				   			$html[] =  "</td>";
			   			}
			    	}
			    }

			    // actions
				if($User->get_module_permissions ("pstn")>=User::ACCESS_R) {
					$html[] = "	<td class='actions' style='padding:0px;'>";
					$links = [];
			        $links[] = ["type"=>"header", "text"=>_("Show")];
			        $links[] = ["type"=>"link", "text"=>_("View prefix"), "href"=>create_link($_GET['page'], "pstn-prefixes", $option['id']), "icon"=>"eye", "visible"=>"dropdown"];

			        if($User->get_module_permissions ("pstn")>=User::ACCESS_RW) {
			            $links[] = ["type"=>"divider"];
			            $links[] = ["type"=>"header", "text"=>_("Manage")];
			            $links[] = ["type"=>"link", "text"=>_("Edit prefix"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/tools/pstn-prefixes/edit.php' data-class='700' data-action='edit' data-id='$option[id]'", "icon"=>"pencil"];
			        }
			        if($User->get_module_permissions ("pstn")>=User::ACCESS_RWA) {
			            $links[] = ["type"=>"link", "text"=>_("Delete prefix"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/tools/pstn-prefixes/edit.php' data-class='700' data-action='delete' data-id='$option[id]'", "icon"=>"times"];
			        }
			        $html[] = $User->print_actions($User->user->compress_actions, $links);
					$html[] = "	</td>";
				}

				$html[] = "</tr>";

                # save old level count
                $old_count = $count;
			}

			if ( $option === false ) { $parent = array_pop( $parent_stack ); }
			# Has slave subnets
			elseif ( !empty( $children_prefixes[$option['id']] ) ) {
				array_push( $parent_stack, $option['master'] );
				$parent = $option['id'];
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
		if (!is_array($all_prefixes)) $all_prefixes = array();
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
		reset( $children_prefixes[$parent] );
		while ( $loop && ( ( $option = current( $children_prefixes[$parent] ) ) || ( $parent > $rootId ) ) )
		{
			next( $children_prefixes[$parent] );
			# repeat
			$repeat  = str_repeat( " &nbsp;&nbsp; ", ( count($parent_stack_prefixes)) );

			# selected
			$selected = $option['id'] == $prefixId ? "selected='selected'" : "";
			if($option['id'])
            $html[] = "<option value='".$option['id']."' $selected>$repeat ".$option['prefix']." (".$option['name'].")</option>";

			if ( $option === false ) { $parent = array_pop( $parent_stack_prefixes ); }
			# Has slave subnets
			elseif ( !empty( $children_prefixes[$option['id']] ) ) {
				array_push( $parent_stack_prefixes, $option['master'] );
				$parent = $option['id'];
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
		// fetch address types
		$this->get_addresses_types();

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
		// fetch address types
		$this->get_addresses_types();

		$count = array();
		$count['used'] = 0;				//initial sum count
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
	 * explode $string using $delimiter and filter null values.
	 *
	 * @param  string $delimiter
	 * @param  string $string
	 * @return mixed
	 */
	public function explode_filtered($delimiter, $string) {
	    $ret = explode($delimiter, $string);
	    if (!is_array($ret))
	        return false;
	    return array_filter($ret);
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
            $id = $this->Database->escape ($id);
            // count ?
            $select = $count ? "count(*) as cnt " : "*";
            // query
            $query = "select $select from
                        (
                        SELECT d.id, d.hostname as name, '' as mask, 'devices' as type, '' as sectionId, d.location, d.description
                        FROM devices d
                        JOIN locations l
                        ON d.location = l.id
                        WHERE l.id = $id

                        UNION ALL
                        SELECT r.id, r.name, '' as mask, 'racks' as type, '' as sectionId, r.location, r.description
                        FROM racks r
                        JOIN locations l
                        ON r.location = l.id
                        WHERE l.id = $id

                        UNION ALL
                        SELECT s.id, s.subnet as name, s.mask, 'subnets' as type, s.sectionId, s.location, s.description
                        FROM subnets s
                        JOIN locations l
                        ON s.location = l.id
                        WHERE l.id = $id

                        UNION ALL
                        SELECT a.id, a.ip_addr as name, 'mask', 'addresses' as type, a.subnetId as sectionId, a.location, a.hostname as description
                        FROM ipaddresses a
                        JOIN locations l
                        ON a.location = l.id
                        WHERE l.id = $id

                        UNION ALL
                        SELECT c.id, c.cid as name, 'mask', 'circuit' as type, 'none' as sectionId, c.location2, 'none' as description
                        FROM circuits c
                        JOIN locations l
                        ON c.location1 = l.id
                        WHERE l.id = $id

                        UNION ALL
                        SELECT c.id, c.cid as name, 'mask', 'circuit' as type, 'none' as sectionId, c.location2, '' as description
                        FROM circuits c
                        JOIN locations l
                        ON c.location2 = l.id
                        WHERE l.id = $id
                        )
                        as linked;";

     		// fetch
    		try { $objects = $this->Database->getObjectsQuery($query); }
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
	 * Fetches all circuits from database
	 *
	 * @method fetch_all_circuits
	 *
	 * @param  array $custom_circuit_fields
	 *
	 * @return false|array
	 */
	public function fetch_all_circuits ($custom_circuit_fields = array ()) {
		// set query
		$query[] = "select";
		$query[] = "c.id,c.cid,c.type,c.device1,c.location1,c.device2,c.location2,c.comment,c.customer_id,p.name,p.description,p.contact,c.capacity,p.id as pid,c.status";
		// custom fields
		if(is_array($custom_circuit_fields)) {
			if(sizeof($custom_circuit_fields)>0) {
				foreach ($custom_circuit_fields as $f) {
					$query[] = ",c.`".$f['name']."`";
				}
			}
		}
		$query[] = "from circuits as c, circuitProviders as p where c.provider = p.id";
		$query[] = "order by c.cid asc;";
		// fetch
		try { $circuits = $this->Database->getObjectsQuery(implode("\n", $query), array()); }
		catch (Exception $e) {
			$this->Result->show("danger", $e->getMessage(), true);
		}
		// return
		return sizeof($circuits)>0 ? $circuits : false;
	}

	/**
	 * Fetches all logical circuits belonging to circuit
	 *
	 * @method fetch_all_logical_circuits_using_circuit
	 * @param  int $circuit_id
	 * @return array|false
	 */
	public function fetch_all_logical_circuits_using_circuit ($circuit_id) {
		// set query
		$query[] = "select";
		$query[] = "lc.*";
		$query[] = "from circuitsLogical as lc";
		$query[] = "join `circuitsLogicalMapping` mapping on mapping.logicalCircuit_id=lc.id";
		$query[] = "WHERE mapping.circuit_id = ?";
		$query[] = "order by lc.logical_cid asc;";
		// fetch
		try { $circuits = $this->Database->getObjectsQuery(implode("\n", $query), [$circuit_id]); }
		catch (Exception $e) {
			$this->Result->show("danger", $e->getMessage(), true);
		}
		// return
		return sizeof($circuits)>0 ? $circuits : false;
	}

	/**
	 * Fetch all members of logical circuit
	 *
	 * @method fetch_all_logical_circuit_members
	 * @param  int $logical_circuit_id
	 * @return array|false
	 */
  	public function fetch_all_logical_circuit_members ($logical_circuit_id) {
  		// set query
		$query2[] = "SELECT";
		$query2[] = "c.*";
		$query2[] = "FROM `circuits` c";
		$query2[] = "join `circuitsLogicalMapping` mapping on mapping.circuit_id=c.id";
		$query2[] = "where mapping.logicalCircuit_id = ?";
		$query2[] = "order by mapping.`order`;";
		// fetch
		try { $circuits = $this->Database->getObjectsQuery(implode("\n", $query2), $logical_circuit_id); }
		catch (Exception $e) {
			$this->Result->show("danger", $e->getMessage(), true);
		}
		return sizeof($circuits)>0 ? $circuits : false;
	}

	/**
	 * Fetches all circuits for specific provider
	 *
	 * @method fetch_all_circuits
	 *
	 * @param  int $provider_id
	 * @param  array $custom_circuit_fields
	 *
	 * @return false|array
	 */
	public function fetch_all_provider_circuits ($provider_id, $custom_circuit_fields = array ()) {
		// set query
		$query[] = "select";
		$query[] = "c.id,c.cid,c.type,c.device1,c.location1,c.device2,c.location2,p.name,p.description,p.contact,c.capacity,p.id as pid,c.status";
		// custom fields
		if(is_array($custom_circuit_fields)) {
			if(sizeof($custom_circuit_fields)>0) {
				foreach ($custom_circuit_fields as $f) {
					$query[] = ",c.`".$f['name']."`";
				}
			}
		}
		$query[] = "from circuits as c, circuitProviders as p where c.provider = p.id and c.provider = ?";
		$query[] = "order by c.cid asc;";
		// fetch
		try { $circuits = $this->Database->getObjectsQuery(implode("\n", $query), array($provider_id)); }
		catch (Exception $e) {
			$this->Result->show("danger", $e->getMessage(), true);
		}
		// return
		return sizeof($circuits)>0 ? $circuits : false;
	}


	/**
	 * Fetches all circuits for specific device
	 *
	 * @method fetch_all_circuits
	 *
	 * @param  int $device_id
	 *
	 * @return false|array
	 */
	public function fetch_all_device_circuits ($device_id) {
		// set query
		$query = "select
					c.id,c.cid,c.type,c.device1,c.location1,c.device2,c.location2,p.name,p.description,p.contact,c.capacity,p.id as pid,c.status
					from circuits as c, circuitProviders as p where c.provider = p.id and (c.device1 = :deviceid or c.device2 = :deviceid)
					order by c.cid asc;";
		// fetch
		try { $circuits = $this->Database->getObjectsQuery($query, array("deviceid"=>$device_id)); }
		catch (Exception $e) {
			$this->Result->show("danger", $e->getMessage(), true);
		}
		// return
		return sizeof($circuits)>0 ? $circuits : false;
	}

	/**
	 * Reformat circuit location
	 *
	 * If device is provided return device
	 * If location return location
	 *
	 * result will be false or array of:
	 * 	- type => "devices" / "locations"
	 *  - icon => "fa-desktop / fa-map"
	 *  - id => $id
	 *  - name => "location or device name"
	 *  - location => "location index or NULL"
	 *  - rack => "NULL if location, rack_id if device is set with rack otherwise NULL"
	 *
	 * @method reformat_circuit_location
	 *
	 * @param  int $deviceId
	 * @param  int $locationId
	 *
	 * @return false|array
	 */
	public function reformat_circuit_location ($deviceId = null, $locationId = null) {
		// check device
		if(is_numeric($deviceId) && $deviceId!=0) {
			// fetch device
			$device = $this->fetch_object ("devices", "id", $deviceId);
			// check
			if ($device === false) {
				return false;
			}
			else {
				$array = array (
								"type"     => "devices",
								"id"       => $device->id,
								"name"     => $device->hostname,
								"icon" 	   => "",
								"location" => is_null($device->location)||$device->location==0 ? NULL : $device->location,
								"rack"     => is_null($device->rack)||$device->rack==0 ? NULL : $device->rack
				                );
				// check rack location if not configured
				if ($array['location']==NULL && $array['rack']!=NULL) {
					$rack_location = $this->fetch_object ("racks", "id", $array['rack']);
					$array['location'] = $rack_location!==false ? $rack_location->location : NULL;
				}
				// result
				return $array;
			}
		}
		// check location
		elseif (is_numeric($locationId) && $locationId!=0) {
			// fetch location
			$location = $this->fetch_object ("locations", "id", $locationId);
			// check
			if ($device === false) {
				return false;
			}
			else {
				$array = array (
								"type"     => "locations",
								"id"       => $location->id,
								"name"     => $location->name,
								"icon" 	   => "fa-map",
								"location" => $location->id,
								"rack"     => NULL
				                );
				return $array;
			}
		}
		else {
			return false;
		}
	}

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
		$query[] = "	`v`.`customer_id` as `customer_id`,";
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
	 * Fetch all objects belonging to customer
	 *
	 * @method fetch_customer_objects
	 * @param  int $customer_id
	 * @return void
	 */
	public function fetch_customer_objects ($customer_id) {
		// out
		$out = [];
		// fetch
		if(is_numeric($customer_id)) {
			foreach ($this->get_customer_object_types() as $table=>$name) {
				$objects = $this->fetch_multiple_objects ($table, "customer_id", $customer_id, $this->get_customer_object_types_sorts ($table));
				if ($objects!==false) {
					$out[$table] = $objects;
				}
			}
		}
		// return
		return $out;
	}


	/**
	 * Fetch all routing subnets
	 *
	 * @method fetch_routing_subnets
	 * @param  string $type [bgp,ospf]
	 * @param  int $id (default: 0)
	 * @param  bool $cnt (default: true)
	 * @return false|array
	 */
	public function fetch_routing_subnets ($type="bgp", $id = 0, $cnt = false) {
		// set type
		$type = $type=="bgp" ? "bgp" : "ospf";
		// set count
		$fields = $cnt ? "count(*) as cnt" : "*,s.id as subnet_id";
		// set query
		$query = "select $fields from subnets as s, routing_subnets as r
					where r.type = ? and r.object_id = ? and r.subnet_id = s.id
					order by r.direction asc, s.subnet asc;";
		// fetch
		try { $subnets = $this->Database->getObjectsQuery($query, [$type, $id]); }
		catch (Exception $e) {
			$this->Result->show("danger", $e->getMessage(), true);
		}
		// return
		return sizeof($subnets)>0 ? $subnets : false;
	}



	/**
	 * Return all possible customer object relations
	 *
	 * @method get_customer_object_types
	 * @return array
	 */
	public function get_customer_object_types () {
		return [
				"subnets"     => _("Subnets"),
				"ipaddresses" => _("Addresses"),
				"vlans"       => _("VLAN"),
				"vrf"         => _("VRF"),
				"circuits"    => _("Circuits"),
				"racks"       => _("Racks"),
				"routing_bgp" => _("BGP Routing")
				];
	}

	/**
	 * Return sorting for fetch_multiple_objects
	 *
	 * @method get_customer_object_types_sorts
	 * @param  string $type
	 * @return string
	 */
	private function get_customer_object_types_sorts ($type) {
		switch ($type) {
			case "subnets"     : return "subnet";
			case "ipaddresses" : return "ip_addr";
			case "vlans"       : return "number";
			case "vrf"         : return "name";
			case "circuits"    : return "cid";
			case "racks"       : return "name";
			default            : return "id";
		}
	}

	/**
	 * Parses import file
	 *
	 * @access public
	 * @param string $filetype
	 * @param object $subnet
	 * @param array $custom_address_fields
	 * @return array
	 */
	public function parse_import_file ($filetype, $subnet, $custom_address_fields) {
    	# start object and get settings
    	$this->get_settings ();
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
    	$data = new Spreadsheet_Excel_Reader(dirname(__FILE__) . '/../../app/subnets/import-subnet/upload/import.xls', false, 'utf-8');

    	//get number of rows
    	$numRows = $data->rowcount(0);
    	$numRows++;

    	$outFile = array();

    	// set delimiter
    	$this->csv_delimiter = ";";

    	//get all to array!
    	for($m=0; $m < $numRows; $m++) {

    		//IP must be present!
    		if(filter_var($data->val($m,'A'), FILTER_VALIDATE_IP)) {
        		//for multicast
        		$mac = $data->val($m,'F');
        		if ($this->settings->enableMulticast=="1") {
            		if (strlen($data->val($m,'F'))==0 && $this->Subnets->is_multicast($data->val($m,'A')))    {
                		$mac = $this->Subnets->create_multicast_mac ($data->val($m,'A'));
                    }
                }

    			$outFile[$m]  = $data->val($m,'A').$this->csv_delimiter.$data->val($m,'B').$this->csv_delimiter.$data->val($m,'C').$this->csv_delimiter.$data->val($m,'D').$this->csv_delimiter;
    			$outFile[$m] .= $data->val($m,'E').$this->csv_delimiter.$mac.$this->csv_delimiter.$data->val($m,'G').$this->csv_delimiter.$data->val($m,'H').$this->csv_delimiter;
    			$outFile[$m] .= $data->val($m,'I').$this->csv_delimiter.$data->val($m,'J').$this->csv_delimiter.$data->val($m,'K');
    			//add custom fields
    			if(sizeof($custom_address_fields) > 0) {
    				$currLett = "L";
    				foreach($custom_address_fields as $field) {
    					$outFile[$m] .= $this->csv_delimiter.$data->val($m,$currLett++);
    				}
    			}
    			$outFile[$m] = $this->convert_encoding_to_UTF8($outFile[$m]);
    		}
    	};
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
    	// get file to string
		$handle = fopen(dirname(__FILE__) . '/../../app/subnets/import-subnet/upload/import.csv', "r");
		if ($handle) {
		    while (($outFile[] = fgets($handle)) !== false) {}
		    fclose($handle);
		} else {
		    $this->Result->show("danger", _('Cannot open upload/import.csv'), true);
		}

    	// delimiter
    	if(isset($outFile[0]))
    	$this->set_csv_delimiter ($outFile[0]);

    	/* validate IP */
    	foreach($outFile as $k=>$v) {
        	//put it to array
        	$field = str_getcsv ($v, $this->csv_delimiter);

        	if(!filter_var($field[0], FILTER_VALIDATE_IP)) {
            	unset($outFile[$k]);
            	unset($field);
        	}
        	else {
            	# mac
        		if ($this->settings->enableMulticast=="1") {
            		if (strlen($field[5])==0 && $this->Subnets->is_multicast($field[0]))  {
                		$field[5] = $this->Subnets->create_multicast_mac ($field[0]);
                    }
        		}
        	}

        	# save
        	if(isset($field)) {
	        	$outFile[$k] = implode($this->csv_delimiter, $field);
    		}
    	}

    	# return
    	return $outFile;
	}

	/**
	 * Detects CSV delimiter
	 *
	 * @method set_csv_delimiter
	 * @param  string $outFile
	 * @return string
	 */
	public function set_csv_delimiter ($outFile) {
		// must be string
		if(is_string($outFile)) {
			// count occurences
			$cnt_coma  = substr_count($outFile, ",");
			$cnt_colon = substr_count($outFile, ";");
			// set higher
			$this->csv_delimiter = $cnt_coma > $cnt_colon ? "," : ";";
		}
		else {
			$this->csv_delimiter = ",";
		}
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

            	//convert encoding if necessary
            	$line = $this->convert_encoding_to_UTF8($line);

            	//put it to array
            	$field = str_getcsv ($line, $this->csv_delimiter);

            	//verify IP address
            	if(!filter_var($field[0], FILTER_VALIDATE_IP)) 	{ $class = "danger";	$errors++; }
            	else											{ $class = ""; }

            	// verify that address is in subnet for subnets
            	if($subnet->isFolder!="1") {
					// check if IP is IPv4 or IPv6
					$ipsm = "32";
                	if (!filter_var($field[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) { $ipsm = "128"; }
                    if ($this->Subnets->is_subnet_inside_subnet ($field[0]."/" . $ipsm, $this->transform_address($subnet->subnet, "dotted")."/".$subnet->mask)==false)    { $class = "danger"; $errors++; }
                }
            	// make sure mac does not exist
                if ($this->settings->enableMulticast=="1" && strlen($class)==0) {
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
		if($type=="IPv4")		{ $query = 'select count(*) as count from `ipaddresses` where cast(`ip_addr` as UNSIGNED) <= 4294967295;'; }
		elseif($type=="IPv6")	{ $query = 'select count(*) as count from `ipaddresses` where cast(`ip_addr` as UNSIGNED) >  4294967295;'; }

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
		# set limit & IPv4/IPv6 selector
		$limit = $limit<=0 ? '' : 'LIMIT '. (int) $limit;
		$type_operator = ($type === 'IPv6') ? '>' : '<=';
		$type_max_mask = ($type === 'IPv6') ? '128' : '32';
		$strict_mode   = ($type === 'IPv6') ? '0' : '2';

		if($perc) {
			$query = "SELECT s.sectionId,s.id,s.subnet,mask,IF(char_length(s.description)>0,s.description,'No description') AS description,
					COUNT(*) AS `usage`,ROUND(COUNT(*)/(POW(2,$type_max_mask-`mask`)-$strict_mode)*100,2) AS `percentage` FROM `ipaddresses` AS `i`
					LEFT JOIN `subnets` AS `s` ON i.subnetId = s.id
					WHERE s.mask < ($type_max_mask-1) AND CAST(s.subnet AS UNSIGNED) $type_operator 4294967295
					GROUP BY i.subnetId
					ORDER BY `percentage` DESC $limit;";
		} else {
			$query = "SELECT s.sectionId,s.id,s.subnet,mask,IF(char_length(s.description)>0,s.description,'No description') AS description,
					COUNT(*) AS `usage` FROM `ipaddresses` AS `i`
					LEFT JOIN `subnets` AS `s` ON i.subnetId = s.id
					WHERE CAST(s.subnet AS UNSIGNED) $type_operator 4294967295
					GROUP BY i.subnetId
					ORDER BY `usage` DESC $limit;";
		}

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
	    try { $addresses = $this->Database->getObjectsQuery("select `id`,`subnetId`,`ip_addr`,`hostname` from `ipaddresses` where length(`hostname`)>1 order by `subnetId` asc;"); }
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
