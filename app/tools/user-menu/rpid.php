<?php
// include composer
require __DIR__ . '/../../../functions/vendor/autoload.php';

// phpipam stuff
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects - to start session
$Database       = new Database_PDO;
$User           = new User ($Database);


print '"'.preg_replace('|https?://|', '', $User->createURL ()).'"';
?>
