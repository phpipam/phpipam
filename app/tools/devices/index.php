<?php

/**
 * Script to display devices
 *
 */

# verify that user is logged in
$User->check_user_session();

# print hosts or all devices
if(isset($_GET['subnetId'])) {
	include('devices-hosts.php');

} else {
	print "<div class='devicePrintHolder'>";
	include('devices-print.php');
	print "</div>";
}

?>