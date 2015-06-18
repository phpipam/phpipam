<?php

/**
 *	Print all available VRFs and configurations
 ************************************************/

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

# get VRF
if($_POST['action']!="add") {
	$vrf = $Admin->fetch_object ("vrf", "vrfId", $_POST['vrfId']);
	$vrf!==false ? : $Result->show("danger", _("Invalid ID"), true, true);
	$vrf = (array) $vrf;
}

# disable edit on delete
$readonly = $_POST['action']=="delete" ? "readonly" : "";
?>


<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('VRF'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="vrfManagementEdit">
	<table id="vrfManagementEdit2" class="table table-noborder table-condensed">

	<!-- name  -->
	<tr>
		<td><?php print _('Name'); ?></td>
		<td>
			<input type="text" class="name form-control input-sm" name="name" placeholder="<?php print _('VRF name'); ?>" value="<?php print @$vrf['name']; ?>" <?php print $readonly; ?>>
		</td>
	</tr>
	<!-- RD -->
	<tr>
		<td><?php print _('RD'); ?></td>
		<td>
			<input type="text" class="rd form-control input-sm" name="rd" placeholder="<?php print _('Route distinguisher'); ?>" value="<?php print @$vrf['rd']; ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<!-- Description -->
	<tr>
		<td><?php print _('Description'); ?></td>
		<td>
			<?php
			if( ($_POST['action'] == "edit") || ($_POST['action'] == "delete") ) { print '<input type="hidden" name="vrfId" value="'. $_POST['vrfId'] .'">'. "\n";}
			?>
			<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
			<input type="text" class="description form-control input-sm" name="description" placeholder="<?php print _('Description'); ?>" value="<?php print @$vrf['description']; ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	</table>
	</form>

	<?php
	//print delete warning
	if($_POST['action'] == "delete")	{ $Result->show("warning", "<strong>"._('Warning').":</strong> "._("removing VRF will also remove VRF reference from belonging subnets!"), false);}
	?>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editVRF"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>
	<!-- result -->
	<div class="vrfManagementEditResult"></div>
</div>