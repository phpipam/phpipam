<?php

/**
 * This script will remove offline addresses after they have been down for
 * predefined number of hours.
 *
 * Subnets with "Ping check" enabled will be used for checking offline addresses.
 *
 */

# script can only be run from cli
if(php_sapi_name()!="cli") 						{ die("This script can only be run from cli!"); }

# include required scripts
require_once( dirname(__FILE__) . '/../functions.php' );

# initialize objects
$Database 	= new Database_PDO;
$Admin		= new Admin ($Database, false);
$Result		= new Result();

# truncate logs table
if(!$Admin->truncate_table("logs")) 		{ $Result->show("danger",  _('Error clearing logs')."!", true); }
else 										{ $Result->show("success", _('Logs cleared successfully')."!", false); }

# truncate logs table
if(!$Admin->truncate_table("changelog")) 	{ $Result->show("danger",  _('Error clearing changelogs')."!", true); }
else 										{ $Result->show("success", _('Changelogs cleared successfully')."!", false); }