<?php

/**
 * Script to display circuits
 */

# verify that user is logged in
$User->check_user_session();

# fetch custom fields
$custom = $Tools->fetch_custom_fields('circuits');
# get hidden fields */
$hidden_fields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_fields = is_array(@$hidden_fields['circuits']) ? $hidden_fields['circuits'] : array();

# check
is_numeric($_GET['subnetId']) ? : $Result->show("danger", _("Invalid ID"), true);

# title - subnets
print "<h4>"._("Belonging Circuits")."</h4><hr>";

//fetch
$device_circuits = $Tools->fetch_all_device_circuits ($device->id);

# Hosts table
print "<table id='switchMainTable' class='circuits table table-striped table-top table-condensed'>";

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
        print '<table id="circuitManagement" class="table sorted table-striped table-top">';

        # headers
        print "<thead>";
        print '<tr>';
        print " <th><span rel='tooltip' data-container='body' title='"._('Sort by Id')."'>"._('Circuit ID')."</span></th>";
        print " <th><span rel='tooltip' data-container='body' title='"._('Sort by Provider')."'>"._('Provider')."</span></th>";
        print " <th><span rel='tooltip' data-container='body' title='"._('Sort by type')."'>"._('Type').'</span></th>';
        print " <th><span rel='tooltip' data-container='body' title='"._('Sort by Capacity')."' class='hidden-sm hidden-xs'>"._('Capacity').'</span></th>';
        print " <th><span rel='tooltip' data-container='body' title='"._('Sort by Capacity')."' class='hidden-sm hidden-xs'>"._('Status').'</span></th>';
        print " <th><span rel='tooltip' data-container='body' title='"._('Sort by location A')."' class='hidden-sm hidden-xs'>"._('Point A').'</span></th>';
        print " <th><span rel='tooltip' data-container='body' title='"._('Sort by location B')."' class='hidden-sm hidden-xs'>"._('Point B').'</span></th>';
        if(sizeof(@$custom_fields_circuits) > 0) {
            foreach($custom_fields_circuits as $field) {
                if(!in_array($field['name'], $hidden_fields)) {
                    print "<th class='hidden-sm hidden-xs hidden-md'><span rel='tooltip' data-container='body' title='"._('Sort by')." $field[name]'>".$field['name']."</th>";
                    $colspanCustom++;
                }
            }
        }
        print '</tr>';
        print "</thead>";

        foreach ($device_circuits as $circuit) {
            // reformat locations
            $locationA = $Tools->reformat_circuit_location ($circuit->device1, $circuit->location1);
            $locationA_html = "<span class='text-muted'>Not set</span>";
            if($locationA!==false) {
                $locationA_html = "<a href='".create_link($_GET['page'],$locationA['type'],$locationA['id'])."'>$locationA[name]</a> <i class='fa fa-gray $locationA[icon]'></i>";
            }

            $locationB = $Tools->reformat_circuit_location ($circuit->device1, $circuit->location2);
            $locationB_html = "<span class='text-muted'>Not set</span>";
            if($locationB!==false) {
                $locationB_html = "<a href='".create_link($_GET['page'],$locationB['type'],$locationB['id'])."'>$locationB[name]</a> <i class='fa fa-gray $locationB[icon]'></i>";
            }

            //print details
            print '<tr>'. "\n";
            print " <td><strong><a href='".create_link("tools","circuits",$circuit->id)."'>$circuit->cid</a></strong></td>";
            print " <td><a href='".create_link("tools","circuits","providers",$circuit->pid)."'>$circuit->name</a></td>";
            print " <td>$circuit->type</td>";
            print " <td class='hidden-xs hidden-sm'>$circuit->capacity</td>";
            print " <td class='hidden-xs hidden-sm'>$circuit->status</td>";
            print " <td class='hidden-xs hidden-sm'>$locationA_html</td>";
            print " <td class='hidden-xs hidden-sm'>$locationB_html</td>";
            //custom
            if(sizeof(@$custom_fields_circuits) > 0) {
                foreach($custom_fields_circuits as $field) {
                    if(!in_array($field['name'], $hidden_fields)) {
                        // create html links
                        $circuit->{$field['name']} = $User->create_links($circuit->{$field['name']}, $field['type']);

                        print "<td class='hidden-xs hidden-sm hidden-md'>".$circuit->{$field['name']}."</td>";
                    }
                }
            }

            print '</tr>'. "\n";
        }

        print '</table>';
    }

}