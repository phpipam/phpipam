<?php
/*
 * Print list of inactive hosts
 **********************************************/

# user must be authenticated
$User->check_user_session ();

// limit
$slimit = 100;

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

print "<h4>"._('Inactive hosts')."</h4><hr>";

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
    print "<table class='table table-striped table-top'>";

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
        print " <td><a href='".create_link("subnets", $s->sectionId, $s->subnetId, "address-details", $s->id)."'>".$Subnets->transform_address($s->ip_addr)."</a></td>";
        print " <td><a href='".create_link("subnets", $s->sectionId, $s->subnetId)."'>".$Subnets->transform_address($s->subnet)."/".$s->mask."</a></td>";
        print " <td>$s->dns_name</td>";
        print " <td>$s->lastSeen</td>";
        print "</tr>";

    }
    print "</table>";
}
?>
