<?php

/**
 *	Generate SQL file
 *********************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# Don't corrupt output with php errors!
disable_php_errors();

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin		= new Admin ($Database);

# verify that user is logged in
$User->check_user_session();

$mysqldump = Config::ValueOf('mysqldump_cli_binary', '/usr/bin/mysqldump');

if ( !file_exists($mysqldump) ) {
    $filename = "error_message.txt";

    $content  = _("Unable to locate executable: ").$mysqldump."\n";
    $content .= _("Please configure \$mysqldump_cli_binary in config.php\n");
} else {
    $filename = "phpipam_MySQL_dump_". date("Y-m-d") .".sql";

    $db = Config::ValueOf('db');

    $command      = "$mysqldump --opt -h '". $db['host'] ."' -u '". $db['user'] ."' -p'". $db['pass'] ."' '". $db['name'] ."'";
    $command_safe = "$mysqldump --opt -h '". $db['host'] ."' -u '". "<REDACTED>" ."' -p'". "<REDACTED>" ."' '". $db['name'] ."'";

    $content  = "# phpipam Database dump \n";
    $content .= "#    command executed: $command_safe \n";
    $content .= "# --------------------- \n\n";
    $content .= shell_exec($command);
}

header("Cache-Control: private");
header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");
header('Content-Disposition: attachment; filename="'. $filename .'"');
header("Content-Length: " . strlen($content));

print($content);
