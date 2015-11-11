<?php

/**
 *	This script takes 2 possible argumets from argv:
 *		* scan type			//status update or subnet discovery?
 *
 *		* subnet in cidr format
 *		- or -
 *		* subnetId
 *
 *	If all is ok it scans the subnet for IP addresses.
 *	If subnet is provided it will scan subnet, otherwise it will fetch subnet from database
 *
 *	Return values are always in json in format, first is status second array values of:
 *
 *	status : 0/1			//success, false
 *	values : error 			//provided error text
 *			 alive			//array of active hosts
 *			 dead			//array of dead hosts
 *			 serror			//error in scanning
 *
 *	Scan type is fetched from DB settings, currently supported scans for cli are:
 *		* ping
 *		* pear
 *		* fping
 *
 */

/* functions */
require( dirname(__FILE__) . '/../../functions/functions.php');
require( dirname(__FILE__) . '/../../functions/classes/class.Thread.php');

# initialize user object
$Database 	= new Database_PDO;
$Subnets	= new Subnets ($Database);
$Scan		= new Scan ($Database);

//set exit flag to true
$Scan->ping_set_exit(true);

/**
 *	Input checks
 */

//script can only be run from cli
if(php_sapi_name()!="cli") 								{ die(json_encode(array("status"=>1, "error"=>"This script can only be run from cli!"))); }
//check input parameters
if(!isset($argv[1]) || !isset($argv[2]))				{ die(json_encode(array("status"=>1, "error"=>"Missing required input parameters"))); }
// test to see if threading is available
if( !Thread::available() ) 								{ die(json_encode(array("status"=>1, "error"=>"Threading is required for scanning subnets. Please recompile PHP with pcntl extension"))); }
//check script
if($argv[1]!="update"&&$argv[1]!="discovery")			{ die(json_encode(array("status"=>1, "error"=>"Invalid scan type!"))); }
//verify cidr
if(!is_numeric($argv[2])) {
	if($Subnets->verify_cidr_address($argv[2])!==true)	{ die(json_encode(array("status"=>1, "error"=>"Invalid subnet CIDR address provided"))); }
}

/**
 * Select how to scan based on scan type.
 *
 * if ping/pear make threads, if fping than just check since it has built-in threading !
 */


# fping
if($Scan->settings->scanPingType=="fping" && $argv[1]=="discovery") {
	# fetch subnet
	$subnet = $Subnets->fetch_subnet(null, $argv[2]);
	$subnet!==false ? : 								  die(json_encode(array("status"=>1, "error"=>"Invalid subnet ID provided")));

	//set exit flag to true
	$Scan->ping_set_exit(false);

	# set cidr
	$subnet_cidr = $Subnets->transform_to_dotted($subnet->subnet)."/".$subnet->mask;
	# execute
	$retval = $Scan->ping_address_method_fping_subnet ($subnet_cidr);

	# errors
	if($retval==3)										{ die(json_encode(array("status"=>1, "error"=>"invalid command line arguments"))); }
	if($retval==4)										{ die(json_encode(array("status"=>1, "error"=>"system call failure"))); }

	# parse result
	if(sizeof(@$Scan->fping_result)==0)					{ die(json_encode(array("status"=>0, "values"=>array("alive"=>null)))); }
	else {
		//check each line
		foreach($Scan->fping_result as $l) {
			//split
			$field = array_filter(explode(" ", $l));
			//create result
			$out['alive'][] = $Subnets->transform_to_decimal($field[0]);
		}
	}
}
# fping - status update
elseif($Scan->settings->scanPingType=="fping") {
	# fetch subnet
	$subnet = $Subnets->fetch_subnet(null, $argv[2]);
	$subnet!==false ? : 								  die(json_encode(array("status"=>1, "error"=>"Invalid subnet ID provided")));

	//set exit flag to true
	$Scan->ping_set_exit(false);

	# set cidr
	$subnet_cidr = $Subnets->transform_to_dotted($subnet->subnet)."/".$subnet->mask;
	# execute
	$retval = $Scan->ping_address_method_fping_subnet ($subnet_cidr);

	# errors
	if($retval==3)										{ die(json_encode(array("status"=>1, "error"=>"invalid command line arguments"))); }
	if($retval==4)										{ die(json_encode(array("status"=>1, "error"=>"system call failure"))); }

	# parse result
	if(sizeof(@$Scan->fping_result)==0)					{ die(json_encode(array("status"=>0, "values"=>array("alive"=>null)))); }
	else {
		//check each line
		foreach($Scan->fping_result as $l) {
			//split
			$field = array_filter(explode(" ", $l));
			//create result
			$out['alive'][] = $Subnets->transform_to_decimal($field[0]);
		}
	}
}
# pear / ping
else {
	# Create array of addresses to scan
	$scan_addresses = $Scan->prepare_addresses_to_scan ($argv[1], $argv[2]);

	$z = 0;			//addresses array index

	//run per MAX_THREADS
	for ($m=0; $m<=sizeof($scan_addresses); $m += $Scan->settings->scanMaxThreads) {
	    // create threads
	    $threads = array();
	    // fork processes
	    for ($i = 0; $i <= $Scan->settings->scanMaxThreads && $i <= sizeof($scan_addresses); $i++) {
	    	//only if index exists!
	    	if(isset($scan_addresses[$z])) {
				//start new thread
	            $threads[$z] = new Thread( "ping_address" );
	            $threads[$z]->start( $Subnets->transform_to_dotted($scan_addresses[$z], true) );

	            $z++;				//next index
			}
	    }
	    // wait for all the threads to finish
	    while( !empty( $threads ) ) {
	        foreach( $threads as $index => $thread ) {
	            if( ! $thread->isAlive() ) {
	            	//online, save to array
	            	if($thread->getExitCode() == 0) 									{ $out['alive'][] = $scan_addresses[$index]; }
	            	//ok, but offline
	            	elseif($thread->getExitCode() == 1 || $thread->getExitCode() == 2) 	{ $out['dead'][]  = $scan_addresses[$index];}
	            	//error
	            	else 																{ $out['error'][] = $scan_addresses[$index]; }
	                //remove thread
	                unset( $threads[$index] );
	            }
	        }
	        usleep(100000);
	    }
	}
}

# compose result - ok
$result['status'] = 0;
$result['values'] = @$out;

# save to json
$out = json_encode(@$result);

# print result
print_r($out);
?>