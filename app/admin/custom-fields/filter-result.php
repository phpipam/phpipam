<?php

/**
 * set which custom field to display
 ************************/


/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# set table name
$table = $_POST['table'];
unset($_POST['table']);

/* enthing to write? */
if(sizeof($_POST)>0) {
	foreach($_POST as $k=>$v) {
		$filtered_fields[] = $k;
	}
}
else {
	$filtered_fields = null;
}

/* save */
if(!$Admin->save_custom_fields_filter($table, $filtered_fields))	{  }
else																{ $Result->show("success", _('Filter saved')); }
?>