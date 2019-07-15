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
if($_POST['action'] == "edit" || $_POST['action'] == "add") {
    $User->check_module_permissions ("portMaps", 2, true, true);
}
else {
    $User->check_module_permissions ("portMaps", 3, true, true);
}

# create csrf token
$csrf = $User->Crypto->csrf_cookie("create", "port");

# validate action
$Admin->validate_action($_POST['action'], true);

# get port object
if ($_POST['action'] != "add") {
    $port = $Admin->fetch_object("ports", "id", $_POST['id']);
    $port !== false ?: $Result->show("danger", _("Invalid ID: ") . $_POST['id'], true, true);
}

# disable edit on delete
$readonly = $_POST['action'] == "delete" ? "readonly" : "";
$link = $readonly ? false : true;

# fetch custom fields
$custom = $Tools->fetch_custom_fields('ports');
?>


<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('Port'); ?></div>

<!-- content -->
<div class="pContent">

    <form id="editPort">
        <table id="editPort" class="table table-noborder table-condensed">

            <tbody>
                <!-- Port Number -->
                <tr>
                    <th><?php print _('Number'); ?></th>
                    <td>
                        <input type="number" class="form-control input-sm" name="number" value="<?php print $Tools->strip_xss($port->number); ?>" placeholder='<?php print _('Number'); ?>' <?php print $readonly; ?>>
                        <input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
                        <input type="hidden" name="id" value="<?php print $port->id; ?>">
                        <input type="hidden" name="map_id" value="<?php print $_POST['map_id']; ?>">
                        <input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
                    </td>
                </tr>

                <!-- VLAN -->
                <tr>
                    <th><?php print _('VLAN'); ?></th>
                    <td>
                        <input type="number" class="form-control input-sm" name="vlan" value="<?php print $Tools->strip_xss($port->vlan); ?>" placeholder='<?php print _($port->vlan); ?>' <?php print $readonly; print $port->vlan;?>>
                    </td>
                </tr>

                <!-- Device -->
                <?php                
                print '<tr>' . "\n";
                print '	<td>' . _('Tagged') . '</td>' . "\n";
                print '	<td>' . "\n";

                $tagOptions = array("UNTAGGED", "TAGGED");
                
                print '<select name="tagged" class="ip_addr form-control input-sm input-w-auto" ' . $delete . '>' . "\n";

                foreach ($tagOptions as $tagOption) {
                    if($port->tagged == $tagOption) {
                        print '<option value="'.$tagOption.'" selected>'.$tagOption.'</option>' . "\n";
                    } else {
                        print '<option value="'.$tagOption.'">'.$tagOption.'</option>' . "\n";
                    }
                }

                print '</select>' . "\n";
                print '	</td>' . "\n";
                print '</tr>' . "\n";
                ?>

                <!-- Name -->
                <tr>
                    <th><?php print _('Name'); ?></th>
                    <td>
                        <input type="text" class="form-control input-sm" name="name" value="<?php print $Tools->strip_xss($port->name); ?>" placeholder='<?php print _($port->name); ?>' <?php
                               print $readonly;
                               print $port->name;
                               ?>>
                    </td>
                </tr>

                <!-- Type -->
                <?php
                print '<tr>' . "\n";
                print '	<td>' . _('Type') . '</td>' . "\n";
                print '	<td>' . "\n";

                $types = array("ETH", "SFP", "SFP+", "QSFP", "QSFP+", "XFP", "RF");
                
                print '<select name="type" class="ip_addr form-control input-sm input-w-auto" ' . $delete . '>' . "\n";
                
                foreach ($types as $type) {
                    if($port->type == $type) {
                        print '<option value="'.$type.'" selected>'.$type.'</option>' . "\n";
                    } else {
                        print '<option value="'.$type.'">'.$type.'</option>' . "\n";
                    }
                }

                print '</select>' . "\n";
                print '	</td>' . "\n";
                print '</tr>' . "\n";
                ?>

                <!-- Device -->
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
                        if ($device['id'] == $port->device) {
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

                <!-- PoE -->
                <?php
                print '<tr>' . "\n";
                print '	<td>' . _('PoE') . '</td>' . "\n";
                print '	<td>' . "\n";

                $poeOptions = array("N/A", "INPUT", "OUTPUT");
                
                print '<select name="poe" class="ip_addr form-control input-sm input-w-auto" ' . $delete . '>' . "\n";

                foreach ($poeOptions as $poeOption) {
                    if ($port->poe == $poeOption) {
                        print '<option value="'.$poeOption.'" selected>'.$poeOption.'</option>';
                    } else {
                        print '<option value="'.$poeOption.'">'.$poeOption.'</option>';
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
                        $custom_input = $Tools->create_custom_field_input($field, $port, $_POST['action'], $timepicker_index);
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
        ?>" id="editPortSubmit"><i class="fa <?php
                if ($_POST['action'] == "add") {
                    print "fa-plus";
                } else if ($_POST['action'] == "delete") {
                    print "fa-trash-o";
                } else {
                    print "fa-check";
                }
                ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
    </div>
    <!-- result -->
    <div class="editPortResult"></div>
</div>
