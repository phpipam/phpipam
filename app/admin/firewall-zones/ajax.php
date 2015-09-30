<?php
// firewall zone ajax.php
// deliver content for ajax requests

// functions 
require( dirname(__FILE__) . '/../../../functions/functions.php');

// initialize classes
$Database = new Database_PDO;
$User = new User ($Database);
$Admin = new Admin($Database);
$Subnets = new Subnets ($Database);
$Result = new Result ();

// validate session parameters
$User->check_user_session();

if($_POST['sectionId']) {
	if(preg_match('/^[0-9]+$/i',$_POST['sectionId'])) {
		$sectionId = $_POST['sectionId'];
		print $Subnets->print_mastersubnet_dropdown_menu($sectionId);
	} else {
		$Result->show('danger', _('Invalid ID.'), true);
	}
}

if($_POST['vlanDomain']){ 
	if(preg_match('/^[0-9]+$/i',$_POST['vlanDomain'])) {
		$vlanDomain = $_POST['vlanDomain'];
		$vlans = $Admin->fetch_multiple_objects("vlans", "domainId", $vlanDomain, "number");

		print '<select name="vlanId" class="form-control input-sm input-w-auto input-max-200">';

			if ($vlans == false) {
				print '<option disabled selected>'._('No VLAN available').'</option>';
			} else {
				foreach ($vlans as $vlan) {
					print '<option value="'.$vlan->vlanId.'">'.$vlan->number.' - '.$vlan->name.' - '.$vlan->description.'</option>';
				}
			}
		print '</select>';
	} else {
		$Result->show('danger', _('Invalid ID.'), true);
	}
}
?>