<?php

# Check we have been included and not called directly
require( dirname(__FILE__) . '/../../../functions/include-only.php' );

#
# Prints map of all Circuits
#

# perm check
$User->check_module_permissions ("circuits", User::ACCESS_R, true, false);

# title
if(isset($_GET['map_specific']) && $_GET['map_specific'] == 'true'){
    print "<h3>"._('Map of circuits')."</h3>";
    $circuits_to_map = json_decode($_GET['circuits_to_map'], true);
}else{
    print "<h3>"._('Map of all circuits')."</h3>";
}


// fetch all circuits and types. Create a hash of the types to avoid lots of queries
$circuits = $Tools->fetch_all_circuits();
$circuit_types = $Tools->fetch_all_objects ("circuitTypes", "ctname");
$type_hash = [];
foreach($circuit_types as $t){
    $type_hash[$t->id] = $t;
}

//Fetch all locations and store info hash, same as above.
//This will elimate the need of looping through circuits the first time
$locations = $Tools->fetch_all_objects("locations");
$all_locations = [];

foreach($locations as $l){ $all_locations[$l->id] = $l; }

// check
if ($User->settings->enableLocations=="1" && strlen(Config::ValueOf('gmaps_api_key'))==0) {
    $Result->show("info text-center nomargin", _("Location: Google Maps API key is unset. Please configure config.php \$gmaps_api_key to enable."));
}
//elseif ($locA->name!=="/" && $locB->name!=="/") { ?
elseif(true) {
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
                var lineSymbol = {
                    path: 'M 0,-1 0,1',
                    strokeOpacity: 1,
                    scale: 4
                      };




                // add markers
                <?php
                $html        = array();

                $map_marker_location_ids = array();
                //Instead of using the locations as the base of the loop, use the circuits to put more info on the map
                //all_locations is now a hash based on database IDs, so no worry about worrying about duplicates in the array

                //Add all locations to map
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
                    /*
                    if($k % 2 == 0) {
                        $html[] = "path = [[".$all_locations[$k+1]->lat.", ".$all_locations[$k+1]->long."], [".$all_locations[$k]->lat.", ".$all_locations[$k]->long."]]";
                        $html[] = "map.drawPolyline({";
                        $html[] = "  path: path,";
                        $html[] = "  strokeColor: '#131540',";
                        $html[] = "  strokeOpacity: 0.6,";
                        $html[] = "  strokeWeight: 3";
                        $html[] = "});";
                    }
                    */
                }
                //Add all circuits to map with type information
                foreach ($circuits as $circuit) {
                  //If map_spepcifc is set and its in the array OR it isn't set, map all
                  if((isset($_GET['map_specific']) && in_array($circuit->id,$circuits_to_map)) || (!isset($_GET['map_specific']))){
                    $html[] = "path = [[".$all_locations[$circuit->location1]->lat.", ".$all_locations[$circuit->location1]->long."], [".$all_locations[$circuit->location2]->lat.", ".$all_locations[$circuit->location2]->long."]]";
                    $html[] = "map.drawPolyline({";
                    $html[] = "  path: path,";
                    $html[] = "  strokeColor: '".$type_hash[$circuit->type]->ctcolor."',";
                    if($type_hash[$circuit->type]->ctpattern == "Dotted") { $html[] = "  strokeOpacity: 0,"; }
                    else{ $html[] = "  strokeOpacity: 0.6,"; }
                    if($type_hash[$circuit->type]->ctpattern == "Dotted") {
                      $html[] = " icons:[{
                        icon: lineSymbol,
                        offset: '0',
                        repeat: '20px'
                        }],"; }
                    $html[] = "  strokeWeight: 3";
                    $html[] = "});";

                    print implode("\n", $html);
                  }
                }
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
        print "<hr>";
        print "<div class='text-right'>";
        print "<h5>"._('Circuit Type Legend')."</h5>";
        foreach($circuit_types as $t){
          print "<span class='badge badge1'  style='color:white;background:$t->ctcolor !important'></i>$t->ctname ($t->ctpattern Line)</span>";
        }
        print "</div>";
        ?>

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
