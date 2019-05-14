<?php if(@$title!==false) { ?>
<h4><?php print _('Locations Map'); ?></h4>
<hr>
<?php } ?>

<?php if($admin && $User->settings->enableLocations=="1") { ?>
<?php
if($User->get_module_permissions ("locations")>1) {
include('menu.php');
}
?>
<br>
<?php } ?>
<?php

/**
 * Script to print locations
 ***************************/

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("locations", 1, true, false);

# perm check
if ($User->get_module_permissions ("locations")<1) {
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}
# check that location support isenabled
elseif ($User->settings->enableLocations!="1") {
    $Result->show("danger", _("Locations module disabled."), false);
}
elseif ($User->settings->enableLocations=="1" && strlen(Config::get('gmaps_api_key'))==0) {
    $Result->show("info text-center nomargin", _("Location: Google Maps API key is unset. Please configure config.php \$gmaps_api_key to enable."));
}
else {
    # fetch all locations
    $all_locations = $Tools->fetch_all_objects("locations", "name");

    # if none than print
    if($all_locations===false) {
        $Result->show("info","No Locations configured", false);
    }
    else {
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

        // calculate
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
                    $html = array();
                    foreach ($all_locations as $location) {
                        // description and apostrophe fix
                        $location->description = strlen($location->description)>0 ? "<span class=\'text-muted\'>".escape_input($location->description)."</span>" : "";
                        $location->description = str_replace(array("\r\n","\n","\r"), "<br>", $location->description );

                        $html[] = "map.addMarker({";
                        $html[] = " title: \"". addslashes($location->name) ."\",";
                        $html[] = " lat: '". escape_input($location->lat) ."',";
                        $html[] = " lng: '". escape_input($location->long) ."',";
                        $html[] = " infoWindow: {";
                        $html[] = "    content: '<h5><a href=\'".create_link("tools", "locations", $location->id)."\'>". addslashes($location->name) ."</a></h5>$location->description'";
                        $html[] = "}";
                        $html[] = "});";
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

    ?>
    </script>
    <?php
}
