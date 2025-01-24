<?php

/**
 *	Move vlan to new domain
 ************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# perm check popup
$User->check_module_permissions ("vlan", User::ACCESS_RW, true, true);

# fetch vlan details
$vlan = $Admin->fetch_object ("vlans", "vlanid", $POST->vlanid);
if($vlan===false)					{ $Result->show("danger", _("Invalid ID"), true, true); }

# fetch current domain
$vlan_domain = $Admin->fetch_object("vlanDomains", "id", $vlan->domainId);
if($vlan_domain===false)			{ $Result->show("danger", _("Invalid ID"), true, true); }

# fetch all l2 domains
$vlan_domains = $Admin->fetch_all_objects("vlanDomains", "id");
?>

<script>
$(document).ready(function(){
     if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }
});
</script>

<!-- header -->
<div class="pHeader"><?php print ucwords("Move VLAN to new domain"); ?></div>

<!-- content -->
<div class="pContent">
	<form id="moveVLAN">

	<table class="table table-noborder table-condensed">
	<!-- domain -->
	<tr>
		<td><?php print _('Current l2 domain'); ?></td>
		<th><?php print $vlan_domain->name." (".$vlan_domain->description.")"; ?></th>
	</tr>
	<tr>
		<td><?php print _('VLAN'); ?></td>
		<th><?php print $vlan->name." ("._("VLAN")." ".$vlan->number.")"; ?></th>
	</tr>
	<tr>
		<td colspan="2"><hr></td>
	</tr>
	<!-- new domain -->
	<tr>
		<td><?php print _('New domain'); ?></td>
		<td>
		<input type="hidden" name="vlanid" value="<?php print $vlan->vlanId; ?>">
		<select name="newDomainId" class="form-control input-w-auto input-sm">
		<?php
		$m=0;
		foreach($vlan_domains as $d) {
			if($d->id!=$vlan_domain->id) {
				print "<option value='$d->id'>$d->name ($d->description)</option>";
				$m++;
			}
		}
		?>
		</select>
		</td>
	</tr>

	</table>
	</form>

	<?php if($m==0)	$Result->show("warning", _("No domains available!"), false); ?>

</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<?php if($m>0) { ?>
		<button class='btn btn-sm btn-default submit_popup' data-script="app/admin/vlans/move-vlan-result.php" data-result_div="moveVLANSubmitResult" data-form='moveVLAN'><?php print ("Move"); ?></button>
		<?php } ?>
	</div>

	<!-- result -->
	<div id="moveVLANSubmitResult"></div>
</div>