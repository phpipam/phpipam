<script type="text/javascript">
    /* fix for ajax-loading tooltips */
    $('body').tooltip({selector: '[rel=tooltip]'});
</script>

<?php
/**
 * Script to display all port maps
 * 
 */
//Verify the user is logged in and has permissions to access the module
$User->check_user_session();
$User->check_module_permissions("portMaps");

//Fetch all portmaps
$portMaps = $Tools->fetch_all_objects("portMaps", "name");

//Strip tags - XSS
$_GET = $User->strip_input_tags($_GET);

//Title
print "<h4>" . _('List of port maps') . "</h4>";
print "<hr>";

# print link to manage
print "<div class='btn-group'>";
//administer
if ($User->get_module_permissions("portMaps") > 1) {
    print "<button class='btn btn-sm btn-default btn-success open_popup' data-script='app/admin/portMaps/edit.php' data-class='500' data-action='add' data-switchid='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> " . _('Add port map') . "</button>";
}
print "</div>";

# table
print '<table id="switchManagement" class="table sorted sortable table-striped table-top" data-cookie-id-table="devices_all">';

print "<thead>";
print '<tr>';
print " <th>" . _('Name') . "</th>";
print " <th>" . _('Description') . "</th>";
print " <th>" . _('Device') . "</th>";

if ($User->get_module_permissions("portMaps") > 1) {
    print '	<th class="actions"></th>';
}
print '</tr>';
print "</thead>";

print "<tbody>";
// no portMaps
if ($portMaps === false) {
    $colspan = 4;
    print "<tr>";
    print "	<td colspan='$colspan'>" . $Result->show('info', _('No results') . "!", false, false, true) . "</td>";
    print "</tr>";
} else {
    foreach ($portMaps as $portMap) {
        $portMap = (array) $portMap;

        //Print details
        print '<tr>' . "\n";
        print "	<td><a class='btn btn-xs btn-default' href='" . create_link("tools", "portMaps", $portMap['id']) . "'><i class='fa fa-desktop prefix'></i> " . $portMap['name'] . '</a></td>' . "\n";
        print "	<td>" . $portMap['description'] . '</td>' . "\n";
        if($portMap['hostDevice'] > 1) {
            $device = (array) $Tools->fetch_object("devices","id",$portMap['hostDevice']);
            print " <td><a href='" . create_link("tools", "devices", $device['id']) . "'>" . $device['hostname'] . "</a></td>";
        } else {
            print " <td>-</td>";
        }

        //Actions
        if ($User->get_module_permissions("portMaps") > 1) {
            // links
            print "<td class='actions'>";
            $links = [];
            $links[] = ["type" => "header", "text" => "Manage port map"];
            $links[] = ["type" => "link", "text" => "Edit port map", "href" => "", "class" => "open_popup", "dataparams" => " data-script='app/admin/portMaps/edit.php' data-class='500' data-action='edit' data-id='$portMap[id]'", "icon" => "pencil"];
            $links[] = ["type" => "link", "text" => "Copy port map", "href" => "", "class" => "open_popup", "dataparams" => " data-script='app/admin/portMaps/edit.php' data-class='500' data-action='copy' data-id='$portMap[id]'", "icon" => "exchange"];

            if ($User->get_module_permissions("devices") > 2) {
                $links[] = ["type" => "link", "text" => "Delete port map", "href" => "", "class" => "open_popup", "dataparams" => " data-script='app/admin/portMaps/edit.php' data-class='500' data-action='delete' data-id='$portMap[id]'", "icon" => "times"];
                $links[] = ["type" => "divider"];
            }
            // print links
            print $User->print_actions($User->user->compress_actions, $links);
            print "</td>";
        }

        print '</tr>' . "\n";
    }
}

print "</tbody>";
print '</table>';
