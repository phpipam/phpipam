<?php

/**
 *
 * User selfMod check end execute
 *
 */


# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects
$Database 	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);

# verify that user is logged in
$User->check_user_session();

# verify email
if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))							{ $Result->show("danger alert-absolute",  _('Email not valid!'), true); }

# verify password if changed (not empty)
if (strlen($_POST['password1']) != 0) {
	if ( (strlen($_POST['password1']) < 8) && (!empty($_POST['password1'])) ) 	{ $Result->show("danger alert-absolute", _('Password must be at least 8 characters long!'), true); }
	else if ($_POST['password1'] != $_POST['password2']) 						{ $Result->show("danger alert-absolute", _('Passwords do not match!', true)); }
}

# set override
$_POST['compressOverride'] = @$_POST['compressOverride']=="Uncompress" ? "Uncompress" : "default";

# Update user
if (!$User->self_update ($_POST)) 												{ $Result->show("danger alert-absolute",  _('Error updating user account!'), true); }
else 																			{ $Result->show("success alert-absolute", _('Account updated successfully'), false); }
?>