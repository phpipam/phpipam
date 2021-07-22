<?php
/*
 * Print list of inactive hosts
 **********************************************/

if (!isset($Database)) {
    die();
}

# user must be authenticated
$User->check_user_session ();

# Find duplicate subnets and hosts
$duplicate_subnets = $Subnets->fetch_duplicate_subnets();
$duplicate_address = $Addresses->fetch_duplicate_addresses();

/**
 * TODO
 *
 * Enumerate $duplicate_subnets & $duplicate_address and remove entries that the user does not have permissions to read.
 * For now just require admin rights.
 */

print "<h4>"._('Duplicated subnets')."</h4>";

if (!$User->is_admin(false)) {
    $Result->show("info", _("Administrative privileges required"));
}
elseif (empty($duplicate_subnets)) {
    print "<blockquote style='margin-top:20px;margin-left:20px;'>";
    print "<p>"._("No duplicate subnets found")."</p>";
    print "</blockquote>";
}
else {
    print '<table class="table sorted 25tall table-striped table-condensed table-hover table-full table-top" data-cookie-id-table="duplicate_subnets">'. "\n";

    print "<thead>";
    print "<tr>";
    print "  <th class=''></th>";
    print "  <th class=''>"._('Subnet')."</th>";
    print "  <th class=''>"._('Description')."</th>";
    print "  <th class=''>"._('Used')."</th>";
    print "  <th class=''>"._('%Free')."</th>";
    print "  <th class=''>"._('Section')."</th>";
    print "  <th class=''>"._('VLAN')."</th>";
    print "  <th class=''>"._('VRF')."</th>";
    print "  <th class=''></th>";
    print "</tr>";
    print "</thead>";

    foreach ($duplicate_subnets as $s) {
        print "<tr>";

        # Error
        print " <td><span class='status status-error'></span></td>";

        # Subnet
        print " <td><a href='".create_link("subnets", $s->sectionId, $s->id)."'>".$s->ip."/".$s->mask."</a></td>";

        # Description
        print " <td>$s->description</td>";

        # Used / % Free
        $calculate = $Subnets->calculate_subnet_usage ( $s );
        print ' <td class="">'. $calculate['used'] .'/'. $calculate['maxhosts'] .'</td>'. "\n";
        print ' <td class="">'. $calculate['freehosts_percent'] .'</td>';

        # Section
        $section = $Tools->fetch_object("sections", "id", $s->sectionId);
        if (is_object($section)) {
            print " <td>$section->name</td>";
        } else {
            print " <td>/</td>";
        }

        # VLAN
        $vlan = $Tools->fetch_object("vlans", "vlanId", $s->vlanId);
        if (is_object($vlan)) {
            print " <td>$vlan->number</td>";
        } else {
            print " <td>/</td>";
        }

        # VRF
        $vrf = $Tools->fetch_object("vrf", "vrfId", $s->vrfId);
        if (is_object($vrf)) {
            print " <td>$vrf->name</td>";
        } else {
            print " <td>"._("Default")."</td>";
        }

        # Actions
        print "  <td class='actions'>";
        print "    <div class='btn-group'>";
        print "      <button class='btn btn btn-xs btn-default editSubnet' data-action='edit'   data-subnetid='".$s->id."' data-sectionid='".$s->sectionId."' rel='tooltip' title='"._('Edit Subnet')."'>   <i class='fa fa-gray fa-pencil'> </i></button>";
        print "      <button class='btn btn btn-xs btn-default editSubnet' data-action='delete' data-subnetid='".$s->id."' data-sectionid='".$s->sectionId."' rel='tooltip' title='"._('Delete Subnet')."'> <i class='fa fa-gray fa-times'>  </i></button>";
        print "    </div>";
        print "  </td>";

        print "</tr>";
    }

    print "</table>";
}


print "<h4>"._('Duplicated hosts')."</h4>";

if (!$User->is_admin(false)) {
    $Result->show("info", _("Administrative privileges required"));
}
elseif (empty($duplicate_address)) {
    print "<blockquote style='margin-top:20px;margin-left:20px;'>";
    print "<p>"._("No duplicate addresses found")."</p>";
    print "</blockquote>";
}
else {
    print '<table class="table sorted 25tall table-striped table-condensed table-hover table-full table-top" data-cookie-id-table="duplicate_ips">'. "\n";

    print "<thead>";
    print "<tr>";
    print "  <th class=''></th>";
    print "  <th class=''>"._('IP address')."</th>";
    print "  <th class=''>"._('Hostname')."</th>";
    print "  <th class=''>"._('Description')."</th>";
    print "  <th class=''>"._('Subnet')."</th>";
    print "  <th class=''>"._('Section')."</th>";
    print "  <th class=''>"._('VLAN')."</th>";
    print "  <th class=''>"._('VRF')."</th>";
    print "  <th class=''></th>";
    print "</tr>";
    print "</thead>";

    // print
    foreach ($duplicate_address as $a) {
        $s = $Tools->fetch_object("subnets", "id", $a->subnetId);

        if (!is_object($s)) {
            // Skip, this is an invalid IP, see Verify Database output
            continue;
        }

        $orphaned = $Subnets->has_slaves($s->id) ? " ("._("Orphaned").")" : "";

        print "<tr>";

        # Error
        print " <td><span class='status status-error'></span></td>";

        # IP address
        if ($orphaned) {
            print " <td><a href='".create_link("subnets", $s->sectionId, $s->id)."'>".$a->ip.$orphaned."</a></td>";
        } else {
            print " <td><a href='".create_link("subnets", $s->sectionId, $s->id,"address-details",$a->id)."'>".$a->ip.$orphaned."</a></td>";
        }

        # Hostname
        print " <td>$a->hostname</td>";

        # Description
        print " <td>$a->description</td>";

        # Subnet
        print " <td><a href='".create_link("subnets", $s->sectionId, $s->id)."' >".$s->ip."/".$s->mask."</a></td>";

        # Section
        $section = $Tools->fetch_object("sections", "id", $s->sectionId);
        if (is_object($section)) {
            print " <td>$section->name</td>";
        } else {
            print " <td>/</td>";
        }

        # VLAN
        $vlan = $Tools->fetch_object("vlans", "vlanId", $s->vlanId);
        if (is_object($vlan)) {
            print " <td>$vlan->number</td>";
        } else {
            print " <td>/</td>";
        }

        # VRF
        $vrf = $Tools->fetch_object("vrf", "vrfId", $s->vrfId);
        if (is_object($vrf)) {
            print " <td>$vrf->name</td>";
        } else {
            print " <td>"._("Default")."</td>";
        }

        # Actions
        print "  <td class='btn-actions'>";
        print "    <div class='btn-group'>";
        print "      <a class='edit_ipaddress   btn btn-xs btn-default modIPaddr' data-action='edit'    data-subnetId='".$s->id."' data-id='".$a->id."' href='#' rel='tooltip' title='"._('Edit IP address')."'>    <i class='fa fa-gray fa-pencil'> </i></a>";
        print "      <a class='delete_ipaddress btn btn-xs btn-default modIPaddr'  data-action='delete' data-subnetId='".$s->id."' data-id='".$a->id."' href='#' rel='tooltip' title='"._('Delete IP address')."'>  <i class='fa fa-gray fa-times'>  </i></a>";
        print "    </div>";
        print "  </td>";

        print "</tr>";
    }

    print "</table>";
}