<?php

/**
 * This script will remove offline addresses after they have been down for
 * predefined number of hours.
 *
 * Subnets with "Ping check" enabled will be used for checking offline addresses.
 */

// script can only be run from cli
if (PHP_SAPI != 'cli') {
    exit('This script can only be run from cli!');
}

// include required scripts
require_once __DIR__.'/../functions.php';

// initialize objects
$Database = new Database_PDO;
$Admin = new Admin($Database, false);
$Result = new Result;

// truncate logs table
if (! $Admin->truncate_table('logs')) {
    $Result->die('danger', _('Error clearing logs').'!');
} else {
    $Result->warn('success', _('Logs cleared successfully').'!');
}

// truncate logs table
if (! $Admin->truncate_table('changelog')) {
    $Result->die('danger', _('Error clearing changelogs').'!');
} else {
    $Result->warn('success', _('Changelogs cleared successfully').'!');
}
