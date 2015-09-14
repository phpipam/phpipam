<?php

/**
 *	Generate XLS file
 *********************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Sections	= new Sections ($Database);
$Subnets	= new Subnets ($Database);
$Addresses	= new Addresses ($Database);
$Tools		= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();


//set filename
$filename = "phpipam_MySQL_dump_". date("Y-m-d") .".sql";

//set content
/* $command = "mysqldump --opt -h $db['host'] -u $db['user'] -p $db['pass'] $db['name'] | gzip > $backupFile"; */
$command = "mysqldump --opt -h '". $db['host'] ."' -u '". $db['user'] ."' -p'". $db['pass'] ."' '". $db['name'] ."'";

$content  = "# phpipam Database dump \n";
$content .= "#    command executed: $command \n";
$content .= "# --------------------- \n\n";
$content .= shell_exec($command);

/* headers */
header("Cache-Control: private");
header("Content-Description: File Transfer");
header('Content-type: application/octet-stream');
header('Content-Disposition: attachment; filename="'. $filename .'"');

print($content);
?>
