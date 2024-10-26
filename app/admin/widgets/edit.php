<?php

/**
 * Script to print add / edit / delete widget
 *************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "widget");

# validate action
$Admin->validate_action();

# fetch widget
if($POST->action!="add") {
	$w = $Admin->fetch_object ("widgets", "wid", $POST->wid);
	$w!==false ? : $Result->show("danger", _("Invalid ID"), true, true);
} else {
	$w = new Params();
}
?>

<!-- header -->
<div class="pHeader"><?php print $User->get_post_action().' '._('widget'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="widgetEdit" name="widgetEdit">
	<table class="table table-noborder table-condensed">

	<!-- name -->
	<tr>
	    <td><?php print _('Title'); ?></td>
	    <td><input class="form-control input-sm input-w-250" type="text" name="wtitle" value="<?php print $w->wtitle; ?>" <?php if($POST->action == "delete") print "readonly"; ?>></td>
    </tr>

    <!-- description -->
    <tr>
    	<td><?php print _('Description'); ?></td>
    	<td>
    		<input class="form-control input-sm input-w-250" type="text" name="wdescription" value="<?php print $w->wdescription; ?>" <?php if($POST->action == "delete") print "readonly"; ?>>

    		<input type="hidden" name="wid" value="<?php print escape_input($POST->wid); ?>">
    		<input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
    		<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
    	</td>
    </tr>

	<!-- File -->
	<tr>
	    <td><?php print _('File'); ?></td>
	    <td><input class="form-control input-sm input-w-250" type="text" name="wfile" value="<?php print $w->wfile; ?>.php" <?php if($POST->action == "delete") print "readonly"; ?>></td>
    </tr>

	<!-- params -->
	<tr>
	    <td><?php print _('Parameters'); ?></td>
	    <td><input class="form-control input-sm input-w-250" type="text" name="wparams" value="<?php print $w->wparams; ?>" <?php if($POST->action == "delete") print "readonly"; ?>></td>
    </tr>

	<!-- Admin -->
	<tr>
	    <td><?php print _('Admin only'); ?></td>
	    <td>
	    	<select name="wadminonly" class="form-control input-sm input-w-auto">
	    		<option value="no"  <?php if($w->wadminonly=='no')  print "selected='selected'"; ?>><?php print _('No'); ?></option>
	    		<option value="yes" <?php if($w->wadminonly=='yes') print "selected='selected'"; ?>><?php print _('Yes'); ?></option>

	    	</select>
	    </td>
    </tr>

	<!-- Active -->
	<tr>
	    <td><?php print _('Active'); ?></td>
	    <td>
	    	<select name="wactive" class="form-control input-sm input-w-auto">
	    		<option value="no"  <?php if($w->wactive=='no')  print "selected='selected'"; ?>><?php print _('No'); ?></option>
	    		<option value="yes" <?php if($w->wactive=='yes') print "selected='selected'"; ?>><?php print _('Yes'); ?></option>

	    	</select>
	    </td>
    </tr>

	<!-- Link to file -->
	<tr>
	    <td><?php print _('Link to page'); ?></td>
	    <td>
	    	<select name="whref" class="form-control input-sm input-w-auto">
	    		<option value="no"  <?php if($w->whref=='no')  print "selected='selected'"; ?>><?php print _('No'); ?></option>
	    		<option value="yes" <?php if($w->whref=='yes') print "selected='selected'"; ?>><?php print _('Yes'); ?></option>

	    	</select>
	    </td>
    </tr>

	<!-- Size -->
	<tr>
	    <td><?php print _('Widget size'); ?></td>
	    <td>
	    	<select name="wsize" class="form-control input-sm input-w-auto">
	    		<option value="4"  <?php if($w->wsize=='4')  print "selected='selected'"; ?>>33%</option>
	    		<option value="6"  <?php if($w->wsize=='6')  print "selected='selected'"; ?>>50%</option>
	    		<option value="8"  <?php if($w->wsize=='8')  print "selected='selected'"; ?>>66%</option>
	    		<option value="12" <?php if($w->wsize=='12') print "selected='selected'"; ?>>100%</option>
	    	</select>
	    </td>
    </tr>

</table>
</form>

</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class='btn btn-sm btn-default submit_popup <?php if($POST->action=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>' data-script="app/admin/widgets/edit-result.php" data-result_div="widgetEditResult" data-form='widgetEdit'>
			<i class="fa <?php if($POST->action=="add") { print "fa-plus"; } elseif ($POST->action=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print $User->get_post_action(); ?>
		</button>

	</div>

	<!-- Result -->
	<div id="widgetEditResult"></div>
</div>
