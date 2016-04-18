<?php

/*
 * Print edit folder
 *********************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

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
$csrf = $User->csrf_cookie ("create", "folder");

# ID must be numeric
if($_POST['action']!="add") {
	if(!is_numeric($_POST['subnetId']))										{ $Result->show("danger", _("Invalid ID"), true, true); }
}

# verify that user has permissions to add subnet
if($_POST['action'] == "add") {
	if($Sections->check_permission ($User->user, $_POST['sectionId']) != 3) { $Result->show("danger", _('You do not have permissions to add new subnet in this section')."!", true, true); }
}
# otherwise check subnet permission
else {
	if($Subnets->check_permission ($User->user, $_POST['subnetId']) != 3) 	{ $Result->show("danger", _('You do not have permissions to add edit/delete this subnet')."!", true, true); }
}


# we are editing or deleting existing subnet, get old details
if ($_POST['action'] != "add") {
    $folder_old_details = (array) $Subnets->fetch_subnet(null, $_POST['subnetId']);
}
# we are adding new folder - get folder details
else {
	# for selecting master subnet if added from subnet details!
	if(strlen($_POST['subnetId']) > 0) {
    	$subnet_old_temp = (array) $Subnets->fetch_subnet(null, $_POST['subnetId']);
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
$readonly = $_POST['action']=="edit" || $_POST['action']=="delete" ? true : false;
?>



<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('folder'); ?></div>


<!-- content -->
<div class="pContent">

	<form id="editFolderDetails">
	<table class="editSubnetDetails table table-noborder table-condensed">

    <!-- name -->
    <tr>
        <td class="middle"><?php print _('Name'); ?></td>
        <td>
            <input type="text" class="form-control input-sm input-w-250" id="field-description" name="description" value="<?php print @$folder_old_details['description']; ?>">
        </td>
        <td class="info2"><?php print _('Enter folder name'); ?></td>
    </tr>

    <?php if($_POST['action'] != "add") { ?>
    <!-- section -->
    <tr>
        <td class="middle"><?php print _('Section'); ?></td>
        <td>
        	<select name="sectionIdNew" class="form-control input-sm input-w-auto">
            	<?php
	            if($sections!==false) {
	            	foreach($sections as $section) {
	            		/* selected? */
	            		if($_POST['sectionId'] == $section->id)  { print '<option value="'. $section->id .'" selected>'. $section->name .'</option>'. "\n"; }
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
        	<?php $Subnets->print_mastersubnet_dropdown_menu($_POST['sectionId'], @$folder_old_details['masterSubnetId'], true); ?>
        </td>
        <td class="info2"><?php print _('Enter master folder if you want to nest it under existing folder, or select root to create root folder'); ?>!</td>
    </tr>

    <!-- hidden values -->
    <input type="hidden" name="sectionId"       value="<?php print $_POST['sectionId'];    ?>">
    <input type="hidden" name="subnetId"        value="<?php print $_POST['subnetId'];     ?>">
    <input type="hidden" name="action"    		value="<?php print $_POST['action']; ?>">
	<input type="hidden" name="vlanId" 			value="0">
	<input type="hidden" name="vrfId" 			value="0">
	<input type="hidden" name="csrf_cookie"     value="<?php print $csrf; ?>">

    <?php
    	# custom Subnet fields
	    if(sizeof($custom_fields) > 0) {
	    	# count datepickers
			$timeP = 0;

	    	print "<tr>";
	    	print "	<td colspan='3' class='hr'><hr></td>";
	    	print "</tr>";
		    foreach($custom_fields as $field) {

		    	# replace spaces
		    	$field['nameNew'] = str_replace(" ", "___", $field['name']);
		    	# retain newlines
		    	$folder_old_details[$field['name']] = str_replace("\n", "\\n", @$folder_old_details[$field['name']]);

				# set default value !
				if ($_POST['action']=="add"){ $folder_old_details[$field['name']] = $field['Default']; }

		    	# required
				if($field['Null']=="NO")	{ $required = "*"; }
				else						{ $required = ""; }

				print '<tr>'. "\n";
				print '	<td>'. $field['name'] .' '.$required.'</td>'. "\n";
				print '	<td colspan="2">'. "\n";

				//set type
				if(substr($field['type'], 0,3) == "set" || substr($field['type'], 0,4) == "enum") {
					//parse values
					$tmp = substr($field['type'], 0,3)=="set" ? explode(",", str_replace(array("set(", ")", "'"), "", $field['type'])) : explode(",", str_replace(array("enum(", ")", "'"), "", $field['type']));

					//null
					if($field['Null']!="NO") { array_unshift($tmp, ""); }

					print "<select name='$field[nameNew]' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$field[Comment]'>";
					foreach($tmp as $v) {
						if($v==$folder_old_details[$field['name']])	{ print "<option value='$v' selected='selected'>$v</option>"; }
						else								{ print "<option value='$v'>$v</option>"; }
					}
					print "</select>";
				}
				//date and time picker
				elseif($field['type'] == "date" || $field['type'] == "datetime") {
					// just for first
					if($timeP==0) {
						print '<link rel="stylesheet" type="text/css" href="css/1.2/bootstrap/bootstrap-datetimepicker.min.css">';
						print '<script type="text/javascript" src="js/1.2/bootstrap-datetimepicker.min.js"></script>';
						print '<script type="text/javascript">';
						print '$(document).ready(function() {';
						//date only
						print '	$(".datepicker").datetimepicker( {pickDate: true, pickTime: false, pickSeconds: false });';
						//date + time
						print '	$(".datetimepicker").datetimepicker( { pickDate: true, pickTime: true } );';

						print '})';
						print '</script>';
					}
					$timeP++;

					//set size
					if($field['type'] == "date")	{ $size = 10; $class='datepicker';		$format = "yyyy-MM-dd"; }
					else							{ $size = 19; $class='datetimepicker';	$format = "yyyy-MM-dd"; }

					//field
					if(!isset($folder_old_details[$field['name']]))	{ print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'] .'" maxlength="'.$size.'" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n"; }
					else										{ print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'] .'" maxlength="'.$size.'" value="'. $folder_old_details[$field['name']]. '" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n"; }
				}
				//boolean
				elseif($field['type'] == "tinyint(1)") {
					print "<select name='$field[nameNew]' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$field[Comment]'>";
					$tmp = array(0=>"No",1=>"Yes");
					//null
					if($field['Null']!="NO") { $tmp[2] = ""; }

					foreach($tmp as $k=>$v) {
						if(strlen($folder_old_details[$field['name']])==0 && $k==2)	{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
						elseif($k==$folder_old_details[$field['name']])				{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
						else													{ print "<option value='$k'>"._($v)."</option>"; }
					}
					print "</select>";
				}
				//text
				elseif($field['type'] == "text") {
					print ' <textarea class="form-control input-sm" name="'. $field['nameNew'] .'" placeholder="'. $field['name'] .'" rowspan=3 rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. str_replace("\\n","",$folder_old_details[$field['name']]). '</textarea>'. "\n";
				}
				//default - input field
				else {
					print ' <input type="text" class="ip_addr form-control input-sm" name="'. $field['nameNew'] .'" placeholder="'. $field['name'] .'" value="'. $folder_old_details[$field['name']]. '" size="30" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n";
				}

				print '	</td>'. "\n";
				print '</tr>'. "\n";
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
    if($_POST['action'] == "delete") {
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
		if(($_POST['action'] == "edit") && ($_POST['location'] == "IPaddresses") ) {
			print "<button class='btn btn-sm btn-default btn-danger editFolderSubmitDelete' data-action='delete' data-subnetId='$folder_old_details[id]'><i class='fa fa-trash-o'></i> "._('Delete folder')."</button>";
		}
		?>
		<button class="btn btn-sm btn-default editFolderSubmit <?php if($_POST['action']=="delete") print "btn-danger"; else print "btn-success"; ?>"><i class="<?php if($_POST['action']=="add") { print "fa fa-plus"; } else if ($_POST['action']=="delete") { print "fa fa-trash-o"; } else { print "fa fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>

	<div class="manageFolderEditResult"></div>
</div>