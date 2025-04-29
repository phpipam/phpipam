<?php

/**
 * Script to print add / edit / delete users
 *************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Subnets	= new Subnets ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "user");

# validate action
$Admin->validate_action();

# fetch custom fields
$custom 	= $Tools->fetch_custom_fields('users');
# fetch all languages
$langs 		= $Admin->fetch_all_objects ("lang", "l_id");
# fetch all auth types
$auth_types = $Admin->fetch_all_objects ("usersAuthMethod", "id");
# fetch all groups
$groups		= $Admin->fetch_all_objects ("userGroups", "g_id");


# set header parameters and fetch user
if($POST->action!="add") {
	$user = $Admin->fetch_object ("users", "id", $POST->id);
	//false
	if($user===false) { $Result->show("danger", _("Invalid ID"), true, true); }
} else {
	$user = new Params();
	$user->lang=$User->settings->defaultLang;
}

# disabled
$disabled = $POST->action=="delete" ? "disabled" : "";

# passkeys
$user_passkeys = $User->get_user_passkeys($user->id);
?>

<script>
$(document).ready(function(){
    if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }

	/* bootstrap switch */
	var switch_options = {
	    onColor: 'default',
	    offColor: 'default',
	    size: "mini",
	    onText: "Yes",
	    offText: "No"
	};
	$(".input-switch").bootstrapSwitch(switch_options);
});
</script>


<!-- header -->
<div class="pHeader"><?php print $User->get_post_action()." "._('user'); ?></div>


<!-- content -->
<div class="pContent">

	<form id="usersEdit" name="usersEdit">
	<table class="usersEdit table table-noborder table-condensed">

	<tbody>
	<!-- real name -->
	<tr>
	    <td><?php print _('Real name'); ?></td>
	    <td><input type="text" class="form-control input-sm" name="real_name" value="<?php print $user->real_name; ?>" <?php print $disabled; ?>></td>
       	<td class="info2"><?php print _('Enter users real name'); ?></td>
    </tr>

    <!-- username -->
    <tr>
    	<td><?php print _('Username'); ?></td>
    	<td>
    		<input type="text" class="form-control input-sm" name="username" value="<?php print $user->username; ?>" <?php if($POST->action=="edit"||$POST->action=="delete") print 'readonly disabled'; ?> <?php print $disabled; ?>></td>
    		<input type="hidden" name="userId" value="<?php print $user->id; ?>">
        	<input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
        	<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
    	<td class="info2">
    		<?php if($POST->action=="add") { ?>
    		<a class='btn btn-xs btn-default adsearchuser' rel='tooltip' title='Search AD for user details'><i class='fa fa-search'></i></a>
    		<?php } ?>
			<?php print _('Enter username'); ?>
		</td>
    </tr>

    <!-- email -->
    <tr>
    	<td><?php print _('e-mail'); ?></td>
    	<td><input type="text" class="form-control input-sm input-w-250" name="email" value="<?php print $user->email; ?>" <?php print $disabled; ?>></td>
    	<td class="info2"><?php print _('Enter users email address'); ?></td>
    </tr>

    <?php if($POST->action!="delete") { ?>

	<tr>
		<td colspan="3"><hr></td>
	</tr>

    <!-- Status -->
    <tr>
    	<td><?php print _('Status'); ?></td>
    	<td>
        <select name="disabled" class="form-control input-sm input-w-auto">
            <option value="Yes" <?php if ($user->disabled == "Yes") print "selected"; ?>><?php print _('Disabled'); ?></option>
            <option value="No" 	<?php if ($user->disabled == "No" || $POST->action == "add") print "selected"; ?>><?php print _('Enabled'); ?></option>
        </select>

        </td>
    	<td class="info2"><?php print _('You can disable user here'); ?>.</td>
	</tr>

    <!-- role -->
    <tr>
    	<td><?php print _('User role'); ?></td>
    	<td>
        <select name="role" class="form-control input-sm input-w-auto">
            <option value="Administrator"   <?php if ($user->role == "Administrator") print "selected"; ?>><?php print _('Administrator'); ?></option>
            <option value="User" 			<?php if ($user->role == "User" || $POST->action == "add") print "selected"; ?>><?php print _('Normal User'); ?></option>
        </select>

        </td>
        <td class="info2"><?php print _('Select user role'); ?>
	    	<ul>
		    	<li><?php print _('Administrator is almighty'); ?></li>
		    	<li><?php print _('Users have access defined based on groups'); ?></li>
		    </ul>
		</td>
	</tr>


	<!-- auth type -->
	<tr>
		<td><?php print _("Authentication method"); ?></td>
		<td>
			<select name="authMethod" id="authMethod" class="form-control input-sm input-w-auto">
			<?php
			foreach($auth_types as $type) {
				# match
				if($type->id==$user->authMethod)	{ print "<option value='$type->id' selected>$type->type ($type->description)</option>"; }
				else								{ print "<option value='$type->id'         >$type->type ($type->description)</option>"; }
			}
			?>
			</select>
		</td>
		<td class="info2"><?php print _("Select authentication method for user"); ?></td>
	</tr>

	<?php if ($User->settings->{'2fa_provider'}!=='none' && $user->{'2fa'} == "1") { ?>

    <tr>
    	<td style="padding-top:10px;"><?php print _('2fa enabled'); ?></td>
    	<td style="padding-top:10px;"><input type="checkbox" value="1" class="input-switch" name="2fa" <?php if($user->{'2fa'} == "1") { print 'checked'; } else { print "disabled"; } ?>></td>
    	<td style="padding-top:10px;" class="info2"><?php print _('Disable 2fa for user'); ?></td>
    </tr>
	<?php } ?>


	<?php if ($User->settings->dbversion >= 40 && $User->settings->{'passkeys'}=="1" && sizeof($user_passkeys)>0 && $POST->action!=="delete") { ?>
	<tr>
		<td colspan="3"><hr></td>
	    <tr>
	    	<td style="padding-top:10px;"><?php print _('Passkeys'); ?></td>
	    	<td style="padding-top:10px;">
	    	<?php
	    	foreach ($user_passkeys as $passkey) {
	    		$passkey->comment = is_null($passkey->comment) ? "-- Unknown --" : $passkey->comment;
	    		print "<input type='checkbox' name='delete-passkey-".$passkey->id."' value='1'> ";
	    		print $User->strip_input_tags($passkey->comment)."<br>";
	    	}
	    	?>
	    	</td>
	    	<td style="padding-top:10px;" class="info2"><?php print _('Check passkey you want to remove'); ?></td>
	    </tr>

	    <tr>
	    	<td style="padding-top:10px;"><?php print _('Passkey login only'); ?></td>
    		<td style="padding-top:10px;"><input type="checkbox" value="1" class="input-switch" name="passkey_only" <?php if($user->passkey_only == "1") { print 'checked'; } ?>></td>
	    	<td style="padding-top:10px;" class="info2"><?php print _('Select to only allow account login with passkey'); ?></td>
	    </tr>
	</tr>

	<?php } ?>

	<tr>
		<td colspan="3"><hr></td>
	</tr>

	</tbody>

    <!-- password -->
	<tbody id="user_password" <?php if($user->authMethod!="1" && isset($user->authMethod)) print "style='display:none'"; ?>>

    <tr class="password">
    	<td><?php print _('Password'); ?></td>
    	<td><input type="password" class="userPass form-control input-sm" name="password1"></td>
    	<td class="info2"><?php print _("User's password"); ?> (<a href="#" id="randomPass"><?php print _('click to generate random'); ?>!</a>)</td>
    </tr>

    <!-- password repeat -->
    <tr class="password">
    	<td><?php print _('Password'); ?></td>
    	<td><input type="password" class="userPass form-control input-sm" name="password2"></td>
    	<td class="info2"><?php print _('Re-type password'); ?></td>
    </tr>

    <!-- password change request -->
    <?php if($POST->action=="add") { ?>
    <tr class="password">
    	<td></td>
    	<td class="info2" colspan="2">
    		<input type="checkbox" name="passChange" value="On" checked>
			<?php print _('Require user to change password after initial login'); ?>
		</td>
    </tr>
    <?php } ?>
	<tr>
		<td colspan="3"><hr></td>
	</tr>
	</tbody>

	<tbody>
	<!-- Language -->
	<tr>
		<td><?php print _('Language'); ?></td>
		<td>
			<select name="lang" class="form-control input-sm input-w-auto">
				<?php
				foreach($langs as $lang) {
					if($lang->l_id==$user->lang)	{ print "<option value='$lang->l_id' selected>$lang->l_name ($lang->l_code)</option>"; }
					else							{ print "<option value='$lang->l_id'		 >$lang->l_name ($lang->l_code)</option>"; }
				}
				?>
			</select>
		</td>
		<td class="info2"><?php print _('Select language'); ?></td>
	</tr>

	<!-- select theme -->
	<tr>
		<td><?php print _('Theme'); ?></td>
		<td>
			<select name="theme" class="form-control input-sm input-w-auto">
				<option value="default"><?php print _("Default"); ?></option>
				<?php
				foreach($User->themes as $theme) {
					if($theme==$user->theme)	{ print "<option value='$theme' selected>$theme</option>"; }
					else						{ print "<option value='$theme'		    >$theme</option>"; }
				}
				?>
			</select>
		</td>
		<td class="info2"><?php print _('Select UI theme'); ?></td>
	</tr>

    <!-- send notification mail -->
    <tr>
    	<td><?php print _('Notification'); ?></td>
    	<td><input type="checkbox" name="notifyUser" value="on" <?php if($POST->action == "add") { print 'checked'; } elseif($POST->action == "delete") { print 'disabled="disabled"';} ?>></td>
    	<td class="info2"><?php print _('Send notification email to user with account details'); ?></td>
    </tr>
	</tbody>

	<!-- mailNotify -->
	<tbody id="user_notifications">
	<tr>
    	<td><?php print _('Mail State changes'); ?></td>
    	<td>
        <select name="mailNotify" class="form-control input-sm input-w-auto">
            <option value="No"><?php print _('No'); ?></option>
            <option value="Yes"  <?php if ($user->mailNotify == "Yes") print "selected='selected'"; ?>><?php print _('Yes'); ?></option>
        </select>


        </td>
        <td class="info2"><?php print _('Select yes to receive notification change mail for State change'); ?></td>
	</tr>

	<!-- mailNotifyChangelog -->
	<tr>
    	<td><?php print _('Mail Changelog'); ?></td>
    	<td>
        <select name="mailChangelog" class="form-control input-sm input-w-auto">
            <option value="No"><?php print _('No'); ?></option>
            <option value="Yes" <?php if ($user->mailChangelog == "Yes") print "selected='selected'"; ?>><?php print _('Yes'); ?></option>
        </select>


        </td>
        <td class="info2"><?php print _('Select yes to receive notification change mail for changelog'); ?></td>
	</tr>
	</tbody>





	<!-- groups -->
	<?php
	print $user->role=="Administrator" ?  "<tbody class='module_permissions' style='display:none'>" : "<tbody class='module_permissions'>";
	?>
	<tr>
		<td colspan="3"><hr><h5><strong><?php print _('Groups'); ?>:</strong></h5></td>
	</tr>
	<tr>
		<td style="vertical-align: top !important"></td>
		<td class="groups">
		<?php
		//print groups
		if($groups!==false) {
			//set groups
			$ugroups = db_json_decode($user->groups, true);
			$ugroups = $Admin->groups_parse_ids($ugroups);

			foreach($groups as $g) {
				# empty fix
				if(sizeof($ugroups) > 0) {
					if(in_array($g->g_id, $ugroups)) 	{ print "<input type='checkbox' name='group$g->g_id' checked>$g->g_name<br>"; }
					else 								{ print "<input type='checkbox' name='group$g->g_id'		>$g->g_name<br>"; }
				}
				else {
														{ print "<input type='checkbox' name='group$g->g_id'>$g->g_name<br>"; }
				}
			}
		}
		else {
			$Result->show("danger", _("No groups configured"), false);
		}

		?>
		</td>
		<td class="info2"><?php print _('Select to which groups the user belongs to'); ?></td>
	</tr>
	</tbody>




	<?php

	print $user->role=="Administrator" ?  "<tbody class='module_permissions' style='display:none'>" : "<tbody class='module_permissions'>";

	// Divider
	print '<tr>';
	print '	<td colspan="3"><hr><h5><strong>'._("Module permissions").':</strong></h5></td>';
	print '</tr>';

	// Modules permissions
	$perm_modules = [];
	// VLAN
	$perm_modules["perm_vlan"] = "VLAN";
	// VLAN
	$perm_modules["perm_l2dom"] = "L2Domains";
	// VRF
	$perm_modules["perm_vrf"]  = "VRF";
	// powerDNS
	if ($User->settings->enablePowerDNS==1)
	$perm_modules["perm_pdns"] = "PowerDNS";
	// devices
	$perm_modules["perm_devices"] = "Devices";
	// Racks
	if ($User->settings->enableRACK==1)
	$perm_modules["perm_racks"] = "Racks";
	// Circuits
	if ($User->settings->enableCircuits==1)
	$perm_modules["perm_circuits"] = "Circuits";
	// NAT
	if ($User->settings->enableNAT==1)
	$perm_modules["perm_nat"] = "NAT";
	// Customers
	if ($User->settings->enableCustomers==1)
	$perm_modules["perm_customers"] = "Customers";
	// Locations
	if ($User->settings->enableLocations==1)
	$perm_modules["perm_locations"] = "Locations";
	// PSTN
	if ($User->settings->enablePSTN==1)
	$perm_modules["perm_pstn"] = "PSTN";
	// Routing
	if ($User->settings->enableRouting==1)
	$perm_modules["perm_routing"] = "Routing";
	// Vaults
	if ($User->settings->enableVaults==1)
	$perm_modules["perm_vaults"] = "Vaults";

	// Set default module permissions
	foreach ($perm_modules as $key => $name) {
		$module_permissions[str_replace("perm_", "", $key)] = "0";
	}
	// Merge with user module permissions
	$user_module_permissions = db_json_decode($user->module_permissions, true);
	if (is_array($user_module_permissions)) {
		$module_permissions = array_merge($module_permissions, $user_module_permissions);
	}

	// loop
	foreach ($perm_modules as $key=>$name) {
		// print row
		print "<tr>";
		print "	<td>"._($name)."</td>";
		print "	<td>";
		print "		<select class='form-control input-sm input-w-auto' name='$key'>";
        foreach (array(0,1,2,3) as $p) {
			$selected = $p==$module_permissions[str_replace("perm_","",$key)] ? "selected" : "";
            print "<option value='$p' $selected>".$Subnets->parse_permissions ($p)."</option>";
        }
		print "		</select>";
		print "	</td>";
		print "	<td class='info2'>"._($name.' module permissions')."</td>";
		print "</tr>";
	}

	?>
	</tbody>

	<!-- Custom -->
	<?php
	if(sizeof($custom) > 0) {
		print '<tr>';
		print '	<td colspan="3"><hr></td>';
		print '</tr>';

		# count datepickers
		$timepicker_index = 0;

		# all my fields
		foreach($custom as $field) {
    		// create input > result is array (required, input(html), timepicker_index)
    		$custom_input = $Tools->create_custom_field_input ($field, (array) $user, $timepicker_index);
    		$timepicker_index = $custom_input['timepicker_index'];
            // print
			print "<tr>";
			print "	<td>".ucwords($field['name'])." ".$custom_input['required']."</td>";
			print "	<td>".$custom_input['field']."</td>";
			print "</tr>";
		}
	}
	?>

	<?php } ?>

</table>
</form>

</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class='btn btn-sm btn-default submit_popup <?php if($POST->action=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>' data-script="app/admin/users/edit-result.php" data-result_div="usersEditResult" data-form='usersEdit'>
			<i class="fa <?php if($POST->action=="add") { print "fa-plus"; } elseif ($POST->action=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print $User->get_post_action(); ?>
		</button>
	</div>

	<!-- Result -->
	<div id="usersEditResult"></div>
</div>