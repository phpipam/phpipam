<?php if(@$title!==false) { ?>
<h4><?php print _('Locations Map'); ?></h4>
<hr>
<?php } ?>

<?php if($admin && $User->settings->enableLocations=="1") { ?>
<div class="btn-group">
    <?php if($_GET['page']=="administration") { ?>
	<a href="" class='btn btn-sm btn-default editLocation' data-action='add' data-id='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> <?php print _('Add location'); ?></a>
	<?php } else { ?>
	<a href="<?php print create_link("administration", "locations") ?>" class='btn btn-sm btn-default' style='margin-bottom:10px;'><i class='fa fa-pencil'></i> <?php print _('Manage'); ?></a>
	<?php } ?>
	<a href="<?php print create_link("tools", "locations") ?>" class='btn btn-sm btn-default' style='margin-bottom:10px;'> <?php print _('Locations list'); ?></a>
</div>
<br>
<?php } ?>

<?php

/**
 * Script to print locations
 ***************************/

# verify that user is logged in
$User->check_user_session();

# check that location support isenabled
if ($User->settings->enableLocations!="1") {
    $Result->show("danger", _("Locations module disabled."), false);
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
                        // description fix
                        $location->description = strlen($location->description)>0 ? "<span class=\'text-muted\'>$location->description</span>" : "";

                        $html[] = "map.addMarker({";
                        $html[] = " title: '$location->name',";
                        $html[] = " lat: '$location->lat',";
                        $html[] = " lng: '$location->long',";
                        $html[] = " infoWindow: {";
                        $html[] = "    content: '<h5><a href=\'".create_link("tools", "locations", $location->id)."\'>$location->name</a></h5>$location->description'";
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
?>