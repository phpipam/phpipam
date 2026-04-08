<?php

/**
 * Script to edit / add / delete users
 *************************************************/

# verify that user is logged in
$User->check_user_session();

// switch user
if($GET->subnetId=="switch"){
	$_SESSION['realipamusername'] = $_SESSION['ipamusername'];
	$_SESSION['ipamusername'] = $GET->sPage;
	print '<script>window.location.href = "'.create_link(null).'";</script>';
}

# print all or specific user?
if(isset($GET->subnetId))	{ include("print-user/index.php"); }
else							{ include("print-all.php"); }