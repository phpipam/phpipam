<!-- test -->
<h4 style='margin-top:30px;'><?php print _('Module permissions'); ?></h4>
<hr>
<span class="info2"><?php print _("Summary of module permissions"); ?></span>
<br><br>



<table class="table table-condensed table-noborder table-auto">

<?php

// pdns
if ($User->settings->enablePowerDNS==1) {
	if(strlen($User->user->pdns)==0) $User->user->pdns = "No";
	$User->user->pdns = $User->user->pdns=="No" ? "<span class='badge badge1 badge5 alert-danger'>"._($User->user->pdns)."</span>" : "<span class='badge badge1 badge5 alert-success'>"._($User->user->pdns)."</span>";

	print "<tr>";
	print "	<td>"._("PowerDNS")."</td>";
	print "	<td>".$User->user->pdns."</td>";
	print "</tr>";
}

// vlan / VRF
if(strlen($User->user->editVlan)==0) $User->user->editVlan = "No";
$User->user->editVlan = $User->user->editVlan=="No" ? "<span class='badge badge1 badge5 alert-danger'>"._($User->user->editVlan)."</span>" : "<span class='badge badge1 badge5 alert-success'>"._($User->user->editVlan)."</span>";

print "<tr>";
print "	<td>"._("VLANs / VRFs")."</td>";
print "	<td>".$User->user->editVlan."</td>";
print "</tr>";

// pstn
if ($User->settings->enablePSTN==1) {
	$User->user->perm_pstn = $User->user->perm_pstn=="0" ? "<span class='badge badge1 badge5 alert-danger'>"._("No")."</span>" : "<span class='badge badge1 badge5 alert-success'>"._($Subnets->parse_permissions ($User->user->perm_pstn))."</span>";

	print "<tr>";
	print "	<td>"._("PSTN")."</td>";
	print "	<td>".$User->user->perm_pstn."</td>";
	print "</tr>";
}

// Circuits
if ($User->settings->enableCircuits==1) {
	$User->user->editCircuits = $User->user->editCircuits=="No"||is_null($User->user->editCircuits) ? "<span class='badge badge1 badge5 alert-danger'>"._("No")."</span>" : "<span class='badge badge1 badge5 alert-success'>"._($User->user->editCircuits)."</span>";

	print "<tr>";
	print "	<td>"._("Circuits")."</td>";
	print "	<td>".$User->user->editCircuits."</td>";
	print "</tr>";
}

// Circuits
if ($User->settings->enableCustomers==1) {
	$User->user->perm_customers = $User->user->perm_customers=="0" ? "<span class='badge badge1 badge5 alert-danger'>"._($User->user->perm_customers)."</span>" : "<span class='badge badge1 badge5 alert-success'>"._($Subnets->parse_permissions ($User->user->perm_customers))."</span>";

	print "<tr>";
	print "	<td>"._("Customers")."</td>";
	print "	<td>".$User->user->perm_customers."</td>";
	print "</tr>";
}

?>

</table>


