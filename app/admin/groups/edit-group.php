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
$csrf = $User->csrf_cookie ("create", "group");

# validate action
$Admin->validate_action ($_POST['action']);

# strip tags - XSS
$_POST = $User->strip_input_tags ($_POST);

# fetch group and set title
if($_POST['action']=="add") {
	$title = _('Add new group');
} else {
    # ID
    if(!is_numeric($_POST['id']))   $Result->show("danger", _("Invalid Id"), true, true);

	//fetch all group details
    $group = (array) $Admin->fetch_object("userGroups", "g_id", $_POST['id']);
    //false die
    $group!==false ? : $Result->show("danger", _("Invalid ID"), true, true);

	$title = ucwords($_POST['action']) .' '._('group').' '.$group['g_name'];
}
?>

<!-- header -->
<div class="pHeader"><?php print $title; ?></div>

<!-- content -->
<div class="pContent">

	<form id="groupEdit" name="groupEdit">
	<table class="groupEdit table table-noborder table-condensed">

	<!-- name -->
	<tr>
	    <td><?php print _('Group name'); ?></td>
	    <td><input type="text" name="g_name" class="form-control input-sm" value="<?php print @$group['g_name']; ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?>></td>
       	<td class="info2">
	   		<?php print _('Enter group name'); ?>
       	</td>
    </tr>

    <!-- description -->
    <tr>
    	<td><?php print _('Description'); ?></td>
    	<td>
    		<input type="text" name="g_desc" class="form-control input-sm" value="<?php print @$group['g_desc']; ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?>>

    		<input type="hidden" name="g_id" value="<?php print $_POST['id']; ?>">
    		<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
    		<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
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
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editGroupSubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>

	<!-- Result -->
	<div class="groupEditResult"></div>
</div>
