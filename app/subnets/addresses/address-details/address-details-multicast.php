<?php
# verify that user is logged in
$User->check_user_session();

# check
$mtest = $Subnets->validate_multicast_mac ($address['mac'], $subnet['sectionId'], $subnet['vlanId'], MCUNIQUE, $address['id']);
// if duplicate
if ($mtest !== true) {
    // find duplicate
    $duplicates = $Subnets->find_duplicate_multicast_mac ($address['id'], $address['mac']);

    // formulate object
    if ($duplicates!==false) {

        // header
        print "<h4>"._('Duplicated addresses').":</h4><hr>";

        print "<table class='ipaddress_subnet table-condensed table-auto'>";

        // loop
        foreach ($duplicates as $s) {
            // fetch subnet and section
            $sn = $Subnets->fetch_subnet("id", $s->subnetId);
            $se = $Sections->fetch_section ("id", $sn->sectionId);

            $se_description = !is_blank($se->description) ? "(".$se->description.")" : "";
            $sn_description = !is_blank($sn->description) ? "(".$sn->description.")" : "";

            // address
            print "<tr>";
            print " <th>"._("Address")."</th>";
            print " <td><a href='".create_link("subnets", $sn->sectionId, $sn->id)."'>".$Subnets->transform_to_dotted( $s->ip_addr)."</a></td>";
            print "</tr>";

            // hostname
            print "<tr>";
            print " <th>"._("Hostname")."</th>";
            print " <td>$s->hostname</td>";
            print "</tr>";

            // description
            print "<tr>";
            print " <th>"._("Description")."</th>";
            print " <td>$s->i_description</td>";
            print "</tr>";

            // mac
            print "<tr>";
            print " <th>"._("Mac")."</th>";
            print " <td>$s->mac</td>";
            print "</tr>";

            // section
            print "<tr>";
            print " <th>"._("Section")."</th>";
            print " <td><a href='".create_link("subnets", $sn->sectionId)."'>$se->name</a> $se_descriptionn</td>";
            print "</tr>";

            // subnet
            print "<tr>";
            print " <th>"._("Subnet")."</th>";
            if($sn->isFolder==1) {
                print " <td><a href='".create_link("folders", $sn->sectionId, $sn->id)."'>$sn_description</a></td>";
            }
            else {
                print " <td><a href='".create_link("subnets", $sn->sectionId, $sn->id)."'>".$Subnets->transform_address($sn->subnet, "dotted")."/".$sn->mask."</a>$sn_description</td>";
            }
            print "</tr>";

            // divider
            print "<tr>";
            print " <td colspan='2'><br><hr></td>";
            print "</tr>";
        }

        print "</table>";
    }
    else {
        $Result->show("info", _("No duplicated addresses found"), false);
    }
}
else {
    $Result->show("info", _("No duplicated addresses found"), false);
}

?>