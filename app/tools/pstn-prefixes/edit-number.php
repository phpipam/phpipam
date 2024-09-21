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
$Addresses	= new Addresses ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "pstn_number");

# perm check popup
$User->check_module_permissions ("pstn", User::ACCESS_RW, true, true);

# get Location object
if($POST->action!="add") {
	$number = $Admin->fetch_object ("pstnNumbers", "id", $POST->id);
	$number!==false ? : $Result->show("danger", _("Invalid ID"), true, true);

	$prefix = $Admin->fetch_object ("pstnPrefixes", "id", $number->prefix);
}
else {
    # id is required
    if (isset($POST->id)) {
    	$prefix = $Admin->fetch_object ("pstnPrefixes", "id", $POST->id);
    	$prefix!==false ? : $Result->show("danger", _("Invalid prefix ID"), true, true);

        $number = new Params ();

        $number->id = 0;
        $number->prefix = $prefix->id;
        $number->deviceId = $prefix->deviceId;
        $number->state = 2;
    }
    else {
        $Result->show("danger", _("Invalid ID"), true, true);
    }
}

# disable edit on delete
$readonly = $POST->action=="delete" ? "readonly" : "";
$link = $readonly ? false : true;

# fetch custom fields
$custom = $Tools->fetch_custom_fields('pstnNumbers');

?>


<!-- header -->
<div class="pHeader"><?php print $User->get_post_action(); ?> <?php print _('PSTN number'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="editPSTNnumber">
	<table id="editPSTNnumber" class="table table-noborder table-condensed">

	<tbody>

    	<tr>
        	<th><?php print _('Prefix'); ?></th>
        	<th colspan="2">
            	<?php print $prefix->name. " (".$prefix->prefix.")"; ?>
        	</th>
        </tr>
    	<tr>
        	<th></th>
        	<th colspan="2">
            	<?php print _("Range").": ".$prefix->start. " - ".$prefix->stop; ?>
        	</th>
        </tr>
        <tr>
            <td colspan="3"><hr></td>
        </tr>

    	<!-- number -->
    	<tr>
        	<th><?php print _('Number'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="number" style="width:100px;" value="<?php print $number->number; ?>" placeholder='<?php print _('Number'); ?>' <?php print $readonly; ?>>
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Number"); ?></span>
        	</td>
        </tr>

    	<!-- name -->
    	<tr>
        	<th><?php print _('Name'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="name" value="<?php print $Tools->strip_xss($number->name); ?>" placeholder='<?php print _('Name'); ?>' <?php print $readonly; ?>>
            	<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
            	<input type="hidden" name="id" value="<?php print $number->id; ?>">
            	<input type="hidden" name="prefix" value="<?php print $number->prefix; ?>">
            	<input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Number Name"); ?></span>
        	</td>
        </tr>

        <!-- Owner -->
    	<tr>
        	<th><?php print _('Owner'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="owner" value="<?php print $Tools->strip_xss($number->owner); ?>" placeholder='<?php print _('Owner'); ?>' <?php print $readonly; ?>>
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Address owner"); ?></span>
        	</td>
        </tr>

        <!-- state -->
    	<?php
    	# fetch all states
    	$ip_types = (array) $Addresses->addresses_types_fetch();
    	# default type
    	if(!is_numeric(@$address['state'])) 		{ $address['state'] = 2; } // online

    	print '<tr>';
    	print '	<th>'._('Tag').'</th>';
    	print '	<td>';
    	print '		<select name="state" '.$readonly.' class="ip_addr form-control input-sm input-w-auto">';
    	# printout
    	foreach($ip_types as $k=>$type) {
    		if($number->state==$k)				{ print "<option value='$k' selected>"._($type['type'])."</option>"; }
    		else									{ print "<option value='$k'>"._($type['type'])."</option>"; }
    	}
    	print '		</select>';
    	print '	</td>';
    	print '</tr>';
    	?>

    	<!-- Device -->
        <?php if ($User->get_module_permissions ("devices")>=User::ACCESS_R) { ?>
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
							if($device->id == $number->deviceId) 	{ print '<option value="'. $device->id .'" selected>'. $device->hostname .'</option>'; }
							else 									{ print '<option value="'. $device->id .'">'. $device->hostname .'</option>';			 }
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
            	<textarea class="form-control input-sm" name="description" placeholder='<?php print $number->description; ?>' <?php print $readonly; ?>><?php print $number->description; ?></textarea>
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
        		$custom_input = $Tools->create_custom_field_input ($field, $number, $timepicker_index);
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
		<button class="btn btn-sm btn-default <?php if($POST->action=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editPSTNnumberSubmit"><i class="fa <?php if($POST->action=="add") { print "fa-plus"; } elseif ($POST->action=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print $User->get_post_action(); ?></button>
	</div>
	<!-- result -->
	<div class="editPSTNnumberResult"></div>
</div>
