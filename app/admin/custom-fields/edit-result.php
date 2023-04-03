<?php

/**
 * Edit custom IP field
 ************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();
$Params		= new Params ($User->strip_input_tags ($_POST));

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "custom_field", $Params->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";


/* checks */
if($Params->action == "delete") {
	# no cecks
}
else {
	# remove spaces
	$Params->name = trim($Params->name);

	# length > 4 and < 12
	if( (mb_strlen($Params->name) < 2) || (mb_strlen($Params->name) > 24) ) 	{ $errors[] = _('Name must be between 4 and 24 characters'); }

	/* validate HTML */

	# must not start with number
	if(is_numeric(substr($Params->name, 0, 1))) 								{ $errors[] = _('Name must not start with number'); }

	# only alphanumeric and _ are allowed
	if(!preg_match('/^(\p{L}|\p{N})[(\p{L}|\p{N}) _.-]+$/u', $Params->name)) 	{ $errors[] = _('Only alphanumeric, spaces and underscore characters are allowed'); }

	# required must have default value
	if($Params->NULL=="NO" && mb_strlen($Params->fieldDefault)==0)			{ $errors[] = _('Required fields must have default values'); }

	# db type validations

	//boolean
	if($Params->fieldType=="bool")	{
		if($Params->fieldSize!=0 && $Params->fieldSize!=1)				{ $errors[] = _('Boolean values can only be 0 or 1'); }
		if($Params->fieldDefault!=0 && $Params->fieldDefault!=1)			{ $errors[] = _('Default boolean values can only be 0 or 1'); }
	}
	//varchar
	elseif($Params->fieldType=="varchar") {
		if(!is_numeric($Params->fieldSize))								{ $errors[] = _('Fieldsize must be numeric'); }
		if($Params->fieldSize>256)											{ $errors[] = _('Varchar size limit is 256 characters'); }
	}
	//number
	elseif($Params->fieldType=="int") {
		if(!is_numeric($Params->fieldSize))								{ $errors[] = _('Integer values must be numeric'); }

	}
}

/* die if errors otherwise execute */
if(!empty($errors)) {
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