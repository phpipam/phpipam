<?php
# ID must be numneric
if(!is_numeric($_GET['subnetId']))	{ $Result->show("danger", _("Invalid ID"), true); }

# fetch l2 domain
$vlan_domain = $Admin->fetch_object("vlanDomains", "id", $_GET['subnetId']);
if($vlan_domain===false)			{ $Result->show("danger", _("Invalid ID"), true); }
# fetch all VLANs in domain
$vlans = $Admin->fetch_multiple_objects("vlans", "domainId", $vlan_domain->id, "number");

# fetch custom fields
$custom = $Tools->fetch_custom_fields('vlans');

# set hidden fields
$hidden_fields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_fields = is_array(@$hidden_fields['vlans']) ? $hidden_fields['vlans'] : array();

# set size of custom fields
$custom_size = sizeof($custom) - sizeof($hidden_fields);
?>


<!-- add new -->
<div class="btn-group" style="margin-bottom:10px;">
	<?php if(sizeof($vlan_domains)>1) { ?>
	<a class="btn btn-sm btn-default"href="<?php print create_link("administration", "vlans"); ?>"><i class="fa fa-angle-left"></i> <?php print _('L2 Domains'); ?></a>
	<?php } ?>
	<button class="btn btn-sm btn-default editVLAN" data-action="add" data-vlanid="" data-domain="<?php print $vlan_domain->id; ?>"><i class="fa fa-plus"></i> <?php print _('Add VLAN'); ?></button>
	<?php if(sizeof($vlan_domains)==1) { ?>
	<button class="btn btn-sm btn-default editVLANdomain" data-action="add" data-domainid="" style="margin-bottom:10px;"><i class="fa fa-plus"></i> <?php print _('Add L2 Domain'); ?></button>
	<?php } ?>
</div>

<h4><?php print _("VLANs in domain")." ".$vlan_domain->name; ?></h4>
<hr>

<?php
if($vlans===false) 					{ $Result->show("info alert-absolute", _('No VLANs configured')."!", false); }
else {
?>
<table id="vlanManagement" class="table slaves table-striped table-top">
	<!-- headers -->
	<tr>
		<th><?php print _('Name'); ?></th>
		<th><?php print _('Number'); ?></th>
		<th><?php print _('Description'); ?></th>
		<?php
		if(sizeof($custom) > 0) {
			foreach($custom as $field) {
				if(!in_array($field['name'], $hidden_fields)) {
					print "<th class='customField hidden-xs hidden-sm'>$field[name]</th>";
				}
			}
		}
		?>
		<th></th>
	</tr>

	<!-- VLANs -->
	<?php
	$m=0;
	foreach ($vlans as $vlan) {
		//cast
		$vlan = (array) $vlan;

		# show free vlans - start
		if($User->user->hideFreeRange!="1") {
		if($m==0 && $vlan['number']!=1)	{
			print "<tr class='success'>";
			print "<td></td>";
			print "<td colspan='".(3+$custom_size)."'><btn class='btn btn-xs btn-default editVLAN' data-action='add' data-number='1' data-domain='$vlan_domain->id'><i class='fa fa-plus'></i></btn> "._('VLAN')."1 - ".($vlan['number']-1)." (".($vlan['number']-1)." "._('free').")</td>";
			print "</tr>";
		}
		# show free vlans - before vlan
		if($m>0)	{
			if( (($vlans[$m]->number)-($vlans[$m-1]->number)-1) > 0 ) {
			print "<tr class='success'>";
			print "<td></td>";
			# only 1?
			if( (($vlans[$m]->number)-($vlans[$m-1]->number)-1) ==1 ) {
			print "<td colspan='".(3+$custom_size)."'><btn class='btn btn-xs btn-default editVLAN' data-action='add' data-domain='$vlan_domain->id' data-number='".($vlan['number']-1)."'><i class='fa fa-plus'></i></btn> "._('VLAN')." ".($vlan['number']-1)." (".(($vlans[$m]->number)-($vlans[$m-1]->number)-1)." "._('free').")</td>";
			} else {
			print "<td colspan='".(3+$custom_size)."'><btn class='btn btn-xs btn-default editVLAN' data-action='add' data-domain='$vlan_domain->id' data-number='".($vlans[$m-1]->number+1)."'><i class='fa fa-plus'></i></btn> "._('VLAN')." ".($vlans[$m-1]->number+1)." - ".($vlan['number']-1)." (".(($vlans[$m]->number)-($vlans[$m-1]->number)-1)." "._('free').")</td>";
			}
			print "</tr>";
		}
		}
		}

		//print details
		print '<tr>'. "\n";

		print '	<td class="name">'. $vlan['name'] .'</td>'. "\n";
		print '	<td class="number"><a href="'.create_link("tools","vlan",$vlan['vlanId']).'">'. $vlan['number'] .'</a></td>'. "\n";
		print '	<td class="description">'. $vlan['description'] .'</td>'. "\n";

		if(sizeof($custom) > 0) {
			foreach($custom as $field) {
				if(!in_array($field['name'], $hidden_fields)) {

					print "<td class='customField hidden-xs hidden-sm'>";

					//booleans
					if($field['type']=="tinyint(1)")	{
						if($vlan[$field['name']] == "0")		{ print _("No"); }
						elseif($vlan[$field['name']] == "1")	{ print _("Yes"); }
					}
					//text
					elseif($field['type']=="text") {
						if(strlen($vlan[$field['name']])>0)	{ print "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", $vlan[$field['name']])."'>"; }
						else											{ print ""; }
					}
					else {
						print $vlan[$field['name']];

					}
					print "</td>";
				}
			}
		}

		print "	<td class='actions'>";
		print "	<div class='btn-group'>";
		print "		<button class='btn btn-xs btn-default editVLAN' data-action='edit'   data-vlanid='$vlan[vlanId]'><i class='fa fa-pencil'></i></button>";
		print "		<button class='btn btn-xs btn-default moveVLAN' 					 data-vlanid='$vlan[vlanId]'><i class='fa fa-external-link'></i></button>";
		print "		<button class='btn btn-xs btn-default editVLAN' data-action='delete' data-vlanid='$vlan[vlanId]'><i class='fa fa-times'></i></button>";
		print "	</div>";
		print "	</td>";
		print '</tr>'. "\n";

		# show free vlans - last
		if($User->user->hideFreeRange!="1") {
		if($m==(sizeof($vlans)-1))	{
			if($User->settings->vlanMax>$vlan['number'])
			print "<tr class='success'>";
			print "<td></td>";
			print "<td colspan='".(3+$custom_size)."'><btn class='btn btn-xs btn-default editVLAN' data-action='add' data-domain='$vlan_domain->id' data-number='".($vlan['number']+1)."'><i class='fa fa-plus'></i></btn> "._('VLAN')." ".($vlan['number']+1)." - ".$User->settings->vlanMax." (".(($User->settings->vlanMax)-($vlan['number']))." "._('free').")</td>";
			print "</tr>";
		}
		}

	$m++;

	}
}
?>
</table>