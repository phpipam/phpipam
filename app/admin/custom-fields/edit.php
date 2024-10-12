<?php

/**
 * Edit custom IP field
 ************************/


/*
	provided values are:
		table		= name of the table
		action		= action
		fieldName	= field name to edit
 */


/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "custom_field");

# validate action
$Admin->validate_action();

/* reset field name for add! */
if($POST->action == "add") 	{ $POST->fieldName = ""; }
else 						{ $POST->oldname = $POST->fieldName;}

# fetch old field definition
$fieldval = (array) $Tools->fetch_full_field_definition($POST->table, $POST->fieldName);
?>

<script type='text/javascript'>
$(document).ready (function () {
// check spce
check_name_whitespace ();
// on focusout
$("input[name='name']").focusout(function () {
check_name_whitespace ();
});
// check space function
function check_name_whitespace () {
	var namefieldval = $("input[name='name']").val();
	if (namefieldval.indexOf(' ') >= 0) {
		$('tr.spacewarning td').html("<div class='alert alert-warning'><i class='fa fa-exclamation'></i> Please consider using Name without spaces!</div>");
		$('tr.spacewarning').show();
	}
	else {
		$('tr.spacewarning').hide();
	}
}
});
</script>

<div class="pHeader"><?php print $User->get_post_action(); ?> <?php print _('custom field'); ?></div>


<div class="pContent">

	<form id="editCustomFields">
	<table id="editCustomFields" class="table table-noborder table-condensed">

	<!-- name -->
	<tr>
		<td><?php print _('Name'); ?></td>
		<td>
			<input type="text" name="name" class="form-control input-sm" value="<?php print $Tools->print_custom_field_name ($POST->fieldName); ?>" placeholder="<?php print _('Select field name'); ?>" <?php if($POST->action == "delete") { print 'readonly'; } ?>>

			<input type="hidden" name="oldname" value="<?php print escape_input($POST->oldname); ?>">
			<input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
			<input type="hidden" name="table" value="<?php print escape_input($POST->table); ?>">
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
		</td>
	</tr>
	<tr class='spacewarning'>
		<td colspan="2"></td>
	</tr>

	<!-- Description -->
	<tr>
		<td><?php print _('Description'); ?></td>
		<td>
			<input type="text" name="Comment" class="form-control input-sm" value="<?php print @$fieldval['Comment']; ?>" placeholder="<?php print _('Enter comment for users'); ?>" <?php if($POST->action == "delete") { print 'readonly'; } ?>>
		</td>
	</tr>

	<!-- type -->
	<tr>
		<td><?php print _('Type'); ?></td>
		<?php
		// define supported types
		$mTypes = $Admin->valid_custom_field_types();
		//reformat old type
		$oldMType = strstr(@$fieldval['Type'] ?: '', "(", true);
		$oldMSize = str_replace(array("(",")"), "",strstr(@$fieldval['Type'] ?: '', "(", false));

		//exceptions
		if(@$fieldval['Type']=="text" || @$fieldval['Type']=="date" || @$fieldval['Type']=="datetime" || @$fieldval['Type']=="set" || @$fieldval['Type']=="enum")	{ $oldMType = @$fieldval['Type']; }
		?>
		<td>
			<select name="fieldType" class="input-sm input-w-auto form-control">
			<?php
			foreach($mTypes as $name=>$type) {
				if($type==$oldMType)							{ print "<option value='$type' selected='selected'>$name</option>"; }
				elseif($type=="bool" && $oldMType=="tinyint")	{ print "<option value='$type' selected='selected'>$name</option>"; }
				else											{ print "<option value='$type'>$name</option>"; }
			}
			?>
			</select>
		</td>
	</tr>

	<!-- size -->
	<tr>
		<td><?php print _('Size / Length'); ?></td>
		<td>
			<input type="text" name="fieldSize" class="form-control input-sm" value="<?php print htmlentities(@$oldMSize); ?>" placeholder="<?php print _('Enter field length'); ?>" <?php if($POST->action == "delete") { print 'readonly'; } ?>>
		</td>
	</tr>

	<!-- Default -->
	<tr>
		<td><?php print _('Default value'); ?></td>
		<td>
			<input type="text" name="fieldDefault" class="form-control input-sm" value="<?php print @$fieldval['Default']; ?>" placeholder="<?php print _('Enter default value'); ?>" <?php if($POST->action == "delete") { print 'readonly'; } ?>>
		</td>
	</tr>

	<!-- required -->
	<tr>
		<td><?php print _('Required field'); ?></td>
		<td>
			<input name="NULL" type="checkbox" value="NO" <?php if(@$fieldval['Null']=="NO") print "checked"; ?>>
		</td>
	</tr>

	</table>
	</form>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Close'); ?></button>
		<button class="btn btn-sm btn-default <?php if($POST->action=="delete") { print "btn-danger"; } else { print "btn-success";} ?>" id="editcustomSubmit"><i class="fa <?php if($POST->action=="add") { print "fa-plus"; } else if ($POST->action=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print $User->get_post_action(); ?></button>
	</div>
	<!-- result -->
	<div class="customEditResult"></div>
</div>