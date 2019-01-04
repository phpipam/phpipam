<?php
/*
 * Prints edit instructions
 **********************************************/

# required functions
if(!is_object(@$User)) {
	require( dirname(__FILE__) . '/../../../functions/functions.php' );
	# classes
	$Database	= new Database_PDO;
	$User 		= new User ($Database);
	$Tools 		= new Tools ($Database);
	$Subnets 	= new Subnets ($Database);
	$Addresses 	= new Addresses ($Database);
	$Result		= new Result ();
}
else {
    header("Location: ".create_link('tools', 'instructions'));
}

# user must be authenticated
$User->check_user_session ();

# no errors!
//ini_set('display_errors', 0);

# set size parameters
$height = 200;
$slimit = 5;			//we dont need this, we will recalculate

# count
$m = 0;

// fetch widget
$widget = $Tools->fetch_object ("widgets", "wfile", "instructions");

# if direct request include plot JS
if($_SERVER['HTTP_X_REQUESTED_WITH']!="XMLHttpRequest")	{
	# get widget details
	if(!$widget = $Tools->fetch_object ("widgets", "wfile", $_REQUEST['section'])) { $Result->show("danger", _("Invalid widget"), true); }
	# reset size and limit
	$height = 350;
	$slimit = 100;
	# and print title
	print "<div class='container'>";
	print "<h4 style='margin-top:40px;'>$widget->wtitle</h4><hr>";
	print "</div>";
}

/* fetch instructions and print them in instructions div */
$instructions = $Tools->fetch_object("instructions", "id", 1);
$instructions = $instructions->instructions;

/* format line breaks */
$instructions = stripslashes($instructions);		//show html

/* prevent <script> */
$instructions = str_replace("<script", "<div class='error'><xmp><script", $instructions);
$instructions = str_replace("</script>", "</script></xmp></div>", $instructions);

// HSS header
header('X-XSS-Protection:1; mode=block');
?>
<div class="well">
<?php print $instructions; ?>
</div>
