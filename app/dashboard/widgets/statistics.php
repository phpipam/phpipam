<?php

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
?>


<!-- stats table -->
<table class="table table-condensed table-hover">

	<!-- sections -->
	<tr>
		<td class="title"><?php print _('Number of Sections'); ?></td>
		<td><?php print $Database->numObjects ("sections"); ?></td>
	</tr>

	<!-- subnets -->
	<tr>
		<td class="title"><?php print _('Number of Subnets'); ?></td>
		<td><?php print $Database->numObjects ("subnets");; ?></td>
	</tr>

	<!-- VLAN -->
	<tr>
		<td class="title"><?php print _('Number of VLANs'); ?></td>
		<td><?php print $Database->numObjects ("vlans");; ?></td>
	</tr>

	<!-- IPv4 addresses -->
	<tr>
		<td class="title"><?php print _('Number of IPv4 addresses'); ?></td>
		<td><?php print $Tools->count_subnets ("IPv4"); ?></td>
	</tr>

	<!-- IPv6 addresses -->
	<tr>
		<td class="title"><?php print _('Number of IPv6 addresses'); ?></td>
		<td><?php print $Tools->count_subnets ("IPv6"); ?></td>
	</tr>

	<!-- All users - only for admin! -->
	<tr>
		<td class="title"><?php print _('Number of users'); ?></td>
		<td><?php print $Database->numObjects ("users");; ?></td>
	</tr>

</table>