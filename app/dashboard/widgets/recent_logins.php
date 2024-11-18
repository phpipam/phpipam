<?php

# required functions
if(!isset($User)) {
	require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
	# classes
	$Database	= new Database_PDO;
	$User		= new User ($Database);
	$Tools		= new Tools ($Database);
	$Subnets	= new Subnets ($Database);
}

# user must be authenticated
$User->check_user_session ();
# user must be admin
$User->is_admin(true);

# if direct request that redirect to tools page
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest")	{
	header("Location: ".create_link("administration","requests"));
}

# fetch widget parameters
$wparam = $Tools->get_widget_params("recent_logins");
$max    = filter_var($wparam->max,    FILTER_VALIDATE_INT, ['options' => ['default' => 5,    'min_range' => 1, 'max_range' => 256]]);
$height = filter_var($wparam->height, FILTER_VALIDATE_INT, ['options' => ['default' => null, 'min_range' => 1, 'max_range' => 800]]);

# fetch all requests
$requests = $Tools->requests_fetch (false);

$query = "select `username`,`lastLogin`,`lastActivity` from `users` order by `lastLogin` DESC limit $max;";
$recent_logins = $Database->getObjectsQuery('users', $query);

?>

<div style="width:98%;margin-left:1%;<?php print (isset($height) ? "height:{$height}px;overflow-y:auto;" : ""); ?>">
<table id="recentlogins" class="table table-condensed table-hover table-top">

<!-- headers -->
<tr>
	<th><?php print _('Username'); ?></th>
	<th><?php print _('Last Login'); ?></th>
	<th><?php print _('Last Activity'); ?></th>
</tr>

<?php
# print recent
foreach($recent_logins as $login) {
	# cast
	$login = (array) $login;
#	# get subnet details
#	$subnet = $Subnets->fetch_subnet ("id", $request['subnetId']);

	print '<tr>'. "\n";
	print '	<td>'. $login['username'] .'</td>'. "\n";
	print '	<td>'. $login['lastLogin'] .'</td>'. "\n";
	print '	<td>'. $login['lastActivity'] .'</td>'. "\n";
	print '</tr>'. "\n";
}

print "</table>";

if($recent_logins===false) {
	print "<blockquote style='margin-top:20px;margin-left:20px;'>";
	print "<small>"._("No recent logins available")."!</small><br>";
	print "</blockquote>";
}
print "</div>";
?>
