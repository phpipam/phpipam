<?php

/**
 *	phpIPAM Section class
 */

class Section
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
	 * 	get Section
	 */
	public function readSection()
	{
		/* check input */
		$this->Common->check_input;

		/* section by id */
		if($this->id) {
			//set query
			$this->query = "select * from `sections` where `id` = ".$this->id.";";
			$this->fetchArray();
			if(sizeof($this->result)==0) 										{ throw new Exception('Invalid section Id '.$this->id); }
		}
		/* all sections  */
		elseif($this->all) {
			//set query
			$this->query = "select * from `sections`;";
			$this->fetchArray();
			if(sizeof($this->result)==0) 										{ throw new Exception('No sections configured'); }
		}
		/* section by name */
		elseif($this->name) {
			//set query
			$this->query = "select * from `sections` where `name` = ".$this->name.";";
			$this->fetchArray();
			if(sizeof($this->result)==0) 										{ throw new Exception('Invalid section name '.$this->name); }
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
	 *	delete Section
	 *		id must be provided
	 *
	 *		we must also delete all subnets and all IP addresses!
	 */
	public function deleteSection ()
	{
		//check input
		$this->Common->check_var ("int", $this->id, null);

		//verify that it exists
		try { $this->readSection(); }
		catch (Exception $e) 													{ throw new Exception($e->getMessage()); }

		//do we need to delete also subnets?
		if($this->subnets) {
			$Subnet = new Subnet;

			# get all belonging subnets
			$Subnet->sectionId = $this->id;
			$Subnet->addresses = $this->addresses;					//flag to delete also IPs

			try { $allsubnets = $Subnet->readSubnet(); }
			catch (Exception $e) {
				# empty?
				if(substr($e->getMessage(), 0, 32)=="Invalid section Id or no subnets")	{}
				else															{ throw new Exception($e->getMessage()); }
			}

			# loop
			if(sizeof($allsubnets)>0) {
				foreach($allsubnets as $s) {
					# provide id and parameters
					$Subnet->id = $s['id'];
					$Subnet->deleteSubnet();
				}
			}
		}

		//set query and execute
		$this->query = "delete from `sections` where `id` = ".$this->id.";";
		$this->executeQuery();

		//set result
		$result['result']   = "success";
		$result['response'] = "section ".$this->id." deleted successfully!";

		//return result
		return $result;
	}
}
