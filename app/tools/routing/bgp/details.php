<?php

# perm check
$User->check_module_permissions ("routing", User::ACCESS_R, true, false);

# check
is_numeric($GET->sPage) ? : $Result->show("danger", _("Invalid ID"), true);


// back link
print "<div'>";
print "<a class='btn btn-sm btn-default' href='".create_link($GET->page,"routing","bgp")."' style='margin-bottom:10px;'><i class='fa fa-angle-left'></i> ". _('BGP table')."</a>";
print "</div>";


# fetch bgp details
$bgp = $Tools->fetch_object ("routing_bgp", "id", $GET->sPage);
if($bgp===false) {
    $Result->show("danger", _("Invalid ID"), true);
}
else {
    // circuit fetch
    if ($User->settings->enableCircuits=="1") {
        $circuit = $Tools->fetch_object ("circuits", "id", $bgp->circuit_id);
    }

    // vrf fetch
    if ($User->settings->enableVRF=="1") {
        $vrf = $Tools->fetch_object ("vrf", "vrfId", $bgp->vrf_id);
    }

    // customers fetch
    if ($User->settings->enableCustomers=="1") {
        $customer = $Tools->fetch_object ("customers", "id", $bgp->customer_id);
    }

    // overlay
    print "<div class='row'>";
        //
        // details
        //
        print "<div class='col-xs-12'>";
        include("details-general.php");
        print "</div>";

        //
        // subnets
        //
        print "<div class='col-xs-12' style='margin-top:50px;'>";
        include("details-subnets.php");
        print "</div>";

    print "</div>";
}