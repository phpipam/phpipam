<?php

/**
 *    phpIPAM PowerDNS class to work with PowerDNS
 *
 *    https://wiki.powerdns.com/trac/wiki/fields
 *
 */
class PowerDNS extends Common_functions {

    /**
     * connection error string
     *
     * (default value: false)
     *
     * @var bool|string
     * @access public
     */
    public $error = false;

    /**
     * Database settings
     *
     * @var array|object
     * @access public
     */
    public $db_settings;

    /**
     * Default settings
     *
     * @var object
     * @access public
     */
    public $defaults;

    /**
     * Number of results
     *
     * @var int
     * @access public
     */
    public $limit;

    /**
     * Order field
     *
     * @var string
     * @access public
     */
    public $orderby;

    /**
     * Order direction
     *
     * @var string
     * @access public
     */
    public $orderdir;

    /**
     * Domain types
     *
     * @var object
     * @access public
     */
    public $domain_types;

    /**
     * Record types
     *
     * @var mixed
     * @access public
     */
    public $record_types;

    /**
     * ttl value
     *
     * @var int|string
     * @access public
     */
    public $ttl;

    /**
     * Last insert id
     *
     * @var int|string
     * @access public
     */
    public $lastId;

    /**
     * array of domains - index = id
     *
     * (default value: array())
     *
     * @var array
     * @access private
     */
    private $domains_cache = array();

    /**
     * Database class - phpipam
     *
     * @var resource
     * @access protected
     */
    protected $Database;

    /**
     * Database class - pdns
     *
     * @var resource
     * @access protected
     */
    protected $Database_pdns;




    /**
     * __construct function.
     *
     * @access public
     * @param Database_PDO $Database
     */
    public function __construct (Database_PDO $Database) {
        # initialize Result
        $this->Result = new Result ();
        # initialize object
        $this->Database = $Database;
        # Log object
        $this->Log = new Logging ($this->Database);

        // get settings
        $this->get_settings ();
        // set database
        $this->db_set ();

        // set domain types
        $this->set_domain_types ();
        // set record types
        $this->set_record_types ();
        // set uery values
        $this->set_query_values ();
        // set ttl values
        $this->set_ttl_values ();
    }











    /* @database settings ---------- */

    /**
     * Sets database connection
     *
     * @access private
     */
    private function db_set () {
        // decode values form powerDNS
        $this->db_settings = strlen($this->settings->powerDNS)>10 ? json_decode($this->settings->powerDNS) : json_decode($this->db_set_db_settings ());
        // set connection
        $this->Database_pdns = new Database_PDO ($this->db_settings->username, $this->db_settings->password, $this->db_settings->host, $this->db_settings->port, $this->db_settings->name);
    }

    /**
     * Sets default values for database connection and othern parameters
     *
     * @access private
     * @return void
     */
    private function db_set_db_settings () {
        // init
        $this->defaults = new StdClass ();
        // database
        $this->defaults->host        = "127.0.0.1";
        $this->defaults->name        = "pdns";
        $this->defaults->username    = "pdns";
        $this->defaults->password    = "pdns";
        $this->defaults->port        = 3306;
        $this->defaults->autoserial  = "No";
        // default values
        $this->defaults->ns          = "localhost";
        $this->defaults->hostmaster  = $this->settings->siteAdminMail;
        $this->defaults->refresh     = 180;
        $this->defaults->retry       = 3600;
        $this->defaults->expire      = 604800;
        $this->defaults->nxdomain_ttl = 180;
        $this->defaults->ttl         = 86400;

        // return
        return json_encode($this->defaults);
    }

    /**
     * Checks database connection with given parameters
     *
     * @access public
     * @return bool
     */
    public function db_check () {
        try { $this->Database_pdns->connect(); }
        catch (Exception $e) {
            // error
            $this->error = $e->getMessage();
            return false;
        }
        // ok
        return true;
    }

    /**
     * Returns Id of last insert
     *
     * @access public
     * @return int
     */
    public function get_last_db_id () {
        return $this->Database_pdns->lastInsertId ();
    }

    /**
     * Returns domain types
     *
     * @access private
     */
    private function set_domain_types () {
        $types = array(
                "NATIVE",
                "MASTER",
                "SLAVE",
                "SUPERSLAVE"
                );
        # save
        $this->domain_types = (object) $types;
    }

    /**
     * Sets array of record types
     *
     *    https://doc.powerdns.com/md/types/
     *
     * $record_types[] = "DS";
     * $record_types[] = "SSHFP";
     * $record_types[] = "SRV";
     * $record_types[] = "DNSKEY";
     * $record_types[] = "NSEC";
     * $record_types[] = "RRSIG";
     * $record_types[] = "AFSDB";
     * $record_types[] = "CERT";
     * $record_types[] = "HINFO";
     * $record_types[] = "KEY";
     * $record_types[] = "LOC";
     * $record_types[] = "NAPTR";
     * $record_types[] = "RP";
     * $record_types[] = "TLSA";
     *
     *
     *    For now only basic record types are available because of validations.
     *    If some other record is required uncomment it, note that input will not be validated
     *
     * @access private
     */
    private function set_record_types () {
        // init
        $record_types = array();
        // set array
        $record_types[] = "A";
        $record_types[] = "AAAA";
        $record_types[] = "MX";
        $record_types[] = "CAA";
        $record_types[] = "CNAME";
        $record_types[] = "PTR";
        $record_types[] = "TXT";
        $record_types[] = "NS";
        $record_types[] = "SOA";
        $record_types[] = "SPF";
        $record_types[] = "SRV";
        $record_types[] = "LUA";

        // save
        $this->record_types = (object) $record_types;
    }

    /**
     * Sets default values for TTL
     *
     * @access private
     */
    private function set_ttl_values () {
        // init
        $ttl = array();
        // set array
        $ttl[60]     = "1 minute";
        $ttl[180]    = "3 minutes";
        $ttl[300]    = "5 minutes";
        $ttl[600]    = "10 minutes";
        $ttl[900]    = "15 minutes";
        $ttl[1800]   = "30 minutes";
        $ttl[2700]   = "45 minutes";
        $ttl[3600]   = "1 hour";
        $ttl[7200]   = "2 hours";
        $ttl[10800]  = "3 hours";
        $ttl[21600]  = "6 hours";
        $ttl[43200]  = "12 hours";
        $ttl[86400]  = "24 hours";
        $ttl[604800] = "1 week";
        // save
        $this->ttl = (object) $ttl;
    }

    /**
     * set_query_values function.
     *
     * @access public
     * @param int|string $limit (default: 1000000)
     * @param string $orderby (default: "id")
     * @param string $orderdir (default: "asc")
     */
    public function set_query_values ($limit = 1000000, $orderby = "id", $orderdir = "asc") {
        $this->limit    = $limit;           // number of results
        $this->orderby  = $orderby;         // order field
        $this->orderdir = $orderdir;        // $order direction
    }






    /* @domains ---------- */


    /**
     * Edit domain wrapper
     *
     * @access public
     * @param string $action
     * @param object|array $values
     * @return void
     */
    public function domain_edit ($action, $values) {
        # strip tags
        $values = $this->strip_input_tags ($values);

        # execute based on action
        if($action=="add")        { return $this->domain_add ($values); }
        elseif($action=="edit")   { return $this->domain_change ($values); }
        elseif($action=="delete") { return $this->domain_delete ($values); }
        else                      { return $this->Result->show("danger", _("Invalid action"), true); }
    }

    /**
     * Creates new domain
     *
     * @access private
     * @param object|array $values
     * @return bool
     */
    private function domain_add ($values) {
        # null empty values
        $values = $this->reformat_empty_array_fields ($values, null);

        # execute
        try { $this->Database_pdns->insertObject("domains", $values); }
        catch (Exception $e) {
            // write log
            $this->Log->write( _("PowerDNS domain create"), _("Failed to create PowerDNS domain").": ".$e->getMessage()."<hr>".$this->array_to_log((array) $values), 2);
            $this->Result->show("danger", _("Error: ").$e->getMessage(), false);
            return false;
        }
        // write log
        $this->Log->write( _("PowerDNS domain create"), _("New PowerDNS domain created").".<hr>".$this->array_to_log((array) $values), 0);
        # ok
        return true;
    }

    /**
     * Edits domain
     *
     * @access private
     * @param object|array $values
     * @return bool
     */
    private function domain_change ($values) {
        # null empty values
        $values = $this->reformat_empty_array_fields ($values, null);

        # execute
        try { $this->Database_pdns->updateObject("domains", $values); }
        catch (Exception $e) {
            // write log
            $this->Log->write( _("PowerDNS domain edit"), _("Failed to edit PowerDNS domain").": ".$e->getMessage()."<hr>".$this->array_to_log((array) $values), 2);
            $this->Result->show("danger", _("Error: ").$e->getMessage(), false);
            return false;
        }
        // write log
        $this->Log->write( _("PowerDNS domain edit"), _("PowerDNS domain edited")."<hr>".$this->array_to_log((array) $values), 0);
        # ok
        return true;
    }

    /**
     * Deletes domain
     *
     * @access private
     * @param object|array $values
     * @return bool
     */
    private function domain_delete ($values) {
        # save old
        $old_domain = $this->fetch_domain ($values['id']);
        # execute
        try { $this->Database_pdns->deleteRow("domains", "id", $values['id']); }
        catch (Exception $e) {
            // write log
            $this->Log->write( _("PowerDNS domain delete"), _("Failed to delete PowerDNS domain").": ".$e->getMessage()."<hr>".$this->array_to_log((array) $old_domain), 2);
            $this->Result->show("danger", _("Error: ").$e->getMessage(), false);
            return false;
        }
        // write log
        $this->Log->write( _("PowerDNS domain delete"), _("PowerDNS domain deleted").".<hr>".$this->array_to_log((array) $old_domain), 0);
        return true;
    }

    /**
     * Fetches all domains
     *
     * @access public
     * @return bool|array|object
     */
    public function fetch_all_domains () {
        # fetch
        try { $res = $this->Database_pdns->getObjects("domains", "id"); }
        catch (Exception $e) {
            $this->Result->show("danger", _("Error: ").$e->getMessage());
            return false;
        }
        # cache
        if (sizeof($res)>0) {
            foreach ($res as $r) { $this->domains_cache[$r->id] = $r; }
        }
        # result
        return sizeof($res)>0 ? $res : false;
    }

    /**
     * Fetches all forward domains
     *
     * @access public
     * @return bool|array|object
     */
    public function fetch_all_forward_domains () {
        # fetch
        try { $res = $this->Database_pdns->findObjects("domains", "name", "%.arpa", "name", true, true, true); }
        catch (Exception $e) {
            $this->Result->show("danger", _("Error: ").$e->getMessage());
            return false;
        }
        # cache
        if (sizeof($res)>0) {
            foreach ($res as $r) { $this->domains_cache[$r->id] = $r; }
        }
        # result
        return sizeof($res)>0 ? $res : false;
    }

    /**
     * Fetches all reverse IPv4 domains
     *
     * @access public
     * @return bool|array|object
     */
    public function fetch_reverse_v4_domains () {
        # fetch
        try { $res = $this->Database_pdns->findObjects("domains", "name", "%.in-addr.arpa", "name", true, true); }
        catch (Exception $e) {
            $this->Result->show("danger", _("Error: ").$e->getMessage());
            return false;
        }
        # cache
        if (sizeof($res) > 0) {
            foreach ($res as $r) { $this->domains_cache[$r->id] = $r; }
        }
        # result
        return sizeof($res) > 0 ? $res : false;
    }

    /**
     * Fetches all reverse IPv6 domains
     *
     * @access public
     * @return bool|array|object
     */
    public function fetch_reverse_v6_domains () {
        # fetch
        try { $res = $this->Database_pdns->findObjects("domains", "name", "%.ip6.arpa", "name", true, true); }
        catch (Exception $e) {
            $this->Result->show("danger", _("Error: ").$e->getMessage());
            return false;
        }
        # cache
        if (sizeof($res) > 0) {
            foreach ($res as $r) { $this->domains_cache[$r->id] = $r; }
        }
        # result
        return sizeof($res) > 0 ? $res : false;
    }

    /**
     * Fetches domain record by id (numberic) of name (varchar)
     *
     * @access public
     * @param string|int $id
     * @return bool|array|object
     */
    public function fetch_domain ($id) {
        # numeric of hostname
        return is_numeric($id) ? $this->fetch_domain_by_id ($id) : $this->fetch_domain_by_name ($id);
    }

    /**
     * Fetches domain details by id
     *
     * @access public
     * @param int|string $id
     * @return bool|array|object
     */
    public function fetch_domain_by_id ($id) {
        # chcek cache
        if (array_key_exists($id, $this->domains_cache)) { return $this->domains_cache[$id]; }

        # fetch
        try { $domain = $this->Database_pdns->getObject("domains", $id); }
        catch (Exception $e) {
            $this->Result->show("danger", _("Error: ").$e->getMessage());
            return false;
        }

        # cache
        $this->domains_cache[$domain->id] = $domain;

        # result
        return !is_null($domain) ? $domain : false;
    }

    /**
     * Fetches domain details by name
     *
     * @access public
     * @param mixed $name
     * @return array|false
     */
    public function fetch_domain_by_name ($name) {
        # fetch
        try { $domain = $this->Database_pdns->findObjects("domains", "name", $name); }
        catch (Exception $e) {
            $this->Result->show("danger", _("Error: ").$e->getMessage());
            return false;
        }

        # cache
        $this->domains_cache[$domain->id] = $domain;

        # result
        return is_object(($domain[0])) ? $domain[0] : false;
    }

    /**
     * Returns number of records for domain
     *
     * @access public
     * @param mixed $domain_id
     * @return void
     */
    public function count_domain_records ($domain_id) {
        // query
        $query = 'SELECT COUNT(*) AS `cnt` FROM `records` WHERE `domain_id` = ? AND `type` IS NOT NULL;';
        // fetch
        try { $records = $this->Database_pdns->getObjectsQuery($query, array($domain_id)); }
        catch (Exception $e) {
            $this->Result->show("danger", _("Error: ").$e->getMessage());
            return false;
        }
        # result
        return $records[0]->cnt;
    }

    /**
     * Conts number of records types
     *
     * @access public
     * @param mixed $domain_id
     * @param string $type (default: "PTR")
     * @return void
     */
    public function count_domain_records_by_type ($domain_id, $type="PTR") {
        // query
        $query = "select count(*) as `cnt` from `records` where `domain_id` = ? and `type` = ?;";
        // fetch
        try { $records = $this->Database_pdns->getObjectsQuery($query, array($domain_id, $type)); }
        catch (Exception $e) {
            $this->Result->show("danger", _("Error: ").$e->getMessage());
            return false;
        }
        # result
        return $records[0]->cnt;
    }









    /* records -------------------- */

    /**
     * Fetches all records for some domain
     *
     * @access public
     * @param mixed $domain_id
     * @return void
     */
    public function fetch_all_domain_records ($domain_id) {
        $query = "SELECT * FROM `records` WHERE `domain_id` = ? AND `type` IS NOT NULL ORDER BY $this->orderby $this->orderdir LIMIT $this->limit;";
        // fetch
        try { $records = $this->Database_pdns->getObjectsQuery($query, array($domain_id)); }
        catch (Exception $e) {
            $this->Result->show("danger", _("Error: ").$e->getMessage());
            return false;
        }
        # result
        return sizeof($records)>0 ? $records : false;
    }

    /**
     * Fetches all records by type.
     *
     * @access public
     * @param mixed $domain_id
     * @param mixed $type
     * @return void
     */
    public function fetch_domain_records_by_type ($domain_id, $type) {
        // query
        $query = "select * from `records` where `domain_id` = ? and `type` = ? order by $this->orderby $this->orderdir limit $this->limit;";
        // fetch
        try { $records = $this->Database_pdns->getObjectsQuery($query, array($domain_id, $type)); }
        catch (Exception $e) {
            $this->Result->show("danger", _("Error: ").$e->getMessage());
            return false;
        }
        # result
        return sizeof($records)>0 ? $records : false;
    }

    /**
     * Fetches record from database
     *
     * @access public
     * @param int|string $record_id
     * @return object|boolean
     */
    public function fetch_record ($record_id) {
        // validate int
        $this->validate_integer ($record_id);
        // fetch
        try { $record = $this->Database_pdns->getObject("records", $record_id); }
        catch (Exception $e) {
            $this->Result->show("danger", _("Error: ").$e->getMessage());
            return false;
        }
        # result
        return !is_null($record) ? $record : false;
    }

    /**
     * Searches records for specific domainid for type and name values
     *
     * @access public
     * @param mixed $domain_id
     * @param mixed $type
     * @param mixed $name
     * @return object|array|boolean
     */
    public function search_record_domain_type_name ($domain_id, $type, $name) {
        // query
        $query = "select * from `records` where `domain_id` = ? and `type` = ? and `name` = ? limit 1;";
        // fetch
        try { $records = $this->Database_pdns->getObjectQuery($query, array($domain_id, $type, $name)); }
        catch (Exception $e) {
            $this->Result->show("danger", _("Error: ").$e->getMessage());
            return false;
        }
        # result
        return sizeof($records)>0 ? $records : false;
    }

    /**
     * Searches for domain record
     *
     * @access public
     * @param string $field (default: "content")
     * @param mixed $value (default: null)
     * @param string $sortField (default: 'id')
     * @param bool $sortAsc (default: true)
     * @return void
     */
    public function search_records ($field = "content", $value = null, $sortField = 'id', $sortAsc = true) {
        // fetch
        try { $records = $this->Database_pdns->findObjects("records", $field, $value, $sortField, $sortAsc); }
        catch (Exception $e) {
            $this->Result->show("danger", _("Error: ").$e->getMessage());
            return false;
        }
        # result
        return sizeof($records)>0 ? $records : false;
    }

    /**
     * Search aliases for specific hostname
     *
     * @access public
     * @param mixed $hostname
     * @return void
     */
    public function seach_aliases ($hostname) {
        // fetch
        try { $records = $this->Database_pdns->findObjects("records", "content", $hostname, "content", "asc"); }
        catch (Exception $e) {
            $this->Result->show("danger", _("Error: ").$e->getMessage());
            return false;
        }
        // remove not CNAME
        if (sizeof($records)>0) {
            foreach ($records as $k=>$r) {
                if ($r->type!=="CNAME") {
                    unset($records[$k]);
                }
            }
        }
        # result
        return sizeof($records)>0 ? $records : false;
    }

    /**
     * Returns unique IP addresses from content field in pdns table
     *
     * @access public
     * @return void
     */
    public function search_unique_ips () {
        // query
        $query = "select DISTINCT(`content`) from records WHERE INET_ATON(`content`) IS NOT NULL or `content` LIKE '%:%';";
         // search
        try { $records = $this->Database_pdns->getObjectsQuery($query); }
        catch (Exception $e) {
            $this->Result->show("danger", _("Error: ").$e->getMessage());
            return false;
        }
        # result
        return sizeof($records)>0 ? $records : false;
    }

    /**
     * Edit PowerDNS record
     *
     * @access public
     * @param mixed $action
     * @param array $values
     * @return bool
     */
    public function record_edit ($action, $values) {
        # strip tags
        $values = $this->strip_input_tags ($values);

        # execute based on action
        if($action=="add")          { return $this->add_domain_record ($values); }
        elseif($action=="edit")     { return $this->update_domain_record ($values['domain_id'], $values); }
        elseif($action=="delete")   { return $this->remove_domain_record ($values['domain_id'], $values['id']); }
        else                        { return $this->Result->show("danger", _("Invalid action"), true); }
    }


    /**
     * Create new record
     *
     * @access public
     * @param array|object $record
     * @param bool $print_success
     * @return bool
     */
    public function add_domain_record ($record, $print_success = true) {
        # null empty values
        $record = $this->reformat_empty_array_fields ($record, null);

        # checks
        $this->validate_record_content ($record);

        # execute
        try { $this->Database_pdns->insertObject("records", $record); }
        catch (Exception $e) {
            // write log
            $this->Log->write( _("PowerDNS record create"), _("Failed to create PowerDNS domain record").": ".$e->getMessage()."<hr>".$this->array_to_log((array) $record), 2);
            $this->Result->show("danger", _("Error: ").$e->getMessage(), true);
            return false;
        }
        // write log
        $this->Log->write( _("PowerDNS record create"), _("New PowerDNS domain record created").".<hr>".$this->array_to_log((array) $record), 0);

        # print ?
        if ($print_success) {
            if ($this->api!==true) {
                $this->Result->show("success", _("Record created"));
            }
        }
        // save id
        $this->lastId = $this->Database_pdns->lastInsertId ();
        # soa update
        $this->update_soa_serial ($record['domain_id']);
        # ok
        return true;
    }

    /**
     * Updates domain record and SOA serial
     *
     * @access public
     * @param mixed $domain_id
     * @param mixed (array) $content
     * @param bool $print_success
     * @return bool
     */
    public function update_domain_record ($domain_id, $content, $print_success=true) {
        // validate domain
        if ($this->fetch_domain ($domain_id)===false)    { $this->Result->show("danger", _("Invalid domain id"), true); }

        # checks
        $this->validate_record_content ($record);

        // remove domain_id if set !
        unset($content->domain_id);

        // remove record
        $this->update_domain_record_content ($content);
        // save id
        $this->lastId = $this->Database_pdns->lastInsertId ();

        // update SOA serial
        $this->update_soa_serial ($domain_id);

        # print ?
        if ($print_success) {
            if ($this->api!==true) {
                $this->Result->show("success", _("Record updated"));
            }
        }
        // ok
        return true;
    }

    /**
     * Removes domain record
     *
     * @access public
     * @param mixed $domain_id
     * @param mixed $record_id
     * @param bool $print_success
     * @return bool
     */
    public function remove_domain_record ($domain_id, $record_id, $print_success=true) {
        // validate domain
        if ($this->fetch_domain ($domain_id)===false)    { $this->Result->show("danger", _("Invalid domain id"), true); }
        // remove record
        $this->remove_domain_record_by_id ($record_id);
        // update SOA serial
        $this->update_soa_serial ($domain_id);

        # print ?
        if ($print_success) {
            if ($this->api!==true) {
                $this->Result->show("success", _("Record deleted"));
            }
        }

        // ok
        return true;
    }

    /**
     * Removes specified domain record by id
     *
     * @access public
     * @param mixed $id
     * @return bool
     */
    public function remove_domain_record_by_id ($id) {
        # fetch old records
        $old_record = $this->fetch_record ($id);
        # execute
        try { $this->Database_pdns->deleteRow("records", "id", $id); }
        catch (Exception $e) {
            // write log
            $this->Log->write( _("PowerDNS record delete"), _("Failed to delete PowerDNS domain record").": ".$e->getMessage()."<hr>".$this->array_to_log((array) $old_record), 2);
            $this->Result->show("danger", _("Error: ").$e->getMessage(), true);
            return false;
        }
        // write log
        $this->Log->write( _("PowerDNS record delete"), _("PowerDNS domain record deleted").".<hr>".$this->array_to_log((array) $old_record), 0);

        # ok
        return true;
    }

    /**
     * Remove all records for IP address and hostname.
     *
     * @access public
     * @param mixed $hostname
     * @param mixed $ip
     * @return void
     */
    public function pdns_remove_ip_and_hostname_records ($hostname, $ip) {
         // set query
        $query = "delete from `records` where (`name` = ? or `content` = ?) and `type` != 'NS' and `type` != 'SOA';";

        // run
		try { $this->Database_pdns->runQuery($query, array($hostname, $ip)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		#result
		return true;
    }

    /**
     * Updates content of specific record
     *
     * @access public
     * @param array|object $content
     * @return bool
     */
    public function update_domain_record_content ($content) {
        # execute
        try { $this->Database_pdns->updateObject("records", $content); }
        catch (Exception $e) {
            // write log
            $this->Log->write( _("PowerDNS record update"), _("Failed to update PowerDNS domain record").": ".$e->getMessage()."<hr>".$this->array_to_log((array) $content), 2);
            $this->Result->show("danger", _("Error: ").$e->getMessage(), true);
            return false;
        }
        // write log
        $this->Log->write( _("PowerDNS record update"), _("PowerDNS domain record updated").".<hr>".$this->array_to_log((array) $content), 0);
        # ok
        return true;
    }

    /**
     * Updates SOA serial
     *
     * @access private
     * @param int|string $domain_id
     * @param mixed $serial
     * @return void
     */
    private function update_soa_serial ($domain_id, $serial = false) {
        // fetch record
        $soa = $this->fetch_domain_records_by_type ($domain_id, "SOA");
        // if not set exit
        if ($soa === false)            { return false; }
        else                        { $soa = $soa[0]; }

        // update serial it not autoserial
        $soa_serial = explode(" ", $soa->content);
        $soa_serial[2] = $this->db_settings->autoserial=="Yes" ? 0 : (int) $soa_serial[2]+1;

        // if serail set override it
        if($serial!==false)         { $soa_serial[2] = $serial; }

        // set update content
        $content = array(
                        "id"=>$soa->id,
                        "content"=>implode(" ", $soa_serial),
                        "change_date"=>$soa_serial[2]
                        );
        // update
        $this->update_domain_record_content ($content);
    }

    /**
     * Updates all SOA serials if it changes from autoserial false to true
     *
     * @access public
     * @param bool $autoserial (Default : No)
     * @return void
     */
    public function update_all_soa_serials ($autoserial = "No") {
        // fetch all domains
        $all_domains = $this->fetch_all_domains ();
        // set new serial
        $serial = $autoserial==="Yes" ? 0 : date("Ymd")."01";
        // set new serial
        if ($all_domains !== false) {
            foreach ($all_domains as $d) {
                $this->update_soa_serial ($d->id, $serial);
            }
        }
    }

    /**
     * Create default records for domain if requested
     *
     *    - SOA record (primary hostmaster serial refresh retry expire default_ttl)
     *    - One entry for each NS
     *
     * @access public
     * @param array $values
     * @param bool $checkOnly
     * @return void
     */
    public function create_default_records ($values, $checkOnly = false) {
        // get last id
        $this->lastId = $checkOnly ? 0 : $this->Database_pdns->lastInsertId ();
        // set defaults
        $this->db_set_db_settings ();

        // content
        $soa   = array();
        $soa[] = array_shift(explode(";", $values['ns']));
        $soa[] = str_replace ("@", ".", $values['hostmaster']);
        $soa[] = $this->set_default_change_date ();
        $soa[] = $this->validate_refresh ($values['refresh']);
        $soa[] = $this->validate_integer ($values['retry']);
        $soa[] = $this->validate_integer ($values['expire']);
        $soa[] = $this->validate_nxdomain_ttl ($values['nxdomain_ttl']);

        // formulate SOA value
        $records[] = $this->formulate_new_record ($this->lastId, $values['name'], "SOA", implode(" ", $soa), $values['ttl'], null, 0, $checkOnly);

        // formulate NS records
        $ns = explode(";", $values['ns']);
        if (sizeof($ns)>0) {
            foreach($ns as $s) {
                // validate
                if($this->validate_hostname($s)===false)        { $this->Result->show("danger", _("Invalid NS")." $s", true); }
                // save
                $records[] = $this->formulate_new_record ($this->lastId, $values['name'], "NS", $s, $values['ttl'], null, 0, $checkOnly);
            }
        }

        // if only check return true
        if ($checkOnly) { return true; }

        // create records
        foreach($records as $r) {
            $this->add_domain_record ($r, false);
        }
        // all good, print it !
        $this->Result->show("success", _("Default records created"), false);
    }

    /**
     * Creates object with values for new record
     *
     * @access public
     * @param mixed $domain_id
     * @param mixed $name
     * @param mixed $type
     * @param mixed $content
     * @param mixed $ttl
     * @param mixed $prio (default: null)
     * @param int|string $disabled (default: 0)
     * @param bool $dont_validate_domain (default: false)
     * @return array
     */
    public function formulate_new_record ($domain_id, $name, $type, $content, $ttl, $prio=null, $disabled = 0, $dont_validate_domain = false) {
        // initiate class
        $record = new StdClass ();
        // set record details
        $record->domain_id   = $dont_validate_domain ? $domain_id : $this->validate_record_domain_id ($domain_id);      // sets domain id
        $record->name        = $this->validate_record_name ($name, $type);                                                     // record name
        $record->type        = $this->validate_record_type ($type);                                                     // record type
        $record->content     = $content;                                                                                // record content
        $record->ttl         = $this->validate_ttl ($ttl);                                                              // ttl validation
        $record->prio        = $this->validate_prio ($prio);                                                            // priority, default NULL
        $record->change_date = $this->set_default_change_date ();                                                       // sets default change date
        $record->disabled    = $disabled;                                                                               // enables of disables record
        // return record
        return (array) $record;
    }

    /**
     * Validates edit of record
     *
     * @access public
     * @param mixed $name (default: null)
     * @param mixed $type (default: null)
     * @param mixed $content (default: null)
     * @param mixed $ttl (default: null)
     * @param mixed $prio (default: null)
     * @param int|string $disabled (default: 0)
     * @param mixed $old_date (default: null)
     * @return array
     */
    public function formulate_update_record ($name=null, $type=null, $content=null, $ttl=null, $prio=null, $disabled=null, $old_date=null) {
        // initiate class
        $record = new StdClass ();
        // set record details
        if (!is_null($name))    $record->name        = $this->validate_record_name ($name, $type);               // record name
        if (!is_null($type))    $record->type        = $this->validate_record_type ($type);               // record type
        if (!is_null($content)) $record->content     = $content;                                          // record content
        if (!is_null($ttl))     $record->ttl         = $this->validate_ttl ($ttl);                        // ttl validation
        if (!is_null($prio))    $record->prio        = $this->validate_prio ($prio);                      // priority, default NULL
                                $record->change_date = $this->update_record_change_date ($old_date);      // updates change date
        if (!is_null($disabled))$record->disabled    = $disabled;                                         // enables of disables record
        // return record
        return (array) $record;
    }

    /**
     * Validates record content.
     *
     * @access private
     * @param mixed $record
     * @return void
     */
    private function validate_record_content ($record) {
        // to object
        $record = (object) $record;

        // a record check
        if($record->type=="A") {
            if (filter_var($record->content, FILTER_VALIDATE_IP) === false) {
                $this->Result->show("danger", _("A record must be an IP address"), true);
            }
        }
        // AAA records check
        elseif($record->type=="AAAA") {
            if (filter_var($record->content, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
                $this->Result->show("danger", _("AAAA record must be an IP address"), true);
            }
        }
    }

    /**
     * Validate domain id
     *
     *    - must be an integer
     *    - domain must already exist
     *
     * @access private
     * @param mixed $domain_id
     * @return void
     */
    private function validate_record_domain_id ($domain_id) {
        // integer
        if (!is_numeric($domain_id))                    { $this->Result->show("danger", _("Domain id must be an integer"), true); }
        // check for domain record
        if ($this->fetch_domain ($domain_id)===false)   { $this->Result->show("danger", _("Domain does not exist"), true); }
        // ok
        return $domain_id;
    }

    /**
     * Validates record name
     *
     *    - if not null validate hostname
     *
     * @access private
     * @param mixed $name
     * @return void
     */
    private function validate_record_name ($name, $type = NULL) {
        // certain record types allow forbidden characters in record name
        // when using reserved words
        if ($type == "TXT") {
            if (preg_match("/^_dmarc.*$/", $name)
                && preg_match("/^.{1,253}$/", $name)                               //overall length check
                && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $name))           //length of each label)
            { return $name; }
            if (preg_match("/^.*_domainkey.*$/", $name)
                && preg_match("/^.{1,253}$/", $name)                               //overall length check
                && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $name))           //length of each label)
            { return $name; }
        }

        // DNS wildcard records are OK (https://tools.ietf.org/html/rfc4592#section-2.1.1)
        if (preg_match("/^\*\..*$/", $name) && $this->validate_hostname(substr($name, 2))) {
            return $name;
        }

        // for all other record types null is ok, otherwise URI is required
        if (strlen($name)>0 && !$this->validate_hostname($name)){ $this->Result->show("danger", _("Invalid record name"), true); }
        // ok
        return $name;
    }

    /**
     * Validates record type
     *
     *    - check against permitted record types
     *
     * @access private
     * @param mixed $type
     * @return void
     */
    private function validate_record_type ($type) {
        // if set check, otherwise ognore
        if(isset($type)) {
            // check record type
            if(!in_array($type, (array) $this->record_types))    { $this->Result->show("danger", _("Invalid record type"), true); }
        }
        // ok
        return $type;
    }

    /**
     * Validate TTL value
     *
     *    - numeric
     *    - between 0 and 2147483647
     *
     * @access private
     * @param mixed $ttl
     * @return void
     */
    private function validate_ttl ($ttl) {
        // check numberfic
        if(!is_numeric($ttl))                            { $this->Result->show("danger", _("Invalid TTL"), true); }
        // check range
        if(0 > $ttl || $ttl > 2147483647)                { $this->Result->show("danger", _("TTL range is from 0 to 2147483647"), true); }
        // ok
        return $ttl;
    }

    /**
     * Validates nxdomain ttl
     *
     * @access private
     * @param mixed $ttl
     * @return void
     */
    private function validate_nxdomain_ttl ($ttl) {
        // check numberfic
        if(!is_numeric($ttl))                            { $this->Result->show("danger", _("Invalid NXDOMAIN TTL"), true); }
        // check range
        if(0 > $ttl || $ttl > 10800)                     { $this->Result->show("danger", _("NXDOMAIN TTL range is from 0 to 10800"), true); }
        // ok
        return $ttl;
    }

    /**
     * Validate refresh SOA value
     *
     * @access private
     * @param mixed $refresh
     * @return void
     */
    private function validate_refresh ($refresh) {
        // check numberfic
        if(!is_numeric($refresh))                        { $this->Result->show("danger", _("Invalid refresh TTL"), true); }
        // check range
        if(1200 > $refresh || $refresh > 2147483647)     { $this->Result->show("danger", _("Refresh TTL range is from 1200 to 2147483647"), true); }
        // ok
        return $refresh;
    }

    /**
     * Validates priority
     *
     * @access private
     * @param mixed $prio
     * @return void
     */
    private function validate_prio ($prio) {
        // validate numbric
        if(!is_null($prio) && strlen($prio)>0) {
            if(!is_numeric($prio))                        { $this->Result->show("danger", _("Invalid priority value"), true); }
            // range
            if(0 > $prio || $prio > 1000)                 { $this->Result->show("danger", _("Priority range is from 0 to 1000"), true); }
        }
        // ok
        return $prio;
    }

    /**
     * Validates integer
     *
     * @access private
     * @param mixed $int
     * @return void
     */
    private function validate_integer ($int) {
        // validate numbric
        if(strlen($int)>0 && !is_null($int) && $int!==false) {
            if(!is_numeric($int))                        { $this->Result->show("danger", _("Invalid integer value"), true); }
        }
        // ok
        return $int;
    }

    /**
     * Sets default change date for record
     *
     *    - 2015032701
     *
     * @access private
     * @return void
     */
    private function set_default_change_date () {
        return date("Ymd")."00";
    }

    /**
     * Updates change date for record
     *
     * @access private
     * @param mixed $current_date (default: null)
     * @return void
     */
    private function update_record_change_date ($current_date=null) {
        // not set
        if (is_null($current_date))        { return $this->set_default_change_date (); }

        // split to date / sequence
        $date = substr($current_date, 0,8);

        // date same ++, otherwise default
        if ($date==date('Ymd'))            { return $current_date+1; }
        else                               { return $this->set_default_change_date (); }
    }

    /**
     * Updates all existing domain records if domain name changes !
     *
     * @access public
     * @param mixed $domain_id
     * @param mixed $old_name
     * @param mixed $name
     * @return void
     */
    public function update_all_records ($domain_id, $old_name, $name) {
        // execute
        try { $this->Database_pdns->runQuery("update `records` set `name` = replace(`name`, ?, ?) where where `domain_id` = ?;", array($old_name, $name, $domain_id)); }
        catch (Exception $e) {
            // write log
            $this->Result->show("danger", _("Error: ").$e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Removes all domain records
     *
     * @access public
     * @param mixed $domain_id
     * @return bool
     */
    public function remove_all_records ($domain_id) {
        // execute
        try { $this->Database_pdns->runQuery("delete from `records` where `domain_id` = ?;", array($domain_id)); }
        catch (Exception $e) {
            // write log
            $this->Log->write( _("PowerDNS domain truncate"), _("Failed to remove all PowerDNS domain records").": ".$e->getMessage()."<hr>".$this->array_to_log((array) $domain_id), 2);
            $this->Result->show("danger", _("Error: ").$e->getMessage());
            return false;
        }
        // write log
        $this->Log->write( _("PowerDNS domain truncate"), _("PowerDNS domain records truncated").".<hr>".$this->array_to_log((array) $domain_id), 0);

        # ok
        $this->Result->show("success", _("All records for domain removed"));
        return true;
    }











    /* @PTR reverse functions -------------------- */

    /**
     * Returns PTR zone from ip address / subnet based on IP version
     *
     * @access public
     * @param mixed $ip
     * @return void
     */
    public function get_ptr_zone_name ($ip, $mask) {
        return $this->identify_address ($ip)=="IPv4" ? $this->get_ptr_zone_name_v4 ($ip, $mask) : $this->get_ptr_zone_name_v6 ($ip, $mask);
    }

    /**
     * Returns PTR zone for IPv4 records
     *
     * @access public
     * @param mixed $ip
     * @return void
     */
    public function get_ptr_zone_name_v4 ($ip, $mask) {
        // check mask to see how many IP bits to remove
        $bits = $mask<24 ? 2 : 1;

        // to array
        $zone = explode(".", $ip);

        // create name
        if ($bits==1)    { return $zone[2].".".$zone[1].".".$zone[0].".in-addr.arpa"; }
        else            { return $zone[1].".".$zone[0].".in-addr.arpa"; }
    }

    /**
     * Returns PTR zone for IPv6 record
     *
     * @access public
     * @param mixed $ip
     * @return void
     */
    public function get_ptr_zone_name_v6 ($ip, $mask) {
        $ipp = inet_pton($ip);
        $maskbin = str_repeat('1', $mask) . str_repeat('0', 128 - $mask);
        $maskhex = '';
        foreach (str_split($maskbin, 4) as $chunk) {
    	    $maskhex .= base_convert($chunk, 2, 16);
        }
        $maskp = inet_pton(substr(chunk_split($maskhex, 4, ':'), 0, -1));

        $networkp = $ipp & $maskp;
        $ipt = '';
        foreach(str_split($networkp) as $char) $ipt .= str_pad(dechex(ord($char)), 2, '0', STR_PAD_LEFT);
        $prefixnibbles = floor($mask / 4);
        $network = substr($ipt, 0, $prefixnibbles);
        $zone = array_reverse(str_split($network));

        return implode(".", $zone).".ip6.arpa";
    }

    /**
     * Set name for PTR host record
     *
     * @access public
     * @param mixed $ip
     * @return void
     */
    public function get_ip_ptr_name ($ip) {
        // set zone prefix and reverse content
        if ($this->identify_address ($ip)=="IPv4") {
            $prefix = ".in-addr.arpa";
            $zone = array_reverse(explode(".", $ip));
        }
        else {
            // PEAR for IPv6
            $this->initialize_pear_net_IPv6 ();
            // uncompress and remove netmask
            $ip = $this->Net_IPv6->uncompress($ip);
            $ip = $this->Net_IPv6->removeNetmaskSpec($ip);

            // to array
            $ip = explode(":", $ip);

            // if 0 than add 4 nulls
            foreach ($ip as $k=>$i) {
                $ip[$k] = str_pad($i, 4, "0", STR_PAD_LEFT);
            }

            $ip = str_split(implode("", $ip));
            $prefix = ".ip6.arpa";
            $zone = array_reverse($ip);
        }
        // return
        return implode(".", $zone).$prefix;
    }

    /**
     * Checks if record exists
     *
     * @access public
     * @param mixed $domain_id
     * @param mixed $name
     * @param mixed $type
     * @param mixed $content
     * @return bool
     */
    public function record_exists ($domain_id, $name, $type, $content) {
        // execute
        try { $res = $this->Database_pdns->getObjectQuery("select count(*) as `cnt` from `records` where `domain_id` = ? and `name`=? and `type`=? and `content`=?;", array($domain_id, $name, $type, $content)); }
        catch (Exception $e) {
            $this->Result->show("danger", _("Error: ").$e->getMessage());
            return false;
        }
        # ok
        return $res->cnt >0 ? true : false;
    }

    /**
     * Checks if record exists by id
     *
     * @access private
     * @param mixed $ptr_id (default: 0)
     * @return bool
     */
    public function record_id_exists ($ptr_id = 0) {
        # 0 or dalse
        if (@$ptr_id==0 || $ptr_id===false)    { return false; }

        # fetch
        try { $count = $this->Database_pdns->numObjectsFilter("records", "id", $ptr_id); }
        catch (Exception $e) {
            $this->Result->show("danger", _("Error: ").$e->getMessage());
            return false;
        }
        # result
        return $count==0 ? false : true;
    }

    /**
     * Removes all PTR records for subnet
     *
     * @access public
     * @param mixed $domain_id
     * @param mixed $indexes (array of PTR indexes)
     * @return bool
     */
    public function remove_all_ptr_records ($domain_id, $indexes = array()) {
        // if false return ok and dont execute
        if (sizeof($indexes)==0 || !is_array($indexes)) {
            return true;
        }
         // set parameters - default
        $params = array($domain_id);
        // set query - start
        $query  = "delete from `records` where `domain_id` = ? and `type` = 'PTR'";
        // loop
        if (sizeof($indexes)>0) {
            $q_tmp = array();
            $query .= " and (";
            foreach ($indexes as $i) {
                $q_tmp[] = " `id` = ? ";
                $params[] = $i;
            }
            // add to query
            $query .= implode(" or ", $q_tmp);
            $query .= ");";
        }

        // execute
        try { $this->Database_pdns->runQuery($query, $params); }
        catch (Exception $e) {
            // write log
            $this->Log->write( _("PowerDNS records delete"), _("Failed to delete all PowerDNS domain PTR records").": ".$e->getMessage()."<hr>".$this->array_to_log((array) $domain_id), 2);
            $this->Result->show("danger", _("Error: ").$e->getMessage());
            return false;
        }
        // write log
        $this->Log->write( _("PowerDNS records delete"), _("All PTR records for PowerDNS removed").".<hr>".$this->array_to_log((array) $domain_id), 0);
        # ok
        return true;
    }

}
