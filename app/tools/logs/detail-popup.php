<?php

/*
 * Print log details popup
 *************************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();


# fetch log
$log = $Admin->fetch_object("logs", "id", $_POST['id']);
if($log==false)	{ $Result->show("danger", _("Invalid ID"), true, true); }
else			{ $log = (array) $log; }

if ($log['severity'] == 0) {
	$log['severityText'] = _("Informational");
	$color = "success";
}
else if ($log['severity'] == 1) {
	$log['severityText'] = _("Notice");
	$color = "warning";
}
else {
	$log['severityText'] = _("Warning");
	$color = "error";
}

# get user details
$user = $Admin->fetch_object("users", "username", $log['username']);
$userprint = $user===false ? "" : $user->real_name."(".$user->username.")";

# details format
$log['details'] = str_replace("\n", "<br>", $log['details']);
?>


<!-- header -->
<div class="pHeader"><?php print _('Log details'); ?></div>

<!-- content -->
<div class="pContent">

	<table class="table table-striped table-condensed">
		<tr>
			<th><?php print _('Log ID'); ?></th>
			<td><?php print $log['id']; ?></td>
		</tr>
		<tr>
			<th><?php print _('Event'); ?></th>
			<td><?php print $log['command']; ?></td>
		</tr>
		<tr class="<?php print $color; ?>">
			<td><strong><?php print _('Severity'); ?></strong></td>
			<td><?php print $log['severityText'] .' ('. $log['severity'] .")"; ?></td>
		</tr>
		<tr>
			<th><?php print _('Date'); ?></th>
			<td><?php print $log['date']; ?></td>
		</tr>
		<tr>
			<th><?php print _('User details'); ?></th>
			<td><?php print $userprint; ?></td>
		</tr>
		<tr>
			<th><?php print _('IP address'); ?></th>
			<td><?php print $log['ipaddr']; ?></td>
		</tr>
		<tr>
			<th><?php print _('Details'); ?></th>
			<td><?php print $log['details']; ?></td>
		</tr>
	</table>

</div>

<!-- footer -->
<div class="pFooter">
	<button class="btn btn-sm btn-default hidePopups"><?php print _('Close window'); ?></button>
</div>
