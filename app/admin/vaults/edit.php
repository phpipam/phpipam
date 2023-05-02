<?php

/**
 * Script to print add / edit / delete Vault
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
if ($User->get_module_permissions ("vaults")<User::ACCESS_RWA) { $Result->show("danger", _("Insufficient privileges").".", true, true); }

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "vaults");

# validate action
$Admin->validate_action ($_POST['action'], true);

# ID must be numeric
if($_POST['action']!="add" && !is_numeric($_POST['id'])) { $Result->show("danger", _("Invalid ID"), true, true); }

# fetch vault for edit / add
if($_POST['action']!="add") {
	# fetch vault details
	$vault = $Admin->fetch_object ("vaults", "id", $_POST['id']);
	# null ?
	$vault===false ? $Result->show("danger", _("Invalid ID"), true) : null;
	# title
	$title =  ucwords($_POST['action']) .' '._('vault').' '.$vault->name;
} else {
	# generate new code
	$vault = new StdClass;
	$vault->Vault_code = $User->Crypto->generate_html_safe_token(32);
	# title
	$title = _('Create new vault');
}

# fetch custom fields
$custom = $Tools->fetch_custom_fields('vaults');
?>


<!-- header -->
<div class="pHeader"><?php print $title; ?></div>

<!-- content -->
<div class="pContent">

	<form id="vaultEdit" name="vaultEdit">
	<table class="groupEdit table table-noborder table-condensed">

	<!-- id -->
	<tr>
	    <td><?php print _('Vault name'); ?></td>
	    <td>
	    	<input type="text" name="name" class="form-control input-sm" value="<?php print $Admin->strip_xss(@$vault->name); ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?>>
	        <input type="hidden" name="id" value="<?php print $vault->id; ?>">
    		<input type="hidden" name="action" value="<?php print escape_input($_POST['action']); ?>">
    		<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
	    </td>
       	<td class="info2"><?php print _('Enter vault name'); ?></td>
    </tr>

	<tr>
	    <td><?php print _('Type'); ?></td>
	    <td>
	    	<select name="type" class="form-control input-sm input-w-auto">
	    	<?php
	    	$types = array("passwords"=>_("Password vault"),"certificates"=>_("Certificates vault"));
	    	foreach($types as $k=>$p) {
		    	if($k==$vault->type)	{ print "<option value='$k' selected='selected'>".$p."</option>"; }
		    	else					{ print "<option value='$k' 				   >".$p."</option>"; }
	    	}
	    	?>
	    	</select>
	    </td>
       	<td class="info2"><?php print _('Vault type'); ?></td>
    </tr>

	<!-- secret -->
	<?php if($_POST['action'] === "add") { ?>
	<tr>
	    <td><?php print _('Vault secret'); ?></td>
	    <td><input type="password" id="secret" name="secret" class="form-control input-sm"></td>
       	<td class="info2"><?php print _('Vault secret. Please store secret as it cannot be retreived if lost!'); ?></td>
    </tr>
    <?php } ?>

    <!-- description -->
    <tr>
    	<td><?php print _('Description'); ?></td>
    	<td>
    		<input type="text" name="description" class="form-control input-sm" value="<?php print $Admin->strip_xss(@$vault->description); ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?>>
    	</td>
    	<td class="info2"><?php print _('Enter description'); ?></td>
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
    		$custom_input = $Tools->create_custom_field_input ($field, $vault, $_POST['action'], $timepicker_index);
    		// add datepicker index
    		$timepicker_index = $timepicker_index++;
            // print
			print "<tr>";
			print "	<td>".ucwords($Tools->print_custom_field_name ($field['name']))." ".$custom_input['required']."</td>";
			print "	<td>".$custom_input['field']."</td>";
            print " <td class='info2'>".$field['Comment']."</td>";
			print "</tr>";
		}
	}
	?>

</table>
</form>

</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class='btn btn-sm btn-default submit_popup <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>' data-script="app/admin/vaults/edit-result.php" data-result_div="vaultEditResult" data-form='vaultEdit'>
			<i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print escape_input(ucwords(_($_POST['action']))); ?>
		</button>

	</div>
	<!-- Result -->
	<div id="vaultEditResult"></div>
</div>