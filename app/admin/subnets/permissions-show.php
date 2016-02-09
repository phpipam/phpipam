<?php

/*
 * Print edit subnet
 *********************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Sections	= new Sections ($Database);
$Subnets	= new Subnets ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->create_csrf_cookie ();


# ID must be numeric
if(!is_numeric($_POST['subnetId']))	{ $Result->show("danger", _("Invalid ID"), true, true); }

# get all groups
$groups = $Admin->fetch_all_objects ("userGroups", "g_id");
# get subnet details
$subnet = $Subnets->fetch_subnet(null, $_POST['subnetId']);
?>


<script type="text/javascript">
$(document).ready(function() {
/* bootstrap switch */
var switch_options = {
	onText: "Yes",
	offText: "No",
    onColor: 'default',
    offColor: 'default',
    size: "mini"
};

$(".input-switch").bootstrapSwitch(switch_options);

$('.input-switch').on('switchChange.bootstrapSwitch', function (e, data) {
	// get state from both
	var state = ($(".input-switch").bootstrapSwitch('state'));
	// change
	if (state==true)	{ $("tr.warning2").removeClass("hidden"); }
	else            	{ $("tr.warning2").addClass("hidden"); }
});
});
</script>



<!-- header -->
<div class="pHeader"><?php print $subnet->isFolder==1 ? _('Manage folder permissions') : _('Manage subnet permissions'); ?></div>

<!-- content -->
<div class="pContent">
	<?php
	if($subnet->isFolder==1)	{ print _('Manage permissions for folder')." $subnet->description"; }
	else						{ print _('Manage permissions for subnet'); ?> <?php print $Subnets->transform_to_dotted($subnet->subnet)."/".$subnet->mask." ($subnet->description)"; }
	?>
	<hr>

	<form id="editSubnetPermissions">
	<table class="editSubnetPermissions table table-noborder table-condensed">

	<?php
	# parse permissions
	if(strlen($subnet->permissions)>1) 	{ $permissons = $Sections->parse_section_permissions($subnet->permissions); }
	else 								{ $permissons = ""; }

	# print each group
	if($groups) {
		foreach($groups as $g) {
			//cast
			$g = (array) $g;

			print "<tr>";
			print "	<td>$g[g_name]</td>";
			print "	<td>";

			print "<span class='checkbox inline noborder'>";

			print "	<input type='radio' name='group$g[g_id]' value='0' checked> na";
			if(@$permissons[$g['g_id']]==1)	{ print " <input type='radio' name='group$g[g_id]' value='1' checked> ro"; }
			else							{ print " <input type='radio' name='group$g[g_id]' value='1'> ro"; }
			if(@$permissons[$g['g_id']]==2)	{ print " <input type='radio' name='group$g[g_id]' value='2' checked> rw"; }
			else							{ print " <input type='radio' name='group$g[g_id]' value='2'> rw"; }
			if(@$permissons[$g['g_id']]==3)	{ print " <input type='radio' name='group$g[g_id]' value='3' checked> rwa"; }
			else							{ print " <input type='radio' name='group$g[g_id]' value='3'> rwa"; }
			print "</span>";

			# hidden
			print "<input type='hidden' name='subnetId' value='$_POST[subnetId]'>";

			print "	</td>";
			print "</tr>";
		}
	} else {
		print "<tr>";
		print "	<td colspan='2'><span class='alert alert-info'>"._('No groups available')."</span></td>";
		print "</tr>";
	}
	?>
	<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">

    <!-- set parameters to slave subnets -->
    <?php if($Subnets->has_slaves($_POST['subnetId'])) { ?>
    <tr>
        <td colspan="2" class="hr"><hr></td>
    </tr>
    <tr>
        <td><?php print _('Propagate changes'); ?></td>
        <td>
            <input type="checkbox" name="set_inheritance" class="input-switch" value="Yes" checked="checked">
        </td>
    </tr>
    <tr class="warning2">
        <td colspan="2">
        <?php $Result->show("info", _('Permissions for all nested subnets will be overridden')."!", false); ?>
        </td>
    </tr>
    <tr>
        <td colspan="2" class="hr"><hr></td>
    </tr>
    <?php } ?>

    </table>
    </form>

</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default btn-success editSubnetPermissionsSubmit"><i class="fa fa-check"></i> <?php print _('Set permissions'); ?></button>
	</div>

	<div class="editSubnetPermissionsResult"></div>
</div>