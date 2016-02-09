<?php

/**
 * Script to print add / edit / delete API
 *************************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->create_csrf_cookie ();

# ID must be numeric
if($_POST['action']!="add" && !is_numeric($_POST['appid'])) { $Result->show("danger", _("Invalid ID"), true, true); }

# fetch api for edit / add
if($_POST['action']!="add") {
	# fetch api details
	$api = $Admin->fetch_object ("api", "id", $_POST['appid']);
	# null ?
	$api===false ? $Result->show("danger", _("Invalid ID"), true) : null;
	# title
	$title =  ucwords($_POST['action']) .' '._('api').' '.$api->app_id;
} else {
	# generate new code
	$api = new StdClass;
	$api->app_code = str_shuffle(md5(microtime()));
	# title
	$title = _('Add new api key');
}
?>


<!-- header -->
<div class="pHeader"><?php print $title; ?></div>

<!-- content -->
<div class="pContent">

	<form id="apiEdit" name="apiEdit">
	<table class="groupEdit table table-noborder table-condensed">

	<!-- id -->
	<tr>
	    <td><?php print _('App id'); ?></td>
	    <td>
	    	<input type="text" name="app_id" class="form-control input-sm" value="<?php print @$api->app_id; ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?>>
	        <input type="hidden" name="id" value="<?php print $api->id; ?>">
    		<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
    		<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
	    </td>
       	<td class="info2"><?php print _('Enter application identifier'); ?></td>
    </tr>

	<!-- code -->
	<tr>
	    <td><?php print _('App code'); ?></td>
	    <td><input type="text" id="appcode" name="app_code" class="form-control input-sm"  value="<?php print @$api->app_code; ?>"  maxlength='32' <?php if($_POST['action'] == "delete") print "readonly"; ?>></td>
       	<td class="info2"><?php print _('Application code'); ?> <button class="btn btn-xs btn-default" id="regApiKey"><i class="fa fa-random"></i> <?php print _('Regenerate'); ?></button></td>
    </tr>

	<!-- permissions -->
	<tr>
	    <td><?php print _('App permissions'); ?></td>
	    <td>
	    	<select name="app_permissions" class="form-control input-sm input-w-auto">
	    	<?php
	    	$perms = array(0=>"Disabled",1=>"Read",2=>"Read / Write",3=>"Read / Write / Admin");
	    	foreach($perms as $k=>$p) {
		    	if($k==$api->app_permissions)	{ print "<option value='$k' selected='selected'>"._($p)."</option>"; }
		    	else							{ print "<option value='$k' 				   >"._($p)."</option>"; }
	    	}
	    	?>
	    	</select>
       	<td class="info2"><?php print _('Application permissions'); ?></td>
    </tr>

	<!-- Security -->
	<tr>
	    <td><?php print _('App security'); ?></td>
	    <td>
	    	<select name="app_security" class="form-control input-sm input-w-auto">
	    	<?php
	    	$perms = array(0=>"crypt",1=>"ssl",2=>"none",3=>"user");

	    	// user not yet supported
	    	unset($perms[3]);

	    	foreach($perms as $k=>$p) {
		    	if($p==$api->app_security)		{ print "<option value='$p' selected='selected'>"._($p)."</option>"; }
		    	else							{ print "<option value='$p' 				   >"._($p)."</option>"; }
	    	}
	    	?>
	    	</select>
       	<td class="info2"><?php print _('Application security'); ?></td>
    </tr>

    <!-- description -->
    <tr>
    	<td><?php print _('Description'); ?></td>
    	<td>
    		<input type="text" name="app_comment" class="form-control input-sm" value="<?php print @$api->app_comment; ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?>>
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
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="apiEditSubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>
	<!-- Result -->
	<div class="apiEditResult"></div>
</div>
