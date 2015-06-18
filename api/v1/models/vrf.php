<?php

/**
 *	phpIPAM Vrf class
 */

class Vrf
{
	# variables
	var $query;				// db query
	var $result;			// array result from database

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
	}


	/**
	 *	fetch form database
	 */
	private function fetchArray()
	{
		try { $this->result = $this->Database->getArray( $this->query ); }
	    catch (Exception $e) 													{ throw new Exception($e->getMessage()); }
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
	 * 	get Vrf
	 */
	public function readVrf()
	{
		/* check input */
		$this->Common->check_input;

		/* all Vrfs */
		if($this->all) {
			//set query
			$this->query = "select * from `vrf`;";
			$this->fetchArray();
			if(sizeof($this->result)==0) 										{ throw new Exception('No vrfs available'); }
		}
		/* Vrf by id */
		elseif($this->id) {
			//set query
			$this->query = "select * from `vrf` where `vrfId` = ".$this->id.";";
			$this->fetchArray();
			if(sizeof($this->result)==0) 										{ throw new Exception('Invalid vrf Id '.$this->id); }
		}
		/* method missing */
		else 																	{ throw new Exception('Selector missing'); }

		//convert object to array
		$result = $this->Common->toArray($this->result);

		//do we need also subnets?
		if($this->id && $this->subnets) {
			//set query
			$this->query = "select * from `subnets` where `vrfId` = ".$this->id.";";
			$this->fetchArray();

			$result['subnets'] = $this->Common->toArray($this->result);
		}

		//return result
		return $result;
	}


	/**
	 *	delete Vrf
	 *		id must be provided
	 */
	public function deleteVrf ()
	{
		//check input
		$this->Common->check_var ("int", $this->id, null);

		//verify that it exists
		try { $this->readVrf(); }
		catch (Exception $e) 													{ throw new Exception($e->getMessage()); }

		//set query to delete Vrf and execute
		$this->query = "delete from `vrf` where `vrfId` = ".$this->id.";";
		$this->executeQuery();

		//set result
		$result['result']   = "success";
		$result['response'] = "Vrf id ".$this->id." deleted successfully!";

		//return result
		return $result;
	}

}
