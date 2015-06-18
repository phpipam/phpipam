<?php

/**
 *	phpIPAM Address class
 */

class Address
{
	# variables
	var $query;				// db query
	var $result;			// array result from database
	var $format;			// set IP format

	# classes
	var $Database;			// to store database object
	var $Common;			// Common functions


	/**
	 * 	construct
	 */
	public function __construct()
	{
		# initialize database class
		require( dirname(__FILE__) . '/../../../config.php' );
		$this->Database = new database($db['host'], $db['user'], $db['pass'], $db['name'], NULL, false);
		# common class
		$this->Common = new Common;
		# set defauld method
		$this->format = "decimal";
	}


	/**
	 *	fetch form database
	 */
	private function fetchArray()
	{
		try { $this->result = $this->Database->getArray( $this->query ); }
	    catch (Exception $e) 											{ throw new Exception($e->getMessage()); }
	}


	/**
	 *	execute query
	 */
	private function executeQuery()
	{
		try { $this->result = $this->Database->executeQuery( $this->query ); }
	    catch (Exception $e) 													{ throw new Exception($e->getMessage()); }
	}


	/**
	 * 	get IP addresses
	 */
	public function readAddress()
	{
		/* check input */
		$this->Common->check_input;

		/* all addresses in subnet */
		if($this->subnetId) {
			//set query
			$this->query = "select * from `ipaddresses` where `subnetId` = ".$this->subnetId.";";
			$this->fetchArray();
			if(sizeof($this->result)==0) 								{ throw new Exception('No addresses'); }
		}
		/* address by id */
		elseif($this->id) {
			//set query
			$this->query = "select * from `ipaddresses` where `id` = ".$this->id.";";
			$this->fetchArray();
			if(sizeof($this->result)==0) 								{ throw new Exception('Invalid IP address Id '.$this->id); }
		}
		/* method missing */
		else 															{ throw new Exception('Selector missing'); }

		//convert object to array
		$result = $this->Common->toArray($this->result);

		//reformat?
		if($this->format == "ip") { $result = $this->Common->format_ip($result); }

		//return result
		return $result;
	}


	/**
	 *	delete Address
	 *		id must be provided
	 */
	public function deleteAddress ()
	{
		//check input
		$this->Common->check_var ("int", $this->id, null);

		//verify that it exists
		try { $this->readAddress(); }
		catch (Exception $e) 											{ throw new Exception($e->getMessage()); }

		//set query and execute
		$this->query = "delete from `ipaddresses` where `id` = ".$this->id.";";
		$this->executeQuery();

		//set result
		$result['result']   = "success";
		$result['response'] = "Address ".$this->id." deleted successfully!";

		//return result
		return $result;
	}

}
