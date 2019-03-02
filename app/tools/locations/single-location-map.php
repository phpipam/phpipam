<?php

/**
 * Script to print single location
 ***************************/

# verify that user is logged in
$User->check_user_session();

# fetch location
if(!$location) {
    $location = $Tools->fetch_object("locations", "id", $location_index);
}

# perm check
if ($User->get_module_permissions ("locations")<1) {
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}
# if none than print
elseif($location===false) {
    $Result->show("info","Invalid location", false);
}
elseif (!isset($gmaps_api_key) || strlen($gmaps_api_key)==0) {
      $Result->show("info text-center nomargin", _("Location: Google Maps API key is unset. Please configure config.php \$gmaps_api_key to enable."));
}else {
    // recode
    if (strlen($location->long)==0 && strlen($location->lat)==0 && strlen($location->address)>0) {
        $latlng = $Tools->get_latlng_from_address ($location->address);
        if($latlng['lat']!=NULL && $latlng['lng']!=NULL) {
            // save
            $Tools->update_latlng ($location->id, $latlng['lat'], $latlng['lng']);
            $location->lat = $latlng['lat'];
            $location->long = $latlng['lng'];
        }
    }

    # resize ?
    $resize = @$resize === false ? false : true;

    # no long/lat
    if( (strlen($location->long)>0 && strlen($location->lat))) {

    // description and apostrophe fix
    $location->description = strlen($location->description)>0 ? "<span class=\'text-muted\'>".escape_input($location->description)."</span>" : "";
    $location->description = str_replace(array("\r\n","\n","\r"), "<br>", $location->description );
    if($gmaps_api_key!="OSMAP") {
    ?>
    <script type="text/javascript">
        $(document).ready(function() {

            // init gmaps
            var map = new GMaps({
              div: '#gmap',
              zoom: 15,
              lat: '<?php print escape_input($location->lat); ?>',
              lng: '<?php print escape_input($location->long); ?>'
            });

            map.addMarker({
             title: "'<?php print addslashes($location->name); ?>'",
             lat: '<?php print escape_input($location->lat); ?>',
             lng: '<?php print escape_input($location->long); ?>',
             infoWindow: {
                content: '<h5><a href="<?php print create_link("tools", "locations", $location->id); ?>."\'><?php print addslashes($location->name); ?></a></h5><?php print $location->description; ?>'
             }
            });

            <?php if($resize===true) { ?>
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

    <div style="width:100%; height:<?php print isset($height) ? $height : "600px" ?>;" id="map_overlay">
    	<div id="gmap" style="width:100%; height:100%;"></div>
    </div>
    <?php }
    else { ?>

    <script type="text/javascript">
		function initMap() {

			var lat = '<?php print escape_input($location->lat); ?>';
			var lon = '<?php print escape_input($location->long); ?>';
			var osmap = L.map('osmap').setView([lat, lon], 15);
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

			// add marker
			var marker = L.marker([<?php print escape_input($location->lat); ?>, <?php print escape_input($location->long); ?>]).addTo(osmap);
			marker.bindTooltip('<?php print addslashes($location->name); ?>');
			marker.bindPopup('<h5><a href="<?php print create_link("tools", "locations", $location->id); ?>."\'><?php print addslashes($location->name); ?></a></h5><?php print $location->description; ?>');

			var group = new L.featureGroup(markers);
			osmap.fitBounds(group.getBounds().pad(0.5));

            	}
		$(document).ready(initMap);
	</script>

    <div style="width:100%; height:<?php print isset($height) ? $height : "600px" ?>;" id="map_overlay">
    	<div id="osmap" style="width:100%; height:100%;"></div>
    </div>

    </script>
<?php
    	}
    }
}
