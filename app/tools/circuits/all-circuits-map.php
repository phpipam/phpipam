<?php

// title
print "<h4>"._('Map of all circuits')."</h4>";
print "<hr>";


// fetch all circuits
$circuits = $Tools->fetch_all_circuits();

// check locations
if($circuits!==false) {
    // all locations
    $all_locations = array ();
    // loop
    foreach ($circuits as $circuit) {
        // format points
        $locationA = $Tools->reformat_circuit_location ($circuit->device1, $circuit->location1);
        $locationB = $Tools->reformat_circuit_location ($circuit->device2, $circuit->location2);

        if($locationA['location']!="" && $locationB['location']!="") {
            $locA = $Tools->fetch_object ("locations", "id", $locationA['location']);
            $locB = $Tools->fetch_object ("locations", "id", $locationB['location']);
            // save to all_locations array
            if ($locA!==false && $locB!==false) {
                $all_locations[] = $locA;
                $all_locations[] = $locB;
            }
        }
    }
}


// check
if ($User->settings->enableLocations=="1" && (!isset($gmaps_api_key) || strlen($gmaps_api_key)==0)) {
    $Result->show("info text-center nomargin", _("Location: Google Maps API key is unset. Please configure config.php \$gmaps_api_key to enable."));
}
elseif ($locA->name!=="/" && $locB->name!=="/") {
    // get all
    if(sizeof($all_locations)>0) {
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
    }

    // print
    if (sizeof($all_locations)>0) { ?>
        <script type="text/javascript">
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
                $html        = array();

                $map_marker_location_ids = array();
                foreach ($all_locations as $k=>$location) {
                    // description and apostrophe fix
                    $description = str_replace(array("\r\n","\n","\r"), "<br>", escape_input($location->description));
                    $description = !empty($description) ? "<span class=\'text-muted\'>".$description."</span>" : "";

                    // Don't generate duplicate map markers
                    if (!in_array($location->id, $map_marker_location_ids, true)) {
                        array_push($map_marker_location_ids, $location->id);

                        $html[] = "map.addMarker({";
                        $html[] = " title: '". escape_input($location->name). "',";
                        $html[] = " lat: '$location->lat',";
                        $html[] = " lng: '$location->long',";
                        $html[] = $k % 2 == 0 ? " icon: 'css/images/red-dot.png'," : " icon: 'css/images/blue-dot.png',";
                        $html[] = " infoWindow: {";
                        $html[] = "    content: '<h5><a href=\'".create_link("tools", "locations", $location->id)."\'>". escape_input($location->name). "</a></h5>$description'";
                        $html[] = "}";
                        $html[] = "});";
                    }

                    if($k % 2 == 0) {
                        $html[] = "path = [[".$all_locations[$k+1]->lat.", ".$all_locations[$k+1]->long."], [".$all_locations[$k]->lat.", ".$all_locations[$k]->long."]]";
                        $html[] = "map.drawPolyline({";
                        $html[] = "  path: path,";
                        $html[] = "  strokeColor: '#131540',";
                        $html[] = "  strokeOpacity: 0.6,";
                        $html[] = "  strokeWeight: 3";
                        $html[] = "});";
                    }
                }

                print implode("\n", $html);
                ?>

                // fit zoom
                <?php if(sizeof($all_locations)>1) { ?>
                map.fitZoom();
                <?php } ?>
                // resize map function
                <?php if(!isset($height)) { ?>
                function resize_map () {
                    var heights = window.innerHeight - 320;
                    $('#map_overlay').css("height", heights+"px");
                }
                resize_map();
                window.onresize = function() {
                    resize_map();
                };
                <?php } ?>
            });
        </script>

        <div style="width:100%; height:<?php print isset($height) ? $height : "1000px";?>;" id="map_overlay">
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
