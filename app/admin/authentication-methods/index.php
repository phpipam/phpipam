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
	print "<li><a href='' class='editAuthMethod' data-action='add' data-type='$type'><i class='fa fa-plus'></i> "._("Create new $type authentication")."</a></li>";
}
?>
</ul>

</div>

<!-- table -->
<table id="userPrint" class="table table-striped table-top table-auto">

<!-- Headers -->
<tr>
    <th><?php print _('Type'); ?></th>
    <th><?php print _('Description'); ?></th>
    <th><?php print _('Parameters'); ?></th>
    <th><?php print _('Users'); ?></th>
    <th><?php print _('Protected'); ?></th>
    <th></th>
</tr>

<!-- data -->
<?php
//loop
foreach($all_methods as $method) {
	//set protected
	$protected_class = $method->protected=="yes" ? "danger" : "";

	//number of users
	$user_num = $Database->numObjectsFilter("users", "authMethod", $method->id);

	print "<tr>";
	print "	<td>$method->type</td>";
	print "	<td>$method->description</td>";
	//parameters
	print "	<td>";
	print "	<span class='text-muted'>";
	if(strlen($method->params)>0) {
		$params = json_decode($method->params);
		foreach($params as $key=>$parameter) {
			// mask user/pass
			if($key=="adminPassword")	{ $parameter = "********"; }
			// print
			print $key." => ".$parameter."<br>";
		}
	}
	else {
		print "no parameters";
	}
	print "	</span>";
	print "	</td>";
	print "	<td class='$protected_class'>$user_num</td>";
	print "	<td class='$protected_class'>$method->protected</td>";
	//actions
	$disabled = $method->type=="local" ? "disabled" : "";
	print "	<td class='actions'>";
	print "	<div class='btn-group'>";
	print "		<button class='btn btn-xs btn-default editAuthMethod' data-id='$method->id' data-action='edit'   data-type='$method->type' rel='tooltip' title='Edit'><i class='fa fa-pencil'></i></button>";
	print "		<button class='btn btn-xs btn-default editAuthMethod' data-id='$method->id' data-action='delete' data-type='$method->type' rel='tooltip' title='Delete'><i class='fa fa-times'></i></button>";
	print "		<button class='btn btn-xs btn-default checkAuthMethod' data-id='$method->id' data-action='check' data-type='$method->type' rel='tooltip' title='Verify connection' $disabled><i class='fa fa-bolt'></i></button>";
	print "	</div>";
	print "	</td>";
	print "</tr>";
}
?>
</table>


<hr>
<div class="alert alert-info alert-absolute" style="margin-top:30px;">
	<?php print _("Here you can set different authentication methods for your users."); ?>
	<hr>
	<?php print _("phpIPAM currently supports 7 methods for authentication:"); ?>
	<ul>
		<li><?php print _("Local authentication"); ?></li>
		<li><?php print _("Apache authentication"); ?></li>
		<li><?php print _("AD (Active Directory) authentication"); ?></li>
		<li><?php print _("LDAP authentication"); ?></li>
		<li><?php print _("NetIQ authentication"); ?></li>
		<li><?php print _("Radius authentication"); ?></li>
		<li><?php print _("SAMLv2 authentication"); ?></li>
	</ul>
	<br>
	<?php print _("For AD/LDAP/NetIQ connection phpipam is using adLDAP, for documentation please check ")."<a href='http://adldap.sourceforge.net/'>adLDAP</a><br><br>"; ?>
	<?php print _('First create new user under user management with <u>same username as on AD</u> and set authention type to one of available methods.')."<br>"._('Also set proper permissions - group membership for new user'); ?>
</div>
