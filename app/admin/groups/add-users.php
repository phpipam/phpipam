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
# not in group - array of ids
$missing = $Admin->group_fetch_missing_users ($_POST['g_id']);
?>


<!-- header -->
<div class="pHeader"><?php print _('Add users to group'); ?> <?php print $group->g_name; ?></div>


<!-- content -->
<div class="pContent">

	<?php if(sizeof($missing) > 0) { ?>

	<form id="groupAddUsers" name="groupAddUsers">
	<table class="groupEdit table table-condensed table-hover table-top">

	<tr>
		<th>
			<input type="hidden" name="gid" value="<?php print escape_input($_POST['g_id']); ?>">
		</th>
		<th><?php print _('Name'); ?></th>
		<th><?php print _('Username'); ?></th>
		<th><?php print _('Email'); ?></th>
	</tr>

	<?php
	# show missing
	foreach($missing as $k=>$m) {
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

    <?php } else { $Result->show("info", _('No available users to add to group'), false); } ?>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _("Cancel"); ?></button>
		<?php if(sizeof($missing) > 0) { ?>
		<button class='btn btn-sm btn-success submit_popup' data-script="app/admin/groups/add-users-result.php" data-result_div="groupAddUsersResult" data-form='groupAddUsers'><?php print _("Add selected users"); ?></button>
		<?php } ?>
	</div>

	<!-- Result -->
	<div id="groupAddUsersResult"></div>
</div>
