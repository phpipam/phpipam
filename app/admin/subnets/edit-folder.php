<?php

/*
 * Print edit folder
 *********************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Sections	= new Sections ($Database);
$Subnets	= new Subnets ($Database);
$Tools		= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $POST->action=="add" ? $User->Crypto->csrf_cookie ("create", "folder_add") : $User->Crypto->csrf_cookie ("create", "folder_".$POST->subnetId);

# validate action
$Admin->validate_action();

# ID must be numeric
if($POST->action!="add") {
	if(!is_numeric($POST->subnetId))										{ $Result->show("danger", _("Invalid ID"), true, true); }
}

# verify that user has permissions to add subnet
if($POST->action == "add") {
	if($Sections->check_permission ($User->user, $POST->sectionId) != 3) { $Result->show("danger", _('You do not have permissions to add new subnet in this section')."!", true, true); }
}
# otherwise check subnet permission
else {
	if($Subnets->check_permission ($User->user, $POST->subnetId) != 3) 	{ $Result->show("danger", _('You do not have permissions to add edit/delete this subnet')."!", true, true); }
}


# we are editing or deleting existing subnet, get old details
if ($POST->action != "add") {
    $folder_old_details = (array) $Subnets->fetch_subnet(null, $POST->subnetId);
}
# we are adding new folder - get folder details
else {
	# for selecting master subnet if added from subnet details!
	if(!is_blank($POST->subnetId)) {
    	$subnet_old_temp = (array) $Subnets->fetch_subnet(null, $POST->subnetId);
    	$subnet_old_details['masterSubnetId'] 	= @$subnet_old_temp['id'];			// same master subnet ID for nested
    	$subnet_old_details['vlanId'] 		 	= @$subnet_old_temp['vlanId'];		// same default vlan for nested
    	$subnet_old_details['vrfId'] 		 	= @$subnet_old_temp['vrfId'];		// same default vrf for nested
	}
}

# fetch custom fields
$custom_fields = $Tools->fetch_custom_fields('subnets');
# fetch all sections
$sections = $Sections->fetch_all_sections();


# set readonly flag
$readonly = $POST->action=="edit" || $POST->action=="delete" ? true : false;
?>



<!-- header -->
<div class="pHeader"><?php print $User->get_post_action(); ?> <?php print _('folder'); ?></div>


<!-- content -->
<div class="pContent">

	<form id="editFolderDetails">
	<table class="editSubnetDetails table table-noborder table-condensed">

    <!-- name -->
    <tr>
        <td class="middle"><?php print _('Name'); ?></td>
        <td>
            <input type="text" class="form-control input-sm input-w-250" id="field-description" name="description" value="<?php print $Tools->strip_xss(@$folder_old_details['description']); ?>">
        </td>
        <td class="info2"><?php print _('Enter folder name'); ?></td>
    </tr>

    <?php if($POST->action != "add") { ?>
    <!-- section -->
    <tr>
        <td class="middle"><?php print _('Section'); ?></td>
        <td>
        	<select name="sectionIdNew" class="form-control input-sm input-w-auto">
            	<?php
	            if($sections!==false) {
	            	foreach($sections as $section) {
	            		/* selected? */
	            		if($POST->sectionId == $section->id)  { print '<option value="'. $section->id .'" selected>'. $section->name .'</option>'. "\n"; }
	            		else 									 { print '<option value="'. $section->id .'">'. $section->name .'</option>'. "\n"; }
	            	}
            	}
            	?>
            </select>

        	</select>
        </td>
        <td class="info2"><?php print _('Move to different section'); ?></td>
    </tr>
    <?php } ?>

    <!-- Master subnet -->
    <tr>
        <td><?php print _('Master Folder'); ?></td>
        <td>
        	<?php $Subnets->print_mastersubnet_dropdown_menu($POST->sectionId, @$folder_old_details['masterSubnetId'], true); ?>
        </td>
        <td class="info2"><?php print _('Enter master folder if you want to nest it under existing folder, or select root to create root folder'); ?>!</td>
    </tr>

    <!-- hidden values -->
    <input type="hidden" name="sectionId"       value="<?php print escape_input($POST->sectionId);    ?>">
    <input type="hidden" name="subnetId"        value="<?php print escape_input($POST->subnetId);     ?>">
    <input type="hidden" name="action"    		value="<?php print escape_input($POST->action); ?>">
	<input type="hidden" name="vlanId" 			value="0">
	<input type="hidden" name="vrfId" 			value="0">
	<input type="hidden" name="csrf_cookie"     value="<?php print $csrf; ?>">

    <?php
	//custom Subnet fields
    if(sizeof($custom_fields) > 0) {
    	# count datepickers
		$timepicker_index = 0;

    	print "<tr>";
    	print "	<td colspan='3' class='hr'><hr></td>";
    	print "</tr>";
	    foreach($custom_fields as $field) {
	    	# retain newlines
	    	$folder_old_details[$field['name']] = str_replace("\n", "\\n", @$folder_old_details[$field['name']]);

			# set default value !
			if ($POST->action=="add")	{ $folder_old_details[$field['name']] = $field['Default']; }

            // create input > result is array (required, input(html), timepicker_index)
            $custom_input = $Tools->create_custom_field_input ($field, $folder_old_details, $timepicker_index);
            $timepicker_index = $custom_input['timepicker_index'];

            // print
            print "<tr>";
            print " <td>".ucwords($Tools->print_custom_field_name ($field['name']))." ".$custom_input['required']."</td>";
            print " <td>".$custom_input['field']."</td>";
            print "</tr>";
	    }
    }

    # divider
    print "<tr>";
    print "	<td colspan='3' class='hr'><hr></td>";
    print "</tr>";
    ?>

    </table>
    </form>

    <?php
    # warning if delete
    if($POST->action == "delete") {
	    print "<div class='alert alert-warning' style='margin-top:0px;'><strong>"._('Warning')."</strong><br>"._('Removing subnets will delete ALL underlaying subnets and belonging IP addresses')."!</div>";
    }
    ?>


</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<?php
		//if action == edit and location = IPaddresses print also delete form
		if(($POST->action == "edit") && ($POST->location == "IPaddresses") ) {
			print "<button class='btn btn-sm btn-default btn-danger editFolderSubmitDelete' data-action='delete' data-subnetId='$folder_old_details[id]'><i class='fa fa-trash-o'></i> "._('Delete folder')."</button>";
		}
		?>
		<button class="btn btn-sm btn-default editFolderSubmit <?php if($POST->action=="delete") print "btn-danger"; else print "btn-success"; ?>"><i class="<?php if($POST->action=="add") { print "fa fa-plus"; } elseif ($POST->action=="delete") { print "fa fa-trash-o"; } else { print "fa fa-check"; } ?>"></i> <?php print $User->get_post_action(); ?></button>
	</div>

	<div class="manageFolderEditResult"></div>
</div>