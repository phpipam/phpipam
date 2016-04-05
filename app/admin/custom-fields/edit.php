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
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->csrf_cookie ("create", "custom_field");


/* reset field name for add! */
if($_POST['action'] == "add") 	{ $_POST['fieldName'] = ""; }
else 							{ $_POST['oldname'] = $_POST['fieldName'];}

# fetch old field definition
$fieldval = (array) $Tools->fetch_full_field_definition($_POST['table'], $_POST['fieldName']);
?>


<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('custom field'); ?></div>


<div class="pContent">

	<form id="editCustomFields">
	<table id="editCustomFields" class="table table-noborder table-condensed">

	<!-- name -->
	<tr>
		<td><?php print _('Name'); ?></td>
		<td>
			<input type="text" name="name" class="form-control input-sm" value="<?php print $_POST['fieldName']; ?>" placeholder="<?php print _('Select field name'); ?>" <?php if($_POST['action'] == "delete") { print 'readonly'; } ?>>

			<input type="hidden" name="oldname" value="<?php print $_POST['oldname']; ?>">
			<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
			<input type="hidden" name="table" value="<?php print $_POST['table']; ?>">
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
		</td>
	</tr>

	<!-- Description -->
	<tr>
		<td><?php print _('Description'); ?></td>
		<td>
			<input type="text" name="Comment" class="form-control input-sm" value="<?php print @$fieldval['Comment']; ?>" placeholder="<?php print _('Enter comment for users'); ?>" <?php if($_POST['action'] == "delete") { print 'readonly'; } ?>>
		</td>
	</tr>

	<!-- type -->
	<tr>
		<td><?php print _('Type'); ?></td>
		<?php
		// define supported types
		$mTypes = array("varchar"=>"varchar", "integer"=>"int", "boolean"=>"bool", "text"=>"text", "date"=>"date", "datetime"=>"datetime", "set"=>"set", "enum"=>"enum");
		//reformat old type
		$oldMType = strstr(@$fieldval['Type'], "(", true);
		$oldMSize = str_replace(array("(",")"), "",strstr(@$fieldval['Type'], "(", false));

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
			<input type="text" name="fieldSize" class="form-control input-sm input-w-100" value="<?php print @$oldMSize; ?>" placeholder="<?php print _('Enter field length'); ?>" <?php if($_POST['action'] == "delete") { print 'readonly'; } ?>>
		</td>
	</tr>

	<!-- Default -->
	<tr>
		<td><?php print _('Default value'); ?></td>
		<td>
			<input type="text" name="fieldDefault" class="form-control input-sm" value="<?php print @$fieldval['Default']; ?>" placeholder="<?php print _('Enter default value'); ?>" <?php if($_POST['action'] == "delete") { print 'readonly'; } ?>>
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
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success";} ?>" id="editcustomSubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>
	<!-- result -->
	<div class="customEditResult"></div>
</div>