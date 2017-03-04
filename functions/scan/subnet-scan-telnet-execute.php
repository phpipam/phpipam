<?php

/**
 *	This script takes 2 argumets from argv:
 *		* subnetId
 *		* ports
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
 *	Scan type is telnet
 *
 */

/* functions */
require( dirname(__FILE__) . '/../../functions/functions.php');
require( dirname(__FILE__) . '/../../functions/classes/class.Thread.php');

# initialize user object
$Database 	= new Database_PDO;
$Subnets	= new Subnets ($Database);
$Addresses	= new Addresses ($Database);
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

/**
 *	Create array of addresses to scan
 */
$scan_addresses = $Scan->prepare_addresses_to_scan ("discovery", $argv[1]);


$z = 0;			//addresses array index

/*
test
*/
$ports = explode(";", $argv[2]);

$out = array();

//reset array, set each IP together with port
foreach($scan_addresses as $k=>$v) {
	foreach($ports as $p) {
		$addresses[] = array("ip"=>$v, "port"=>$p);
	}
}


# run per MAX_THREADS
for ($m=0; $m<=sizeof($addresses); $m += $Scan->settings->scanMaxThreads) {
    //create threads
    $threads = array();
    //fork processes
    for ($i = 0; $i <= $Scan->settings->scanMaxThreads && $i <= sizeof($addresses); $i++) {
    	//only if index exists!
    	if(isset($addresses[$z])) {
			//start new thread
            $threads[$z] = new Thread( 'telnet_address' );
            $threads[$z]->start( $Subnets->transform_to_dotted($addresses[$z]['ip']), $addresses[$z]['port'], 2);
            $z++;				//next index
		}
    }
    //wait for all the threads to finish
    while( !empty( $threads ) ) {
        foreach( $threads as $index => $thread ) {
            if( ! $thread->isAlive() ) {
            	//online, save to array
            	if($thread->getExitCode() == 0) { $out['alive'][$addresses[$index]['ip']][] = $addresses[$index]['port']; }
            	//ok, but offline
            	else 							{ $out['dead'][$addresses[$index]['ip']][]  = $addresses[$index]['port'];}
                //remove thread
                unset( $threads[$index] );
            }
        }
        usleep(100000);
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