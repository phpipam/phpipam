<?php

/*
 * This script finds slave subnets via snmp
 *
 * Used in creating new subnet
 *
 ******************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Tools	 	= new Tools ($Database);
$Sections	= new Sections ($Database);
$Subnets	= new Subnets ($Database);
$Snmp       = new phpipamSNMP ();
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# scan disabled
if ($User->settings->enableSNMP!="1")           { $Result->show("danger", "SNMP module disbled", true, true, false, true); }

# check section permissions
if($Sections->check_permission ($User->user, $_POST['sectionId']) != 3) { $Result->show("danger", _('You do not have permissions to add new subnet in this section')."!", true, true); }

// no errors
error_reporting(E_ERROR);

# fetch devices that use get_routing_table query
$devices_used = $Tools->fetch_multiple_objects ("devices", "snmp_queries", "%get_routing_table%", "id", true, true);

# fetch all IPv4 masks
$masks =  $Subnets->get_ipv4_masks ();

// if none set die
if ($devices_used===false)                      { $Result->show("danger", _("No devices for SNMP route table query available")."!", true, true, false, true); }

// ok, we have devices, connect to each device and do query
foreach ($devices_used as $d) {
    // init
    $Snmp->set_snmp_device ($d);
    // execute
    try {
        $res = $Snmp->get_query("get_routing_table");
        // remove those not in subnet
        if (sizeof($res)>0) {
           // save for debug
           $debug[$d->hostname][$q] = $res;

           // save result
           $found[$d->id][$q] = $res;
        }
    } catch (Exception $e) {
       // save for debug
       $debug[$d->hostname][$q] = $res;
       $errors[] = $e->getMessage();
	}
}

# none and errors
if(sizeof($found)==0 && isset($errors))          { $Result->show("info", _("No new subnets found")."</div><hr><div class='alert alert-warning'>".implode("<hr>", $errors)."</div>", true, true); }
# none
elseif(sizeof($found)==0) 	                     { $Result->show("info", _("No new subnets found")."!", true, true); }
# ok
else {
?>

<!-- header -->
<div class="pHeader"><?php print _('Scan results'); ?></div>

<!-- content -->
<div class="pContent">
        <?php

    	//table
    	print "<table class='table table-striped table-top table-condensed'>";

    	// titles
    	print "<tr>";
    	print "	<th>"._("Subnet")."</th>";
    	print "	<th>"._("Bitmask")."</th>";
    	print "	<th>"._("Mask")."</th>";
    	print "	<th style='width:5px;'></th>";
    	print "</tr>";

    	// alive
    	$m=0;
    	foreach($found as $deviceid=>$device) {

        	// fetch device
        	$device_details = $Tools->fetch_object("devices", "id", $deviceid);

        	foreach ($device as $query_result ) {
            	if ($query_result!==false) {

                	print "<tr>";
                	print " <th colspan='6'><i class='fa fa-times btn btn-xs btn-danger remove-snmp-results' data-target='device-$deviceid'></i> ".$device_details->hostname."</th>";
                	print "</tr>";

                    print "<tbody id=device-$deviceid>";
                	foreach ($query_result as $ip) {
                    	//get bitmask
                    	foreach ($masks as $k=>$m) {
                        	if ($m->netmask == $ip['mask']) {
                            	$ip['bitmask']=$k;
                            	break;
                        	}
                    	}
                        print "<tr>";
                		//ip
                		print "<td>$ip[subnet]</td>";
                		print "<td>$ip[mask]</td>";
                		print "<td>$ip[bitmask]</td>";
                		//select button
                		print 	"<td><a href='' class='btn btn-xs btn-success select-snmp-subnet' data-subnet='$ip[subnet]' data-mask='$ip[bitmask]'><i class='fa fa-check'></i> "._('Select')."</a></td>";
                		print "</tr>";

                		$m++;
                    }
                    print "</tbody>";
                }
    		}
    	}

    	print "</table>";
    }
    // print errors
    if (isset($errors)) {
        print "<hr>";
        foreach ($errors as $e) {
            print $Result->show ("warning", $e, false, false, true);
        }
    }

    //print scan method
    print "<div class='text-right' style='margin-top:7px;'>";
    print " <span class='muted'>";
    print " Scan method: SNMP Route table<hr>";
    print " Scanned devices: <br>";
    foreach ($debug as $k=>$d) {
        print "&middot; ".$k."<br>";
    }
    print "</span>";
    print "</div>";

    # show debug?
    if($_POST['debug']==1) 				{ print "<pre>"; print_r($debug); print "</pre>"; }

    ?>
</div>


<!-- footer -->
<div class="pFooter">
    <button class="btn btn-sm btn-default hidePopup2"><?php print _('Cancel'); ?></button>
</div>
