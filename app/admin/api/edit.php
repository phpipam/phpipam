<?php

/**
 * Script to print add / edit / delete API
 *************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "apiedit");

# validate action
$Admin->validate_action();

# ID must be numeric
if($POST->action!="add" && !is_numeric($POST->appid)) { $Result->show("danger", _("Invalid ID"), true, true); }

# fetch api for edit / add
if($POST->action!="add") {
	# fetch api details
	$api = $Admin->fetch_object ("api", "id", $POST->appid);
	# null ?
	$api===false ? $Result->show("danger", _("Invalid ID"), true) : null;
	# title
	$title = $User->get_post_action().' '._('api').' '.$api->app_id;
} else {
	# generate new code
	$api = new Params;
	$api->app_code = $User->Crypto->generate_html_safe_token(32);
	$api->app_permissions = 1;
	$api->app_security = "ssl_code";
	# title
	$title = _('Add new api key');
}
?>


<!-- header -->
<div class="pHeader"><?php print $title.$User->print_doc_link("API"); ?></div>

<!-- content -->
<div class="pContent">

	<form id="apiEdit" name="apiEdit">
	<table class="groupEdit table table-noborder table-condensed">

	<!-- id -->
	<tr>
	    <td><?php print _('App id'); ?></td>
	    <td>
	    	<input type="text" name="app_id" class="form-control input-sm" value="<?php print $api->app_id; ?>" <?php if($POST->action == "delete") print "readonly"; ?>>
	        <input type="hidden" name="id" value="<?php print $api->id; ?>">
    		<input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
    		<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
	    </td>
       	<td class="info2"><?php print _('Enter application identifier'); ?></td>
    </tr>

	<!-- code -->
	<tr>
	    <td><?php print _('App code'); ?></td>
	    <td><input type="text" id="appcode" name="app_code" class="form-control input-sm"  value="<?php print $api->app_code; ?>"  maxlength='32' <?php if($POST->action == "delete") print "readonly"; ?>></td>
       	<td class="info2"><?php print _('Application code'); ?> <button class="btn btn-xs btn-default" id="regApiKey"><i class="fa fa-random"></i> <?php print _('Regenerate'); ?></button></td>
    </tr>

	<!-- permissions -->
	<tr>
	    <td><?php print _('App permissions'); ?></td>
	    <td>
	    	<select name="app_permissions" class="form-control input-sm input-w-auto">
	    	<?php
	    	$perms = array(0=>_("Disabled"),1=>_("Read"),2=>_("Read / Write"),3=>_("Read / Write / Admin"));
	    	foreach($perms as $k=>$p) {
		    	if($k==$api->app_permissions)	{ print "<option value='$k' selected='selected'>".$p."</option>"; }
		    	else							{ print "<option value='$k' 				   >".$p."</option>"; }
	    	}
	    	?>
	    	</select>
	    </td>
       	<td class="info2"><?php print _('Application permissions'); ?></td>
    </tr>

	<!-- Security -->
	<tr>
	    <td><?php print _('App security'); ?></td>
	    <td>
	    	<select name="app_security" class="form-control input-sm input-w-auto">
	    	<?php
	    	$perms = array("ssl_token"=>_("SSL with User token"), "ssl_code"=>_("SSL with App code token"), "crypt"=>_("Encrypted"), "none"=>_("User token"));

	    	foreach($perms as $k=>$p) {
		    	if($k==$api->app_security)		{ print "<option value='$k' selected='selected'>$p</option>"; }
		    	else							{ print "<option value='$k' 				   >$p</option>"; }
	    	}
	    	?>
	    	</select>
	    </td>
       	<td class="info2"><?php print _('Application security'); ?></td>
    </tr>

	<!-- lock -->
	<tr>
	    <td><?php print _('Transaction lock'); ?></td>
	    <td>
	    	<select name="app_lock" class="form-control input-sm input-w-auto">
	    	<?php
	    	$perms = array(0=>_("No"),1=>_("Yes"));
	    	foreach($perms as $k=>$p) {
		    	if($k==$api->app_lock)	{ print "<option value='$k' selected='selected'>".$p."</option>"; }
		    	else					{ print "<option value='$k' 				   >".$p."</option>"; }
	    	}
	    	?>
	    	</select>
	    </td>
       	<td class="info2"><?php print _('Lock POST transactions'); ?></td>
    </tr>

	<!-- lock wait -->
	<tr>
	    <td><?php print _('Lock timeout'); ?></td>
	    <td>
	    	<input name="app_lock_wait" class="form-control input-sm input-w-auto" value="<?php print $api->app_lock_wait; ?>">
	    </td>
       	<td class="info2"><?php print _('Seconds to wait for transaction lock to clear'); ?></td>
    </tr>

	<!-- app_nest_custom_fields -->
	<tr>
	    <td><?php print _('Nest custom fields'); ?></td>
	    <td>
	    	<select name="app_nest_custom_fields" class="form-control input-sm input-w-auto">
	    	<?php
	    	$perms = array(0=>_("No"),1=>_("Yes"));
	    	foreach($perms as $k=>$p) {
		    	if($k==$api->app_nest_custom_fields)	{ print "<option value='$k' selected='selected'>".$p."</option>"; }
		    	else									{ print "<option value='$k' 				   >".$p."</option>"; }
	    	}
	    	?>
	    	</select>
	    </td>
       	<td class="info2"><?php print _('Group custom fields to separate item in result'); ?></td>
    </tr>

	<!-- app_show_links -->
	<tr>
	    <td><?php print _('Show links'); ?></td>
	    <td>
	    	<select name="app_show_links" class="form-control input-sm input-w-auto">
	    	<?php
	    	$perms = array(0=>_("No"),1=>_("Yes"));
	    	foreach($perms as $k=>$p) {
		    	if($k==$api->app_show_links)	{ print "<option value='$k' selected='selected'>".$p."</option>"; }
		    	else							{ print "<option value='$k' 				   >".$p."</option>"; }
	    	}
	    	?>
	    	</select>
	    </td>
       	<td class="info2"><?php print _('Show links in result (override with ?links=true)'); ?></td>
    </tr>

    <!-- description -->
    <tr>
    	<td><?php print _('Description'); ?></td>
    	<td>
    		<input type="text" name="app_comment" class="form-control input-sm" value="<?php print $api->app_comment; ?>" <?php if($POST->action == "delete") print "readonly"; ?>>
    	</td>
    	<td class="info2"><?php print _('Enter description'); ?></td>
    </tr>

</table>
</form>

</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class='btn btn-sm btn-default submit_popup <?php if($POST->action=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>' data-script="app/admin/api/edit-result.php" data-result_div="apiEditResult" data-form='apiEdit'>
			<i class="fa <?php if($POST->action=="add") { print "fa-plus"; } elseif ($POST->action=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print $User->get_post_action(); ?>
		</button>

	</div>
	<!-- Result -->
	<div id="apiEditResult"></div>
</div>
