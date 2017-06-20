<?php

/**
 * Script to print racks
 ***************************/

# verify that user is logged in
$User->check_user_session();
?>

<?php

# check that rack support isenabled
if ($User->settings->enableRACK!="1") {
    $Result->show("danger", _("RACK management disabled."), false);
}
else {
    # init racks object
    $Racks = new phpipam_rack ($Database);
    # fetch all racks
    $Racks->fetch_all_racks();

    # none
    if ($Racks->all_racks === false) {
        $Result->show("info", _("No racks available"), false, false, true);
    }
    # print
    else {

        // array of racks
        $all_rack_locations = array ();

        // reorder by location
        foreach ($Racks->all_racks as $r) {
            // null location
            if($r->location=="" || is_null($r->location)) {
                $r->location = "0";
            }
            // save
            $all_rack_locations[$r->location][] = $r;
        }

        // reorder
        ksort($all_rack_locations);

        $m=1;
        // go through locations and print racks
        foreach ($all_rack_locations as $location_id=>$all_racks) {
            // null
            if($location_id=="0") {
                $location = new StdClass ();
                $location->name = "No location";
                $location->description = "Location not set for this racks.";
            }
            else {
                $location = $Tools->fetch_object ("locations", "id", $location_id);
            }

            // print
            if($location!==false) {
                // title
                if($location_id==0)
                print "<h4>$m.) ".$location->name."</h4><hr>";
                else
                print "<h4><a href='".create_link("tools", "locations", $location_id)."'>$m.) ".$location->name."</a></h4><hr>";
                print strlen($location->description)>0 ? "<span class='text-muted'>$location->description</span>" : "";
                // racks
                print "<div style='margin-bottom:30px;'>";
                foreach ($all_racks as $r) {
                    print "<img src='".$Tools->create_rack_link ($r->id)."'' style='width:180px;margin-right:5px;vertical-align:bottom'>";
                }
                print "</div>";

                $m++;
            }
        }


    }
}