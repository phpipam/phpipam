<?php

/**
 *
 *set users widgets
 *
 */

# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects
$Database 	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);

/* save widgets */
if (!$User->self_update_widgets ($_POST['widgets'])) 	{ $Result->show("danger", _('Error updating'),true); }
else 													{ $Result->show("success", _('Widgets updated'),true); }
?>