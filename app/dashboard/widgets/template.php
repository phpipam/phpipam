<?php

/*

This is a template for creating new widgets

*/

# required functions
if(!is_object(@$User)) {
	require( dirname(__FILE__) . '/../../../functions/functions.php' );
	# classes
	$Database	= new Database_PDO;
	$User 		= new User ($Database);
	$Tools 		= new Tools ($Database);
}

# user must be authenticated
$User->check_user_session ();

# if direct request that redirect to tools page
if($_SERVER['HTTP_X_REQUESTED_WITH']!="XMLHttpRequest")	{
	header("Location: ".create_link("administration","logs"));
}

/* You can check who requested this, to adjust parameters  */
if($_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest")	{ $dashboard = true; }
else													{ $dashboard = false; }

?>

<!-- CSS -->
<style type="text/css">
/* You can write your CSS here */
</style>

<!-- JS -->
<script type="text/javascript">
$(document).ready(function() {
	//if you need some JS write it here, jQuery is already included
	return false;
});
</script>