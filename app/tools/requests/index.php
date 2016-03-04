<?php

/**
 * Script to get all active IP requests
 ****************************************/

# verify that user is logged in
$User->check_user_session();

# fetch all Active requests
$requests   = $Tools->fetch_multiple_objects ("requests", "processed", 0, "id", false);

# validate permissions
if ($requests !== false) {
	foreach ($requests as $k=>$r) {
		// check permissions
		if($Subnets->check_permission($User->user, $r->subnetId) != 3) {
			unset($requests[$k]);
		}
	}
	# null
	if (sizeof($requests)==0) {
		$requests=false;
	}
}
?>

<h4><?php print _('List of unprocessed IP addresses requests'); ?></h4>
<hr><br>

<?php
# none
if($requests===false) { print "<div class='alert alert-info'>"._('No IP address requests available')."!</div>"; }
else {
?>
<table id="requestedIPaddresses" class="table sorted table-striped table-condensed table-hover table-top">

<!-- headers -->
<thead>
<tr>
	<th style="width:50px;"></th>
	<th><?php print _('IP'); ?></th>
	<th><?php print _('Subnet'); ?></th>
	<th><?php print _('Hostname'); ?></th>
	<th><?php print _('Description'); ?></th>
	<th><?php print _('Requested by'); ?></th>
	<th><?php print _('Comment'); ?></th>
</tr>
</thead>

<tbody>
<?php
	# print requests
	foreach($requests as $k=>$request) {
		//cast
		$request = (array) $request;

		//get subnet details
		$subnet = $Subnets->fetch_subnet (null, $request['subnetId']);

		//valid
		if($subnet===false) {
			unset($requests[$k]);
		}
		else {
			$subnet = (array) $subnet;
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
</tbody>
</table>
<?php
}