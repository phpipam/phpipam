<?php

/**
 *	Script that checks if IP is alive
 */

# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects
$Database 	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);
$Subnets	= new Subnets ($Database);
$Tools	    = new Tools ($Database);
$Addresses	= new Addresses ($Database);
$Ping		= new Scan ($Database);

# verify that user is logged in
$User->check_user_session();

# validate post
is_numeric($_POST['subnetId']) ?:							$Result->show("danger", _("Invalid ID"), true, true, false, true);
if(is_numeric($_POST['id'])) {
	strlen($_POST['id'])!=0 ?:								$Result->show("danger", _("Invalid ID"), true, true, false, true);
	# fetch address
	$address = (array) $Addresses->fetch_address(null, $_POST['id']);
}
// from adding new IP, validate
else {
	$validate = $Subnets->identify_address ($_POST['id'])=="IPv4" ? filter_var($_POST['id'], FILTER_VALIDATE_IP) : filter_var($_POST['id'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
	if ($validate===false)									{ $Result->show("danger", _("Invalid IP address"), true, true, false, true); }
	else {
		$address['ip'] = $_POST['id'];
	}
}
# set and check permissions
$subnet_permission = $Subnets->check_permission($User->user, $_POST['subnetId']);
$subnet_permission > 1 ?:								$Result->show("danger", _('Cannot edit IP address details').'! <br>'._('You do not have write access for this network'), true, true);

# try to ping it
$pingRes = $Ping->ping_address($address['ip']);

# update last seen if success
if($pingRes==0 && is_numeric($_POST['id'])) { @$Ping->ping_update_lastseen($address['id']); }
?>

<!-- header -->
<div class="pHeader"><?php print _('Ping check result'); ?></div>

<!-- content -->
<div class="pContent">
	<?php
	# online
	if($pingRes==0) 					{ $Result->show("success", _("IP address")." $address[ip] "._("is alive"), false);	}
	# offline
	elseif ($pingRes==1 || $pingRes==2) { $Result->show("danger",  _("IP address")." $address[ip] "._("is not alive"), false); }
	# error
	else {
		# fetch error code
		$ecode = $Ping->ping_exit_explain($pingRes);
										{ $Result->show("danger",  _("Error").": $ecode ($pingRes)", false); }
	}

	# hr
	print "<hr>";
										{ $Result->show("muted pull-right", "(".$Ping->settings->scanPingType.")", false); }
	# additional notes
	if(isset($Ping->rtt))				{ $Result->show("muted pull-right", $Ping->rtt." ms", false); }
	?>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<a class='ping_ipaddress btn btn-sm btn-default' data-subnetId='<?php print $_POST['subnetId']; ?>' data-id='<?php print $_POST['id']; ?>' href='#'><i class='fa fa-gray fa-cogs'></i> <?php print _('Repeat'); ?></a>
		<button class="btn btn-sm btn-default hidePopup2"><?php print _('Close window'); ?></button>
	</div>
</div>