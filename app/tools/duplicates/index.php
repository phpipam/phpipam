<?php
/*
 * Print list of inactive hosts
 **********************************************/

# user must be authenticated
$User->check_user_session ();

# duplicated ids
$duplicated_ids_subnets   = [];
$duplicated_ids_addresses = [];


# Find duplicate subnets and hosts
$duplicate_subnets   = $Database->getObjectsQuery("select subnet,mask,count(*) as cnt from subnets where COALESCE(`isFolder`,0) = 0 group by `subnet`,`mask` having cnt > 1;");
$duplicate_addresses = $Database->getObjectsQuery("select ip_addr,count(*) as cnt from ipaddresses group by ip_addr having cnt > 1;");


# create array of duplicate items
if (sizeof($duplicate_subnets)>0) {
    foreach ($duplicate_subnets as $s) {
        $subnets = $Database->getObjectsQuery("select * from subnets where subnet = ? and mask = ?", [$s->subnet, $s->mask]);
        if (sizeof($subnets)>0) {
            foreach ($subnets as $subnet) {
                // permission

                // save
            }
        }
    }
}

print "<h4>"._('Duplicated subnets')."</h4><hr>";

# error - none found but not permitted
if (sizeof($duplicated_ids_subnets)==0) {
	print "<blockquote style='margin-top:20px;margin-left:20px;'>";
	print "<p>"._("No duplicate subnets found")."</p>";
	print "</blockquote>";
}
# found
else {
    // table
    print "<table class='table table-striped table-top'>";

    // print "<tr>";
    // print " <th></th>";
    // print " <th>"._("Address")."</th>";
    // print " <th>"._("Subnet")."</th>";
    // print " <th>"._("Hostname")."</th>";
    // print " <th>"._("Last seen")."</th>";
    // print "</tr>";

    // print
    foreach ($out as $s) {

        print "<tr>";
        print " <td><span class='status status-error'></span></td>";
        print " <td><a href='".create_link("subnets", $s->sectionId, $s->subnetId, "address-details", $s->id)."'>".$Subnets->transform_address($s->ip_addr)."</a></td>";
        print " <td><a href='".create_link("subnets", $s->sectionId, $s->subnetId)."'>".$Subnets->transform_address($s->subnet)."/".$s->mask."</a></td>";
        print " <td>$s->hostname</td>";
        print " <td>$s->lastSeen</td>";
        print "</tr>";

    }
    print "</table>";
}

print "<h4>"._('Duplicated hosts')."</h4><hr>";