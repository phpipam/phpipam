<?php

/**
 * Script to display circuits
 */

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("devices", User::ACCESS_R, true, false);
# perm check
$User->check_module_permissions ("circuits", User::ACCESS_R, true, false);

# fetch custom fields
$custom = $Tools->fetch_custom_fields('circuits');
# fetch circuit types
$circuit_types = $Tools->fetch_all_objects ("circuitTypes", "ctname");
$type_hash = [];
foreach($circuit_types as $t){ $type_hash[$t->id] = $t->ctname; }
# get hidden fields */
$hidden_fields = db_json_decode($User->settings->hiddenCustomFields, true);
$hidden_fields = is_array(@$hidden_fields['circuits']) ? $hidden_fields['circuits'] : array();

# check
is_numeric($GET->subnetId) ? : $Result->show("danger", _("Invalid ID"), true);

# title - subnets
print "<h4>"._("Belonging Circuits")."</h4><hr>";

//fetch
$device_circuits = $Tools->fetch_all_device_circuits ($device->id);


# headers
if ($User->settings->enableCircuits!="1") {
    $Result->show("danger", _("Circuits module disabled."), false);
}
else {

    // print
    if($device_circuits===false) {
        $Result->show("info", _("No circuits"), false);
    }
    else {
        # table
        print '<table id="circuitManagement" class="table sorted table-condensed table-striped table-top" data-cookie-id-table="device_circuits">';

        # headers
        print "<thead>";
        print '<tr>';
        print " <th>"._('Circuit ID')."</th>";
        print " <th>"._('Provider')."</th>";
        print " <th>"._('Type').'</th>';
        print " <th>"._('Capacity').'</th>';
        print " <th>"._('Status').'</th>';
        if($User->get_module_permissions ("locations")>=User::ACCESS_R) {
        print " <th>"._('Point A').'</th>';
        print " <th>"._('Point B').'</th>';
        }
        print '</tr>';
        print "</thead>";

        print "<tbody>";
        foreach ($device_circuits as $circuit) {
            // reformat locations
            $locationA = $Tools->reformat_circuit_location ($circuit->device1, $circuit->location1);
            $locationA_html = "<span class='text-muted'>Not set";
            if($locationA!==false) {
                $locationA_html = "<a href='".create_link($GET->page,$locationA['type'],$locationA['id'])."'>$locationA[name]</a> <i class='fa fa-gray $locationA[icon]'></i>";
            }

            $locationB = $Tools->reformat_circuit_location ($circuit->device2, $circuit->location2);
            $locationB_html = "<span class='text-muted'>Not set";
            if($locationB!==false) {
                $locationB_html = "<a href='".create_link($GET->page,$locationB['type'],$locationB['id'])."'>$locationB[name]</a> <i class='fa fa-gray $locationB[icon]'></i>";
            }

            //print details
            print '<tr>'. "\n";
            print " <td><a class='btn btn-xs btn-default' href='".create_link("tools","circuits",$circuit->id)."'><i class='fa fa-random prefix'></i> $circuit->cid</a></td>";
            print " <td><a href='".create_link("tools","circuits","providers",$circuit->pid)."'>$circuit->name</a></td>";
            print " <td>{$type_hash[$circuit->type]}</td>";
            print " <td class='hidden-xs hidden-sm'>$circuit->capacity</td>";
            print " <td class='hidden-xs hidden-sm'>$circuit->status</td>";
            if($User->get_module_permissions ("locations")>=User::ACCESS_R) {
            print " <td class='hidden-xs hidden-sm'>$locationA_html</td>";
            print " <td class='hidden-xs hidden-sm'>$locationB_html</td>";
            }
            print '</tr>'. "\n";
        }
        print "</tbody>";
        print '</table>';
    }
}
