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
# perm check popup
if($POST->action=="edit") {
    $User->check_module_permissions ("locations", User::ACCESS_RW, true, true);
}
else {
    $User->check_module_permissions ("locations", User::ACCESS_RWA, true, true);
}


# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "location");

# validate action
$Admin->validate_action();

# get Location object
if($POST->action!="add") {
	$location = $Admin->fetch_object ("locations", "id", $POST->id);
	$location!==false ? : $Result->show("danger", _("Invalid ID"), true, true);
} else {
	$location = new Params();
}

# disable edit on delete
$readonly = $POST->action=="delete" ? "readonly" : "";
$link = $readonly ? false : true;

# fetch custom fields
$custom = $Tools->fetch_custom_fields('locations');

?>


<!-- header -->
<div class="pHeader"><?php print $User->get_post_action(); ?> <?php print _('Location'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="editLocation">
	<table id="editLocation" class="table table-noborder table-condensed">

	<tbody>
    	<!-- name -->
    	<tr>
        	<th><?php print _('Name'); ?></th>
        	<td colspan="2">
            	<input type="text" class="form-control input-sm" name="name" value="<?php print $location->name; ?>" placeholder='<?php print _('Name'); ?>' <?php print $readonly; ?>>
            	<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
            	<input type="hidden" name="id" value="<?php print $location->id; ?>">
            	<input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
        	</td>
        </tr>

        <!-- description -->
    	<tr>
        	<th><?php print _('Description'); ?></th>
        	<td colspan="2">
            	<textarea class="form-control input-sm" name="description" placeholder='<?php print _('Description'); ?>' <?php print $readonly; ?>><?php print $location->description; ?></textarea>
        	</td>
        </tr>

    	<!-- Address -->
    	<tr>
        	<td colspan="3"><hr></td>
    	</tr>
    	<tr>
        	<th><?php print _('Address'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="address" value="<?php print $location->address; ?>" placeholder='<?php print _('Address'); ?>' <?php print $readonly; ?>>
            	<?php print _('or'); ?>
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Location address"); ?></span>
        	</td>
        </tr>

    	<tr>
        	<th><?php print _('Latitude'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="lat" value="<?php print $location->lat; ?>" placeholder='<?php print _('Latitude'); ?>' <?php print $readonly; ?>>
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("latitude"); ?></span>
        	</td>
        </tr>

    	<tr>
        	<th><?php print _('Longitude'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="long" value="<?php print $location->long; ?>" placeholder='<?php print _('Longitude'); ?>' <?php print $readonly; ?>>
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Longitude"); ?></span>
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
        		$custom_input = $Tools->create_custom_field_input ($field, $location, $timepicker_index);
        		$timepicker_index = $custom_input['timepicker_index'];
                // print
    			print "<tr>";
    			print "	<td>".ucwords($Tools->print_custom_field_name ($field['name']))." ".$custom_input['required']."</td>";
    			print "	<td colspan='2'>".$custom_input['field']."</td>";
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
		<button class="btn btn-sm btn-default <?php if($POST->action=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editLocationSubmit"><i class="fa <?php if($POST->action=="add") { print "fa-plus"; } elseif ($POST->action=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print $User->get_post_action(); ?></button>
	</div>
	<!-- result -->
	<div class="editLocationResult"></div>
</div>
