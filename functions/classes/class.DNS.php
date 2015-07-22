<?php

/**
 *	phpIPAM DNS class to manage DNS-related dunctions
 *
 */

class DNS  {

	/**
	 * private variables
	 */
	protected $settings = false;				//settings

	/**
	 * object holders
	 */
	protected $Result;						//for Result printing
	protected $Database;					//for Database connection



	/**
	 * __construct method
	 *
	 * @access public
	 * @return void
	 */
	public function __construct (Database_PDO $Database, $settings=null) {
		# initialize Result
		$this->Result = new Result ();
		# initialize object
		$this->Database = $Database;
		# settings
		if(!is_null($settings)) { $this->settings = $settings; }
	}


	/**
	 * fetches settings from database
	 *
	 * @access private
	 * @return void
	 */
	protected function get_settings () {
		# cache check
		if($this->settings == false) {
			try { $this->settings = $this->Database->getObject("settings", 1); }
			catch (Exception $e) { $this->Result->show("danger", _("Database error: ").$e->getMessage()); }
		}
	}










	/**
	* @resolve address methods
	* -------------------------------
	*/


 	/**
 	 * Resolves hostname
 	 *
 	 * @access public
 	 * @param mixed $address		address object
 	 * @param boolena $override		override DNS resolving flag
 	 * @return void
 	 */
 	public function resolve_address ($address, $override=false) {
	 	# settings
	 	$this->get_settings();
	 	# addresses object
	 	$Address = new Addresses ($this->Database);
	 	# make sure it is dotted format
	 	$address->ip = $Address->transform_address ($address->ip_addr, "dotted");
		# if dns_nameis set try to check
		if(empty($address->dns_name) || is_null($address->dns_name)) {
			# if permitted in settings
			if($this->settings->enableDNSresolving == 1 || $override) {
				# resolve
				$resolved = gethostbyaddr($address->ip);
				if($resolved==$address->ip)		$resolved="";			//resolve fails

				return array("class"=>"resolved", "name"=>$resolved);
			}
			else {
				return array("class"=>"", "name"=>"");
			}
		}
		else {
				return array("class"=>"", "name"=>$address->dns_name);
		}
 	}

}