<?php

/**
 *	Print all available locations
 ************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $_POST['action']=="add" ? $User->Crypto->csrf_cookie ("create", "pstn_add") : $User->Crypto->csrf_cookie ("create", "pstn_".$_POST['id']);

# perm check popup
if($_POST['action']=="edit") {
    $User->check_module_permissions ("pstn", User::ACCESS_RW, true, true);
}
else {
    $User->check_module_permissions ("pstn", User::ACCESS_RWA, true, true);
}


# get Location object
if($_POST['action']!="add") {
	$prefix = $Admin->fetch_object ("pstnPrefixes", "id", $_POST['id']);
	$prefix!==false ? : $Result->show("danger", _("Invalid ID"), true, true);

	$master_prefix = $Admin->fetch_object ("pstnPrefixes", "id", $prefix->master);
}
else {
    # init object
    $prefix = new StdClass ();
    $prefix->master = 0;

    $master_prefix = new StdClass ();
    $master_prefix->name = 'root';
    $master_prefix->prefix = "/";

    # if id is set we are adding slave prefix
    if (isset($_POST['id'])) {
        if($_POST['id']!=0) {
        	$master_prefix = $Admin->fetch_object ("pstnPrefixes", "id", $_POST['id']);
        	$master_prefix!==false ? : $Result->show("danger", _("Invalid master ID"), true, true);

            $prefix->master = $master_prefix->id;
            $prefix->prefix = $master_prefix->prefix;
            $prefix->start  = $master_prefix->start;
            $prefix->deviceId = $master_prefix->deviceId;
        }
    }
}

# disable edit on delete
$readonly = $_POST['action']=="delete" ? "readonly" : "";
$link = $readonly ? false : true;

# fetch custom fields
$custom = $Tools->fetch_custom_fields('pstnPrefixes');

?>


<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('PSTN prefix'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="editPSTN">
	<table id="editPSTN" class="table table-noborder table-condensed">

	<tbody>

        <!-- Master prefix -->
        <?php if($prefix->master!=0) { ?>
    	<tr>
        	<th><?php print _('Master prefix'); ?></th>
        	<th colspan="2">
            	<?php print $master_prefix->name. " (".$master_prefix->prefix.")"; ?>
        	</th>
        </tr>
    	<tr>
        	<th></th>
        	<th colspan="2">
            	<?php print _("Range").": ".$master_prefix->start. " - ".$master_prefix->stop; ?>
        	</th>
        </tr>
        <tr>
            <td colspan="3"><hr></td>
        </tr>
        <?php } ?>

    	<!-- name -->
    	<tr>
        	<th><?php print _('Name'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="name" value="<?php print $Tools->strip_xss(@$prefix->name); ?>" placeholder='<?php print _('Name'); ?>' <?php print $readonly; ?>>
            	<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
            	<input type="hidden" name="id" value="<?php print @$prefix->id; ?>">
            	<input type="hidden" name="master" value="<?php print @$prefix->master; ?>">
            	<input type="hidden" name="action" value="<?php print escape_input($_POST['action']); ?>">
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Set Prefix name"); ?></span>
        	</td>
        </tr>

        <!-- Prefix -->
    	<tr>
        	<th><?php print _('Prefix'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="prefix" value="<?php print $Tools->strip_xss(@$prefix->prefix); ?>" placeholder='<?php print _('Prefix'); ?>' <?php print $readonly; ?>>
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Prefix"); ?></span>
        	</td>
        </tr>

        <!-- Start -->
    	<tr>
        	<th><?php print _('Start'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="start" style="width:70px;" value="<?php print @$prefix->start; ?>" placeholder='<?php print _('Start'); ?>' <?php print $readonly; ?>>
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Set start number"); ?></span>
        	</td>
        </tr>

        <!-- Stop -->
    	<tr>
        	<th><?php print _('Stop'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="stop" style="width:70px;"  value="<?php print @$prefix->stop; ?>" placeholder='<?php print _('Stop'); ?>' <?php print $readonly; ?>>
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Set stop number"); ?></span>
        	</td>
        </tr>


    	<tr>
        	<td colspan="3"><hr></td>
        </tr>

        <!-- Master prefix -->
<!--
        <tr>
            <th><?php print _('Master prefix'); ?></th>
            <td>
    			<?php $Tools->print_masterprefix_dropdown_menu ($prefix->master); ?>
            </td>
            <td>
                <span class='text-muted'><?php print _('Enter master prefix if you want to nest it under existing subnet, or select root to create root prefix'); ?></span>
            </td>
        </tr>
-->


    	<!-- Device -->
        <?php if($User->get_module_permissions ("devices")>=User::ACCESS_R) { ?>
    	<tr>
    		<th><?php print _('Device'); ?></th>
    		<td id="deviceDropdown">
    			<select name="deviceId" class="form-control input-sm input-w-auto">
    				<option value="0"><?php print _('None'); ?></option>
    				<?php
    				// fetch all devices
    				$devices = $Admin->fetch_all_objects("devices", "hostname");
    				// loop
    				if ($devices!==false) {
    					foreach($devices as $device) {
							//if same
							if($device->id == $prefix->deviceId) 	{ print '<option value="'. $device->id .'" selected>'. $device->hostname .'</option>'. "\n"; }
							else 									{ print '<option value="'. $device->id .'">'. $device->hostname .'</option>'. "\n";			 }
    					}
    				}
    				?>
    			</select>
    		</td>
    		<td class="info2"><?php print _('Select device where prefix is located'); ?></td>
        </tr>
        <?php } ?>

        <!-- description -->
    	<tr>
        	<td colspan="3"><hr></td>
        </tr>
    	<tr>
        	<th><?php print _('Description'); ?></th>
        	<td colspan="2">
            	<textarea class="form-control input-sm" name="description" placeholder='<?php print @$prefix->description; ?>' <?php print $readonly; ?>><?php print @$prefix->description; ?></textarea>
        	</td>
        </tr>


    	<!-- Custom -->
    	<?php
    	if(sizeof($custom) > 0) {
    		print '<tr>';
    		print '	<td colspan="2"><hr></td>';
    		print '</tr>';
    		# count datepickers
    		$timepicker_index = 0;
    		# all my fields
    		foreach($custom as $field) {
        		// create input > result is array (required, input(html), timepicker_index)
        		$custom_input = $Tools->create_custom_field_input ($field, $prefix, $timepicker_index);
        		$timepicker_index = $custom_input['timepicker_index'];
                // print
    			print "<tr>";
    			print "	<td>".ucwords($Tools->print_custom_field_name ($field['name']))." ".$custom_input['required']."</td>";
    			print "	<td>".$custom_input['field']."</td>";
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
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editPSTNSubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print escape_input(ucwords(_($_POST['action']))); ?></button>
	</div>
	<!-- result -->
	<div class="editPSTNResult"></div>
</div>