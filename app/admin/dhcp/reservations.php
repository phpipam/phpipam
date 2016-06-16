<?php


# verify that user is logged in
$User->check_user_session();


# print reservations
include(dirname(__FILE__)."/../../tools/dhcp/reservations.php");
?>