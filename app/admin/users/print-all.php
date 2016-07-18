<?php

/**
 * Script to edit / add / delete users
 *************************************************/

# verify that user is logged in
$User->check_user_session();

# fetch all APIs
$users = $Admin->fetch_all_objects("users", "username");
# fetch custom fields
$custom = $Tools->fetch_custom_fields('users');

/* check customfields */
$ffields = json_decode($User->settings->hiddenCustomFields, true);
$ffields = is_array(@$ffields['users']) ? $ffields['users'] : array();
?>

<!-- display existing users -->
<h4><?php print _('User management'); ?></h4>
<hr><br>

<!-- Add new -->
<button class='btn btn-sm btn-default editUser' style="margin-bottom:10px;" data-action='add'><i class='fa fa-plus'></i> <?php print _('Create user'); ?></button>

<!-- table -->
<table id="userPrint1" class="table sorted table-striped table-top">

<!-- Headers -->
<thead>
<tr>
	<th></th>
    <th><?php print _('Real Name'); ?></th>
    <th><?php print _('Username'); ?></th>
    <th><?php print _('E-mail'); ?></th>
    <th><?php print _('Role'); ?></th>
    <th><?php print _('Language'); ?></th>
    <th><?php print _('Authentication'); ?></th>
    <?php if ($User->settings->enablePowerDNS==1) { ?>
    <th><?php print _('PowerDNS'); ?></th>
    <?php } ?>
    <th><?php print _('Manage VLANs'); ?></th>
    <?php if ($User->settings->enablePSTN==1) { ?>
    <th><?php print _('PSTN'); ?></th>
    <?php } ?>
    <th><?php print _('Groups'); ?></th>
    <th><?php print _('Last login'); ?></th>
	<?php
	if(sizeof(@$custom) > 0) {
		foreach($custom as $field) {
			if(!in_array($field['name'], $ffields)) {
				print "<th>$field[name]</th>";
			}
		}
	}
	?>
    <th></th>
</tr>
</thead>

<tbody>
<?php
/* print existing sections */
foreach ($users as $user) {
	//cast
	$user = (array) $user;
	print '<tr>' . "\n";

	# set icon based on normal user or admin
	if($user['role'] == "Administrator") 	{ print '	<td><img src="css/1.2/images/userVader.png" rel="tooltip" title="'._('Administrator').'"></td>'. "\n"; }
	else 									{ print '	<td><img src="css/1.2/images/userTrooper.png" rel="tooltip" title="'. _($user['role']) .'"></td>'. "\n";	}

	print '	<td><a href="'.create_link("administration","users",$user['id']).'">' . $user['real_name'] . '</a></td>'. "\n";
	print '	<td>' . $user['username']  . '</td>'. "\n";
	print '	<td>' . $user['email']     . '</td>'. "\n";
	print '	<td>' . $user['role']      . '</td>'. "\n";

	# language
	if(strlen($user['lang'])>0) {
		# get lang name
		$lname = $Admin->fetch_object("lang", "l_id", $user['lang']);
		print "<td>$lname->l_name</td>";
	}
	else {
		print "<td>English (default)</td>";
	}

	# check users auth method
	$auth_method = $Admin->fetch_object("usersAuthMethod", "id", $user['authMethod']);
	//false
	print "<td>";
	if($auth_method===false) { print "<span class='text-muted'>No auth method</span>"; }
	else 					 { print $auth_method->type." <span class='text-muted'>(".$auth_method->description."</a>)"; }
	print "</span></td>";

	# powerDNS
	if($user['role']=="Administrator") {
    	if ($User->settings->enablePowerDNS==1) {
     	print "<td><span class='badge badge1 badge5 alert-success'>"._("Yes")."</span></td>";
     	}
     	print "<td><span class='badge badge1 badge5 alert-success'>"._("Yes")."</span></td>";
    	if ($User->settings->enablePSTN==1) {
     	print "<td><span class='badge badge1 badge5 alert-success'>"._($Subnets->parse_permissions (3))."</span></td>";
     	}
	}
	else {
    	if ($User->settings->enablePowerDNS==1) {
    	if(strlen($user['pdns'])==0) $user['pdns'] = "No";

        // append badge
    	$user['pdns'] = $user['pdns']=="No" ? "<span class='badge badge1 badge5 alert-danger'>"._($user['pdns'])."</span>" : "<span class='badge badge1 badge5 alert-success'>"._($user['pdns'])."</span>";

    	print "<td>";
    	print $user['pdns'];
    	print "</td>";
    	}

    	if(strlen($user['editVlan'])==0) $user['editVlan'] = "No";

        // append badge
    	$user['editVlan'] = $user['editVlan']=="No" ? "<span class='badge badge1 badge5 alert-danger'>"._($user['editVlan'])."</span>" : "<span class='badge badge1 badge5 alert-success'>"._($user['editVlan'])."</span>";

    	print "<td>";
    	print $user['editVlan'];
    	print "</td>";

        // pstn
    	if ($User->settings->enablePSTN==1) {
         // append badge
    	$user['pstn'] = $user['pstn']=="No" ? "<span class='badge badge1 badge5 alert-danger'>"._($user['pstn'])."</span>" : "<span class='badge badge1 badge5 alert-success'>"._($Subnets->parse_permissions ($user['pstn']))."</span>";

    	print "<td>";
    	print $user['pstn'];
    	print "</td>";
    	}
	}

	# groups
	if($user['role'] == "Administrator") {
	print '	<td>'._('All groups').'</td>'. "\n";
	}
	else {
		$groups = json_decode($user['groups'], true);
		$gr = $Admin->groups_parse($groups);

		print '	<td>';
		if(sizeof($gr)>0) {
			foreach($gr as $group) {
				print $group['g_name']."<br>";
			}
		}
		else {
			print "<span class='text-muted'>No groups</span>";
		}
		print '	</td>'. "\n";
	}

	# last login
	print "<td>";
	print strlen($user['lastLogin'])>0 ? $user['lastLogin'] : "<span class='text-muted'>"._("Never")."</span>";
	print "</td>";

	# custom
	if(sizeof($custom) > 0) {
		foreach($custom as $field) {
			if(!in_array($field['name'], $ffields)) {
				print "<td>";
				//booleans
				if($field['type']=="tinyint(1)")	{
					if($user[$field['name']] == "0")		{ print _("No"); }
					elseif($user[$field['name']] == "1")	{ print _("Yes"); }
				}
				//text
				elseif($field['type']=="text") {
					if(strlen($user[$field['name']])>0)		{ print "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", $user[$field['name']])."'>"; }
					else									{ print ""; }
				}
				else {
					print $user[$field['name']];

				}
				print "</td>";
			}
		}
	}

	# edit, delete
	print "	<td class='actions'>";
	print "	<div class='btn-group'>";
	print "		<a class='btn btn-xs btn-default' href='".create_link("administration","users",$user['id'])."'><i class='fa fa-eye'></i></a></button>";
	print "		<button class='btn btn-xs btn-default editUser' data-userid='$user[id]' data-action='edit'  ><i class='fa fa-pencil'></i></button>";
	print "		<a class='btn btn-xs btn-default";
	if($_SESSION['realipamusername']) { print " disabled";}
	print "' href='".create_link("administration","users","switch","$user[username]")."'><i class='fa fa-exchange'></i></a></button>";
	print "		<button class='btn btn-xs btn-default editUser' data-userid='$user[id]' data-action='delete'><i class='fa fa-times'></i></button>";
	print "	</div>";
	print "	</td>";

	print '</tr>' . "\n";
}
?>
</tbody>
</table>

<div class="alert alert-info alert-absolute">
<ul>
	<li><?php print _('Administrator users will be able to view and edit all sections and subnets'); ?></li>
	<li><?php print _('Normal users will have permissions set based on group access to sections and subnets'); ?></li>
</ul>
</div>
