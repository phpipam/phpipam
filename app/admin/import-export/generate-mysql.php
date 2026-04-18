<?php

/**
 *	Generate SQL file
 *********************************/

/* functions */
require_once(dirname(__FILE__) . '/../../../functions/functions.php');

# Don't corrupt output with php errors!
disable_php_errors();

# initialize user object
$Database     = new Database_PDO;
$User         = new User($Database);
$Admin        = new Admin($Database);

# verify that user is logged in
$User->check_user_session();

$mysqldump = Config::ValueOf('mysqldump_cli_binary', '/usr/bin/mysqldump');

if (!file_exists($mysqldump)) {
    $filename = "error_message.txt";

    $content  = _("Unable to locate executable: ") . $mysqldump . "\n";
    $content .= _("Please configure \$mysqldump_cli_binary in config.php\n");
} else {
    $filename = "phpipam_MySQL_dump_" . date("Y-m-d") . ".sql";

    $db = Config::ValueOf('db');

    $command      = sprintf("%s --opt -h %s -u %s -p %s", escapeshellcmd($mysqldump), escapeshellarg($db['host']), escapeshellarg($db['user']), escapeshellarg($db['name']));
    $command_safe = sprintf("%s --opt -h %s -u %s -p %s", escapeshellcmd($mysqldump), escapeshellarg($db['host']), _("'<REDACTED>'"), escapeshellarg($db['name']));

    $pipes = [];

    $descriptorspec = [
        0 => ["pipe", "r"], // STDIN
        1 => ["pipe", "w"], // STDOUT
        2 => ["pipe", "w"]  // STDERR
    ];

    $content  = "# phpipam Database dump \n";
    $content .= "#    command executed: $command_safe \n";
    $content .= "# --------------------- \n\n";

    $process = proc_open($command, $descriptorspec, $pipes);
    if (is_resource($process)) {
        // Write password to STDIN
        fwrite($pipes[0], $db['pass']);
        fclose($pipes[0]);

        // Read STDOUT
        $content .= stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        // Read STDERR
        $content .= stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $return_value = proc_close($process);
    }
}

header("Cache-Control: private");
header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");
header('Content-Disposition: attachment; filename="' . $filename . '"');
header("Content-Length: " . strlen($content));

print($content);
