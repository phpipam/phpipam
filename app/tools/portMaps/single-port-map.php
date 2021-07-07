<h4><?php print _('Port Map Details'); ?></h4>
<hr>

<?php
/**
 * Script to print single port map
 * ************************* */
# verify that user is logged in
$User->check_user_session();

# fetch all ports for the selected map
$object_id = $_GET['subnetId'];
if (isset($mapId)) {
    $object_id = $mapId;
}

?>
<br>

<div class="btn-toolbar">
    <?php if ($_GET['page'] == "administration") { ?>
        <a href="" class='btn btn-sm btn-default editPort' data-action='add' data-id='' <?php print "data-map_id='" . $_GET['subnetId'] . "' " ?> style='margin-bottom:10px;'><i class='fa fa-plus'></i> <?php print _('Add Port'); ?></a>
    <?php
    } else {
        if ($User->get_module_permissions("portMaps") > 1) {
            print "<button class='btn btn-sm btn-default btn-success open_popup' data-script='app/admin/portMaps/edit.php' data-class='500' data-action='edit' data-id='" . $object_id . "' style='margin-bottom:10px;'><i class='fa fa-pencil'></i> " . _('Edit port map') . "</button>";
            print "";
            print "<button class='btn btn-sm btn-default btn-success open_popup' data-script='app/admin/ports/edit.php' data-class='500' data-action='add' data-map_id='" . $_GET['subnetId'] . "' style='margin-bottom:10px;'><i class='fa fa-plus'></i> " . _('Add port') . "</button>";
        }
    }
    ?>
</div>
<br>
<?php

$port_map = $Tools->fetch_object("portMaps", "id", $object_id);
$all_ports_in_map = $Tools->fetch_multiple_objects("ports", "map_id", $object_id, $sortField = 'number');

$colspan = 8;

if($admin || $User->get_module_permissions("portMaps") > 1) {
    $colspan = $colspan + 1;
}

print "<table class='ipaddress_subnet table-condensed table-auto' boarder=\"0\">";

print "<tr>";
print "<th>"._('Name')."</th>";
print "<td>$port_map->name</td>";
print "</tr>";
print "<tr>";
print "<th>"._('Description')."</th>";
print "<td>$port_map->description</td>";
print "</tr>";
print "<tr>";
print "<th>"._('Device')."</th>";
$port_map->hostDevice = strlen($port_map->hostDevice) == 0 ? "-" : "<a href='".create_link("tools", "devices", $port_map->hostDevice)."'>".$Tools->fetch_object("devices","id",$port_map->hostDevice)->hostname."</a>";
print "<td>$port_map->hostDevice</td>";
print "</tr>";

print "</table>";

// table
print "<table class='table sorted table-striped table-top table-td-top' data-cookie-id-table='all_ports'>";
// headers
print "<thead>";
print "<tr>";
print " <th>" . _('Port Number') . "</th>";
print " <th>" . _('VLAN') . "</th>";
print " <th>" . _('Tagged') . "</th>";
print " <th>" . _('Name') . "</th>";
print " <th>" . _('Device') . "</th>";
//print " <th>" . _('Remote Port') . "</th>"; TODO: Add ability to point to a specific port on other port map
print " <th>" . _('Type') . "</th>";
print " <th>" . _('PoE') . "</th>";
if (sizeof($custom) > 0) {
    foreach ($custom as $field) {
        if (!in_array($field['name'], $hidden_custom_fields)) {
            print "<th class='hidden-xs hidden-sm hidden-md'>" . $Tools->print_custom_field_name($field['name']) . "</th>";
            $colspan++;
        }
    }
}
if ($User->get_module_permissions("portMaps") > 1) {
    print '	<th class="actions"></th>';
}
print "</tr>";
print "</thead>";

print "<tbody>";

# if none than print
if ($all_ports_in_map === false) {
    print "<tr>";
    print " <td colspan='$colspan'>" . $Result->show("info", "No ports configured", false, false, true) . "</td>";
    print "</tr>";
} else {
    foreach ($all_ports_in_map as $port) {
        // port number
        print "<tr>";
        print "<td>$port->number</td>";
        //TODO: Add page to view port information pulled from device vian snmp (State, negotiated speed, connected mac addresses, etc)
        //print "<td><a class='btn btn-xs btn-default' href='" . create_link("tools", "ports", $port->id) . "'><i class='fa fa-exchange prefix'></i> $port->number</a></td>";
        // vlan number
        $port->vlan = strlen($port->vlan) == 0 ? "-" : $port->vlan;
        print "<td><span class='text-muted'>$port->vlan</span></td>";
        // Tagged
        $port->tagged = strlen($port->tagged) == 0 ? "-" : $port->tagged;
        print "<td><span class='text-muted'>$port->tagged</span></td>";
        // given name
        $port->name = strlen($port->name) == 0 ? "-" : $port->name;
        print "<td>$port->name</td>";
        // device
        $port->device = strlen($port->device) == 0 ? "-" : "<a href='".create_link("tools", "devices", $port->device)."&sPage=portMap'>".$Tools->fetch_object("devices","id",$port->device)->hostname."</a>";
        print "<td>$port->device</td>";
        
        //Remote port will be a reference to a specific port id on port map of other device. 
        //TODO: Add ability to point to a specific port on other port map
        // Remote Port
        //$port->remote_port = strlen($port->remote_port) == 0 ? "-" : "<a href='".create_link("tools", "ports", $port->remote_port)."'>".$Tools->fetch_object("ports","id",$port->remote_port)->number."</a>";
        //print "<td>$port->remote_port</td>";
        
        // Port type
        $port->type = strlen($port->type) == 0 ? "-" : $port->type;
        print "<td>$port->type</td>";

        // PoE
        $port->poe = strlen($port->poe) == 0 ? "-" : $port->poe;
        print "<td>$port->poe</td>";
        
        //custom
        if (sizeof($custom) > 0) {
            foreach ($custom as $field) {
                if (!in_array($field['name'], $hidden_custom_fields)) {
                    print "<td class='hidden-xs hidden-sm hidden-md'>";
                    $Tools->print_custom_field($field['type'], $port->{$field['name']});
                    print "</td>";
                }
            }
        }
        
        // actions
        if ($User->get_module_permissions("portMaps") > 1) {
            // links
            print "<td class='actions'>";
            $links = [];
            $links[] = ["type" => "header", "text" => "Manage port"];
            $links[] = ["type" => "link", "text" => "Edit port", "href" => "", "class" => "open_popup", "dataparams" => " data-script='app/admin/ports/edit.php' data-class='500' data-action='edit' data-map_id='$port->map_id' data-id='$port->id'", "icon" => "pencil"];

            if ($User->get_module_permissions("devices") > 2) {
                $links[] = ["type" => "divider"];
                $links[] = ["type" => "link", "text" => "Delete port", "href" => "", "class" => "open_popup", "dataparams" => " data-script='app/admin/ports/edit.php' data-class='500' data-action='delete' data-id='$port->id'", "icon" => "times"];
            }
            // print links
            print $User->print_actions($User->user->compress_actions, $links);
            
            print "</td>";
        }

        print "</tr>";
    }
}
print "</tbody>";
print "</table>";