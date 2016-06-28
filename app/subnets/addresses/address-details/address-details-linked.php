<?php
# verify that user is logged in
$User->check_user_session();

// now search for similar addresses if chosen
if (strlen($User->settings->link_field)>0) {
	// search
	$similar = $Addresses->search_similar_addresses ($User->settings->link_field, $address[$User->settings->link_field], $address['id']);

	if($similar!==false) {

		print "<h4>"._('Addresses linked with')." ".$User->settings->link_field." <strong>".$address[$User->settings->link_field]."</strong>:</h4><hr>";

        print "<table class='ipaddress_subnet table-condensed table-auto'>";

        foreach ($similar as $k=>$s) {
            // fetch subnet and section
            $sn = $Subnets->fetch_subnet("id", $s->subnetId);
            $se = $Sections->fetch_section ("id", $sn->sectionId);

            $se_description = strlen($se->description)>0 ? "(".$se->description.")" : "";
            $sn_description = strlen($sn->description)>0 ? "(".$sn->description.")" : "";

            // address
            print "<tr>";
            print " <th>"._("Address")."</th>";
            print " <td><a href='".create_link("subnets", $sn->sectionId, $sn->id)."'>".$Subnets->transform_to_dotted( $s->ip_addr)."</a></td>";
            print "</tr>";

            // hostname
            print "<tr>";
            print " <th>"._("Hostname")."</th>";
            print " <td>$s->dns_name</td>";
            print "</tr>";

            // section
            $se->description = strlen($se->description)>0 ? "(".$se->description.")" : "";
            print "<tr>";
            print " <th>"._("Section")."</th>";
            print " <td><a href='".create_link("subnets", $sn->sectionId)."'>$se->name</a> $se_description</td>";
            print "</tr>";

            // subnet
            $sn->description = strlen($sn->description)>0 ? "(".$sn->description.")" : "";
            print "<tr>";
            print " <th>"._("Subnet")."</th>";
            print " <td><a href='".create_link("subnets", $sn->sectionId, $sn->id)."'>".$Subnets->transform_address($sn->subnet, "dotted")."/".$sn->mask."</a> ".$sn_description."</td>";
            print "</tr>";

            // divider
            print "<tr>";
            print " <td colspan='2'><br><hr></td>";
            print "</tr>";

        }

        print "</table>";
	}
	else {
        $Result->show("info", _("No linked addresses found"), false);
	}
}
else {
    $Result->show("info", _("Address linking disabled"), false);
}
?>