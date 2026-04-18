<?php

/**
 * Script to print add / edit / delete group
 *************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools      = new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# verify that user has permission to module
$User->check_module_permissions ("devices", User::ACCESS_R, true, false);

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "group");
# validate action
$Admin->validate_action(false);

# fetch group and set title
if($POST->action=="add") {
	$title = _('Add new group');
} else {
    # ID
    if(!is_numeric($POST->g_id))   $Result->show("danger", _("Invalid Id"), true, true);

	//fetch all group details
    $group = (array) $Tools->fetch_object("deviceGroups", "id", $POST->g_id);
    //false die
    $group !== false ? : $Result->show("danger", _("Invalid ID"), true, true);

	$title = $User->get_post_action().' '._('group').' ' . "<b><i>" . $group['name'] . "</i></b>";
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
	    <td><input type="text" name="name" class="form-control input-sm" value="<?php print @$group['name']; ?>" <?php if($POST->action == "delete") print "readonly"; ?>></td>
       	<td class="info2"><?php print _('Enter group name'); ?></td>
    </tr>

    <!-- description -->
    <tr>
    	<td><?php print _('Description'); ?></td>
    	<td>
    		<input type="text" name="desc" class="form-control input-sm" value="<?php print @$group['desc']; ?>" <?php if($POST->action == "delete") print "readonly"; ?>>

    		<input type="hidden" name="id" value="<?php print escape_input($POST->g_id); ?>">
    		<input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
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
        <button class='btn btn-sm btn-default submit_popup <?php if($POST->action=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>' data-script="app/admin/device-groups/edit-group-result.php" data-result_div="groupEditResult" data-form='groupEdit'>
            <i class="fa <?php if($POST->action=="add") { print "fa-plus"; } elseif ($POST->action=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print $User->get_post_action(); ?>
        </button>
	</div>

	<!-- Result -->
	<div id="groupEditResult"></div>
</div>
