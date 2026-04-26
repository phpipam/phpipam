<?php

/**
 *	Generate SQL file
 *********************************/

/* functions */
require_once(__DIR__ . '/../../../functions/functions.php');

# Don't corrupt output with php errors!
disable_php_errors();

# initialize user object
$Database     = new Database_PDO;
$User         = new User($Database);
$Admin        = new Admin($Database);

# verify that user is logged in
$User->check_user_session();
# admin check
$User->is_admin();

$mysqldump = Config::ValueOf('mysqldump_cli_binary', '/usr/bin/mysqldump');

if (!file_exists($mysqldump)) {
    $filename = "error_message.txt";

    $content  = _("Unable to locate executable: ") . $mysqldump . "\n";
    $content .= _("Please configure \$mysqldump_cli_binary in config.php\n");
} else {
    $tmp_file = tempnam(sys_get_temp_dir(), 'phpipam_mysqldump_');

    if ($tmp_file === false) {
        $filename = "error_message.txt";
        $content  = _("Unable to create tmp file");
    } else {
        $cnf_file = "$tmp_file.cnf";
        rename($tmp_file, $cnf_file);

        $fh = fopen($cnf_file, "w");
        if ($fh !== false) {
            $db = Config::ValueOf('db');
            fputs($fh, sprintf("[mysqldump]\nhost=%s\nuser=%s\npassword=%s\n", $db['host'], $db['user'], $db['pass']));

            $filename = "phpipam_MySQL_dump_" . date("Y-m-d") . ".sql";

            $command = sprintf("%s --defaults-extra-file=$cnf_file --opt %s", escapeshellcmd($mysqldump), escapeshellarg((string) $db['name']));

            $content  = "# phpipam Database dump \n";
            $content .= "#    command executed: $command \n";
            $content .= "# --------------------- \n\n";
            $content .= shell_exec($command);

            fclose($fh);
        } else {
            $filename = "error_message.txt";
            $content  = _("Unable to open tmp file");
        }
        unlink($cnf_file);
    }
}

header("Cache-Control: private");
header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");
header('Content-Disposition: attachment; filename="' . $filename . '"');
header("Content-Length: " . strlen($content));

print($content);
