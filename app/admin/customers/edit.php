<?php

/**
 * Script to print add / edit / delete Customer
 *************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# verify module permissions
if($_POST['action']=="edit") {
	$User->check_module_permissions ("customers", 2, true, true);
}
else {
	$User->check_module_permissions ("customers", 3, true, true);
}

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "customer");

# validate action
$Tools->validate_action ($_POST['action'], true);
# fetch custom fields
$custom = $Tools->fetch_custom_fields('customers');

# ID must be numeric
if($_POST['action']!="add" && !is_numeric($_POST['id'])) { $Result->show("danger", _("Invalid ID"), true, true); }

# fetch customer for edit / add
if($_POST['action']!="add") {
	# fetch api details
	$customer = $Tools->fetch_object ("customers", "id", $_POST['id']);
	# null ?
	$customer===false ? $Result->show("danger", _("Invalid ID"), true) : null;
	# title
	$title =  ucwords($_POST['action']) .' '._('customer').' '.$customer->title;
} else {
	# generate new code
	$customer = new StdClass;
	$customer->id = null;
	# title
	$title = _('Add new customer');
}
?>


<!-- header -->
<div class="pHeader"><?php print $title; ?></div>

<!-- content -->
<div class="pContent">

	<form id="customerEdit" name="customerEdit">
	<table class="groupEdit table table-noborder table-condensed">

	<!-- title -->
	<tr>
	    <td><?php print _('Title'); ?></td>
	    <td>
	    	<input type="text" name="title" class="form-control input-sm" value="<?php print $Tools->strip_xss(@$customer->title); ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?> placeholder="<?php print _("Title"); ?>">
	        <input type="hidden" name="id" value="<?php print $customer->id; ?>">
    		<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
    		<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
	    </td>
       	<td class="info2">* <?php print _('Customer title'); ?></td>
    </tr>

	<!-- divider -->
	<tr>
		<td colspan="3"><hr></td>
	</tr>

	<!-- Address -->
	<tr>
	    <td style="vertical-align: top !important"><?php print _('Address'); ?></td>
	    <td>
	    	<input type="text" name="address" class="form-control input-sm" value="<?php print $Tools->strip_xss(@$customer->address); ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?> placeholder="<?php print _("Address"); ?>">
	    	<input type="text" name="postcode" class="form-control input-sm" value="<?php print $Tools->strip_xss(@$customer->postcode); ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?> placeholder="<?php print _("Postcode"); ?>">
	    	<input type="text" name="city" class="form-control input-sm" value="<?php print $Tools->strip_xss(@$customer->city); ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?> placeholder="<?php print _("City"); ?>">
	    	<input type="text" name="state" class="form-control input-sm" value="<?php print $Tools->strip_xss(@$customer->state); ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?> placeholder="<?php print _("State"); ?>">

	    </td>
       	<td class="info2" style="vertical-align: top !important">* <?php print _('Customer address'); ?></td>
    </tr>

	<!-- divider -->
	<tr>
		<td colspan="3"><hr></td>
	</tr>

	<!-- Contact -->
	<tr>
	    <td style="vertical-align: top !important"><?php print _('Contact details'); ?></td>
	    <td>
	    	<input type="text" name="contact_person" class="form-control input-sm" value="<?php print $Tools->strip_xss(@$customer->contact_person); ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?> placeholder="<?php print _("Contact person"); ?>">
	    	<input type="text" name="contact_mail" class="form-control input-sm" value="<?php print $Tools->strip_xss(@$customer->contact_mail); ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?> placeholder="<?php print _("Email address"); ?>">
	    	<input type="text" name="contact_phone" class="form-control input-sm" value="<?php print $Tools->strip_xss(@$customer->contact_phone); ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?> placeholder="<?php print _("Phone"); ?>">

	    </td>
       	<td class="info2" style="vertical-align: top !important"><?php print _('Customer contact details'); ?></td>
    </tr>

	<tr>
	    <td style="vertical-align: top !important"><?php print _('Note'); ?></td>
	    <td colspan="2">
	    	<textarea type="text" name="note" class="form-control input-sm" <?php if($_POST['action'] == "delete") print "readonly"; ?> placeholder="<?php print _("Random notes"); ?>"><?php print $Tools->strip_xss(@$customer->note); ?></textarea>

	    </td>
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
			// readonly
			$disabled = $readonly == "readonly" ? true : false;
    		// create input > result is array (required, input(html), timepicker_index)
    		$custom_input = $Tools->create_custom_field_input ($field, $customer, $_POST['action'], $timepicker_index, $disabled);
    		// add datepicker index
    		$timepicker_index = $timepicker_index + $custom_input['timepicker_index'];
            // print
			print "<tr>";
			print "	<td>".ucwords($Tools->print_custom_field_name ($field['name']))." ".$custom_input['required']."</td>";
			print "	<td>".$custom_input['field']."</td>";
			print "	<td></td>";
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
		<button class='btn btn-sm btn-default submit_popup <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>' data-script="app/admin/customers/edit-submit.php" data-result_div="customerEditResult" data-form='customerEdit'>
			<i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?>
		</button>

	</div>
	<!-- Result -->
	<div id="customerEditResult"></div>
</div>