<?php

/**
 *	Post-installation submit
 */

# functions
require_once( dirname(__FILE__) . '/../../functions/functions.php' );

if (!defined('VERSION_VISIBLE') || Config::ValueOf('disable_installer')) { print _("Install scripts disabled"); exit(0); }

# objects
$Database 	= new Database_PDO;
$Admin 		= new Admin ($Database, false);
$Install 	= new Install ($Database);
$User		= new User ($Database);
$Result		= new Result;

# only permit if Admin user has default pass !!!
$admin = $Admin->fetch_object ("users","username","Admin");
if($admin->password!='$6$rounds=3000$JQEE6dL9NpvjeFs4$RK5X3oa28.Uzt/h5VAfdrsvlVe.7HgQUYKMXTJUsud8dmWfPzZQPbRbk8xJn1Kyyt4.dWm4nJIYhAV2mbOZ3g.') {
	$Result->show("danger", "Not allowed!", true);
}
# update
else {
	# check lengths
	if(strlen($POST->password1)<8)				{ $Result->show("danger", _("Password must be at least 8 characters long!"), true); }
	if(strlen($POST->password2)<8)				{ $Result->show("danger", _("Password must be at least 8 characters long!"), true); }

	# check password match
	if($POST->password1!=$POST->password2)	{ $Result->show("danger", _("Passwords do not match"), true); }

	# Crypt password
	$POST->password1 = $User->crypt_user_pass ($POST->password1);

	# all good, update password!
	$Install->postauth_update($POST->password1, $POST->siteTitle, $POST->siteURL);
	# ok
	$Result->show("success", _("Settings updated, installation complete!") . "<hr><a class='btn btn-sm btn-default' href='" . create_link("login") . "'>" . _("Proceed to login.") . "</a>", false);
}
