<?php

/**
 * Script to print add / edit / delete vault item
 *************************************************/

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

# make sure user has access
if ($User->get_module_permissions ("vaults")<User::ACCESS_RW) { $Result->show("danger", _("Insufficient privileges").".", true, true); }

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "vaultitem");

# validate action
$Admin->validate_action();

# ID must be numeric
if($POST->action!="add" && !is_numeric($POST->id)) { $Result->show("danger", _("Invalid ID"), true, true); }

# fetch api for edit / add
if($POST->action!="add") {
	# fetch vault details
	$item = $Admin->fetch_object ("vaultItems", "id", $POST->id);
	# null ?
	$item===false ? $Result->show("danger", _("Invalid ID"), true) : null;
	# to json and decode
	$item_objects = db_json_decode($User->Crypto->decrypt($item->values, $_SESSION['vault'.$item->vaultId]));
	# title
	$title = $User->get_post_action().' '._('password');
} else {
	# generate new code
	$item = new StdClass;
	$item->vaultId = $POST->vaultid;
	# title
	$title = _('Add new password');
}

# fetch custom fields
$custom = $Tools->fetch_custom_fields('vaultItems');
?>

<!-- header -->
<div class="pHeader"><?php print $title; ?></div>

<!-- content -->
<div class="pContent">

	<form id="vaultItemEdit" name="vaultItemEdit" autocomplete="off">
	<table class="groupEdit table table-noborder table-condensed">

	<!-- id -->
	<tr>
	    <td><?php print _('Name'); ?></td>
	    <td>
	    	<input type="text" name="name" class="form-control input-sm" value="<?php print $Admin->strip_xss(@$item_objects->name); ?>" <?php if($POST->action == "delete") print "readonly"; ?> autocomplete="off">
	        <input type="hidden" name="id" value="<?php print $item->id; ?>">
	        <input type="hidden" name="vaultId" value="<?php print $item->vaultId; ?>">
    		<input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
    		<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
	    </td>
       	<td class="info2"><?php print _('Enter name'); ?></td>
    </tr>

    <?php if($POST->action!="delete") { ?>

	<!-- Username -->
	<tr>
	    <td><?php print _('Username'); ?></td>
	    <td><input type="text" id="username" name="username" class="form-control input-sm" value="<?php print $Admin->strip_xss(@$item_objects->username); ?>" <?php if($POST->action == "delete") print "readonly"; ?> autocomplete="off"></td>
       	<td class="info2"><?php print _('Username'); ?></td>
    </tr>

	<!-- Password -->
	<tr>
	    <td><?php print _('Password'); ?></td>
	    <td><input type="password" id="password" name="password" class="form-control input-sm"  value="<?php print $Admin->strip_xss(@$item_objects->password); ?>" <?php if($POST->action == "delete") print "readonly"; ?>></td>
       	<td class="info2"><?php print _('Password'); ?></td>
    </tr>

    <!-- description -->
    <tr>
    	<td><?php print _('Description'); ?></td>
    	<td>
    		<input type="text" name="description" class="form-control input-sm" value="<?php print $Admin->strip_xss(@$item_objects->description); ?>" <?php if($POST->action == "delete") print "readonly"; ?>>
    	</td>
    	<td class="info2"><?php print _('Enter description'); ?></td>
    </tr>


	<!-- Custom -->
	<?php
	if(sizeof($custom) > 0) {

		print '<tr>';
		print '	<td colspan="3"><hr></td>';
		print '</tr>';

		# count datepickers
		$timepicker_index = 0;

		# all my fields
		foreach($custom as $field) {
    		// disabled
    		$cust_disabled = $POST->action == "delete" ? true : false;
    		// create input > result is array (required, input(html), timepicker_index)
    		$custom_input = $Tools->create_custom_field_input ($field, $item, $timepicker_index, $disabled);
    		// add datepicker index
    		$timepicker_index++;
            // print
			print "<tr>";
			print "	<td>".ucwords($Tools->print_custom_field_name ($field['name']))." ".$custom_input['required']."</td>";
			print "	<td>".$custom_input['field']."</td>";
			print "	<td class='info2'>".$field['Comment']."</td>";
			print "</tr>";
		}

		print '<tr>';
		print '	<td colspan="2"><hr></td>';
		print '</tr>';
	}
	?>

    <?php } ?>

</table>
</form>

</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class='btn btn-sm btn-default submit_popup <?php if($POST->action=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>' data-script="app/admin/vaults/edit-item-password-result.php" data-result_div="vaultItemEditResult" data-form='vaultItemEdit'>
			<i class="fa <?php if($POST->action=="add") { print "fa-plus"; } elseif ($POST->action=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print $User->get_post_action(); ?>
		</button>

	</div>
	<!-- Result -->
	<div id="vaultItemEditResult"></div>
</div>
