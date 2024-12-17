<?php


/**
 *
 * This script will sync phpipam groups with AD groups
 *
 *
 *
 *
 */

// functions
require_once( dirname(__FILE__) . '/../functions.php' );

// AD sync
$Database = new Database_PDO;

$AD_sync  = new AD_user_sync ($Database);
$AD_sync->set_debug (true);

