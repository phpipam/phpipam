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
<table class="table table-condensed table-hover table-noborder">

	<!-- sections -->
	<tr>
		<td class="title"><?php print _('Number of Sections'); ?></td>
		<td class='stats-badge'><span class='badge badge1 badge5'><?php print $Database->numObjects ("sections"); ?></span></td>
	</tr>

	<!-- subnets -->
	<tr>
		<td class="title"><?php print _('Number of Subnets'); ?></td>
		<td class='stats-badge'><span class='badge badge1 badge5'><?php print $Database->numObjects ("subnets");; ?></span></td>
	</tr>

	<!-- VLAN -->
	<tr>
		<td class="title"><?php print _('Number of VLANs'); ?></td>
		<td class='stats-badge'><span class='badge badge1 badge5'><?php print $Database->numObjects ("vlans");; ?></span></td>
	</tr>

	<!-- VRF -->
	<tr>
		<td class="title"><?php print _('Number of VRFs'); ?></td>
		<td class='stats-badge'><span class='badge badge1 badge5'><?php print $Database->numObjects ("vrf");; ?></span></td>
	</tr>
	<!-- IPv4 addresses -->
	<tr>
		<td class="title"><?php print _('Number of IPv4 addresses'); ?></td>
		<td class='stats-badge'><span class='badge badge1 badge5'><?php print $Tools->count_subnets ("IPv4"); ?></span></td>
	</tr>

	<!-- IPv6 addresses -->
	<tr>
		<td class="title"><?php print _('Number of IPv6 addresses'); ?></td>
		<td class='stats-badge'><span class='badge badge1 badge5'><?php print $Tools->count_subnets ("IPv6"); ?></span></td>
	</tr>

	<!-- All users - only for admin! -->
	<tr>
		<td class="title"><?php print _('Number of users'); ?></td>
		<td class='stats-badge'><span class='badge badge1 badge5'><?php print $Database->numObjects ("users"); ?></span></td>
	</tr>

</table>