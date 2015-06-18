<?php

/**
 *
 * All functions to communicate with database
 *
 * Extended mysqli class to simplify result handling
 * 
 */
 
 
class database extends mysqli  {

	# variables
	public $lastSqlId;		// last SQL insert id
	
	

	/** 
	 * Construct object
	 */
	public function __construct($host = NULL, $username = NULL, $dbname = NULL, $port = NULL, $socket = NULL, $printError = true) {		

		# throw exceptions
		mysqli_report(MYSQLI_REPORT_STRICT);

		# open database connection
		try { parent::__construct($host, $username, $dbname, $port, $socket); }
		catch (Exception $e) {
    		if($printError) { print "<div class='alert alert-danger'>error:".$e->getMessage()."</div>"; }
			return false;
		}	

		if(!isset($e))
		$this->set_charset("utf8");
		
		
		# change back reporting for exception throwing to scripts
		//mysqli_report(MYSQLI_REPORT_ERROR);
	} 

	
	/**
	 * execute given query 
	 *
	 */
	public function executeQuery( $query, $lastId = false ) 
	{
		# execute query
		$result     = parent::query( $query );
		$this->lastSqlId   = $this->insert_id;
		
		# if it failes throw new exception
		if ( mysqli_error( $this ) ) {
            throw new exception( mysqli_error( $this ), mysqli_errno( $this ) ); 
      		}
        else {
        	# return lastId if requested
        	if($lastId)	{ return $this->lastSqlId; }
        	else 		{ return true; }
        }
	}
	

	/**
	 * get only 1 row
	 *
	 */
    function getRow ( $query ) 
    {
        /* get result */
        if ($result = parent::query($query)) {     
            $resp = $result->fetch_row();   
        }
        else {
            throw new exception( mysqli_error( $this ), mysqli_errno( $this ) ); 
        }
        /* return result */
        return $resp;   
    }

	
	
	/**
	 * get array of results
	 *
	 * returns multi-dimensional array
	 *     first dimension is number
	 *     from second on the values
	 * 
	 * if nothing is provided use assocciative results
	 *
	 */
	function getArray( $query , $assoc = true ) 
	{	
		/* execute query */
		$result = parent::query($query);
	
	    /* if it failes throw new exception */
		if(mysqli_error($this)) {
      		throw new exception(mysqli_error($this), mysqli_errno($this)); 
        }
        
		/** 
		 * fetch array of all access responses 
         * either assoc or num, based on input
         *
         */
		if ($assoc == true) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $fields[] = $row;	
            }
		} 
		else {
            while($row = $result->fetch_array(MYSQLI_NUM)) {
                $fields[] = $row;	
            }
        }
        
		/* return result array */
		if(isset($fields)) {
        	return($fields);
        }
        else {
        	$fields = array();
        	return $fields;
        }
	}


	
	/**
	 * get array of multiple results
	 *
	 * returns multi-dimensional array
	 *     first dimension is number
	 *     from second on the values
	 * 
	 * if nothing is provided use assocciative results
	 *
	 */
	function getMultipleResults( $query ) 
	{
        /* execute query */
		$result = parent::multi_query($query);
		
		/**
		 * get results to array
		 * first save it, than get each row from result and store it to active[]
		 */
		do { 
            $results = parent::store_result();
			
			/* save each to array (only first field) */
			while ( $row = $results->fetch_row() ) {
				$rows[] = $row[0];
			}
			$results->free();
		}
		while( parent::next_result() );
		
		/* return result array of rows */
		return($rows);
	}
	
	
	/**
	 * Execute multiple querries!
	 *
	 */
	function executeMultipleQuerries( $query, $lastId = false ) 
	{	
        # execute querries 
		//$result = parent::multi_query($query);
		
		if ($result = parent::multi_query($query)) {
		    do {
		        /* store first result set */
		        if ($result = parent::store_result()) {
		            $result->free();
		        }
		    } while (parent::next_result());
		}
		
		# save lastid
		$this->lastSqlId   = $this->insert_id;

		# if it failes throw new exception
		if ( mysqli_error( $this ) ) {
            throw new exception( mysqli_error( $this ), mysqli_errno( $this ) ); 
      	}
        else {
       		if($lastId)	{ return $this->lastSqlId; }
        	else 		{ return true; }
        }
	}


	/**
	 * Select database
	 *
	 */
	function selectDatabase( $database ) 
	{	
        /* execute querries */
		$result = parent::select_db($database);

		/* if it failes throw new exception */
		if ( mysqli_error( $this ) ) {
            throw new exception( mysqli_error( $this ), mysqli_errno( $this ) ); 
      	}
        else {
            return true;
        }	
	}
}

?>