<?php
/**
 * 	Print all port maps
 * ********************************************** */
/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Admin = new Admin($Database, false);
$Tools = new Tools($Database);
$Result = new Result ();

# verify that user is logged in
$User->check_user_session();

# perm check popup
if($_POST['action']=="edit") {
    $User->check_module_permissions ("portMaps", 2, true, true);
}
else {
    $User->check_module_permissions ("portMaps", 3, true, true);
}

# create csrf token
$csrf = $User->Crypto->csrf_cookie("create", "portMap");

# validate action
$Admin->validate_action($_POST['action'], true);

# get port map object
if ($_POST['action'] != "add") {
    $portMap = $Admin->fetch_object("portMaps", "id", $_POST['id']);
    $portMap !== false ?: $Result->show("danger", _("Invalid ID"), true, true);
}

# disable edit on delete
$readonly = $_POST['action'] == "delete" ? "readonly" : "";
$link = $readonly ? false : true;

# fetch custom fields
$custom = $Tools->fetch_custom_fields('portMaps');
?>


<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('Port Map'); ?></div>

<!-- content -->
<div class="pContent">

    <form id="editPortMap">
        <table id="editPortMap" class="table table-noborder table-condensed">

            <tbody>
                <!-- name -->
                <tr>
                    <th><?php print _('Name'); ?></th>
                    <td>
                        <input type="text" class="form-control input-sm" name="name" value="<?php print $Tools->strip_xss($portMap->name); ?>" placeholder='<?php print _('Name'); ?>' <?php print $readonly; ?>>
                        <input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
                        <input type="hidden" name="id" value="<?php print $portMap->id; ?>">
                        <input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
                    </td>
                </tr>

                <!-- description -->
                <tr>
                    <th><?php print _('Description'); ?></th>
                    <td colspan="2">
                        <textarea class="form-control input-sm" name="description" placeholder='<?php print _('Description'); ?>' <?php print $readonly; ?>><?php print $portMap->description; ?></textarea>
                    </td>
                </tr>

                <!-- device -->
                <?php
                print '<tr>' . "\n";
                print '	<td>' . _('Device') . $required . '</td>' . "\n";
                print '	<td>' . "\n";

                print '<select name="device" class="ip_addr form-control input-sm input-w-auto" ' . $delete . '>' . "\n";
                print '<option disabled>' . _('Select device') . ':</option>' . "\n";
                if ($required == "")
                    print '<option value="0" selected>' . _('None') . '</option>' . "\n";

// fetch devices
                $devices = $Tools->fetch_all_objects("devices", "hostname");
                if ($devices !== false) {
                    foreach ($devices as $device) {
                        $device = (array) $device;
                        //if same
                        if ($device['id'] == $portMap->hostDevice) {
                            print '<option value="' . $device['id'] . '" selected>' . $device['hostname'] . '</option>' . "\n";
                        } else {
                            print '<option value="' . $device['id'] . '">' . $device['hostname'] . '</option>' . "\n";
                        }
                    }
                }
                print '</select>' . "\n";
                print '	</td>' . "\n";
                print '</tr>' . "\n";
                ?>
                <!-- Custom -->
                <?php
                if (sizeof($custom) > 0) {

                    print '<tr>';
                    print '	<td colspan="2"><hr></td>';
                    print '</tr>';


                    # count datepickers
                    $timepicker_index = 0;

                    # all my fields
                    foreach ($custom as $field) {
                        // create input > result is array (required, input(html), timepicker_index)
                        $custom_input = $Tools->create_custom_field_input($field, $portMap, $_POST['action'], $timepicker_index);
                        // add datepicker index
                        $timepicker_index = $timepicker_index + $custom_input['timepicker_index'];
                        // print
                        print "<tr>";
                        print "	<td>" . ucwords($Tools->print_custom_field_name($field['name'])) . " " . $custom_input['required'] . "</td>";
                        print "	<td>" . $custom_input['field'] . "</td>";
                        print "</tr>";
                    }
                }
                ?>


            </tbody>

        </table>
    </form>
</div>


<!-- footer -->
<div class="pFooter">
    <div class="btn-group">
        <button class="btn btn-sm btn-default hidePopupsReload"><?php print _('Cancel'); ?></button>
        <button class="btn btn-sm btn-default <?php
        if ($_POST['action'] == "delete") {
            print "btn-danger";
        } else {
            print "btn-success";
        }
        ?>" id="editPortMapSubmit"><i class="fa <?php
                if ($_POST['action'] == "add" || $_POST['action'] == "copy") {
                    print "fa-plus";
                } else if ($_POST['action'] == "delete") {
                    print "fa-trash-o";
                } else {
                    print "fa-check";
                }
                ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
    </div>
    <!-- result -->
    <?php
    print '<div class="editPortMapResult"></div>';
    ?>
</div>
