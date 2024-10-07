<?php

/*
 *	Script to install database
 **************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$Install 	= new Install ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# make sure it is properly requested
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest")						{ $Result->show("danger", _("Invalid request"), true); }

# if already installed ignore!
if($Install->check_table ("widgets", false) && $POST->dropdb!="on") {
	# check for possible errors
	if(sizeof($errors = $Tools->verify_database())>0) 						{ }
	else 																	{ $Result->show("danger", _("Database already installed"), true);}
}

# get possible advanced options
$dropdb 		= $POST->dropdb=="on" ? true : false;
$createdb 		= $POST->createdb=="on" ? true : false;
$creategrants 	= $POST->creategrants=="on" ? true : false;

# migration flag - select different sql file
$migrate = $POST->install_type==="migrate" ? true : false;

# try to install new database */
if($Install->install_database ($POST->mysqlrootuser, $POST->mysqlrootpass, $dropdb, $createdb, $creategrants, $migrate)) {
	if($migrate) {
	 	$Result->show("success alert-block", _("Database installed successfully!").' <a href="'.create_link().'" class="btn btn-sm btn-default">'._("Continue").'</a>', true);
	}
	else {
	 	$Result->show("success alert-block", _("Database installed successfully!").' <a href="index.php?page=install&section=install_automatic&subnetId=configure" class="btn btn-sm btn-default">'._("Continue").'</a>', true);
	 }
}
