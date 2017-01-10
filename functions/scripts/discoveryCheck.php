<?php

/**
 * This script does the following:
 * 		- fetches all subnets that are marked for discovering new hosts
 * 		- Scans each subnet for new hosts
 * 		- If new host is discovered it will be added to database
 *
 *	Scan type be used as defined under administration:
 *		- ping
 *		- pear ping
 *		- fping
 *
 *	Fping is new since version 1.2, it will work faster because it has built-in threading
 *	so we are only forking separate subnets
 *
 *	Script must be run from cron, here is a crontab example, 1x/day should be enough:
 * 		0 1 * * *  /usr/local/bin/php /<sitepath>/functions/scripts/pingCheck.php > /dev/null 2>&1
 *
 *
 *	In case of problems set reset_debugging to true
 *
 */


# include required scripts
require( dirname(__FILE__) . '/../functions.php' );
require( dirname(__FILE__) . '/../../functions/classes/class.Thread.php');

# initialize objects
$Database 	= new Database_PDO;
$Subnets	= new Subnets ($Database);
$Addresses	= new Addresses ($Database);
$Tools		= new Tools ($Database);
$Scan		= new Scan ($Database);
$DNS		= new DNS ($Database);
$Result		= new Result();

// set exit flag to true
$Scan->ping_set_exit(true);
// set debugging
$Scan->reset_debugging(false);
// change scan type?
//$Scan->reset_scan_method ("pear");
// set ping statuses
$statuses = explode(";", $Scan->settings->pingStatus);
// set mail override flag
$send_mail = true;

// set now for whole script
$now     = time();
$nowdate = date ("Y-m-d H:i:s");


// response for mailing
$address_change = array();			// Array with differences, can be used to email to admins


// script can only be run from cli
if(php_sapi_name()!="cli") 						{ die("This script can only be run from cli!"); }
// test to see if threading is available
if(!Thread::available()) 						{ die("Threading is required for scanning subnets. Please recompile PHP with pcntl extension"); }
// verify ping path
if ($Scan->icmp_type=="ping") {
if(!file_exists($Scan->settings->scanPingPath)) { die("Invalid ping path!"); }
}
// verify fping path
if ($Scan->icmp_type=="fping") {
if(!file_exists($Scan->settings->scanFPingPath)){ die("Invalid fping path!"); }
}


//first fetch all subnets to be scanned
$scan_subnets = $Subnets->fetch_all_subnets_for_discoveryCheck (1);
//set addresses
if ($scan_subnets!==false) {
    // initial array
    $addresses_tmp = array();
    // loop
    foreach($scan_subnets as $i => $s) {
    	// if subnet has slaves dont check it
    	if ($Subnets->has_slaves ($s->id) === false) {
    		$addresses_tmp[$s->id] = $Scan-> prepare_addresses_to_scan ("discovery", $s->id, false);
			// save discovery time
			$Scan->update_subnet_discoverytime ($s->id, $nowdate);
        } else {
            unset( $scan_subnets[$i] );
    	}
    }

    //reindex
    if(sizeof($addresses_tmp)>0) {
        foreach($addresses_tmp as $s_id=>$a) {
        	foreach($a as $ip) {
        		$addresses[] = array("subnetId"=>$s_id, "ip_addr"=>$ip);
        	}
        }
    }
}


if($Scan->debugging)							{ print_r($scan_subnets); }
if($scan_subnets===false || !count($scan_subnets)) { die("No subnets are marked for new hosts checking\n"); }


//scan
if($Scan->debugging)							{ print "Using $Scan->icmp_type\n--------------------\n\n"; }


$z = 0;			//addresses array index

// let's just reindex the subnets array to save future issues
$scan_subnets   = array_values($scan_subnets);
$size_subnets   = count($scan_subnets);
$size_addresses = max(array_keys($addresses));

//different scan for fping
if($Scan->icmp_type=="fping") {
	//run per MAX_THREADS
	for ($m=0; $m<=$size_subnets; $m += $Scan->settings->scanMaxThreads) {
	    // create threads
	    $threads = array();
	    //fork processes
	    for ($i = 0; $i <= $Scan->settings->scanMaxThreads && $i <= $size_subnets; $i++) {
	    	//only if index exists!
	    	if(isset($scan_subnets[$z])) {
				//start new thread
	            $threads[$z] = new Thread( 'fping_subnet' );
				$threads[$z]->start_fping( $Subnets->transform_to_dotted($scan_subnets[$z]->subnet)."/".$scan_subnets[$z]->mask );
	            $z++;				//next index
			}
	    }
	    // wait for all the threads to finish
	    while( !empty( $threads ) ) {
			foreach($threads as $index => $thread) {
				$child_pipe = "/tmp/pipe_".$thread->getPid();

				if (file_exists($child_pipe)) {
					$file_descriptor = fopen( $child_pipe, "r");
					$child_response = "";
					while (!feof($file_descriptor)) {
						$child_response .= fread($file_descriptor, 8192);
					}
					//we have the child data in the parent, but serialized:
					$child_response = unserialize( $child_response );
					//store
					$scan_subnets[$index]->discovered = $child_response;
					//now, child is dead, and parent close the pipe
					unlink( $child_pipe );
					unset($threads[$index]);
				}
			}
	        usleep(200000);
	    }
	}

	//fping finds all subnet addresses, we must remove existing ones !
	foreach($scan_subnets as $sk=>$s) {
    	if(isset($s->discovered)) {
    		foreach($s->discovered as $rk=>$result) {
    			if(!in_array($Subnets->transform_to_decimal($result), $addresses_tmp[$s->id])) {
    				unset($scan_subnets[$sk]->discovered[$rk]);
    			}
    		}
            //rekey
            $scan_subnets[$sk]->discovered = array_values($scan_subnets[$sk]->discovered);
		}
	}
}
//ping, pear
else {
	//run per MAX_THREADS
    for ($m=0; $m<=$size_addresses; $m += $Scan->settings->scanMaxThreads) {
        // create threads
        $threads = array();

        //fork processes
        for ($i = 0; $i <= $Scan->settings->scanMaxThreads && $i <= $size_addresses; $i++) {
        	//only if index exists!
        	if(isset($addresses[$z])) {
				//start new thread
	            $threads[$z] = new Thread( 'ping_address' );
	            $threads[$z]->start( $Subnets->transform_to_dotted($addresses[$z]['ip_addr']) );
				$z++;			//next index
			}
        }

        // wait for all the threads to finish
        while( !empty( $threads ) ) {
            foreach( $threads as $index => $thread ) {
                if( ! $thread->isAlive() ) {
					//unset dead hosts
					if($thread->getExitCode() != 0) {
						unset($addresses[$index]);
					}
                    //remove thread
                    unset( $threads[$index]);
                }
            }
            usleep(200000);
        }
	}

	//ok, we have all available addresses, rekey them
	foreach($addresses as $a) {
		$add_tmp[$a['subnetId']][] = $Subnets->transform_to_dotted($a['ip_addr']);
	}
	//add to scan_subnets as result
	foreach($scan_subnets as $sk=>$s) {
		if(isset($add_tmp[$s->id])) {
			$scan_subnets[$sk]->discovered = $add_tmp[$s->id];
		}
	}
}


# print change
if($Scan->debugging)							{ "\nDiscovered addresses:\n----------\n"; print_r($scan_subnets); }



# reinitialize objects
$Database 	= new Database_PDO;
$Admin		= new Admin ($Database, false);
$Addresses	= new Addresses ($Database);
$Subnets	= new Subnets ($Database);
$DNS		= new DNS ($Database);
$Scan		= new Scan ($Database);
$Result		= new Result();

# insert to database
$discovered = 0;				//for mailing

foreach($scan_subnets as $s) {
	if(sizeof(@$s->discovered)>0) {
		foreach($s->discovered as $ip) {
			// fetch subnet
			$subnet = $Subnets->fetch_subnet ("id", $s->id);
			$nsid = $subnet===false ? false : $subnet->nameserverId;
			// try to resolve hostname
			$hostname = $DNS->resolve_address ($ip, false, true, $nsid);

			//set update query
			$values = array("subnetId"=>$s->id,
							"ip_addr"=>$Subnets->transform_address($ip, "decimal"),
							"dns_name"=>$hostname['name'],
							"description"=>"-- autodiscovered --",
							"note"=>"This host was autodiscovered on ".$nowdate,
							"lastSeen"=>$nowdate,
							"state"=>"2",
							"action"=>"add"
							);
			//insert
			$Addresses->modify_address($values);

			//set discovered
			$discovered++;
		}
	}
}

# update scan time
$Scan->ping_update_scanagent_checktime (1, $nowdate);



# send mail
if($discovered>0 && $send_mail) {

	# check for recipients
	foreach($Admin->fetch_multiple_objects ("users", "role", "Administrator") as $admin) {
		if($admin->mailNotify=="Yes") {
			$recepients[] = array("name"=>$admin->real_name, "email"=>$admin->email);
		}
	}
	# none?
	if(!isset($recepients))	{ die(); }

	# fetch mailer settings
	$mail_settings = $Admin->fetch_object("settingsMail", "id", 1);
	# fake user object, needed for create_link
	$User = new StdClass();
	@$User->settings->prettyLinks = $Scan->settings->prettyLinks;

	# initialize mailer
	$phpipam_mail = new phpipam_mail($Scan->settings, $mail_settings);
	$phpipam_mail->initialize_mailer();



	// set subject
	$subject	= "phpIPAM new addresses detected ".date("Y-m-d H:i:s");

	//html
	$content[] = "<h3>phpIPAM found $discovered new hosts</h3>";
	$content[] = "<table style='margin-left:10px;margin-top:5px;width:auto;padding:0px;border-collapse:collapse;border:1px solid gray;'>";
	$content[] = "<tr>";
	$content[] = "	<th style='padding:3px 8px;border:1px solid silver;border-bottom:2px solid gray;'>IP</th>";
	$content[] = "	<th style='padding:3px 8px;border:1px solid silver;border-bottom:2px solid gray;'>Subnet</th>";
	$content[] = "	<th style='padding:3px 8px;border:1px solid silver;border-bottom:2px solid gray;'>Section</th>";
	$content[] = "</tr>";
	//plain
	$content_plain[] = "phpIPAM found $discovered new hosts\r\n------------------------------";
	//Changes
	foreach($scan_subnets as $s) {
		if(sizeof(@$s->discovered)>0) {
			foreach($s->discovered as $ip) {
				//set subnet
				$subnet 	 = $Subnets->fetch_subnet(null, $s->id);
				//set section
				$section 	 = $Admin->fetch_object("sections", "id", $s->sectionId);

				$content[] = "<tr>";
				$content[] = "	<td style='padding:3px 8px;border:1px solid silver;'>$ip</td>";
				$content[] = "	<td style='padding:3px 8px;border:1px solid silver;'><a href='".rtrim($Scan->settings->siteURL, "/")."".create_link("subnets",$section->id,$subnet->id)."'>".$Subnets->transform_to_dotted($subnet->subnet)."/".$subnet->mask." - ".$subnet->description."</a></td>";
				$content[] = "	<td style='padding:3px 8px;border:1px solid silver;'><a href='".rtrim($Scan->settings->siteURL, "/")."".create_link("subnets",$section->id)."'>$section->name $section->description</a></td>";
				$content[] = "</tr>";

				//plain content
				$content_plain[] = "\t * $ip (".$Subnets->transform_to_dotted($subnet->subnet)."/".$subnet->mask.")";
			}
		}
	}
	$content[] = "</table>";


	# set content
	$content 		= $phpipam_mail->generate_message (implode("\r\n", $content));
	$content_plain 	= implode("\r\n",$content_plain);

	# try to send
	try {
		$phpipam_mail->Php_mailer->setFrom($mail_settings->mAdminMail, $mail_settings->mAdminName);
		//add all admins to CC
		foreach($recepients as $admin) {
			$phpipam_mail->Php_mailer->addAddress(addslashes($admin['email']), addslashes($admin['name']));
		}
		$phpipam_mail->Php_mailer->Subject = $subject;
		$phpipam_mail->Php_mailer->msgHTML($content);
		$phpipam_mail->Php_mailer->AltBody = $content_plain;
		//send
		$phpipam_mail->Php_mailer->send();
	} catch (phpmailerException $e) {
		$Result->show_cli("Mailer Error: ".$e->errorMessage(), true);
	} catch (Exception $e) {
		$Result->show_cli("Mailer Error: ".$e->errorMessage(), true);
	}
}

?>
