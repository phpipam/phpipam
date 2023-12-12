<?php

/**
 * Script to add users to group
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


# id must be numeric
if(!is_numeric($_POST['g_id']))		{ $Result->show("danger", _("Invalid ID"), true, true); }

# get group details
$group   = $Admin->fetch_object("userGroups", "g_id", $_POST['g_id']);
# users in group - array of ids
$existing = $Admin->group_fetch_users ($_POST['g_id']);
?>


<!-- header -->
<div class="pHeader"><?php print _('Remove users from group'); ?> <?php print $group->g_name; ?></div>


<!-- content -->
<div class="pContent">

	<?php if(sizeof($existing) > 0) { ?>

	<form id="groupRemoveUsers" name="groupRemoveUsers">
	<table class="groupEdit table table-condensed table-top">

	<tr>
		<th>
			<input type="hidden" name="gid" value="<?php print escape_input($_POST['g_id']); ?>">
		</th>
		<th><?php print _('Name'); ?></th>
		<th><?php print _('Username'); ?></th>
		<th><?php print _('Email'); ?></th>
	</tr>

	<?php
	# show existing
	foreach($existing as $m) {
		# get user details
		$u = (array) $Admin->fetch_object("users", "id", $m);

		print "<tr>";

		print "	<td>";
		print "	<input type='checkbox' name='user$u[id]'>";
		print "	</td>";

		print "	<td>$u[real_name]</td>";
		print "	<td>$u[username]</td>";
		print "	<td>$u[email]</td>";

		print "</tr>";
	}
	?>

    </table>
    </form>

    <?php } else { $Result->show("info", _('No users in this group'), false);  } ?>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<?php if(sizeof($existing) > 0) { ?>
		<button class='btn btn-sm btn-success submit_popup' data-script="app/admin/groups/remove-users-result.php" data-result_div="groupRemoveUsersResult" data-form='groupRemoveUsers'><?php print _('Remove selected users'); ?></button>
		<?php } ?>
	</div>

	<!-- Result -->
	<div id="groupRemoveUsersResult"></div>
</div>
