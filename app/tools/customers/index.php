<?php

/**
 * Based on GET parameter we load:
 * 	- all customers
 *  - specific customer
 *
 */

# verify that user is logged in
$User->check_user_session();
# verify module permissions
$User->check_module_permissions ("customers", 1, true);

# load subpage
if (!isset($_GET['subnetId'])) {
	include('all-customers.php');
}
else {
	include("customer/index.php");
}