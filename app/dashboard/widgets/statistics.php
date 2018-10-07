<?php

# required functions
if(!is_object(@$User)) {
	require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
	# classes
	$Database	= new Database_PDO;
	$User 		= new User ($Database);
	$Tools 		= new Tools ($Database);
}

# user must be authenticated
$User->check_user_session ();
?>


<!-- stats table -->
<div class="container-fluid" style='padding-top:5px'>
<table class="table table-condensed table-hover statistics">

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

	<!-- Devices -->
	<tr>
		<td class="title"><?php print _('Number of Devices'); ?></td>
		<td class='stats-badge'><span class='badge badge1 badge5'><?php print $Database->numObjects ("devices"); ?></span></td>
	</tr>

	<!-- Locations -->
	<tr>
		<td class="title"><?php print _('Number of Locations'); ?></td>
		<td class='stats-badge'><span class='badge badge1 badge5'><?php print $Database->numObjects ("locations"); ?></span></td>
	</tr>

	<!-- Racks -->
	<tr>
		<td class="title"><?php print _('Number of Racks'); ?></td>
		<td class='stats-badge'><span class='badge badge1 badge5'><?php print $Database->numObjects ("racks"); ?></span></td>
	</tr>

	<!-- All users - only for admin! -->
	<tr>
		<td class="title"><?php print _('Number of users'); ?></td>
		<td class='stats-badge'><span class='badge badge1 badge5'><?php print $Database->numObjects ("users"); ?></span></td>
	</tr>

</table>
</div>