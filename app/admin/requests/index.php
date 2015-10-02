<?php

/**
 * Script to get all active IP requests
 ****************************************/

# verify that user is logged in
$User->check_user_session();

# fetch all Active requests
$active_requests   = $Admin->fetch_multiple_objects ("requests", "processed", 0, "id", false);
$inactive_requests = $Admin->fetch_multiple_objects ("requests", "processed", 1, "id", false);
?>

<h4><?php print _('List of all active IP addresses requests'); ?></h4>
<hr><br>

<?php
# none
if($active_requests===false) { print "<div class='alert alert-info'>"._('No IP address requests available')."!</div>"; }
else {
?>
<table id="requestedIPaddresses" class="table table-striped table-condensed table-hover table-top table-auto1">

<!-- headers -->
<tr>
	<th style="width:50px;"></th>
	<th><?php print _('IP'); ?></th>
	<th><?php print _('Subnet'); ?></th>
	<th><?php print _('Hostname'); ?></th>
	<th><?php print _('Description'); ?></th>
	<th><?php print _('Requested by'); ?></th>
	<th><?php print _('Comment'); ?></th>
</tr>

<?php
	# print requests
	foreach($active_requests as $k=>$request) {
		//cast
		$request = (array) $request;

		//get subnet details
		$subnet = (array) $Subnets->fetch_subnet (null, $request['subnetId']);

		//valid
		if(sizeof($subnet)==0 || @$subnet[0]===false) {
			unset($active_requests[$k]);
		}
		else {
			// ip not provided
			$request['ip_addr'] = strlen($request['ip_addr'])>0 ? $request['ip_addr'] : _("Automatic");

			print '<tr>'. "\n";
			print "	<td><button class='btn btn-sm btn-default' data-requestid='$request[id]'><i class='fa fa-pencil'></i> "._('Process')."</button></td>";
			print '	<td>'. $request['ip_addr'] .'</td>'. "\n";
			print '	<td>'. $Subnets->transform_to_dotted($subnet['subnet']) .'/'. $subnet['mask'] .' ('. $subnet['description'] .')</td>'. "\n";
			print '	<td>'. $request['dns_name'] .'</td>'. "\n";
			print '	<td>'. $request['description'] .'</td>'. "\n";
			print '	<td>'. $request['requester'] .'</td>'. "\n";
			print '	<td>'. $request['comment'] .'</td>'. "\n";
			print '</tr>'. "\n";
		}
	}
?>

</table>
<?php
}
# print resolved if present
if($inactive_requests!==false) { ?>

<h4 style="margin-top:50px;"><?php print _('List of all processes IP addresses requests'); ?></h4>
<hr><br>

<table id="requestedIPaddresses" class="table table-striped table-condensed table-hover table-top table-auto1">

<!-- headers -->
<tr>
	<th><?php print _('Subnet'); ?></th>
	<th><?php print _('Hostname'); ?></th>
	<th><?php print _('Description'); ?></th>
	<th><?php print _('Requested by'); ?></th>
	<th><?php print _('Comment'); ?></th>
	<th><?php print _('Admin comment'); ?></th>
	<th><?php print _('Accepted'); ?></th>
</tr>

<?php
	# print requests
	foreach($inactive_requests as $k=>$request) {
		//cast
		$request = (array) $request;

		//get subnet details
		$subnet = (array) $Subnets->fetch_subnet (null, $request['subnetId']);

		//valid
		if(sizeof($subnet)==0 || @$subnet[0]===false) {
			unset($inactive_requests[$k]);
		}
		else {
			print '<tr>'. "\n";
			print '	<td>'. $Subnets->transform_to_dotted($subnet['subnet']) .'/'. $subnet['mask'] .' ('. $subnet['description'] .')</td>'. "\n";
			print '	<td>'. $request['dns_name'] .'</td>'. "\n";
			print '	<td>'. $request['description'] .'</td>'. "\n";
			print '	<td>'. $request['requester'] .'</td>'. "\n";
			print '	<td>'. $request['comment'] .'</td>'. "\n";
			print '	<td>'. $request['adminComment'] .'</td>'. "\n";
			print '	<td>';
			print $request['accepted']==1 ? "Yes" : "No";
			print '</td>'. "\n";
			print '</tr>'. "\n";
		}
	}
?>

</table>

<?php } ?>