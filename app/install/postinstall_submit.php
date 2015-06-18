<?php

/**
 *	Post-installation submit
 */

# functions
require( dirname(__FILE__) . '/../../functions/functions.php' );

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
	# check lenghts
	if(strlen($_POST['password1'])<8)				{ $Result->show("danger", _("Invalid password"), true); }
	if(strlen($_POST['password2'])<8)				{ $Result->show("danger", _("Invalid password"), true); }

	# check password match
	if($_POST['password1']!=$_POST['password2'])	{ $Result->show("danger", _("Passwords do not match"), true); }

	# Crypt password
	$_POST['password1'] = $User->crypt_user_pass ($_POST['password1']);

	# all good, update password!
	$Install->postauth_update($_POST['password1'], $_POST['siteTitle'], $_POST['siteURL']);
	# ok
													{ $Result->show("success", "Settings updated, installation complete!<hr><a class='btn btn-sm btn-default' href='".create_link("login")."'>Proceed to login</a>", false); }
}
?>