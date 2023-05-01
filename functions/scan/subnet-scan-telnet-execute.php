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
require_once( dirname(__FILE__) . '/../../functions/functions.php' );

# Don't corrupt output with php errors!
disable_php_errors();

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
if( !PingThread::available($errmsg) ) 								{ die(json_encode(array("status"=>1, "error"=>"Threading is required for scanning subnets - Error: $errmsg\n"))); }

/**
 *	Create array of addresses to scan
 */
$scan_addresses = $Scan->prepare_addresses_to_scan ("discovery", $argv[1]) ?: [];


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

while ($z < sizeof($scan_addresses)) {

    $threads = [];

    try {
        //run per MAX_THREADS
        for ($i = 0; $i < $Scan->settings->scanMaxThreads; $i++) {
            if (isset($addresses[$z])) {
                $thread = new PingThread('telnet_address');
                $thread->start($Subnets->transform_to_dotted($addresses[$z]['ip']), $addresses[$z]['port'], 2);
                $threads[$z++] = $thread;
            }
        }
    } catch (Exception $e) {
        // We failed to spawn a scanning process.
        $result['debug'] .= "Failed to start scanning pool, spawned " . sizeof($threads) . " of " . $Scan->settings->scanMaxThreads . "\n";
    }

    if (empty($threads)) {
        die(json_encode(array("status" => 1, "error" => "Unable to spawn scanning pool")));
    }

    //wait for all the threads to finish
    foreach ($threads as $index => $thread) {
        if ($thread->getExitCode() === 0) {
            //online, save to array
            $out['alive'][$addresses[$index]['ip']][] = $addresses[$index]['port'];
        } else {
            // offline
            $out['dead'][$addresses[$index]['ip']][]  = $addresses[$index]['port'];
        }
    }
    unset($threads);
}

# compose result - ok
$result['status'] = 0;
$result['values'] = @$out;

# save to json
$out = json_encode(@$result);

# print result
print_r($out);
