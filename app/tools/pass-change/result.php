<?php

# functions
require_once('../../../functions/functions.php');

# Classes
$Database 	= new Database_PDO;
$User 	= new User ($Database);
$Result = new Result;

# user must be authenticated
$User->check_user_session ();

# checks
if(strlen($_POST['ipampassword1'])<8)					{ $Result->show("danger", _("Invalid password"), true); }
if($_POST['ipampassword1']!=$_POST['ipampassword2'])	{ $Result->show("danger", _("Passwords do not match"), true); }

# update pass
$User->update_user_pass($_POST['ipampassword1']);
?>