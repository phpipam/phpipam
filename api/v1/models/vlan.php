<?php

/**
 *	phpIPAM Vlan class
 */

class Vlan
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
	 * 	get Vlan
	 */
	public function readVlan()
	{
		/* check input */
		$this->Common->check_input;

		/* all Vlans */
		if($this->all) {
			//set query
			$this->query = "select * from `vlans`;";
			$this->fetchArray();
			if(sizeof($this->result)==0) 										{ throw new Exception('No Vlans available'); }
		}
		/* Vlan by id */
		elseif($this->id) {
			//set query
			$this->query = "select * from `vlans` where `vlanId` = ".$this->id.";";
			$this->fetchArray();
			if(sizeof($this->result)==0) 										{ throw new Exception('Invalid vlan Id '.$this->id); }
		}
		/* method missing */
		else 																	{ throw new Exception('Selector missing'); }

		//convert object to array
		$result = $this->Common->toArray($this->result);

		//do we need also subnets?
		if($this->id && $this->subnets) {
			//set query
			$this->query = "select * from `subnets` where `vlanId` = ".$this->id.";";
			$this->fetchArray();

			$result['subnets'] = $this->Common->toArray($this->result);
		}

		//return result
		return $result;
	}


	/**
	 *	delete Vlan
	 *		id must be provided
	 */
	public function deleteVlan ()
	{
		//check input
		$this->Common->check_var ("int", $this->id, null);

		//verify that it exists
		try { $this->readVlan(); }
		catch (Exception $e) 													{ throw new Exception($e->getMessage()); }

		//set query to delete Vlan and execute
		$this->query = "delete from `vlans` where `vlanId` = ".$this->id.";";
		$this->executeQuery();

		//set result
		$result['result']   = "success";
		$result['response'] = "Vlan id ".$this->id." deleted successfully!";

		//return result
		return $result;
	}

}
