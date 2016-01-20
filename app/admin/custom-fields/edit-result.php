<?php

/**
 * Edit custom IP field
 ************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# validate csrf cookie
$_POST['csrf_cookie']==$_SESSION['csrf_cookie'] ? :                         $Result->show("danger", _("Invalid CSRF cookie"), true);


/* checks */
if($_POST['action'] == "delete") {
	# no cecks
}
else {
	# remove spaces
	$_POST['name'] = trim($_POST['name']);

	# length > 4 and < 12
	if( (strlen($_POST['name']) < 2) || (strlen($_POST['name']) > 24) ) 	{ $errors[] = _('Name must be between 4 and 24 characters'); }

	/* validate HTML */

	# must not start with number
	if(is_numeric(substr($_POST['name'], 0, 1))) 							{ $errors[] = _('Name must not start with number'); }

	# only alphanumeric and _ are allowed
	if(!preg_match('/^[a-zA-Z0-9 \_]+$/i', $_POST['name'])) 				{ $errors[] = _('Only alphanumeric, spaces and underscore characters are allowed'); }

	# required must have default value
	if($_POST['NULL']=="NO" && strlen($_POST['fieldDefault'])==0)			{ $errors[] = _('Required fields must have default values'); }

	# db type validations

	//boolean
	if($_POST['fieldType']=="bool")	{
		if($_POST['fieldSize']!=0 && $_POST['fieldSize']!=1)				{ $errors[] = _('Boolean values can only be 0 or 1'); }
		if($_POST['fieldDefault']!=0 && $_POST['fieldDefault']!=1)			{ $errors[] = _('Default boolean values can only be 0 or 1'); }
	}
	//varchar
	elseif($_POST['fieldType']=="varchar") {
		if(!is_numeric($_POST['fieldSize']))								{ $errors[] = _('Fieldsize must be numeric'); }
		if($_POST['fieldSize']>256)											{ $errors[] = _('Varchar size limit is 256 characters'); }
	}
	//number
	elseif($_POST['fieldType']=="int") {
		if(!is_numeric($_POST['fieldSize']))								{ $errors[] = _('Integer values must be numeric'); }

	}
}

/* die if errors otherwise execute */
if(sizeof($errors) != 0) {
	print '<div class="alert alert alert-danger">'._('Please correct the following errors').':'. "\n";
	print '<ul>'. "\n";
	foreach($errors as $error) {
		print '<li style="text-align:left">'. $error .'</li>'. "\n";
	}
	print '</ul>'. "\n";
	print '</div>'. "\n";
}
else {
	if(!$Admin->update_custom_field_definition($_POST)) { $Result->show("danger",  _("Failed to $_POST[action] field"), true); }
	else 												{ $Result->show("success", _("Field $_POST[action] success"), true); }
}
?>
