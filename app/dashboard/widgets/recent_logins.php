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
$widget = $Tools->fetch_object ("widgets", "wfile", "recent_logins");
# set max and then overwrite max from wparams
$max = 5;
if(isset($widget->wparams)) {
	parse_str($widget->wparams, $p);
	if (@is_numeric($p['max'])) {
		$max = intval($p['max']);
	}
	if (@is_numeric($p['height'])) {
		$height = intval($p['height']);
	}
	unset($p);
}

# fetch all requests
$requests = $Tools->requests_fetch (false);

$query = "select `username`,`lastLogin`,`lastActivity` from `users` order by `lastLogin` DESC limit $max;";
$recent_logins = $Database->getObjectsQuery($query);

?>

<div style="width:98%;margin-left:1%;<?php print (isset($height)) ? "height:{$height}px;overflow:scroll;" : ""; ?>">
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
