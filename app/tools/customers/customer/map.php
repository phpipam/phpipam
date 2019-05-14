<?php

/**
 * Script to display customer details
 *
 */

# verify that user is logged in
$User->check_user_session();
# verify module permissions
$User->check_module_permissions ("customers", 1, true);


# check key
if (strlen(Config::get('gmaps_api_key'))==0) {
    $Result->show("info text-center nomargin", _("Location: Google Maps API key is unset. Please configure config.php \$gmaps_api_key to enable."));
}
else {
    // get lat long
    if (strlen($customer->long)==0 && strlen($customer->lat)==0 && strlen($customer->address)>0) {
        $latlng = $Tools->get_latlng_from_address ($customer->address);
        if($latlng['lat']!=NULL && $latlng['lng']!=NULL) {
            // save
            $Tools->update_latlng ($customer->id, $latlng['lat'], $latlng['lng']);
            $customer->lat = $latlng['lat'];
            $customer->long = $latlng['lng'];
        }
    }

    # resize ?
    $resize = @$resize === false ? false : true;

    # no long/lat
    if( (strlen($customer->long)>0 && strlen($customer->lat))) {

    // description and apostrophe fix
    $customer->note = strlen($customer->note)>0 ? "<span class=\'text-muted\'>".escape_input($customer->note)."</span>" : "";
    $customer->note = str_replace(array("\r\n","\n","\r"), "<br>", $customer->note );
    ?>
    <script type="text/javascript">
        $(document).ready(function() {

            // init gmaps
            var map = new GMaps({
              div: '#gmap',
              zoom: 15,
              lat: '<?php print escape_input($customer->lat); ?>',
              lng: '<?php print escape_input($customer->long); ?>'
            });

            map.addMarker({
             title: "'<?php print addslashes($customer->title); ?>'",
             lat: '<?php print escape_input($customer->lat); ?>',
             lng: '<?php print escape_input($customer->long); ?>'
            });

        });
    </script>

    <div style="width:100%; height:400px; margin-top:40px;" id="map_overlay">
    	<div id="gmap" style="width:100%; height:100%;"></div>
    </div>

    </script>
<?php
    }
}