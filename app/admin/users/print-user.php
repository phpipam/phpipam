<?php

/**
 * Script to edit / add / delete users
 *************************************************/

# verify that user is logged in
$User->check_user_session();

# fetch user details
$user 		  = $Admin->fetch_object("users", "id", $_GET['subnetId']);

# invalid?
if($user===false)		{ $Result->show("danger", _("Invalid ID"), true); }

# fetch user lang
$language 	  = $Admin->fetch_object("lang", "l_id", $user->lang);
# check users auth method
$auth_details = $Admin->fetch_object("usersAuthMethod", "id", $user->authMethod);
# fetch custom fields
$custom_fields = $Tools->fetch_custom_fields('users');
?>

<!-- display existing users -->
<h4><?php print _('User details'); ?></h4>
<hr><br>

<!-- Add new -->
<a class='btn btn-sm btn-default' href="<?php print create_link("administration","users"); ?>" style="margin-bottom:10px;"><i class='fa fa-angle-left'></i> <?php print _('All users'); ?></a>

<!-- table -->
<table id="userPrint" class="table table-hover table-auto table-condensed">

<tr>
	<td><?php print _('Real Name'); ?></td>
	<td><strong><?php print $user->real_name; ?></strong></td>
</tr>
<tr>
	<td><?php print _('Username'); ?></td>
	<td><?php print $user->username; ?></td>
</tr>
<tr>
	<td><?php print _('E-mail'); ?></td>
	<td><?php print $user->email; ?></td>
</tr>
<tr>
	<td><?php print _('Language'); ?></td>
	<td><?php print $language->l_name; ?></td>
</tr>
<?php if ($User->settings->enablePowerDNS==1) { ?>
<tr>
    <?php
    $user->pdns = $user->pdns=="Yes"||$user->role=="Administrator" ? "Yes" : "No";
    ?>
	<td><?php print _('PowerDNS'); ?></td>
	<td><?php print $user->pdns; ?></td>
</tr>
<?php } ?>
<tr>
    <?php
    $user->editVlan = $user->editVlan=="Yes"||$user->role=="Administrator" ? "Yes" : "No";
    ?>
	<td><?php print _('Manage VLANs'); ?></td>
	<td><?php print $user->editVlan; ?></td>
</tr>
<?php if ($User->settings->enablePSTN==1) { ?>
<tr>
	<td><?php print _('PSTN'); ?></td>
	<td><?php print $Subnets->parse_permissions ($user->pstn); ?></td>
</tr>
<?php } ?>
<tr>
	<td></td>
	<td>
	<div class='btn-group'>
		<button class='btn btn-xs btn-default editUser' data-userid='<?php print $user->id; ?>' data-action='edit'  ><i class='fa fa-pencil'></i></button>
		<button class='btn btn-xs btn-default editUser' data-userid='<?php print $user->id; ?>' data-action='delete'><i class='fa fa-times'></i></button>
	</div>
	</td>
</tr>


<tr>
	<td colspan="2"><h4><?php print _('Authentication settings'); ?></h4><hr></td>
</tr>

<tr>
	<td><?php print _('Role'); ?></td>
	<td><?php print $user->role; ?></td>
</tr>
<tr>
	<td><?php print _('Authentication'); ?></td>
	<td>
	<?php
	if($auth_details===false) 	{ print "<span class='text-muted'>No auth method</span>"; }
	else 					 	{ print $auth_details->type." <span class='text-muted'>(".$auth_details->description.")</span>"; }
	?>
	</td>
</tr>
<tr>
	<td><?php print _('Last login'); ?></td>
	<td><?php print strlen($user->lastLogin)>0 ? $user->lastLogin : "<span class='text-muted'>"._("Never")."</span>"; ?></td>
</tr>
<tr>
	<td><?php print _('Last activity'); ?></td>
	<td><?php print strlen($user->lastActivity)>0 ? $user->lastActivity : "<span class='text-muted'>"._("Never")."</span>"; ?></td>
</tr>
<tr>
	<td><?php print _('Groups'); ?></td>
	<td>
	<?php
	if($user->role == "Administrator") {
	print _('All groups');
	}
	else {
		$groups = json_decode($user->groups, true);
		$gr = $Admin->groups_parse($groups);
		if(sizeof($gr)>0) {
			foreach($gr as $group) {
				print $group['g_name']."<br>";
			}
		}
		else {
			print "<span class='text-muted'>No groups</span>";
		}
	}
	?>
	</td>
</tr>
<tr>
	<td><?php print _('Password change required'); ?></td>
	<td><?php print $user->passChange; ?></td>
</tr>



<tr>
	<td colspan="2"><h4><?php print _('Display settings'); ?></h4><hr></td>
</tr>
<tr>
	<td><?php print _('compress override'); ?></td>
	<td><?php print $user->compressOverride==1 ? _("Yes") : _("No") ?></td>
</tr>
<tr>
	<td><?php print _('Hide free range'); ?></td>
	<td><?php print $user->hideFreeRange==1 ? _("Yes") : _("No") ?></td>
</tr>
<tr>
	<td><?php print _('Menu type'); ?></td>
	<td><?php print $user->menuType; ?></td>
</tr>



<tr>
	<td colspan="2"><h4><?php print _('Mail settings'); ?></h4><hr></td>
</tr>
<tr>
	<td><?php print _('Mail notifications'); ?></td>
	<td><?php print $user->role == "Administrator" ? _($user->mailNotify) : _("No"); ?></td>
</tr>
<tr>
	<td><?php print _('Changelog notifications'); ?></td>
	<td><?php print $user->role == "Administrator" ? _($user->mailChangelog) : _("No"); ?></td>
</tr>



<tr>
	<td colspan="2"><h4><?php print _('Custom fields'); ?></h4><hr></td>
</tr>

<?php
# custom subnet fields
if(sizeof($custom_fields) > 0) {
	foreach($custom_fields as $key=>$field) {
		$user->{$key} = str_replace("\n", "<br>",$user->{$key});
		print "<tr>";
		print "	<td>$key</td>";
		print "	<td>";
		//no length
		if(strlen($user->{$key})==0) {
			print "/";
		}
		//booleans
		elseif($field['type']=="tinyint(1)")	{
			if($user->{$key} == "0")		{ print _("No"); }
			elseif($user->{$key} == "1")	{ print _("Yes"); }
		}
		else {
			print $user->{$key};
		}
		print "	</td>";
		print "</tr>";
		}
}
?>


</table>