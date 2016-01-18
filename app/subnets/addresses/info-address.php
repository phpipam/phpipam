<?php

/**
 *	Script that checks if IP is alive
 */

# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );
require_once( dirname(__FILE__) . '/../../../functions/PEAR/Net/IPv4.php' );
require_once( dirname(__FILE__) . '/../../../functions/PEAR/Net/IPv6.php' );

# initialize required objects
$Database 	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);
$Subnets	= new Subnets ($Database);
$Tools	    = new Tools ($Database);
$Addresses	= new Addresses ($Database);
//$Ping		= new Scan ($Database);
$DNS = new DNS ($Database);

$validNM = $GLOBALS['Net_IPv4_Netmask_Map'];


# verify that user is logged in
$User->check_user_session();

# id must be numeric
is_numeric($_POST['id']) || strlen($_POST['id'])==0 ?:	$Result->show("danger", _("Invalid ID"), true);
# get IP address id
$id = $_POST['id'];
$subnetId = $_POST['subnetId'];

$address = (array) $Addresses->fetch_address(null, $id);
$gateway=$Subnets->find_gateway ($subnetId);
$resolve = $DNS->resolve_address($address['ip_addr'], $address['dns_name'], false, $subnet['nameserverId']);

$subnet  = (array) $Subnets->fetch_subnet(null, $address['subnetId']);
$mask = $validNM[$subnet['mask']];

# validate post
#is_numeric($_POST['subnetId']) ?:                                                       $Result->show("danger", _("Invalid ID"), true, true, false, true);
#if(is_numeric($id)) {
#        strlen($id)!=0 ?:                                                              $Result->show("danger", _("Invalid ID"), true, true, false, true);
        # fetch address
#        $address = (array) $Addresses->fetch_address(null, $id);
#}
// from adding new IP, validate
#else {
#        $validate = $Subnets->identify_address ($_POST['id'])=="IPv4" ? filter_var($_POST['id'], FILTER_VALIDATE_IP) : filter_var($_POST['id'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);


#        if ($validate===false)                                                                  { $Result->show("danger", _("Invalid IP address"), true, true, false, true); }
#        else {
#                $address['ip'] = $id;
#        }
#}
# set and check permissions
$subnet_permission = $Subnets->check_permission($User->user, $_POST['subnetId']);
$subnet_permission > 2 ?:                                                               $Result->show("danger", _('Cannot edit IP address details').'! <br>'._('You do not have write access for this network'), true, true);


?>

<!-- header -->
<div class="pHeader"><?php print _('Show Individual IP Info'); ?></div>

<!-- content -->
<div class="pContent">
	<?php

	print "<table class='ipaddress_subnet table table-noborder table-condensed' style='margin-top:10px;'>";

	print "<tr>";
	print "	<th>"._('Hostname')."</th>";
	print " <td>" . $resolve['name'] . "</td>\n";
	print "</tr>";

	print "<tr>";
	print "	<th>"._('Address')."</th>";
	print " <td>" . $address['ip'] . "</td>\n";
	print "</tr>";

	print "<tr>";
	print "	<th>"._('Subnet Mask')."</th>";
	print " <td>" . $mask . "</td>\n";
	print "</tr>";

	print "<tr>";
	print "	<th>"._('Default Gateway')."</th>";
	print " <td>" . $Subnets->transform_address($gateway->ip_addr,"dotted") . "</td>\n";
	print "</tr>";

	print "</table>";

	?>
</div>

<!-- footer -->
<div class="pFooter">
        <div class="btn-group">
                <button class="btn btn-sm btn-default hidePopups"><?php print _('Close'); ?></button>
        </div>

        <div class="manageSubnetEditResult"></div>
        <!-- vlan add holder from subnets -->
        <div id="addNewVlanFromSubnetEdit" style="display:none"></div>
</div>

