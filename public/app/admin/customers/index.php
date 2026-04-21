<?php
if (!isset($User)) { exit(); }

/**
 * Based on GET parameter we load:
 * 	- all customers
 *  - specific customer
 *
 */

# verify that user is logged in
$User->check_user_session();
# Make sure user is admin
$User->is_admin(true);

# load subpage
if (!isset($GET->subnetId)) {
	include(__DIR__.'/../../tools/customers/all-customers.php');
}
else {
	include(__DIR__.'/../../tools/customers/customer/index.php');
}
