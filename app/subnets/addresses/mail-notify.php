<?php

/**
 * Script to print mail notification form
 ********************************************/

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

# id must be numeric
is_numeric($_POST['id']) || strlen($_POST['id'])==0 ?:	$Result->show("danger", _("Invalid ID"), true);

# get IP address id
$id = $_POST['id'];

# fetch address, subnet and vlan
$address = (array) $Addresses->fetch_address (null, $id);
$subnet  = (array) $Subnets->fetch_subnet (null, $address['subnetId']);
$vlan    = (array) $Tools->fetch_object("vlans", "vlanId", @$address['vlanId']);

# get all custom fields
$custom_fields = $Tools->fetch_custom_fields ('ipaddresses');


# checks
sizeof($address)>0 ?:	$Result->show("danger", _("Invalid ID"), true);
sizeof($subnet)>0 ?:	$Result->show("danger", _("Invalid subnet"), true);


# set title
$title = _('IP address details').' :: ' . $address['ip'];


# address
										$content[] = "&bull; "._('IP address').": \t $address[ip]/$subnet[mask]";
# description
empty($address['description']) ? : 		$content[] = "&bull; "._('Description').":\t $address[description]";
# hostname
empty($address['dns_name']) ? : 		$content[] = "&bull; "._('Hostname').": \t $address[dns_name]";
# subnet desc
$s_descrip = empty($address['description']) ? "" : 	 " (" . $subnet['description']. ")";
# subnet
										$content[] = "&bull; "._('Subnet').": \t $subnet[ip]/$subnet[mask] $s_descrip";
# gateway
$gateway = $Subnets->find_gateway($subnet['id']);
if($gateway !==false)
 										$content[] = "&bull; "._('Gateway').": \t". $Subnets->transform_to_dotted($gateway->ip_addr);
# VLAN
empty($address['vlan']) ? : 			$content[] = "&bull; "._('VLAN').": \t\t $address[vlan]";
# Switch
if(!empty($address['switch'])) {
	# get device by id
	$device = (array) $Tools->fetch_device(null, $address['switch']);
	!sizeof($device)>1 ? : 				$content[] = "&bull; "._('Device').": \t\t $device[hostname]";
}
# port
empty($address['port']) ? : 			$content[] = "&bull; "._('Port').": \t $address[port]";
# mac
empty($address['mac']) ? : 				$content[] = "&bull; "._('Mac address').": \t $address[mac]";
# owner
empty($address['owner']) ? : 			$content[] = "&bull; "._('Owners').": \t $address[owner]";

# custom
if(sizeof($custom_fields) > 0) {
	foreach($custom_fields as $custom_field) {
		if(!empty($address[$custom_field['name']])) {
										$content[] =  "&bull; ". _($custom_field['name']).":\t".$address[$custom_field['name']];
		}
	}
}
?>



<!-- header -->
<div class="pHeader"><?php print _('Send email notification'); ?></div>

<!-- content -->
<div class="pContent mailIPAddress">

	<!-- sendmail form -->
	<form name="mailNotify" id="mailNotify">
	<table id="mailNotify" class="table table-noborder table-condensed">

	<!-- recipient -->
	<tr>
		<th><?php print _('Recipients'); ?></th>
		<td>
			<input type="text" class='form-control input-sm pull-left' name="recipients" style="width:400px;margin-right:5px;">
			<i class="fa fa-info input-append" rel="tooltip" data-placement="bottom" title="<?php print _('Separate multiple recepients with ,'); ?>"></i>
		</td>
	</tr>

	<!-- title -->
	<tr>
		<th><?php print _('Title'); ?></t>
		<td>
			<input type="text" class='form-control input-sm' name="subject" style="width:400px;" value="<?php print $title; ?>">
		</td>
	</tr>

	<!-- content -->
	<tr>
		<th><?php print _('Content'); ?></th>
		<td style="padding-right:20px;">
			<textarea name="content" class='form-control input-sm' rows="7" style="width:100%;"><?php print implode("\n", $content); ?></textarea>
		</td>
	</tr>

	</table>
	</form>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default btn-success" id="mailIPAddressSubmit"><i class="fa fa-envelope-o"></i> <?php print _('Send Mail'); ?></button>
	</div>

	<!-- holder for result -->
	<div class="sendmail_check"></div>
</div>