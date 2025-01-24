<?php

# verify that user is logged in
$User->check_user_session();

# fetch all auth methods
$all_methods = $Admin->fetch_all_objects("usersAuthMethod");
# fetch all parameters for each method
$all_method_types = $User->fetch_available_auth_method_types();
?>


<h4><?php print _("Authentication methods"); ?></h4>
<hr>

<!-- Add new -->
<div class="dropdown" style="margin-bottom: 20px;">
    <button class="btn btn-default btn-sm dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"><?php print _("Create new:"); ?> <span class="caret"></span>
</button>

<ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
<?php
foreach($all_method_types as $type) {
	print "<li><a class='open_popup' data-script='app/admin/authentication-methods/edit.php' data-class='700' data-action='add' data-type='$type'><i class='fa fa-plus'></i> "._("Create new ").$type._(" authentication")."</a></li>";
}
?>
</ul>

</div>

<div class='clearfix'></div>
<div class="panel panel-default pull-left" style="width:auto;border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;">

<!-- table -->
<table id="userPrint" class="table nosearch table-striped table-top" data-cookie-id-table="admin_authm" style="bordmarginer-bottom:0px;">

<!-- Headers -->
<thead>
<tr>
    <th><?php print _('Type'); ?></th>
    <th><?php print _('Description'); ?></th>
    <th><?php print _('Parameters'); ?></th>
    <th><?php print _('Users'); ?></th>
    <th><?php print _('Protected'); ?></th>
    <th></th>
</tr>
</thead>

<tbody>
<!-- data -->
<?php
//loop
foreach($all_methods as $method) {
	//set protected
	$protected_class = $method->protected=="yes" ? "danger" : "";

	//number of users
	$user_num = $Database->numObjectsFilter("users", "authMethod", $method->id);

	print "<tr>";
	print "	<td><span class='badge badge1 badge-white'>$method->type</span></td>";
	print "	<td>$method->description</td>";
	//parameters
	print "	<td>";
	print "	<span class='text-muted'>";
	if(!is_blank($method->params)) {
		$secure_keys=[
			'adminPassword',
			'secret',
			'spx509key'
		];
		$params = db_json_decode($method->params);
		foreach($params as $key=>$parameter) {
			// mask secure keys
			if(in_array($key, $secure_keys) && !is_blank($parameter) ) { $parameter = "********"; }
			$parameter = $Tools->shorten_text($parameter, 80);
			// print
			print $key." => ".$parameter."<br>";
		}
	}
	else {
		print _("no parameters");
	}
	// radius - composer validation
	if($method->type=="Radius") {
	    if($User->composer_has_errors(["dapphp/radius"])) {
	        $Result->show("danger", $User->composer_err, false);
	    }
	}
	print "	</span>";
	print "	</td>";
	print "	<td class='$protected_class'>$user_num</td>";
	print "	<td class='$protected_class'>$method->protected</td>";
	//actions
	$disabled = $method->type=="local" ? "disabled" : "";
	print "	<td class='actions'>";
	print "	<div class='btn-group'>";
	if ($method->type=="SAML2")
	print "		<a class='btn btn-xs btn-default' href='".create_link('saml2-idp')."' target='_blank' title='"._("SAML2 Metadata")."'><i class='fa fa-info'></i></a>";
	print "		<button class='btn btn-xs btn-default open_popup' data-script='app/admin/authentication-methods/edit.php' data-class='700' data-action='edit' data-type='$method->type' data-id='$method->id' title='"._("Edit")."'><i class='fa fa-pencil'></i></button>";
	print "		<button class='btn btn-xs btn-default open_popup' data-script='app/admin/authentication-methods/edit.php' data-class='700' data-action='delete' data-type='$method->type' data-id='$method->id' title='"._("Delete")."'><i class='fa fa-times'></i></button>";
	print "		<button class='btn btn-xs btn-default open_popup' data-script='app/admin/authentication-methods/check-connection.php' data-class='500' data-id='$method->id' title='"._("Verify connection")."' $disabled><i class='fa fa-bolt'></i></button>";
	print "	</div>";
	print "	</td>";
	print "</tr>";
}
?>
</tbody>
</table>
</div>
<div class='clearfix'></div>



<div class="alert alert-info alert-absolute">
	<?php print _("Here you can set different authentication methods for your users."); ?>
	<hr>
	<?php print _("phpIPAM currently supports following authentication methods:"); ?>
	<ul>
		<li><?php print _("Local authentication"); ?></li>
		<li><?php print _("Apache authentication"); ?></li>
		<li><?php print _("AD (Active Directory) authentication"); ?></li>
		<li><?php print _("LDAP authentication"); ?></li>
		<li><?php print _("NetIQ authentication"); ?></li>
		<li><?php print _("Radius authentication"); ?></li>
		<li><?php print _("SAMLv2 authentication"); ?></li>
		<li><?php print _("Passkey authentication"); ?></li>
	</ul>
	<br>
	<?php print _("For AD/LDAP/NetIQ connection phpipam is using adLDAP, for documentation please check ")."<a href='http://adldap.sourceforge.net/'>adLDAP</a><br><br>"; ?>
	<?php print _('First create new user under user management with <u>same username as on AD</u> and set authentication type to one of available methods.')."<br>"._('Also set proper permissions - group membership for new user'); ?>
</div>
