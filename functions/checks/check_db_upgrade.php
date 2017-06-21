<?php

/**
 * Check if database needs upgrade to newer version
 ****************************************************/

# use required functions

/* redirect */
if($User->settings->version < VERSION) {
	header("Location: ".create_link("upgrade"));
	die();
}
?>