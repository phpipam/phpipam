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
$Tools		= new Tools ($Database);

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "custom_field", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";


/* checks */
if($POST->action == "delete") {
	# no checks
}
else {
	# remove spaces
	$POST->name = trim($POST->name);

	if($POST->action == "add") {
		# check if name is taken
		$custom_fields = $Tools->fetch_custom_fields($POST->table);
		$custom_field_names = array_map('strtolower', array_keys($custom_fields));
		if (in_array("custom_" . strtolower($POST->name),$custom_field_names))	$errors[] = _('Field') . " " . $POST->name . " " . _('already exists!');
	}

	# length > 4 and < 12
	if( (mb_strlen($POST->name) < 2) || (mb_strlen($POST->name) > 24) ) 	{ $errors[] = _('Name must be between 4 and 24 characters'); }

	/* validate HTML */

	# must not start with number
	if(is_numeric(substr($POST->name, 0, 1))) 								{ $errors[] = _('Name must not start with number'); }

	# only alphanumeric and _ are allowed
	if(!preg_match('/^(\p{L}|\p{N})[(\p{L}|\p{N}) _.-]+$/u', $POST->name)) 	{ $errors[] = _('Only alphanumeric, spaces and underscore characters are allowed'); }

	# required must have default value
	if($POST->NULL=="NO" && mb_strlen($POST->fieldDefault)==0)			{ $errors[] = _('Required fields must have default values'); }

	# db type validations

	//boolean
	if($POST->fieldType=="bool")	{
		if($POST->fieldSize!="" && $POST->fieldSize!=0 && $POST->fieldSize!=1)				{ $errors[] = _('Boolean values can only be 0 or 1'); }
		if($POST->fieldDefault!="" && $POST->fieldDefault!=0 && $POST->fieldDefault!=1)			{ $errors[] = _('Default boolean values can only be 0 or 1'); }
	}
	//varchar
	elseif($POST->fieldType=="varchar") {
		if(!is_numeric($POST->fieldSize))								{ $errors[] = _('Fieldsize must be numeric'); }
		if($POST->fieldSize>256)											{ $errors[] = _('Varchar size limit is 256 characters'); }
	}
	//number
	elseif($POST->fieldType=="int") {
		if(!is_numeric($POST->fieldSize))								{ $errors[] = _('Fieldsize must be numeric'); }

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
	if(!$Admin->update_custom_field_definition($POST->as_array())) { $Result->show("danger",  _("Failed to " . $User->get_post_action() . " field"), true); }
	else 												{ $Result->show("success", _("Field " . $User->get_post_action() . " success"), true); }
}
