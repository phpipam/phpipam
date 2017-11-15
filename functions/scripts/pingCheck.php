<?php

/**
 * This script does the following:
 * 		- fetches all subnets that are marked for scanning for host addresses
 * 		- For each subnet it fetches all configured IP addresses
 * 		- If address is available it will update lastAvailable time in database
 * 		- If change happened it will mail change to all admins, that have it enabled in their config
 *
 *	Scan type be used as defined under administration:
 *		- ping
 *		- pear ping
 *		- fping
 *
 *
 *	Fping is new since version 1.2, it will work faster because it has built-in threading
 *	so we are only forking separate subnets
 *
 *
 *	Script must be run from cron, here is a crontab example for 15 minutes scanning:
 * 		*\/15 * * * *  /usr/local/bin/php /<sitepath>/functions/scripts/pingCheck.php > /dev/null 2>&1
 *
 *
 *	In case of problems set reset_debugging to true
 *
 *  Statuses:
 *      0 = online
 *      2 = offline
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
$Admin		= new Admin ($Database, false);
$Scan		= new Scan ($Database);
$DNS		= new DNS ($Database);
$Result		= new Result();

// set exit flag to true
$Scan->ping_set_exit(true);
// set debugging
$Scan->reset_debugging(false);
// fetch agent
$agent = $Tools->fetch_object("scanAgents", "id", 1);
// set address types array
$Tools->get_addresses_types ();
// change scan type?
if(@$config['ping_check_method'])
$Scan->reset_scan_method ($config['ping_check_method']);

// set ping statuses
$statuses = explode(";", $Scan->settings->pingStatus);
// set mail override flag
if(!isset($config['ping_check_send_mail'])) {
	$config['ping_check_send_mail'] = true;
}

// response for mailing
$address_change = array();			// Array with differences, can be used to email to admins

// set now for whole script
$now     = time();
$nowdate = date ("Y-m-d H:i:s");


// script can only be run from cli
if(php_sapi_name()!="cli") 						{ die("This script can only be run from cli!"); }
// test to see if threading is available
if(!PingThread::available()) 					{ die("Threading is required for scanning subnets. Please recompile PHP with pcntl extension"); }
// verify ping path
if ($Scan->icmp_type=="ping") {
if(!file_exists($Scan->settings->scanPingPath)) { die("Invalid ping path!"); }
}
// verify fping path
if ($Scan->icmp_type=="fping") {
if(!file_exists($Scan->settings->scanFPingPath)){ die("Invalid fping path!"); }
}


//first fetch all subnets to be scanned
$scan_subnets = $Subnets->fetch_all_subnets_for_pingCheck (1);
if($Scan->debugging)							{ print_r($scan_subnets); }
if($scan_subnets===false) 						{ die("No subnets are marked for checking status updates\n"); }
//fetch all addresses that need to be checked
foreach($scan_subnets as $s) {

	// if subnet has slaves dont check it
	if ($Subnets->has_slaves ($s->id) === false) {

		$subnet_addresses = $Addresses->fetch_subnet_addresses ($s->id);
		//set array for fping
		if($Scan->icmp_type=="fping")	{
			$subnets[] = array(
								"id"         =>$s->id,
								"cidr"       =>$Subnets->transform_to_dotted($s->subnet)."/".$s->mask,
								"nsid"       =>$s->nameserverId,
								"resolveDNS" =>$s->resolveDNS
			                   );
		}
		//save addresses
		if(sizeof($subnet_addresses)>0) {
			foreach($subnet_addresses as $a) {
				//ignore excludePing
				if($a->excludePing!=1) {
					//create different array for fping
					if($Scan->icmp_type=="fping")	{
						$addresses2[$s->id][$a->id] = array(
															"id"          =>$a->id,
															"ip_addr"     =>$a->ip_addr,
															"description" =>$a->description,
															"dns_name"    =>$a->dns_name,
															"subnetId"    =>$a->subnetId,
															"lastSeenOld" =>$a->lastSeen,
															"lastSeen"    =>$a->lastSeen,
															"state"       =>$a->state,
															"resolveDNS"  =>$s->resolveDNS,
															"nsid"        =>$s->nsid
															);
						$addresses[$s->id][$a->id]  = $a->ip_addr;
					}
					else {
						$addresses[] 		 		= array(
															"id"          =>$a->id,
															"ip_addr"     =>$a->ip_addr,
															"description" =>$a->description,
															"dns_name"    =>$a->dns_name,
															"subnetId"    =>$a->subnetId,
															"lastSeenOld" =>$a->lastSeen,
															"lastSeen"    =>$a->lastSeen,
															"state"       =>$a->state,
															"resolveDNS"  =>$s->resolveDNS,
															"nsid"        =>$s->nsid
						                          			);
					}
				}
			}
		}
		// save update time
		$Scan->update_subnet_scantime ($s->id, $nowdate);
	}
}


if($Scan->debugging)							{ print "Using $Scan->icmp_type\n--------------------\n\n";print_r($addresses); }
//if none die
if(!isset($addresses))							{ die("No addresses to check"); }


/* scan */

$z = 0;			//addresses array index

//different scan for fping
if($Scan->icmp_type=="fping") {
	//run per MAX_THREADS
	for ($m=0; $m<=sizeof($subnets); $m += $Scan->settings->scanMaxThreads) {
	    // create threads
	    $threads = array();
	    //fork processes
	    for ($i = 0; $i <= $Scan->settings->scanMaxThreads && $i <= sizeof($subnets); $i++) {
	    	//only if index exists!
	    	if(isset($subnets[$z])) {
				//start new thread
	            $threads[$z] = new PingThread( 'fping_subnet' );
	            $threads[$z]->start_fping( $subnets[$z]['cidr'] );
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
					$subnets[$index]['result'] = $child_response;

					//now, child is dead, and parent close the pipe
					unlink( $child_pipe );
					unset($threads[$index]);
				}
			}
	        usleep(200000);
	    }
	}

	//now we must remove all non-existing hosts
	foreach($subnets as $sk=>$s) {
		if(sizeof(@$s['result'])>0 && sizeof($addresses[$s['id']])>0) {
			//loop addresses
			foreach($addresses[$s['id']] as $ak=>$a) {
				//offline host
				if(array_search($Subnets->transform_to_dotted($a), $subnets[$sk]['result'])===false) {
    				// new change = null
    				$addresses2[$s['id']][$ak]['lastSeenNew'] = NULL;
					//save to out array
                    $address_change[] = $addresses2[$s['id']][$ak];
				}
				//online host
				else {
    				// new change = now
    				$addresses2[$s['id']][$ak]['lastSeenNew'] = $nowdate;
					//save to out array
                    $address_change[] = $addresses2[$s['id']][$ak];
                    //update status
                    $Scan->ping_update_lastseen ($addresses2[$s['id']][$ak]['id'], $nowdate);
				}

		        //resolve hostnames
				if($subnets[$sk]['resolveDNS']=="1") {
					$old_hostname_save = $a['dns_name'];	// save old hostname to detect change
					$old_hostname = $config['resolve_emptyonly']===false ? false : $addresses2[$s['id']][$ak]['dns_name'];
			        $hostname = $DNS->resolve_address ($Subnets->transform_to_dotted($addresses2[$s['id']][$ak]['ip_addr']), $old_hostname, true, $subnets[$sk]['nsid']);
					if($hostname['class']=="resolved") {
						if ($hostname['name']!=$old_hostname_save) {
							$Addresses->update_address_hostname ($Subnets->transform_to_dotted($addresses2[$s['id']][$ak]['ip_addr']), $addresses2[$s['id']][$ak]['id'], $hostname['name']);
						}
					}
				}
			}
		}
	}
}
//ping, pear
else {
	//run per MAX_THREADS
	for ($m=0; $m<=sizeof($addresses); $m += $Scan->settings->scanMaxThreads) {
	    // create threads
	    $threads = array();
	    //fork processes
	    for ($i = 0; $i <= $Scan->settings->scanMaxThreads && $i <= sizeof($addresses); $i++) {
	    	//only if index exists!
	    	if(isset($addresses[$z])) {
				//start new thread
	            $threads[$z] = new PingThread( 'ping_address' );
	            $threads[$z]->start($Subnets->transform_to_dotted($addresses[$z]['ip_addr']));
	            $z++;				//next index
			}
	    }
	    // wait for all the threads to finish
	    while( !empty( $threads ) ) {
	        foreach( $threads as $index => $thread ) {
	            if( ! $thread->isAlive() ) {
	            	//online
	            	if($thread->getExitCode() == 0) {
    	            	// set new available time
    	            	$addresses[$index]['lastSeenNew'] =  $nowdate;
                        $address_change[$index] = $addresses[$index];	 				//change to online
	            	}
	            	//offline
	            	else {
    	            	// set nw online
    	            	$addresses[$index]['lastSeenNew'] =  NULL;
                        $address_change[$index] = $addresses[$index];	 				//change to online
					}
	            	//save exit code for host
	                $addresses[$index]['newStatus'] = $thread->getExitCode();
	                //remove thread
	                unset( $threads[$index] );
	            }
	        }
	        usleep(200000);
	    }
	}

	//update statuses for online

	# re-initialize classes
	$Database  = new Database_PDO;
	$Scan      = new Scan ($Database, $Subnets->settings);
	$Addresses = new Addresses ($Database);

	// reset debugging
	$Scan->reset_debugging(false);

	# update all active statuses
	foreach($addresses as $k=>$a) {
		if($a['newStatus']==0) {
			$Scan->ping_update_lastseen ($a['id'], $nowdate);
		}

        //resolve hostnames
		if($a['resolveDNS']=="1") {
			$old_hostname_save = $a['dns_name'];	// save old hostname to detect change
			$old_hostname = $config['resolve_emptyonly']===false ? false : $a['dns_name'];
	        $hostname = $DNS->resolve_address ($Subnets->transform_to_dotted($a['ip_addr']), $old_hostname, true, $a['nsid']);
			if($hostname['class']=="resolved") {
				if ($hostname['name']!=$old_hostname_save) {
					$Addresses->update_address_hostname ($Subnets->transform_to_dotted($a['ip_addr']), $a['id'], $hostname['name']);
				}
			}
		}
	}
}



/**
 * Now check for diffs - if time change between lastSeenOld and lastSeen > statuses[1]
 */

// loop
foreach ($address_change as $k=>$change) {
    // null old - set to epoch time
    if (strtotime($change['lastSeenOld'])===false)  { $change['lastSeenOld'] = date("Y-m-d H:i:s", 0); }

    // set general diffs
    $deviceDiff = $now - strtotime($change['lastSeenOld']);	        // now - device last seen
    $agentDiff  = $now - strtotime($agent->last_access);	        // now - last agent check

    // if now online and old offline send mail
    if ($change['lastSeenNew']!=NULL && $deviceDiff >= (int) $statuses[1]) {
        $address_change[$k]['oldStatus'] = 2;
        $address_change[$k]['newStatus'] = 0;
        // update tag if not already online
        // tags have different indexes than script exit code is - 1=offline, 2=online
        if($address_change[$k]['state']!=2 && $Scan->settings->updateTags==1 && $Tools->address_types[$address_change[$k]['state']]['updateTag']==1) {
	        $Scan->update_address_tag ($address_change[$k]['id'], 2, $address_change[$k]['state'], $change['lastSeenOld']);
    	}
    }
    // now offline, and diff > offline period, do checks
    elseif($change['lastSeenNew']==NULL && $deviceDiff >= (int) $statuses[1]) {
        // if not already reported
        if ($deviceDiff <= ((int) $statuses[1] + $agentDiff))  {
            $address_change[$k]['oldStatus'] = 0;
            $address_change[$k]['newStatus'] = 2;
	        // update tag if not already offline
	        // tags have different indexes than script exit code is - 1=offline, 2=online
	        if($address_change[$k]['state']!=1 && $Scan->settings->updateTags==1 && $Tools->address_types[$address_change[$k]['state']]['updateTag']==1) {
		        $Scan->update_address_tag ($address_change[$k]['id'], 1, $address_change[$k]['state'], $change['lastSeenOld']);
		    }
        }
        else {
        	// already reported, check tag
	        if($address_change[$k]['state']!=1 && $Scan->settings->updateTags==1 && $Tools->address_types[$address_change[$k]['state']]['updateTag']==1) {
		        $Scan->update_address_tag ($address_change[$k]['id'], 1, $address_change[$k]['state'], $change['lastSeenOld']);
		    }
        	// remove from change array
            unset ($address_change[$k]);
        }
    }
    // remove
    else {
    	// check tag
    	if ($change['lastSeenNew']!=NULL) {
	        // update tag if not already online
	        // tags have different indexes than script exit code is - 1=offline, 2=online
	        if($address_change[$k]['state']!=2 && $Scan->settings->updateTags==1 && $Tools->address_types[$address_change[$k]['state']]['updateTag']==1) {
		        $Scan->update_address_tag ($address_change[$k]['id'], 2, $address_change[$k]['state'], $change['lastSeenOld']);
	    	}
    	}
    	else {
    		// update tag if not already offline
	        // tags have different indexes than script exit code is - 1=offline, 2=online
	        if($address_change[$k]['state']!=1 && $Scan->settings->updateTags==1 && $Tools->address_types[$address_change[$k]['state']]['updateTag']==1) {
		        $Scan->update_address_tag ($address_change[$k]['id'], 1, $address_change[$k]['state'], $change['lastSeenOld']);
		    }
    	}

        unset ($address_change[$k]);
    }
}

# update scan time
$Scan->ping_update_scanagent_checktime (1, $nowdate);


# print change
if($Scan->debugging)							{ print "\nAddress changes:\n----------\n"; print_r($address_change); }

# all done, mail diff?
if(sizeof($address_change)>0 && $config['ping_check_send_mail']) {

	# remove old classes
	unset($Database, $Subnets, $Addresses, $Tools, $Scan, $Result);

	$Database 	= new Database_PDO;
	$Subnets	= new Subnets ($Database);
	$Addresses	= new Addresses ($Database);
	$Tools		= new Tools ($Database);
	$Scan		= new Scan ($Database);
	$Result		= new Result();

	// set exit flag to true
	$Scan->ping_set_exit(true);
	// set debugging
	$Scan->reset_debugging(false);


	# check for recipients
	foreach($Tools->fetch_multiple_objects ("users", "role", "Administrator") as $admin) {
		if($admin->mailNotify=="Yes") {
			$recepients[] = array("name"=>$admin->real_name, "email"=>$admin->email);
		}
	}
	# none?
	if(!isset($recepients))	{ die(); }

	# fetch mailer settings
	$mail_settings = $Tools->fetch_object("settingsMail", "id", 1);
	# fake user object, needed for create_link
	$User = new StdClass();
	@$User->settings->prettyLinks = $Scan->settings->prettyLinks;

	# initialize mailer
	$phpipam_mail = new phpipam_mail($Scan->settings, $mail_settings);
	$phpipam_mail->initialize_mailer();

	// set subject
	$subject	= "phpIPAM IP state change ".$nowdate;

	//html
	$content[] = "<p style='margin-left:10px;'>$Subnets->mail_font_style <font style='font-size:16px;size:16px;'>phpIPAM host changes</font></font></p><br>";

	$content[] = "<table style='margin-left:10px;margin-top:5px;width:auto;padding:0px;border-collapse:collapse;border:1px solid #ccc;'>";
	$content[] = "<tr>";
	$content[] = "	<th style='padding:3px 8px;border:1px solid #ccc;border-bottom:2px solid gray;white-space:nowrap;'>$Subnets->mail_font_style IP</font></th>";
	$content[] = "	<th style='padding:3px 8px;border:1px solid #ccc;border-bottom:2px solid gray;'>$Subnets->mail_font_style Description</font></th>";
	$content[] = "	<th style='padding:3px 8px;border:1px solid #ccc;border-bottom:2px solid gray;'>$Subnets->mail_font_style Hostname</font></th>";
	$content[] = "	<th style='padding:3px 8px;border:1px solid #ccc;border-bottom:2px solid gray;'>$Subnets->mail_font_style Subnet</font></th>";
	$content[] = "	<th style='padding:3px 8px;border:1px solid #ccc;border-bottom:2px solid gray;'>$Subnets->mail_font_style Last seen</font></th>";
	$content[] = "	<th style='padding:3px 8px;border:1px solid #ccc;border-bottom:2px solid gray;'>$Subnets->mail_font_style Status</font></th>";
	$content[] = "</tr>";

	//plain
	$content_plain[] = "phpIPAM host changes \r\n------------------------------";

	//Changes
	foreach($address_change as $change) {
		//reformat statuses
		if($change['oldStatus'] == 0) {
			$oldStatus = "<font style='color:#04B486'>Online</font>";
			$newStatus = "<font style='color:#DF0101'>Offline</font>";
		}
		else {
			$oldStatus = "<font style='color:#DF0101'>Offline</font>";
			$newStatus = "<font style='color:#04B486'>Online</font>";
		}

		//set subnet
		$subnet 	 = $Subnets->fetch_subnet(null, $change['subnetId']);
		//ago
		if(is_null($change['lastSeen']) || $change['lastSeen']=="1970-01-01 00:00:01" || $change['lastSeen']=="0000-00-00 00:00:00") {
			$ago	  = "never";
		} else {
			$timeDiff = $now - strtotime($change['lastSeen']);

    		// reformat
    		$lastSeen = date("m/d H:i", strtotime($change['lastSeen']) );
			$ago 	  = $lastSeen." (".$Result->sec2hms($timeDiff)." ago)";
		}
        // desc
		$change['description'] = strlen($change['description'])>0 ? "$Subnets->mail_font_style $change[description]</font>" : "$Subnets->mail_font_style / </font>";
		// subnet desc
		$subnet->description = strlen($subnet->description)>0 ? "$Subnets->mail_font_style $subnet->description</font>" : "$Subnets->mail_font_style / </font>";

		//content
		$content[] = "<tr>";
		$content[] = "	<td style='padding:3px 8px;border:1px solid #ccc;'><a href='".rtrim(str_replace(BASE, "",$Scan->settings->siteURL), "/")."".create_link("subnets",$subnet->sectionId,$subnet->id)."'>$Subnets->mail_font_style_href ".$Subnets->transform_to_dotted($change['ip_addr'])."</font></a></td>";
		$content[] = "	<td style='padding:3px 8px;border:1px solid #ccc;'>$Subnets->mail_font_style $change[description]</font></td>";
		$content[] = "	<td style='padding:3px 8px;border:1px solid #ccc;'>$Subnets->mail_font_style_href $change[dns_name]</font></td>";
		$content[] = "	<td style='padding:3px 8px;border:1px solid #ccc;'><a href='".rtrim(str_replace(BASE, "",$Scan->settings->siteURL), "/")."".create_link("subnets",$subnet->sectionId,$subnet->id)."'>$Subnets->mail_font_style_href ".$Subnets->transform_to_dotted($subnet->subnet)."/".$subnet->mask."</font></a>".$subnet->description."</td>";
		$content[] = "	<td style='padding:3px 8px;border:1px solid #ccc;'>$Subnets->mail_font_style $ago</td>";
		$content[] = "	<td style='padding:3px 8px;border:1px solid #ccc;'>$Subnets->mail_font_style $oldStatus > $newStatus</td>";
		$content[] = "</tr>";

		//plain content
		$content_plain[] = "\t * ".$Subnets->transform_to_dotted($change['ip_addr'])." (".$Subnets->transform_to_dotted($subnet->subnet)."/".$subnet->mask.")\r\n \t  ".strip_tags($oldStatus)." => ".strip_tags($newStatus);

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
