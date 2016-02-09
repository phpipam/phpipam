<?php

/**
 * Script to print add / edit / delete group
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

# get lang details
if($_POST['action']=="edit" || $_POST['action']=="delete")
$lang = (array) $Admin->fetch_object ("lang", "l_id", $_POST['langid']);

# set title
if($_POST['action'] == "edit")  		{ $title = 'Edit language'; }
elseif($_POST['action'] == "delete") 	{ $title = 'Delete language'; }
else 									{ $title = 'Add new language'; }
?>

<!-- header -->
<div class="pHeader"><?php print _($title); ?></div>

<!-- content -->
<div class="pContent">

	<form id="langEdit" name="langEdit">
	<table class="table table-noborder table-condensed">

	<!-- name -->
	<tr>
	    <td><?php print _('Language code'); ?></td>
	    <td><input type="text" name="l_code" class="form-control input-sm" value="<?php print @$lang['l_code']; ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?>></td>
    </tr>

    <!-- description -->
    <tr>
    	<td><?php print _('Language name'); ?></td>
    	<td>
    		<input type="text" name="l_name" class="form-control input-sm" value="<?php print @$lang['l_name']; ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?>>

    		<input type="hidden" name="l_id" value="<?php print $_POST['langid']; ?>">
    		<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
    		<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
    	</td>
    </tr>

</table>
</form>

</div>




<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="langEditSubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>

	<!-- Result -->
	<div class="langEditResult"></div>
</div>
