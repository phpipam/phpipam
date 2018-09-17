<?php

/**
 *	Print all available VLAN groups
 ************************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# make sue user can edit
if ($User->is_admin(false)==false && $User->user->editVlan!="Yes") {
    $Result->show("danger", _("Not allowed to change VLANs"), true, true);
}

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "vlan");

# strip tags - XSS
$_POST = $User->strip_input_tags ($_POST);

# validate action
$Admin->validate_action ($_POST['action'], true);

# fetch vlan details
$vlanGroup = $Admin->fetch_object ("vlanGroups", "id", @$_POST['groupId']);
$vlanGroup = $vlan!==false ? (array) $vlanGroup : array();

# set readonly flag
$readonly = $_POST['action']=="delete" ? "readonly" : "";
$formId = "vlanManagementEdit";

# domain
if(!isset($_POST['domain'])) 	{ $_POST['domain']=1; }

# fetch l2 domain
if($_POST['action']=="add") {
	# all
	if (@$_POST['domain']=="all") {
		$vlan_domains = $Admin->fetch_all_objects("vlanDomains");
	} else {
		$vlan_domain = $Admin->fetch_object("vlanDomains", "id", $_POST['domain']);
	}
} else {
		$vlan_domain = $Admin->fetch_object("vlanDomains", "id", $vlanGroup["domainId"]);
}

//if($vlan_domain===false)			{ $Result->show("danger", _("Invalid ID"), true, true); }
?>

<script type="text/javascript">
$(document).ready(function(){
     if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }
});
</script>


<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('VLAN Group'); ?></div>

<!-- content -->
<div class="pContent">
	<form id="<?php print $formId; ?>">

	<table id="vlanManagementEdit2" class="table table-noborder table-condensed">
	<!-- domain -->
	<tr>
		<td><?php print _('l2 domain'); ?></td>
		<th>
		<?php
		# not all
		if (@$_POST['domain']!="all") {
			print $vlan_domain->name." (".$vlan_domain->description.")";
		} else {
			print "<select name='domainId' class='form-control input-sm'>";
			foreach ($vlan_domains as $d) {
				print "<option value='$d->id'>$d->name</option>";
			}
			print "</select>";
		}
		?>
		</th>
	</tr>
	<tr>
		<td colspan="2"><hr></td>
	</tr>

	<!-- Group name  -->
	<tr>
		<td><?php print _('Group name'); ?></td>
		<td>
			<input type="text" class="name form-control input-sm" name="name" placeholder="<?php print _('VLAN Group name'); ?>" value="<?php print $Tools->strip_xss(@$vlanGroup["name"]); ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<!-- First VLAN number  -->
	<tr>
		<td><?php print _('First VLAN number'); ?></td>
		<td>
			<input type="text" class="firstVlan form-control input-sm" name="firstVlan" placeholder="<?php print _('First VLAN number'); ?>" value="<?php print $Tools->strip_xss(@$vlanGroup["firstVlan"]); ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<!-- Last VLAN number -->
	<tr>
		<td><?php print _('Last VLAN number'); ?></td>
		<td>
			<input type="text" class="lastVlan form-control input-sm" name="lastVlan" placeholder="<?php print _('Last VLAN number'); ?>" value="<?php print $Tools->strip_xss(@$vlanGroup["lastVlan"]); ?>" <?php print $readonly; ?>>
			<input type="hidden" name="vlanId" value="<?php print @$_POST['vlanId']; ?>">
			<?php if(@$_POST['domain']!=="all") { ?>
			<input type="hidden" name="domainId" value="<?php print $vlan_domain->id; ?>">
			<?php } ?>
			<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
			<input type="hidden" name="id" value="<?php print $_POST['groupId'] ?>">
		</td>
	</tr>

	<?php if($_POST['action']=="add" || $_POST['action']=="edit") { ?>
    <!-- require unique -->
    <tr>
    	<td><?php print _('Allow overlaps'); ?></td>
    	<td>
	    	<input type="checkbox" name="overrideOverlapCheck">
	    	<span class="text-muted">Allow overlapping VLAN groups</span>
	    </td>
    </tr>
	<?php } ?>

	</table>
	</form>

	<?php
	//print delete warning
	if($_POST['action'] == "delete")	{ $Result->show("warning", _('Warning').':</strong> '._('Are you sure you want to delete this VLAN Group')."?", false);  }
	?>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default <?php if(isset($_POST['fromSubnet'])) { print "hidePopup2"; } else { print "hidePopups"; } ?>"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?> vlanManagementEditFromSubnetButton" id="editVLANgroupSubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>

	<!-- result -->
	<div class="<?php print $formId; ?>Result"></div>
</div>
