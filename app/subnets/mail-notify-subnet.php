<?php

/**
 * Script to print mail notification form
 ********************************************/

# include required scripts
require_once( dirname(__FILE__) . '/../../functions/functions.php' );

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
is_numeric($_POST['id']) || is_blank($_POST['id']) ?:	$Result->show("danger", _("Invalid ID"), true);

$csrf = $User->Crypto->csrf_cookie ("create", "mail_notify");

# get IP address id
$id = $_POST['id'];

# fetch subnet, vlan and nameservers
$subnet  = (array) $Subnets->fetch_subnet (null, $_POST['id']);
$vlan    = (array) $Tools->fetch_object ("vlans", "vlanId", $subnet['vlanId']);
$vrf     = (array) $Tools->fetch_object ("vrf", "vrfId", $subnet['vrfId']);
$nameservers    = (array) $Tools->fetch_object("nameservers", "id", $subnet['nameserverId']);

# get all custom fields
$custom_fields = $Tools->fetch_custom_fields ('subnets');

# checks
sizeof($subnet)>0 ?:	$Result->show("danger", _("Invalid subnet"), true);


# set title
$title = _('Subnet details').' :: ' . $Subnets->transform_address($subnet['subnet'], "dotted")."/".$subnet['mask'];


# address
										$content[] = "&bull; "._('Subnet').": \t\t ".$Subnets->transform_address($subnet['subnet'], "dotted")."/".$subnet['mask'];
# description
empty($subnet['description']) ? : 		$content[] = "&bull; "._('Description').":\t\t $subnet[description]";
# gateway
$gateway = $Subnets->find_gateway($subnet['id']);
if($gateway !==false)
 						$content[] = "&bull; "._('Gateway').": \t\t". $Subnets->transform_to_dotted($gateway->ip_addr);

# VLAN
empty($subnet['vlanId']) ? : 			$content[] = "&bull; "._('VLAN').": \t\t\t $vlan[number] ($vlan[name])";

# VLAN
empty($subnet['vlanId']) ? : 			$content[] = "&bull; "._('VRF').": \t\t\t $vrf[name] ($vrf[description])";

# Nameserver sets
if ( !empty( $subnet['nameserverId'] ) ) {
	$nslist = str_replace(";", ", ", $nameservers['namesrv1']);

						$content[] = "&bull; "._('Nameservers').": \t $nslist (${nameservers['name']})";
}

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
	<form name="mailNotifySubnet" id="mailNotifySubnet">
	<table id="mailNotifySubnet" class="table table-noborder table-condensed">

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
		<th><?php print _('Title'); ?></th>
		<td>
			<input type="text" class='form-control input-sm' name="subject" style="width:400px;" value="<?php print $title; ?>">
		</td>
	</tr>

	<!-- content -->
	<tr>
		<th><?php print _('Content'); ?></th>
		<td style="padding-right:20px;">
			<textarea name="content" class='form-control input-sm' rows="10" style="width:100%;"><?php print implode("\n", $content); ?></textarea>
		</td>
	</tr>

	</table>
	<input type="hidden" name='csrf_cookie' value='<?php print $csrf; ?>'>
	</form>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default btn-success" id="mailSubnetSubmit"><i class="fa fa-envelope-o"></i> <?php print _('Send Mail'); ?></button>
	</div>

	<!-- holder for result -->
	<div class="sendmail_check"></div>
</div>
