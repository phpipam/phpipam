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
if ($User->get_module_permissions ("locations")==User::ACCESS_NONE) {
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}
# if none than print
elseif($location===false) {
    $Result->show("info","Invalid location", false);
}
elseif (strlen(Config::ValueOf('gmaps_api_key'))==0) {
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
    ?>
    <script>
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

    </script>
<?php
    }
}