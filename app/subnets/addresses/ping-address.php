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
$Ping		= new Scan ($Database, $User->settings);

# verify that user is logged in
$User->check_user_session();

# validate post
is_numeric($_POST['subnetId']) ?:						$Result->show("danger", _("Invalid ID"), true);
is_numeric($_POST['id']) || strlen($_POST['id'])==0 ?:	$Result->show("danger", _("Invalid ID"), true);

# set and check permissions
$subnet_permission = $Subnets->check_permission($User->user, $_POST['subnetId']);
$subnet_permission > 2 ?:								$Result->show("danger", _('Cannot edit IP address details').'! <br>'._('You do not have write access for this network'), true, true);

# fetch address
$address = (array) $Addresses->fetch_address(null, $_POST['id']);


# try to ping it
$pingRes = $Ping->ping_address($address['ip']);

# update last seen if success
if($pingRes==0) { @$Ping->ping_update_lastseen($address['id']); }
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
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Close window'); ?></button>
	</div>
</div>