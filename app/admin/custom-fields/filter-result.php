<?php

/**
 * set which custom field to display
 ************************/


/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools		= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# set table name
$table = $_POST['table'];
unset($_POST['table']);

# fetch custom fields
$fields = $Tools->fetch_custom_fields($table);

/* enthing to write? */
if(sizeof($_POST)>0) {
	foreach($_POST as $k=>$v) {
		$kTest = str_replace("___", " ", $k);
		$filtered_fields[] = array_key_exists($kTest, $fields) ? $kTest : $k;
	}
}
else {
	$filtered_fields = null;
}

/* save */
if(!$Admin->save_custom_fields_filter($table, $filtered_fields))	{  }
else																{ $Result->show("success", _('Filter saved')); }