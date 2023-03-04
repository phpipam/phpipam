<?php

# required functions
if(!isset($User)) {
	require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
	# classes
	$Database	= new Database_PDO;
	$User 		= new User ($Database);
	$Tools 		= new Tools ($Database);
	$Subnets 	= new Subnets ($Database);
}

# user must be authenticated
$User->check_user_session ();

# if direct request that redirect to tools page
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest")	{
	header("Location: ".create_link("administration","requests"));
}

# fetch all requests
$requests = $Tools->requests_fetch (false);
?>



<?php
if($requests===false) {
	print "<blockquote style='margin-top:20px;margin-left:20px;'>";
	print "<small>"._("No IP address requests available")."!</small><br>";
	print "</blockquote>";
}
# print
else {
?>

<table id="requestedIPaddresses" class="table table-condensed table-hover table-top">

<!-- headers -->
<tr>
	<th></th>
	<th><?php print _('Subnet'); ?></th>
	<th><?php print _('Hostname'); ?></th>
	<th><?php print _('Description'); ?></th>
	<th><?php print _('Requested by'); ?></th>
</tr>

<?php
	# print requests
	foreach($requests as $request) {
		# cast
		$request = (array) $request;
		# get subnet details
		$subnet = $Subnets->fetch_subnet ("id", $request['subnetId']);

		print '<tr>'. "\n";
		print "	<td><button class='btn btn-xs btn-default open_popup' data-script='app/admin/requests/edit.php' data-class='700' data-action='edit' data-requestid='$request[id]'><i class='fa fa-pencil'></i></button></td>";
		print '	<td>'. $subnet->ip .'/'. $subnet->mask .' ('. $subnet->description .')</td>'. "\n";
		print '	<td>'. $request['hostname'] .'</td>'. "\n";
		print '	<td>'. $request['description'] .'</td>'. "\n";
		print '	<td>'. $request['requester'] .'</td>'. "\n";
		print '</tr>'. "\n";
	}
?>

</table>

<?php } ?>