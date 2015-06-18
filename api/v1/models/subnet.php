<?php

/**
 *	phpIPAM Subnet class
 */

class Subnet
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
	 * 	get Subnet
	 */
	public function readSubnet()
	{
		/* check input */
		$this->Common->check_input;

		/* all subnets */
		if($this->all) {
			//set query
			$this->query = "select * from `subnets`;";
			$this->fetchArray();
			if(sizeof($this->result)==0) 										{ throw new Exception('No subnets available'); }
		}
		/* subnet by id */
		elseif($this->id) {
			//set query
			$this->query = "select * from `subnets` where `id` = ".$this->id.";";
			$this->fetchArray();
			if(sizeof($this->result)==0) 										{ throw new Exception('Invalid subnet Id '.$this->id); }
		}
		/* all subnets in section */
		elseif($this->sectionId) {
			//set query
			$this->query = "select * from `subnets` where `sectionId` = ".$this->sectionId.";";
			$this->fetchArray();
			if(sizeof($this->result)==0) 										{ throw new Exception('Invalid section Id or no subnets '.$this->sectionId); }
		}
		/* method missing */
		else 																	{ throw new Exception('Selector missing'); }

		//convert object to array
		$result = $this->Common->toArray($this->result);

		//reformat?
		if($this->format == "ip") { $result = $this->Common->format_ip($result); }

		//return result
		return $result;
	}


	/**
	 *	delete Subnet
	 *		id must be provided
	 *
	 *		we must also delete all IP addresses if requested!
	 */
	public function deleteSubnet ()
	{
		//check input
		$this->Common->check_var ("int", $this->id, null);

		//verify that it exists
		try { $this->readSubnet(); }
		catch (Exception $e) 													{ throw new Exception($e->getMessage()); }

		# we need address class to delete IPs!
		if($this->addresses) {
			$Address = new Address;

			//fetch and delete all ips in subnet
			$Address->subnetId = $this->id;			//provide subnetis
			try { $addresses = $Address->readAddress(); }
			catch (Exception $e) 												{
				//if empty it is ok!
				if($e->getMessage()=="No addresses") 							{  }
				else															{ throw new Exception($e->getMessage()); }
			}

			//delete all Ips
			if(sizeof($addresses)>0) {
				foreach($addresses as $a) {
					$Address->id = $a['id'];			//provide id
					//delete
					try { $addresses = $Address->deleteAddress(); }
					catch (Exception $e) 										{ throw new Exception($e->getMessage()); }

				}
			}
		}

		//set query to delete subnet and execute
		$this->query = "delete from `subnets` where `id` = ".$this->id.";";
		$this->executeQuery();

		//set result
		$result['result']   = "success";
		$result['response'] = "subnet id ".$this->id." deleted successfully!";

		//return result
		return $result;
	}

}
