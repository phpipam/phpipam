<?php

/**
 * Script to print add / edit / delete widget
 *************************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->csrf_cookie ("create", "widget");

# strip tags - XSS
$_POST = $User->strip_input_tags ($_POST);

# validate action
$Admin->validate_action ($_POST['action'], true);

# fetch widget
if($_POST['action']!="add") {
	$w = $Admin->fetch_object ("widgets", "wid", $_POST['wid']);
	$w!==false ? : $Result->show("danger", _("Invalid ID"), true, true);
	$w = (array) $w;
}
?>

<!-- header -->
<div class="pHeader"><?php print ucwords($_POST['action'])." widget"; ?></div>

<!-- content -->
<div class="pContent">

	<form id="widgetEdit" name="widgetEdit">
	<table class="table table-noborder table-condensed">

	<!-- name -->
	<tr>
	    <td><?php print _('Title'); ?></td>
	    <td><input class="form-control input-sm input-w-250" type="text" name="wtitle" value="<?php print @$w['wtitle']; ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?>></td>
    </tr>

    <!-- description -->
    <tr>
    	<td><?php print _('Description'); ?></td>
    	<td>
    		<input class="form-control input-sm input-w-250" type="text" name="wdescription" value="<?php print @$w['wdescription']; ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?>>

    		<input type="hidden" name="wid" value="<?php print $_POST['wid']; ?>">
    		<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
    		<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
    	</td>
    </tr>

	<!-- File -->
	<tr>
	    <td><?php print _('File'); ?></td>
	    <td><input class="form-control input-sm input-w-250" type="text" name="wfile" value="<?php print @$w['wfile']; ?>.php" <?php if($_POST['action'] == "delete") print "readonly"; ?>></td>
    </tr>

	<!-- params -->
	<tr>
	    <td><?php print _('Parameters'); ?></td>
	    <td><input class="form-control input-sm input-w-250" type="text" name="wparams" value="<?php print @$w['wparams']; ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?>></td>
    </tr>

	<!-- Admin -->
	<tr>
	    <td><?php print _('Admin only'); ?></td>
	    <td>
	    	<select name="wadminonly" class="form-control input-sm input-w-auto">
	    		<option value="no"  <?php if(@$w['wadminonly']=='no')  print "selected='selected'"; ?>><?php print _('No'); ?></option>
	    		<option value="yes" <?php if(@$w['wadminonly']=='yes') print "selected='selected'"; ?>><?php print _('Yes'); ?></option>

	    	</select>
	    </td>
    </tr>

	<!-- Active -->
	<tr>
	    <td><?php print _('Active'); ?></td>
	    <td>
	    	<select name="wactive" class="form-control input-sm input-w-auto">
	    		<option value="no"  <?php if(@$w['wactive']=='no')  print "selected='selected'"; ?>><?php print _('No'); ?></option>
	    		<option value="yes" <?php if(@$w['wactive']=='yes') print "selected='selected'"; ?>><?php print _('Yes'); ?></option>

	    	</select>
	    </td>
    </tr>

	<!-- Link to file -->
	<tr>
	    <td><?php print _('Link to page'); ?></td>
	    <td>
	    	<select name="whref" class="form-control input-sm input-w-auto">
	    		<option value="no"  <?php if(@$w['whref']=='no')  print "selected='selected'"; ?>><?php print _('No'); ?></option>
	    		<option value="yes" <?php if(@$w['whref']=='yes') print "selected='selected'"; ?>><?php print _('Yes'); ?></option>

	    	</select>
	    </td>
    </tr>

	<!-- Size -->
	<tr>
	    <td><?php print _('Widget size'); ?></td>
	    <td>
	    	<select name="wsize" class="form-control input-sm input-w-auto">
	    		<option value="4"  <?php if(@$w['wsize']=='4')  print "selected='selected'"; ?>>25%</option>
	    		<option value="6"  <?php if(@$w['wsize']=='6')  print "selected='selected'"; ?>>50%</option>
	    		<option value="8"  <?php if(@$w['wsize']=='8')  print "selected='selected'"; ?>>75%</option>
	    		<option value="12" <?php if(@$w['wsize']=='12') print "selected='selected'"; ?>>100%</option>
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
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="widgetEditSubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>

	<!-- Result -->
	<div class="widgetEditResult"></div>
</div>
