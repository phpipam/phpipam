<?php

/*
 *	Script to install database
 **************************************/

/* functions */
require( dirname(__FILE__) . '/../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$Install 	= new Install ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# make sure it is properly requested
if($_SERVER['HTTP_X_REQUESTED_WITH']!="XMLHttpRequest")						{ $Result->show("danger", _("Invalid request"), true); }

# if already installed ignore!
if($Install->check_table ("widgets", false) && @$_POST['dropdb']!="on") {
	# check for possible errors
	if(sizeof($errors = $Tools->verify_database())>0) 						{ }
	else 																	{ $Result->show("danger", _("Database already installed"), true);}
}

# get possible advanced options
$dropdb 		= @$_POST['dropdb']=="on" ? true : false;
$createdb 		= @$_POST['createdb']=="on" ? true : false;
$creategrants 	= @$_POST['creategrants']=="on" ? true : false;

# try to install new database */
if($Install->install_database ($_POST['mysqlrootuser'], $_POST['mysqlrootpass'], $dropdb, $createdb, $creategrants)) {
 	$Result->show("success alert-block", 'Database installed successfully! <a href="?page=install&section=install_automatic&subnetId=configure" class="btn btn-sm btn-default">Continue</a>', true);
}
?>