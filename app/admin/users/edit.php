<?php

/**
 * Script to print add / edit / delete users
 *************************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

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
$csrf = $User->csrf_cookie ("create", "user");


# fetch custom fields
$custom 	= $Tools->fetch_custom_fields('users');
# fetch all languages
$langs 		= $Admin->fetch_all_objects ("lang", "l_id");
# fetch all auth types
$auth_types = $Admin->fetch_all_objects ("usersAuthMethod", "id");
# fetch all groups
$groups		= $Admin->fetch_all_objects ("userGroups", "g_id");


# set header parameters and fetch user
if($_POST['action']!="add") {
	$user = $Admin->fetch_object ("users", "id", $_POST['id']);
	//false
	if($user===false)		{ $Result->show("danger", _("Invalid ID"), true, true); }
	else {
		$user = (array) $user;
	}
}
else {
	$user = array();
	//set default lang
	$user['lang']=$User->settings->defaultLang;
}
?>

<script type="text/javascript">
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
<div class="pHeader"><?php print ucwords($_POST['action'])." "._('user'); ?></div>


<!-- content -->
<div class="pContent">

	<form id="usersEdit" name="usersEdit">
	<table class="usersEdit table table-noborder table-condensed">

	<tbody>
	<!-- real name -->
	<tr>
	    <td><?php print _('Real name'); ?></td>
	    <td><input type="text" class="form-control input-sm" name="real_name" value="<?php print @$user['real_name']; ?>"></td>
       	<td class="info2"><?php print _('Enter users real name'); ?></td>
    </tr>

    <!-- username -->
    <tr>
    	<td><?php print _('Username'); ?></td>
    	<td><input type="text" class="form-control input-sm" name="username" value="<?php print @$user['username']; ?>" <?php if($_POST['action']=="edit"||$_POST['action']=="delete") print 'readonly'; ?>></td>
    	<td class="info2">
    		<a class='btn btn-xs btn-default adsearchuser' rel='tooltip' title='Search AD for user details'><i class='fa fa-search'></i></a>
			<?php print _('Enter username'); ?>
		</td>
    </tr>

    <!-- email -->
    <tr>
    	<td><?php print _('e-mail'); ?></td>
    	<td><input type="text" class="form-control input-sm input-w-250" name="email" value="<?php print @$user['email']; ?>"></td>
    	<td class="info2"><?php print _('Enter users email address'); ?></td>
    </tr>

    <!-- role -->
    <tr>
    	<td><?php print _('User role'); ?></td>
    	<td>
        <select name="role" class="form-control input-sm input-w-auto">
            <option value="Administrator"   <?php if (@$user['role'] == "Administrator") print "selected"; ?>><?php print _('Administrator'); ?></option>
            <option value="User" 			<?php if (@$user['role'] == "User" || $_POST['action'] == "add") print "selected"; ?>><?php print _('Normal User'); ?></option>
        </select>


        <input type="hidden" name="userId" value="<?php print @$user['id']; ?>">
        <input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
        <input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">

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
				if($type->id==@$user['authMethod'])	{ print "<option value='$type->id' selected>$type->type ($type->description)</option>"; }
				else								{ print "<option value='$type->id'         >$type->type ($type->description)</option>"; }
			}
			?>
			</select>
		</td>
		<td class="info2"><?php print _("Select authentication method for user"); ?></td>
	</tr>

	<tr>
		<td colspan="3"><hr></td>
	</tr>

	</tbody>

    <!-- password -->
	<tbody id="user_password" <?php if(@$user['authMethod']!="1" && isset($user['authMethod'])) print "style='display:none'"; ?>>

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
    <?php if($_POST['action']=="add") { ?>
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
					if($lang->l_id==$user['lang'])	{ print "<option value='$lang->l_id' selected>$lang->l_name ($lang->l_code)</option>"; }
					else							{ print "<option value='$lang->l_id'		 >$lang->l_name ($lang->l_code)</option>"; }
				}
				?>
			</select>
		</td>
		<td class="info2"><?php print _('Select language'); ?></td>
	</tr>

    <!-- send notification mail -->
    <tr>
    	<td><?php print _('Notification'); ?></td>
    	<td><input type="checkbox" name="notifyUser" value="on" <?php if($_POST['action'] == "add") { print 'checked'; } else if($_POST['action'] == "delete") { print 'disabled="disabled"';} ?>></td>
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
            <option value="Yes"  <?php if (@$user['mailNotify'] == "Yes") print "selected='selected'"; ?>><?php print _('Yes'); ?></option>
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
            <option value="Yes" <?php if (@$user['mailChangelog'] == "Yes") print "selected='selected'"; ?>><?php print _('Yes'); ?></option>
        </select>


        </td>
        <td class="info2"><?php print _('Select yes to receive notification change mail for changelog'); ?></td>
	</tr>
	</tbody>

	<!-- groups -->
	<tbody>
	<tr>
		<td colspan="3"><hr></td>
	</tr>
	<tr>
		<td><?php print _('Groups'); ?></td>
		<td class="groups">
		<?php
		//print groups
		if($groups!==false) {
			//set groups
			$ugroups = json_decode(@$user['groups'], true);
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

	<!-- vlans -->
	<tr>
		<td colspan="3"><hr></td>
	</tr>
	<tr>
    	<td><?php print _("VLANs"); ?></td>
    	<td>
            <input type="checkbox" class="input-switch" value="Yes" name="editVlan" <?php if($user['editVlan'] == "Yes") print 'checked'; ?>>
    	</td>
		<td class="info2"><?php print _('Select to allow user to manage VLANs'); ?></td>
	</tr>

	<!-- pdns -->
    <?php if ($User->settings->enablePowerDNS==1) { ?>
	<tr>
    	<td><?php print _("PowerDNS"); ?></td>
    	<td>
            <input type="checkbox" class="input-switch" value="Yes" name="pdns" <?php if($user['pdns'] == "Yes") print 'checked'; ?>>
    	</td>
		<td class="info2"><?php print _('Select to allow user to create DNS records'); ?></td>
	</tr>
    <?php } ?>

	<!-- pstn -->
    <?php if ($User->settings->enablePSTN==1) { ?>
	<tr>
    	<td><?php print _("PSTN");?></td>
    	<td>
        	<select class="form-control input-sm input-w-auto" name="pstn">
            <?php
            foreach (array(0,1,2,3) as $p) {
                $selected = $p==$user['pstn'] ? "selected" : "";
                print "<option value='$p' $selected>".$Subnets->parse_permissions ($p)."</option>";
            }
            ?>
        	</select>
    	</td>
		<td class="info2"><?php print _('Select to allow user to manage PSTN numbers'); ?></td>
	</tr>
    <?php } ?>

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
    		$custom_input = $Tools->create_custom_field_input ($field, $user, $_POST['action'], $timepicker_index);
    		// add datepicker index
    		$timepicker_index = $timepicker_index + $custom_input['timepicker_index'];
            // print
			print "<tr>";
			print "	<td>".ucwords($field['name'])." ".$custom_input['required']."</td>";
			print "	<td>".$custom_input['field']."</td>";
			print "</tr>";
		}
	}
	?>
	</tbody>


</table>
</form>

</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editUserSubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>

	<!-- Result -->
	<div class="usersEditResult"></div>
</div>