<?php

/**
 * Based on GET parameter we load:
 * 	- all customers
 *  - specific customer
 *
 */

# verify that user is logged in
$User->check_user_session();

# perm check
if ($User->get_module_permissions ("customers")<1) {
	$Result->show("danger", _("You do not have permissions to access this module"), false);
}
# load subpage
elseif (!isset($_GET['subnetId'])) {
	include('all-customers.php');
}
else {
	include("customer/index.php");
}