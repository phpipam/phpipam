<?php

# Check we have been included and not called directly
require( dirname(__FILE__) . '/../../../../functions/include-only.php' );

# perm check
$User->check_module_permissions ("circuits", User::ACCESS_R, true, false);

// title
print "<h4>"._('Map')."</h4>";
print "<hr>";

// check
if ($User->settings->enableLocations=="1" && strlen(Config::ValueOf('gmaps_api_key'))==0) {
    $Result->show("info text-center nomargin", _("Location: Google Maps API key is unset. Please configure config.php \$gmaps_api_key to enable."));
}
elseif ($locA->name_print!=="/" && $locB->name_print!=="/") {
	$all_locations = array ();

	// add point A and B
	if ($locA->name_print!=="/") { $all_locations[] = $locA; }
	if ($locB->name_print!=="/") { $all_locations[] = $locB; }

    // get all
    foreach ($all_locations as $k=>$l) {
        // map used
        if(strlen($l->long)==0 && strlen($l->lat)==0 && strlen($l->address)==0 ) {
            // map not used
            unset($all_locations[$k]);
        }
        // recode
        elseif (strlen($l->long)==0 && strlen($l->lat)==0 && strlen($l->address)>0) {
            $latlng = $Tools->get_latlng_from_address ($l->address);
            if($latlng['lat']==NULL || $latlng['lng']==NULL) {
                unset($all_locations[$k]);
            }
            else {
                // save
                $Tools->update_latlng ($l->id, $latlng['lat'], $latlng['lng']);
                $all_locations[$k]->lat = $latlng['lat'];
                $all_locations[$k]->long = $latlng['lng'];
            }
        }
    }

    // reindex array
    array_keys($all_locations);

    // print
    if (sizeof($all_locations)>0) { ?>
        <script>
            $(document).ready(function() {
                // init gmaps
                var map = new GMaps({
                  div: '#gmap',
                  zoom: 15,
                  lat: '<?php print $all_locations[0]->lat; ?>',
                  lng: '<?php print $all_locations[0]->long; ?>'
                });

                var bounds = [];

                // add markers
                <?php
                $html = array();
                foreach ($all_locations as $k=>$location) {
                    // description and apostrophe fix
                    $location->description = strlen($location->description)>0 ? "<span class=\'text-muted\'>".addslashes($location->description)."</span>" : "";
                    $location->description = str_replace(array("\r\n","\n","\r"), "<br>", $location->description );

                    $html[] = "map.addMarker({";
                    $html[] = " title: '". addslashes($location->name). "',";
                    $html[] = " lat: '$location->lat',";
                    $html[] = " lng: '$location->long',";
                    $html[] = $k==0 ? " icon: 'css/images/red-dot.png'," : " icon: 'css/images/blue-dot.png',";
                    $html[] = " infoWindow: {";
                    $html[] = "    content: '<h5><a href=\'".create_link("tools", "locations", $location->id)."\'>". addslashes($location->name). "</a></h5>$location->description'";
                    $html[] = "}";
                    $html[] = "});";
                }
                print implode("\n", $html);

                // add line
                if(sizeof($all_locations)==2) {
                	$html   = array ();
                	$html[] = "path = [[".$all_locations[0]->lat.", ".$all_locations[0]->long."], [".$all_locations[1]->lat.", ".$all_locations[1]->long."]]";
                	$html[] = "map.drawPolyline({";
                	$html[] = "  path: path,";
                	$html[] = "  strokeColor: '#131540',";
                	$html[] = "  strokeOpacity: 0.6,";
                	$html[] = "	 strokeWeight: 3";
                	$html[] = "});";

                print implode("\n", $html);
                }
                ?>

                // fit zoom
                <?php if(sizeof($all_locations)>1) { ?>
                map.fitZoom();
                <?php } ?>
            });
        </script>

        <div style="width:100%; height:400px;" id="map_overlay">
        	<div id="gmap" style="width:100%; height:100%;"></div>
        </div>


        <?php
        # no coordinates
        }
        else {
            $Result->show("info","No Locations with coordinates configured", false);
        }
}
else {
	$Result->show("danger", _("Location not set"), true);
}
