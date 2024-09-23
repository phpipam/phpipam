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

// check customfields
$ffields = db_json_decode($User->settings->hiddenCustomFields, true);
$ffields = is_array(@$ffields['users']) ? $ffields['users'] : array();
?>

<!-- display existing users -->
<h4><?php print _('User management'); ?></h4>
<hr><br>

<!-- Add new -->
<button class='btn btn-sm btn-default open_popup' data-script='app/admin/users/edit.php' data-class='700' data-action='add'><i class='fa fa-plus'></i> <?php print _('Create user'); ?></button>


<!-- table -->
<table id="userPrint1" class="table sorted table-striped table-top table-td-top" data-cookie-id-table="admin_users">

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
    <th><?php print _('Module permissions'); ?></th>
    <th><?php print _('Groups'); ?></th>
    <th><?php print _('Last login'); ?></th>
	<?php
	if(sizeof(@$custom) > 0) {
		foreach($custom as $field) {
			if(!in_array($field['name'], $ffields)) {
				print "<th class='hidden-xs hidden-sm hidden-md'>".$Tools->print_custom_field_name ($field['name'])."</th>";
			}
		}
	}
	?>
    <th class="actions"></th>
</tr>
</thead>

<tbody>
<?php
/* print existing sections */
foreach ($users as $user) {
	//cast
	$user = (array) $user;

	// passkeys
	if ($User->settings->dbversion >= 40 && $User->settings->{'passkeys'}=="1") {
		// get user passkeys
		$user_passkeys = $User->get_user_passkeys($user['id']);
		// set passkey_only flag
		$passkey_only = sizeof($user_passkeys)>0 && $user['passkey_only']=="1" ? true : false;
	}

	print '<tr>' . "\n";

	# set icon based on normal user or admin
	if($user['role'] == "Administrator") 	{ print '	<td><img src="css/images/userVader.png" alt="'._("Administrator user icon").'" rel="tooltip" title="'._('Administrator').'"></td>'. "\n"; }
	else 									{ print '	<td><img src="css/images/userTrooper.png" alt="'._("Standard user icon").'" rel="tooltip" title="'. _($user['role']) .'"></td>'. "\n";	}

	# disabled
	$disabled = $user['disabled']=="Yes" ? "<span class='badge badge1 badge5 alert-danger'>"._("Disabled")."</span>" : "";

	print '	<td><a href="'.create_link("administration","users",$user['id']).'">' . $user['real_name'] . '</a> '.$disabled.'</td>'. "\n";
	print '	<td>' . $user['username']  . '</td>'. "\n";
	print '	<td>' . $user['email']     . '</td>'. "\n";
	print '	<td>' . $user['role']      . '</td>'. "\n";

	# language
	if(!is_blank($user['lang'])) {
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
	if($auth_method===false) 	{ print "<span class='text-muted'>No auth method</span>"; }
	elseif($passkey_only)   	{ print "<span class='badge badge1 badge5 alert-success'>"._("Passkey only")."</span>"; }
	else 					 	{ print "<span class='badge badge1 badge5 alert-success'>".$auth_method->type."</span> <span class='text-muted'>(".$auth_method->description."</a>)"; }
	// 2fa
	if ($User->settings->{'2fa_provider'}!=='none' && $passkey_only!==true) {
		if (!is_null($user['2fa_secret']) && $user['2fa']=="1") {
			print "<br><span class='badge badge1 badge5 alert-success'>"._("2fa enabled")."</span>";
		}
		else {
			print "<br><span class='badge badge1 badge5 alert-warning'>"._("2fa disabled")."</span>";
		}
	}

	// passkeys
	if ($User->settings->dbversion >= 40 && $User->settings->{'passkeys'}=="1") {
		// get user passkeys
		$user_passkeys = $User->get_user_passkeys($user['id']);
		if (sizeof($user_passkeys)>0) {
			print "<br><span class='badge badge1 badge5 alert-success'>".sizeof($user_passkeys)." "._("Passkeys")."</span>";
		}
	}
	print "</span></td>";

	# Module permisisons
	if($user['role']=="Administrator") {
     	print "<td><span class='badge badge1 badge5 alert-success'>"._("All")."</span></td>";
	}
	else {
		print "<td>";
		print "<btn class='btn btn-xs btn-default toggle-module-permissions'>Show <i class='fa fa-angle-down'></i></btn><div class='hidden module-permissions'>";
		include("print_module_permissions.php");
		print "</div>";
		print "</td>";
	}

	# groups
	if($user['role'] == "Administrator") {
	print '	<td>'._('All groups').'</td>'. "\n";
	}
	else {
		$groups = db_json_decode($user['groups'], true);
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
	print !is_blank($user['lastLogin']) ? $user['lastLogin'] : "<span class='text-muted'>"._("Never")."</span>";
	print "</td>";

	# custom
	if(sizeof($custom) > 0) {
		foreach($custom as $field) {
			if(!in_array($field['name'], $ffields)) {
				print "<td class='hidden-xs hidden-sm hidden-md'>";
				$Tools->print_custom_field ($field['type'], $user[$field['name']]);
				print "</td>";
			}
		}
	}


	print "	<td class='actions'>";
    $links = [];
    $links[] = ["type"=>"header", "text"=>_("Show user")];
    $links[] = ["type"=>"link", "text"=>_("Show user"), "href"=>create_link("administration", "users", $user['id']), "icon"=>"eye", "visible"=>"dropdown"];
    $links[] = ["type"=>"divider"];
    $links[] = ["type"=>"header", "text"=>_("Manage user")];
    $links[] = ["type"=>"link", "text"=>_("Edit user"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/users/edit.php' data-class='700' data-action='edit' data-id='$user[id]'", "icon"=>"pencil"];
    $links[] = ["type"=>"link", "text"=>_("Delete user"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/users/edit.php' data-class='700' data-action='delete' data-id='$user[id]'", "icon"=>"times"];
    if(!isset($_SESSION['realipamusername'])) {
        $links[] = ["type"=>"divider"];
        $links[] = ["type"=>"header", "text"=>_("Swap user")];
        $links[] = ["type"=>"link", "text"=>_("Swap user"), "href"=>create_link("administration", "users", "switch", $user['username']), "icon"=>"exchange"];
    }

    // print links
    print $User->print_actions($User->user->compress_actions, $links);
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
