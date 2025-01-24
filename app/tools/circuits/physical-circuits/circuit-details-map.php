<?php

# Check we have been included and not called directly
require( dirname(__FILE__) . '/../../../../functions/include-only.php' );

# perm check
$User->check_module_permissions ("circuits", User::ACCESS_R, true, false);

// title
print "<h4>"._('Map')."</h4>";
print "<hr>";

$circuit_types = $Tools->fetch_all_objects ("circuitTypes", "ctname");
$circuit_types = is_array($circuit_types) ? $circuit_types : [];

$type_hash = [];
foreach($circuit_types as $t){
    $type_hash[$t->id] = $t;
}

// check
$OSM = new OpenStreetMap($Database);
$all_locations = [$locA, $locB];

// get all
foreach ($all_locations as $k=>$l) {
    if(is_blank($l->long) && is_blank($l->lat) && is_blank($l->address) ) {
        // map not used
        unset($all_locations[$k]);
    }
    // recode
    elseif (is_blank($l->long) && is_blank($l->lat) && !is_blank($l->address)) {
        $latlng = $OSM->get_latlng_from_address ($l->address);
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
$all_locations = array_values($all_locations);

if (sizeof($all_locations) == 1) {
    $OSM->add_location($all_locations[0]);
} elseif (sizeof($all_locations) == 2) {
    $OSM->add_circuit($all_locations[0], $all_locations[1], $type_hash[$circuit->type]);
}
$OSM->map($height ?? null);
