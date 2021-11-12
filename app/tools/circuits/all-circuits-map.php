<?php

# Check we have been included and not called directly
require( dirname(__FILE__) . '/../../../functions/include-only.php' );

#
# Prints map of all Circuits
#

# perm check
$User->check_module_permissions ("circuits", User::ACCESS_R, true, false);

print "<h3>"._('Map of all circuits')."</h3>";

// fetch all circuits and types. Create a hash of the types to avoid lots of queries
$circuits = $Tools->fetch_all_circuits();
$circuits = is_array($circuits) ? $circuits : [];

$circuit_types = $Tools->fetch_all_objects ("circuitTypes", "ctname");
$circuit_types = is_array($circuit_types) ? $circuit_types : [];

$type_hash = [];
foreach($circuit_types as $t){
    $type_hash[$t->id] = $t;
}

//Fetch all locations and store info hash, same as above.
//This will elimate the need of looping through circuits the first time
$locations = $Tools->fetch_all_objects("locations");
$locations = is_array($locations) ? $locations : [];

$all_locations = [];
foreach($locations as $l){ $all_locations[$l->id] = $l; }

$OSM = new OpenStreetMap($Database);

foreach ($all_locations as $k=>$l) {
    // map used
    if(strlen($l->long)==0 && strlen($l->lat)==0 && strlen($l->address)==0 ) {
        unset($all_locations[$k]);
    }
    // recode
    elseif (strlen($l->long)==0 && strlen($l->lat)==0 && strlen($l->address)>0) {
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

foreach ($circuits as $circuit) {
    // Reformat circuit location
    // result will be false or array
    $rcl1 = $Tools->reformat_circuit_location($circuit->device1, $circuit->location1);
    $rcl2 = $Tools->reformat_circuit_location($circuit->device2, $circuit->location2);

    if (!is_array($rcl1) || !is_array($rcl2)) {
        continue;
    }

    // Convert location id to location object
    $circuit_l1 = $all_locations[$rcl1['location']];
    $circuit_l2 = $all_locations[$rcl2['location']];

    $OSM->add_circuit($circuit_l1, $circuit_l2, $type_hash[$circuit->type]);
}
$OSM->map();

print "<hr>";
print "<div class='text-right'>";
print "<h5>"._('Circuit Type Legend')."</h5>";
foreach($circuit_types as $t){
    print "<span class='badge badge1'  style='color:white;background:$t->ctcolor !important'></i>$t->ctname ($t->ctpattern Line)</span>";
}
print "</div>";
