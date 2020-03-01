<?php

if (!function_exists("create_link")) {
	# If create_link() does not exist we have been invoked directly via CLI/HTTP
	require_once( dirname(__FILE__) . '/functions.php' );
	$Result = new Result ();

	$Result->show("danger", _("Invalid request"), true);
}