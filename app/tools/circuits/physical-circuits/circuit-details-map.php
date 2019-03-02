<?php

# perm check
$User->check_module_permissions ("circuits", 1, true, false);

// title
print "<h4>"._('Map')."</h4>";
print "<hr>";

// check
if ($User->settings->enableLocations=="1" && (!isset($gmaps_api_key) || strlen($gmaps_api_key)==0)) {
    $Result->show("info text-center nomargin", _("Location: Google Maps API key is unset. Please configure config.php \$gmaps_api_key to enable."));
}
elseif ($locA->name!=="/" && $locB->name!=="/") {
	$all_locations = array ();

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
    if (sizeof($all_locations)>0) {
	if($gmaps_api_key!="OSMAP"){ ?>
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
	<?php }
	else { ?>
	    <script type="text/javascript">
		function initMap() {
			var osmap = L.map('osmap').setView([0, 0], 1);
		        //http://leaflet-extras.github.io/leaflet-providers/preview/
		     /*   L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
		            // Il est toujours bien de laisser le lien vers la source des données
		            attribution: 'données © <a href="//osm.org/copyright">OpenStreetMap</a>/ODbL - rendu <a href="//openstreetmap.fr">OSM France</a>',
		            minZoom: 1,
		            maxZoom: 20
		        }).addTo(osmap); */

			var OpenTopoMap = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
				maxZoom: 17,
				attribution: 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)'
			}).addTo(osmap);

			var markers = [];
			var path = [];
			// add markers
		        <?php
		        $html        = array();

		        $map_marker_location_ids = array();
		        foreach ($all_locations as $k=>$location) {
		            // description and apostrophe fix
		            $location->description = strlen($location->description)>0 ? "<span class=\'text-muted\'>".addslashes($location->description)."</span>" : "";
                    	    $location->description = str_replace(array("\r\n","\n","\r"), "<br>", $location->description );


				$html[] = "var marker = L.marker([$location->lat, $location->long]).addTo(osmap);";
				$html[] = "marker.bindTooltip('".escape_input($location->name)."');";
				$html[] = "marker.bindPopup('<h5><a href=\'".create_link("tools", "locations", $location->id)."\'>". escape_input($location->name). "</a></h5>$location->description');";
				$html[] = "markers.push(marker);";
		        }
			if(sizeof($all_locations)==2) {
				    $html[] = "path = [[".$all_locations[0]->long.", ".$all_locations[0]->lat."], [".$all_locations[1]->long.", ".$all_locations[1]->lat."]]";
                		    $html[] = 'var gj = L.geoJSON({"type": "LineString","coordinates": path}).addTo(osmap);';
				    if($circuit->custom_code)
				        $html[] = 'gj.bindTooltip("'.$circuit->custom_code.'");';
			}

		        print implode("\n", $html);
		        ?>

			var group = new L.featureGroup(markers);
			osmap.fitBounds(group.getBounds().pad(0.5));

            	}
		$(document).ready(initMap);
	</script>

	<div style="width:100%; height:400px;" id="map_overlay">
        	<div id="osmap" style="width:100%; height:100%;"></div>
        </div>

        <?php
        # no coordinates
	     }
        }
        else {
            $Result->show("info","No Locations with coordinates configured", false);
        }
}
else {
	$Result->show("danger", _("Location not set"), true);
}
