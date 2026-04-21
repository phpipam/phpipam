<?php
# verify that user is logged in
$User->check_user_session();

// now search for similar addresses if chosen
if (!is_blank($User->settings->link_field)) {
	// search
	$similar = $Addresses->search_similar_addresses ((object)$address, $User->settings->link_field, $address[$User->settings->link_field]);

	if($similar!==false) {
		$link_field_print = $User->settings->link_field == "ip_addr" ? $Subnets->transform_to_dotted($address[$User->settings->link_field]) : $address[$User->settings->link_field];

		print "<h4>"._('Addresses linked with')." ".$User->settings->link_field." <strong>".$link_field_print."</strong>:</h4><hr>";

        print "<table class='ipaddress_subnet table-condensed table-auto'>";

        foreach ($similar as $k=>$s) {
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

            // section
            print "<tr>";
            print " <th>"._("Section")."</th>";
            print " <td><a href='".create_link("subnets", $sn->sectionId)."'>$se->name</a> $se_description</td>";
            print "</tr>";

            // subnet
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