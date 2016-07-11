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

# if none than print
if($location===false) {
    $Result->show("info","Invalid location", false);
}
else {

    # sensor check
    if(isset($gmaps_api_key)) {
        $key = strlen($gmaps_api_key)>0 ? "?key=".$gmaps_api_key : "";
    }

    # resize ?
    $resize = @$resize === false ? false : true;

    # no long/lat
    if(strlen($location->long)>0 && strlen($location->lat)) {
    ?>

    <script type="text/javascript" src="https://maps.google.com/maps/api/js<?php print $key; ?>"></script>
    <script type="text/javascript" src="js/1.2/gmaps.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            <?php
            // address
            if(strlen($location->address)>1) {
                $html[] = "GMaps.geocode({";
                $html[] = " address: '$location->address',";
                $html[] = " callback: function(results, status) {";
                $html[] = "     if (status == 'OK') {";
                $html[] = "       var latlng = results[0].geometry.location;";
                $html[] = "       var map = new GMaps({";
                $html[] = "          div: '#gmap',";
                $html[] = "          zoom: 15,";
                $html[] = "          lat: latlng.lat(),";
                $html[] = "          lng: latlng.lng(),";
                $html[] = "       });";
                $html[] = "       map.addMarker({";
                $html[] = "         title: '$location->name',";
                $html[] = "         lat: latlng.lat(),";
                $html[] = "         lng: latlng.lng(),";
                $html[] = "         infoWindow: {";
                $html[] = "             content: '<h5><a href=\'".create_link("tools", "locations", $location->id)."\'>$location->name</a></h5>, <span class=\'text-muted\'>$location->description</span>'";
                $html[] = "         }";
                $html[] = "       });";
                $html[] = "     }";
                $html[] = "  }";
                $html[] = "});";
            }
            // latlng
            elseif(strlen($location->lat)>0 && strlen($location->long)>0) {
                $html[] = "GMaps.geocode({";
                $html[] = " lat: '$location->lat',";
                $html[] = " lng: '$location->long',";
                $html[] = " callback: function(results, status) {";
                $html[] = "     if (status == 'OK') {";
                $html[] = "       var latlng = results[0].geometry.location;";
                $html[] = "       var map = new GMaps({";
                $html[] = "          div: '#gmap',";
                $html[] = "          zoom: 15,";
                $html[] = "          lat: latlng.lat(),";
                $html[] = "          lng: latlng.lng(),";
                $html[] = "       });";
                $html[] = "       map.addMarker({";
                $html[] = "         title: '$location->name',";
                $html[] = "         lat: latlng.lat(),";
                $html[] = "         lng: latlng.lng(),";
                $html[] = "         infoWindow: {";
                $html[] = "             content: '<h5><a href=\'".create_link("tools", "locations", $location->id)."\'>$location->name</a></h5>, <span class=\'text-muted\'>$location->description</span>'";
                $html[] = "         }";
                $html[] = "       });";
                $html[] = "     }";
                $html[] = "  }";
                $html[] = "});";
            }

            print implode("\n", $html);
            ?>

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
?>