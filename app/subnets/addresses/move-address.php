<?php

/**
 * Script to print edit / delete / new IP address
 *
 * Fetches info from database
 *************************************************/


# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects
$Database 	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);
$Subnets	= new Subnets ($Database);
$Tools	    = new Tools ($Database);
$Addresses	= new Addresses ($Database);

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# create csrf token
$csrf = $User->csrf_cookie ("create", "address");

# validate action
$Tools->validate_action ($_POST['action']);

# validate post
is_numeric($_POST['subnetId']) ?:						$Result->show("danger", _("Invalid ID"), true);
is_numeric($_POST['id']) || strlen($_POST['id'])==0 ?:	$Result->show("danger", _("Invalid ID"), true);

# fetch address and subnet
$address = (array) $Addresses->fetch_address(null, $_POST['id']);
$subnet  = (array) $Subnets->fetch_subnet(null, $_POST['subnetId']);

# fetch all slave subnets
$Subnets->fetch_subnet_slaves_recursive ($subnet['id']);
?>

<!-- header -->
<div class="pHeader"><?php print _('Move IP address to different subnet'); ?></div>

<!-- content -->
<div class="pContent editIPAddress">

	<!-- IP address modify form -->
	<form class="editipaddress" name="editipaddress">
	<!-- edit IP address table -->
	<table id="editipaddress" class="table table-noborder table-condensed">

	<!-- IP address -->
	<tr>
		<td><?php print _('IP address'); ?>
		</td>
		<td>
			<strong><?php print $address['ip']; ?></strong>

   			<input type="hidden" name="action" 	 	value="<?php print $_POST['action']; ?>">
			<input type="hidden" name="id" 		 	value="<?php print $address['id']; ?>">
			<input type="hidden" name="subnet"   	value="<?php print $subnet['ip']."/$subnet[mask]"; ?>">
			<input type="hidden" name="subnetId" 	value="<?php print $subnet['id']; ?>">
			<input type="hidden" name="section" 	value="<?php print $subnet['sectionId']; ?>">
			<input type="hidden" name="state" 		value="">
            <input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
    	</td>
	</tr>

	<!-- description -->
	<tr>
		<td><?php print _('Description'); ?></td>
		<td><?php print strlen(@$address['description'])>0 ? $address['description'] : "/"; ?></td>
	</tr>

	<!-- DNS name -->
	<tr>
		<td><?php print _('DNS name'); ?></td>
		<td><?php print strlen(@$address['dns_name'])>0 ? $address['dns_name'] : "/"; ?></td>
	</tr>

	<!-- divider -->
	<tr>
		<td colspan="2"><hr></td>
	</tr>

	<tr>
		<td><?php print _('Select new subnet'); ?>:</td>
		<td>
			<select name="newSubnet" class="ip_addr form-control input-sm input-w-auto">
				<?php
				foreach($Subnets->slaves as $slave) {
					$slave_subnet = (array) $Subnets->fetch_subnet(null, $slave);
					print "<option value='$slave_subnet[id]'>$slave_subnet[description] (".$Subnets->transform_address($slave_subnet['subnet'], "dotted")."/$slave_subnet[mask])</option>";
				}
				?>
			</select>
		</td>

	</tr>

</table>	<!-- end edit ip address table -->
</form>		<!-- end IP address edit form -->

</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default" id="editIPAddressSubmit"><?php print _('Move IP address'); ?></button>
	</div>
	<!-- holder for result -->
	<div class="addnew_check"></div>
</div>