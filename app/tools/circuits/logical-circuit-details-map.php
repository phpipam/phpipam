<?php

// title
print "<h4>"._('Map')."</h4>";

print "<hr>";
print "<h4>"._('Circuit Type Legend')."</h2>";
print "<hr>";

$circuit_types = $Tools->fetch_all_circuit_types();
$type_hash = [];
foreach($circuit_types as $t){
  $type_hash[$t->id] = $t;
  $html[] = "<a style='background:$t->ctcolor'>";
  $html[] = "    <span class='badge badge1'  style='color:white' ></i>$t->ctname ($t->ctpattern Line)</span>";
  $html[] = "</a>";
}
print implode("\n", $html);
$locations = $Tools->fetch_all_objects("locations");
$all_locations = [];
foreach($locations as $l){ $all_locations[$l->id] = $l; }
$location_ids_to_map = array();
if($member_circuits != false){
  foreach($member_circuits as $circuit){
    if(!in_array($circuit->location1, $location_ids_to_map)) { array_push($location_ids_to_map, $circuit->location1); }
    if(!in_array($circuit->location2, $location_ids_to_map)) { array_push($location_ids_to_map, $circuit->location2); }
  }
}
// check
if ($User->settings->enableLocations=="1" && (!isset($gmaps_api_key) || strlen($gmaps_api_key)==0)) {
    $Result->show("info text-center nomargin", _("Location: Google Maps API key is unset. Please configure config.php \$gmaps_api_key to enable."));
}
elseif(sizeof($location_ids_to_map) == 0){
  $Result->show("info","No members of logical circuit.", false);
}
elseif ($locA->name!=="/" && $locB->name!=="/") {
	//$all_locations = array ();

	// add point A and B
	if ($locA->name!=="/") { $all_locations[] = $locA; }
	if ($locB->name!=="/") { $all_locations[] = $locB; }

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
                var lineSymbol = {
                    path: 'M 0,-1 0,1',
                    strokeOpacity: 1,
                    scale: 4
                      };

                // add markers
                <?php
                $html = array();
                foreach ($all_locations as $k=>$location){
                  if(in_array($location->id, $location_ids_to_map)){
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
                }
                print implode("\n", $html);
                // add lines

                foreach ($member_circuits as $circuit) {
                  //If map_spepcifc is set and its in the array OR it isn't set, map all
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
