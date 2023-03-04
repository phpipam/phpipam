<?php
/*
 * Print list of inactive hosts
 **********************************************/

# required functions
if(!isset($User)) {
	require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
	# classes
	$Database	= new Database_PDO;
	$User 		= new User ($Database);
	$Tools 		= new Tools ($Database);
	$Subnets 	= new Subnets ($Database);
	$Addresses 	= new Addresses ($Database);
	$Result		= new Result ();
}
else {
    header("Location: ".create_link('tools', 'inactive-hosts'));
}

# user must be authenticated
$User->check_user_session ();

# no errors!
//ini_set('display_errors', 0);

# set size parameters
$height = 200;
$slimit = 5;			//we dont need this, we will recalculate

# count
$m = 0;

// fetch widget
$widget = $Tools->fetch_object ("widgets", "wfile", "inactive-hosts");

# if direct request include plot JS
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest")	{
	# get widget details
	if(!$widget = $Tools->fetch_object ("widgets", "wfile", $_GET['section'])) { $Result->show("danger", _("Invalid widget"), true); }
	# reset size and limit
	$height = 350;
	$slimit = 100;
	# and print title
	print "<div class='container'>";
	print "<h4 style='margin-top:40px;'>$widget->wtitle</h4><hr>";
	print "</div>";
}

// time_range - 30 days
$seconds = 86400 * 30;

# Find inactive hosts
$inactive_hosts = $Subnets->find_inactive_hosts ($seconds, $slimit);

# check permissions
if ($inactive_hosts!==false) {
    foreach ($inactive_hosts as $h) {
        # fetch subnet
        $subnet = $Subnets->fetch_subnet("id", $h->subnetId);
        if ($subnet!==false) {
            # check permission of user
            $sp = $Subnets-> check_permission ($User->user, $subnet->id);
            if($sp != "0") {
                $h->sectionId = $subnet->sectionId;
                $h->subnet = $subnet->subnet;
                $h->mask = $subnet->mask;
                $out[] = $h;
            }
            $m++;
            # break after limit
            if ($m>$slimit) {
                break;
            }
        }
    }
}

# error - none found but not permitted
if ($inactive_hosts===false) {
	print "<blockquote style='margin-top:20px;margin-left:20px;'>";
	print "<p>"._("No inactive hosts found")."</p>";
	print "</blockquote>";
}
# error - found but not permitted
elseif (!isset($out)) {
	print "<blockquote style='margin-top:20px;margin-left:20px;'>";
	print "<p>"._("No inactive hosts found")."</p>";
	print "</blockquote>";
}
# found
else {
    // table
    print "<table class='table table-top table-threshold table-condensed'>";

    print "<tr>";
    print " <th></th>";
    print " <th>"._("Address")."</th>";
    print " <th>"._("Subnet")."</th>";
    print " <th>"._("Hostname")."</th>";
    print " <th>"._("Last seen")."</th>";
    print "</tr>";

    // print
    foreach ($out as $s) {

        print "<tr>";
        print " <td><span class='status status-error'></span></td>";
        print " <td class='ip_addr'><a href='".create_link("subnets", $s->sectionId, $s->subnetId, "address-details", $s->id)."'>".$Subnets->transform_address($s->ip_addr)."</a></td>";
        print " <td><a href='".create_link("subnets", $s->sectionId, $s->subnetId)."'>".$Subnets->transform_address($s->subnet)."/".$s->mask."</a></td>";
        print " <td>$s->hostname</td>";
        print " <td>$s->lastSeen</td>";
        print "</tr>";

    }

    print "</table>";
}