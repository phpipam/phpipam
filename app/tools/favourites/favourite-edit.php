<?php

/* Edit favourite subnets */

# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects
$Database 	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);

# verify that user is logged in
$User->check_user_session();

# checks
is_numeric($_POST['subnetId']) ? : $Result->show("danger", _('Invalid ID'),false, true);

# execute action
if(!$User->edit_favourite($_POST['action'], $_POST['subnetId'])) 	{ $Result->show("danger", _('Error editing favourite'),false, true); }
else 																{ print "success"; }
?>