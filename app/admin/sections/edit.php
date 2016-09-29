<?php

/*
 * Print edit sections form
 *************************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Sections	= new Sections ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->csrf_cookie ("create", "section");

# strip tags - XSS
$_POST = $User->strip_input_tags ($_POST);

# validate action
$Admin->validate_action ($_POST['action'], true);

# fetch all sections for master section
$sections = $Sections->fetch_all_sections ();
# fetch groups
$groups   = $Admin->fetch_all_objects("userGroups", "g_id");
# fetch section
$section  = (array) $Sections->fetch_section (null, @$_POST['sectionId']);
?>

<!-- header -->
<div class="pHeader"><?php print ucwords(_($_POST['action'])); ?> <?php print _('Section'); ?></div>


<!-- content -->
<div class="pContent">

	<!-- form -->
	<form id="sectionEdit" name="sectionEdit">

		<!-- edit table -->
		<table class="table table-condensed table-noborder sectionEdit">

		<!-- section name -->
		<tr>
			<td><?php print _('Name'); ?></td>
			<td colspan="2">
				<input type="text" class='input-xlarge form-control input-sm input-w-250' name="name" value="<?php print @$section['name']; ?>" size="30" <?php if ($_POST['action'] == "delete" ) { print ' readonly '; } ?> placeholder="<?php print _('Section name'); ?>">
				<!-- hidden -->
				<input type="hidden" name="action" 	value="<?php print $_POST['action']; ?>">
				<input type="hidden" name="id" 		value="<?php print $_POST['sectionId']; ?>">
				<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
			</td>
		</tr>
		<!-- description -->
		<tr>
			<td><?php print _('Description'); ?></td>
			<td colspan="2">
				<input type="text" class='input-xlarge form-control input-sm input-w-250' name="description" value="<?php print @$section['description']; ?>" size="30" <?php if ($_POST['action'] == "delete") { print " readonly ";}?> placeholder="<?php print _('Section description'); ?>">
			</td>
		</tr>
		<!-- Master Subnet -->
		<tr>
			<td><?php print _('Parent'); ?></td>
			<td colspan="2">
				<select name="masterSection" class="form-control input-sm input-w-auto pull-left" <?php if($_POST['action']=="delete") print 'disabled="disabled"'; ?>>
					<option value="0">Root</option>
					<?php
					if($sections!==false) {
						foreach($sections as $s) {
							# show only roots and ignore self
							if($s->masterSection==0 && $s->id!=$_POST['sectionId']) {
								if($s->id==$section['masterSection'])	{ print "<option value='$s->id' selected='selected'>$s->name</option>"; }
								else									{ print "<option value='$s->id'>$s->name</option>"; }
							}
						}
					}
					?>
				</select>
				<span class="help-inline info2"><?php print _('Select parent section to create subsection'); ?></span>
			</td>
		</tr>

		<!-- Strict Mode -->
		<tr>
			<td><?php print _('Strict Mode'); ?></td>
			<td colspan="2">
				<select name="strictMode" class="input-small form-control input-sm input-w-auto pull-left" <?php if($_POST['action']=="delete") print 'disabled="disabled"'; ?>>
					<option value="1"><?php print _('Yes'); ?></option>
					<option value="0" <?php if(@$section['strictMode'] == "0") print "selected='selected'"; ?>><?php print _('No'); ?></option>
				</select>
				<span class="help-inline info2"><?php print _('No disables overlapping subnet checks. Subnets can be nested/created randomly. Anarchy.'); ?></span>
			</td>
		</tr>

		<!-- Show VLANs -->
		<tr>
			<td><?php print _('Show VLANs'); ?></td>
			<td colspan="2">
				<select name="showVLAN" class="input-small form-control input-sm input-w-auto  pull-left" <?php if($_POST['action']=="delete") print 'disabled="disabled"'; ?>>
					<option value="1"><?php print _('Yes'); ?></option>
					<option value="0" <?php if(@$section['showVLAN'] == "0") print "selected='selected'"; ?>><?php print _('No'); ?></option>
				</select>
				<span class="help-inline info2"><?php print _('Show list of VLANs and belonging subnets in subnet list'); ?></span>
			</td>
		</tr>

		<!-- Show VRFs -->
		<tr>
			<td><?php print _('Show VRFs'); ?></td>
			<td colspan="2">
				<select name="showVRF" class="input-small form-control input-sm input-w-auto  pull-left" <?php if($_POST['action']=="delete") print 'disabled="disabled"'; ?>>
					<option value="1"><?php print _('Yes'); ?></option>
					<option value="0" <?php if(@$section['showVRF'] == "0") print "selected='selected'"; ?>><?php print _('No'); ?></option>
				</select>
				<span class="help-inline info2"><?php print _('Show list of VRFs and belonging subnets in subnet list'); ?></span>
			</td>
		</tr>

		<!-- Subnet ordering -->
		<tr>
			<td class="title"><?php print _('Subnet ordering'); ?></td>
			<td colspan="2">
				<select name="subnetOrdering" class="form-control input-sm input-w-auto pull-left">
					<?php
					$opts = array(
						"default"			=> _("Default"),
						"subnet,asc"		=> _("Subnet, ascending"),
						"subnet,desc"		=> _("Subnet, descending"),
						"description,asc"	=> _("Description, ascending"),
						"description,desc"	=> _("Description, descending"),
					);

					foreach($opts as $key=>$line) {
						if($section['subnetOrdering'] == $key) 	{ print "<option value='$key' selected>$line</option>"; }
						else 									{ print "<option value='$key'>$line</option>"; }
					}

					?>
				</select>
				<span class="info2"><?php print _('How to order display of subnets'); ?></span>
			</td>
		</tr>

		<tr>
			<td colspan="3">
				<hr>
			</td>
		</tr>
		<!-- permissions -->
		<?php
		$permissions = strlen(@$section['permissions'])>1 ? $Sections->parse_section_permissions($section['permissions']) : "";

		# print for each group
		$m=0;

		if($groups) {
			foreach($groups as $g) {
				//cast
				$g = (array) $g;
				# structure
				print "<tr>";
				# title
				if($m == 0) { print "<td>"._('Permissions')."</td>"; }
				else		{ print "<td></td>"; }

				# name
				print "<td>$g[g_name]</td>";

				# line
				print "<td>";
				print "<span class='checkbox inline noborder'>";

				print "	<input type='radio' name='group$g[g_id]' value='0' checked> na";
				if(@$permissions[$g['g_id']]==1)	{ print " <input type='radio' name='group$g[g_id]' value='1' checked> ro"; }
				else								{ print " <input type='radio' name='group$g[g_id]' value='1'> ro"; }
				if(@$permissions[$g['g_id']]==2)	{ print " <input type='radio' name='group$g[g_id]' value='2' checked> rw"; }
				else								{ print " <input type='radio' name='group$g[g_id]' value='2'> rw"; }
				if(@$permissions[$g['g_id']]==3)	{ print " <input type='radio' name='group$g[g_id]' value='3' checked> rwa"; }
				else								{ print " <input type='radio' name='group$g[g_id]' value='3'> rwa"; }
				print "</span>";
				print "</td>";

				print "</tr>";

				$m++;
			}
		}
		else {
				print "<tr>";
				print "<td>"._('Permissions')."</td>";
				print "<td><div class='alert alert-info'>"._('No groups available')."</div></td>";
				print "</tr>";
			}
		?>

		<?php
		if($_POST['action'] == "edit") { ?>
		<!-- Apply to subnets -->
		<tr>
			<td colspan="3">
				<hr>
			</td>
		</tr>
		<tr>
			<td><?php print _('Delegate'); ?></td>
			<td colspan="2">
			<div class="checkbox">
				<input type="checkbox" name="delegate" class="input-switch" value="1" checked="checked">
			</div>
			</td>
		</tr>
        <tr class="warning2">
            <td></td>
            <td colspan="2">
            <?php $Result->show("info", _('Permission changes will be propagated to all nested subnets')."!", false); ?>
            </td>
        </tr>
		<?php } ?>

		</table>	<!-- end table -->
	</form>		<!-- end form -->

	<!-- delete warning -->
	<?php
	if ($_POST['action'] == "delete") {
		//print '<div class="alert alert-warning"><b>'._('Warning').'!</b><br>'._('Deleting Section will delete all belonging subnets and IP addresses').'!</div>' . "\n";
	}
	?>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger";} else { print "btn-success"; } ?>" id="editSectionSubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>
	<!-- result holder -->
	<div class="sectionEditResult"></div>
</div>


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