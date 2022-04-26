<?php

/**
 * This script does the following:
 *      - fetches all subnets that are marked for discovering new hosts
 *      - Scans each subnet for new hosts
 *      - If new host is discovered it will be added to database
 *
 *  Scan type be used as defined under administration:
 *      - ping
 *      - pear ping
 *      - fping
 *
 *  Fping is new since version 1.2, it will work faster because it has built-in threading
 *  so we are only forking separate subnets
 *
 *  Script must be run from cron, here is a crontab example, 1x/day should be enough:
 *      0 1 * * *  /usr/local/bin/php /<sitepath>/functions/scripts/pingCheck.php > /dev/null 2>&1
 *
 *
 *  In case of problems set reset_debugging to true
 *
 */

# include required scripts
require_once(dirname(__FILE__) . '/../functions.php');
require(dirname(__FILE__) . '/../../functions/classes/class.Thread.php');

# initialize objects
$Database   = new Database_PDO;
$Subnets    = new Subnets($Database);
$Addresses  = new Addresses($Database);
$Tools      = new Tools($Database);
$Scan       = new Scan($Database);
$DNS        = new DNS($Database);
$Result     = new Result();

// set exit flag to true
$Scan->ping_set_exit(true);
// set debugging
$Scan->set_debugging(false);
// change scan type?
if (@$config['discovery_check_method'])
    $Scan->reset_scan_method($config['discovery_check_method']);

# Check if scanning has been disabled
if ($Scan->icmp_type == "none") {
    $Result->show("danger", _('Scanning disabled') . ' (scanPingType=None)', true, true);
}

// set ping statuses
$statuses = explode(";", $Scan->settings->pingStatus);
// set mail override flag
if (!isset($config['discovery_check_send_mail'])) {
    $config['discovery_check_send_mail'] = true;
}

// set now for whole script
$now     = time();
$nowdate = date("Y-m-d H:i:s");

// response for mailing
$address_change = [];          // Array with differences, can be used to email to admins
$hostnames      = [];          // Array with detected hostnames


// script can only be run from cli
if (php_sapi_name() != "cli") {
    die("discoveryCheck-Fatal-Error: This script can only be run from cli!\n");
}
// test to see if threading is available
if (!PingThread::available($errmsg)) {
    die("discoveryCheck-Fatal-Error: Threading is required for scanning subnets - Error: $errmsg\n");
}
// verify fping / ping path
if ($Scan->icmp_type == "fping" && !file_exists($Scan->settings->scanFPingPath)) {
    die("discoveryCheck-Fatal-Error: Invalid fping path!\n");
} elseif (!file_exists($Scan->settings->scanPingPath)) {
    die("discoveryCheck-Fatal-Error: Invalid ping path!\n");
}
// verify date.timezone
if (strlen(ini_get('date.timezone')) == 0) {
    print("discoveryCheck-Warning: date.timezone is not set in ".php_ini_loaded_file()."\n");
    print("discoveryCheck-Warning: Online/Offline calculations may be unreliable due to incorrect local time.\n\n");
}


//first fetch all subnets to be scanned
$scan_subnets = $Subnets->fetch_all_subnets_for_discoveryCheck(1);
$addresses = [];

//set addresses
if ($scan_subnets !== false) {
    // initial array
    $addresses_tmp = [];
    // loop
    foreach ($scan_subnets as $i => $s) {
        $addresses_tmp[$s->id] = $Scan->prepare_addresses_to_scan("discovery", $s->id, false);
        // save discovery time
        $Scan->update_subnet_discoverytime($s->id, $nowdate);
    }

    //reindex
    foreach ($addresses_tmp as $s_id => $a) {
        foreach ($a as $ip) {
            $addresses[] = ["subnetId" => $s_id, "ip_addr" => $ip];
        }
    }
}


if (empty($scan_subnets)) {
    die("discoveryCheck: No subnets are marked for new hosts checking\n");
}
if ($Scan->get_debugging()) {
    print "discoveryCheck: Using $Scan->icmp_type\n--------------------\n\n";
    print_r($scan_subnets);
}


$z = 0;         // array index

// let's just reindex the arrays to save future issues
$scan_subnets  = array_values($scan_subnets);
$addresses     = array_values($addresses);

if ($Scan->icmp_type == "fping") {
    //different scan for fping
    print "discoveryCheck: Scan start, " . sizeof($scan_subnets) . " subnets\n";

    while ($z < sizeof($scan_subnets)) {

        $threads = [];

        try {
            //run per MAX_THREADS
            for ($i = 0; $i < $Scan->settings->scanMaxThreads; $i++) {
                if (isset($scan_subnets[$z])) {
                    $thread = new PingThread('fping_subnet');
                    $thread->start_fping($Subnets->transform_to_dotted($scan_subnets[$z]->subnet) . "/" . $scan_subnets[$z]->mask);
                    $threads[$z++] = $thread;
                }
            }
        } catch (Exception $e) {
            // We failed to spawn a scanning process.
            print "discoveryCheck-Warning: Failed to start scanning pool, spawned " . sizeof($threads) . " of " . $Scan->settings->scanMaxThreads . "\n";
        }

        if (empty($threads)) {
            die("discoveryCheck-Fatal-Error: Unable to spawn scanning pool\n");
        }

        // wait for all the threads to finish
        foreach ($threads as $index => $thread) {
            $scan_subnets[$index]->discovered = $thread->ipc_recv_data();
            $thread->getExitCode();
        }
        unset($threads);
    }

    //fping finds all subnet addresses, we must remove existing ones !
    foreach ($scan_subnets as $sk => $s) {
        if (isset($s->discovered) && is_array($s->discovered)) {
            foreach ($s->discovered as $rk => $result) {
                if (!in_array($Subnets->transform_to_decimal($result), $addresses_tmp[$s->id])) {
                    unset($scan_subnets[$sk]->discovered[$rk]);
                }
            }
            //rekey
            $scan_subnets[$sk]->discovered = array_values($scan_subnets[$sk]->discovered);
        }
    }
} else {
    //ping, pear
    print "discoveryCheck: Scan start, " . sizeof($addresses) . " IPs\n";

    while ($z < sizeof($addresses)) {

        $threads = [];

        try {
            ///run per MAX_THREADS
            for ($i = 0; $i < $Scan->settings->scanMaxThreads; $i++) {
                if (isset($addresses[$z])) {
                    $thread = new PingThread('ping_address');
                    $thread->start($Subnets->transform_to_dotted($addresses[$z]['ip_addr']));
                    $threads[$z++] = $thread;
                }
            }
        } catch (Exception $e) {
            // We failed to spawn a scanning process.
            print "discoveryCheck-Warning: Failed to start scanning pool, spawned " . sizeof($threads) . " of " . $Scan->settings->scanMaxThreads . "\n";
        }

        if (empty($threads)) {
            die("discoveryCheck-Fatal-Error: Unable to spawn scanning pool\n");
        }

        // wait for all the threads to finish
        foreach ($threads as $index => $thread) {
            if ($thread->getExitCode() !== 0) {
                //unset dead hosts
                $addresses[$index] = null;
            }
        }
        unset($threads);
    }

    $addresses = array_filter($addresses);

    //ok, we have all available addresses, rekey them
    foreach ($addresses as $a) {
        $add_tmp[$a['subnetId']][] = $Subnets->transform_to_dotted($a['ip_addr']);
    }
    //add to scan_subnets as result
    foreach ($scan_subnets as $sk => $s) {
        if (isset($add_tmp[$s->id])) {
            $scan_subnets[$sk]->discovered = $add_tmp[$s->id];
        }
    }
}

# print change
if ($Scan->get_debugging()) {
    print "\n";
    print "discoveryCheck: Discovered addresses:\n----------\n";
    print_r($scan_subnets);
}



# reinitialize objects
$Database   = new Database_PDO;
$Admin      = new Admin($Database, false);
$Addresses  = new Addresses($Database);
$Subnets    = new Subnets($Database);
$DNS        = new DNS($Database);
$Scan       = new Scan($Database);
$Result     = new Result();

# insert to database
$discovered = 0;                //for mailing

foreach ($scan_subnets as $s) {
    if (isset($s->discovered)) {
        foreach ($s->discovered as $ip) {
            // fetch subnet
            $subnet = $Subnets->fetch_subnet("id", $s->id);
            $nsid = $subnet === false ? false : $subnet->nameserverId;
            // try to resolve hostname
            $hostname = $DNS->resolve_address($ip, false, true, $nsid);
            // save to hostnames
            $hostnames[$ip] = $hostname['name'] == $ip ? "" : $hostname['name'];

            //set update query
            $values = [
                "subnetId"    => $s->id,
                "ip_addr"     => $ip,
                "hostname"    => $hostname['name'],
                "description" => _("-- autodiscovered --"),
                "note"        => _("This host was autodiscovered on") . " " . $nowdate,
                "lastSeen"    => $nowdate,
                "state"       => "2",
                "action"      => "add"
            ];
            //insert
            $Addresses->modify_address($values);

            //set discovered
            $discovered++;
        }
    }
}

# update scan time
$Scan->ping_update_scanagent_checktime(1, $nowdate);
print "discoveryCheck: Scan complete, $discovered new hosts\n";

# send mail
if ($discovered > 0 && $config['discovery_check_send_mail']) {

    # check for recipients
    foreach ($Admin->fetch_multiple_objects("users", "role", "Administrator") as $admin) {
        if ($admin->mailNotify == "Yes") {
            $recepients[] = ["name" => $admin->real_name, "email" => $admin->email];
        }
    }
    # none?
    if (!isset($recepients)) {
        die();
    }

    # fake user object, needed for create_link
    $User = new FakeUser($Scan->settings->prettyLinks);

    # try to send
    try {
        # fetch mailer settings
        $mail_settings = $Admin->fetch_object("settingsMail", "id", 1);
        # initialize mailer
        $phpipam_mail = new phpipam_mail($Scan->settings, $mail_settings);

        // set subject
        $subject    = _("phpIPAM new addresses detected") . " " . date("Y-m-d H:i:s");

        //html
        $content[] = "<h3>" . _("phpIPAM found") . " $discovered " . _("new hosts") . "</h3>";
        $content[] = "<table style='margin-left:10px;margin-top:5px;width:auto;padding:0px;border-collapse:collapse;border:1px solid gray;'>";
        $content[] = "<tr>";
        $content[] = "  <th style='padding:3px 8px;border:1px solid silver;border-bottom:2px solid gray;'>" . _("IP") . "</th>";
        $content[] = "  <th style='padding:3px 8px;border:1px solid silver;border-bottom:2px solid gray;'>" . _("Hostname") . "</th>";
        $content[] = "  <th style='padding:3px 8px;border:1px solid silver;border-bottom:2px solid gray;'>" . _("Subnet") . "</th>";
        $content[] = "  <th style='padding:3px 8px;border:1px solid silver;border-bottom:2px solid gray;'>" . _("Section") . "</th>";
        $content[] = "</tr>";
        //plain
        $content_plain[] = _("phpIPAM found") . " $discovered " . _("new hosts") . "\r\n------------------------------";
        //Changes
        foreach ($scan_subnets as $s) {
            if (is_array($s->discovered)) {
                foreach ($s->discovered as $ip) {
                    //set subnet
                    $subnet      = $Subnets->fetch_subnet(null, $s->id);
                    //set section
                    $section     = $Admin->fetch_object("sections", "id", $s->sectionId);

                    $content[] = "<tr>";
                    $content[] = "  <td style='padding:3px 8px;border:1px solid silver;'>$ip</td>";
                    $content[] = "  <td style='padding:3px 8px;border:1px solid silver;'>" . $hostnames[$ip] . "</td>";
                    $content[] = "  <td style='padding:3px 8px;border:1px solid silver;'><a href='" . rtrim($Scan->settings->siteURL, "/") . "" . create_link("subnets", $section->id, $subnet->id) . "'>" . $Subnets->transform_to_dotted($subnet->subnet) . "/" . $subnet->mask . " - " . $subnet->description . "</a></td>";
                    $content[] = "  <td style='padding:3px 8px;border:1px solid silver;'><a href='" . rtrim($Scan->settings->siteURL, "/") . "" . create_link("subnets", $section->id) . "'>$section->name $section->description</a></td>";
                    $content[] = "</tr>";

                    //plain content
                    $content_plain[] = "\t * $ip (" . $Subnets->transform_to_dotted($subnet->subnet) . "/" . $subnet->mask . ")";
                }
            }
        }
        $content[] = "</table>";


        # set content
        $content        = $phpipam_mail->generate_message(implode("\r\n", $content));
        $content_plain  = implode("\r\n", $content_plain);

        $phpipam_mail->Php_mailer->setFrom($mail_settings->mAdminMail, $mail_settings->mAdminName);
        //add all admins to CC
        foreach ($recepients as $admin) {
            $phpipam_mail->Php_mailer->addAddress(addslashes($admin['email']), addslashes($admin['name']));
        }
        $phpipam_mail->Php_mailer->Subject = $subject;
        $phpipam_mail->Php_mailer->msgHTML($content);
        $phpipam_mail->Php_mailer->AltBody = $content_plain;
        //send
        $phpipam_mail->Php_mailer->send();
    } catch (PHPMailer\PHPMailer\Exception $e) {
        $Result->show_cli(_("Mailer Error") . ": " . $e->errorMessage(), true);
    } catch (Exception $e) {
        $Result->show_cli(_("Mailer Error") . ": " . $e->getMessage(), true);
    }
}
