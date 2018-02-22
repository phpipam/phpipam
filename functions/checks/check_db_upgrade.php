<?php

/**
 * Check if database needs upgrade to newer version
 ****************************************************/

# use required functions

/* redirect */
if($User->settings->version < VERSION) {
	$User->settings->prettyLinks="No";
	header("Location: ".create_link("upgrade"));
	die();
}