<h4><?php print _("Subnets"); ?></h4>
<hr>
<span class="text-muted"><?php print _("All subnets belonging to customer"); ?>.</span>

<script>
/* fix for ajax-loading tooltips */
$('body').tooltip({ selector: '[rel=tooltip]' });
</script>
<?php

#
# Only if some are present
#
if (isset($objects["subnets"])) {

# set which custom fields to display
$hidden_fields = db_json_decode($User->settings->hiddenCustomFields, true);
$visible_fields = array();
# set visible fields
foreach ($custom_fields as $k=>$f) {
    if (isset($hidden_fields['subnets'])) {
        if (!in_array($k, $hidden_fields['subnets'])) {
            $visible_fields[$k] = $f;
        }
    }
}
# set colspan
$colspan_subnets = 5 + sizeof($visible_fields);


# print HTML table
print '<table class="slaves table sorted table-striped table-condensed table-hover table-full table-top" data-cookie-id-table="subnet_slaves">'. "\n";

# headers
print "<thead>";
print "<tr>";
print "	<th class='small'>"._('VLAN')."</th>";
print "	<th>"._('Subnet')."</th>";
print "	<th>"._('Description')."</th>";
# custom
if(isset($visible_fields)) {
foreach ($visible_fields as $f) {
print "	<th class='hidden-xs hidden-sm hidden-md'>".$Tools->print_custom_field_name ($f['name'])."</th>";
}
}
print "	<th class='small hidden-xs hidden-sm hidden-md'>"._('Used')."</th>";
print "	<th class='small hidden-xs hidden-sm hidden-md'>% "._('Free')."</th>";
print "	<th class='small hidden-xs hidden-sm hidden-md'>"._('Requests')."</th>";
print " <th style='width:80px;'></th>";
print "</tr>";
print "</thead>";

$m = 0;				//slave index

# loop
print "<tbody>";
foreach ($objects['subnets'] as $slave_subnet) {
	# cast to array
	$slave_subnet = (array) $slave_subnet;

	# calculate free / used / percentage
	$calculate = $Subnets->calculate_subnet_usage ( $slave_subnet );

	# get VLAN details
	$slave_vlan = (array) $Tools->fetch_object("vlans", "vlanId", $slave_subnet['vlanId']);
	if($slave_vlan===false) 	{ $slave_vlan['number'] = "/"; }				//reformat empty vlan

	# add full information
	$fullinfo = $slave_subnet['isFull']==1 ? " <span class='badge badge1 badge2 badge4'>"._("Full")."</span>" : "";
    if ($slave_subnet['isFull']!=1) {
        # if usage is 100%, fake usFull to true!
        if ($calculate['freehosts']==0)  { $fullinfo = "<span class='badge badge1 badge2 badge4'>"._("Full")."</span>"; }
    }

	# slaves info
	$has_slaves = $Subnets->has_slaves ($slave_subnet['id']) ? true : false;

	# description
	$has_slaves_ind = $has_slaves ? " <i class='fa fa-folder'></i> ":"";
	$slave_subnet['description'] = !is_blank($slave_subnet['description']) ? $slave_subnet['description'] : " / ";
	$slave_subnet['description'] = $has_slaves_ind . $slave_subnet['description'];

	# section
	$section = $Tools->fetch_object ("sections", "id", $slave_subnet['sectionId']);

	print "<tr>";
    print "	<td class='small'>".@$slave_vlan['number']."</td>";
    print "	<td><a href='".create_link("subnets",$section->id,$slave_subnet['id'])."'>".$Subnets->transform_address($slave_subnet['subnet'],"dotted")."/$slave_subnet[mask]</a> $fullinfo</td>";
    print "	<td><a href='".create_link("subnets",$section->id,$slave_subnet['id'])."'>$slave_subnet[description]</a></td>";

    # custom
    if(isset($visible_fields)) {
    foreach ($visible_fields as $key=>$field) {
		#booleans
		if($field['type']=="tinyint(1)")	{
			if($slave_subnet[$key] == "0")		{ $html_custom = _("No"); }
			elseif($slave_subnet[$key] == "1")	{ $html_custom = _("Yes"); }
		}
		else {
			$html_custom = $Tools->create_links($slave_subnet[$key]);
		}

        print "<td>".$html_custom."</td>";
    }
    }

    print ' <td class="small hidden-xs hidden-sm hidden-md">'. $calculate['used'] .'/'. $calculate['maxhosts'] .'</td>'. "\n";
    print '	<td class="small hidden-xs hidden-sm hidden-md">'. $calculate['freehosts_percent'] .'</td>';

	# allow requests
	if($slave_subnet['allowRequests'] == 1) 	{ print '<td class="allowRequests small hidden-xs hidden-sm hidden-md"><i class="fa fa-gray fa-check"></td>'; }
	else 										{ print '<td class="allowRequests small hidden-xs hidden-sm hidden-md">/</td>'; }

	# edit
	print "	<td class='actions'>";

    // actions
	$slave_subnet_permission = $Subnets->check_permission($User->user, $subnet['id']);

    $links = [];

    $links[] = ["type"=>"header", "text"=>_("View")];
    $links[] = ["type"=>"link", "text"=>_("Show subnet"), "href"=>create_link("subnets", $section->id,$slave_subnet['id']), "icon"=>"eye", "visible"=>"dropdown"];

    if($slave_subnet_permission==3) {
        // manage
        $links[] = ["type"=>"divider"];
        $links[] = ["type"=>"header", "text"=>_("Manage")];
        $links[] = ["type"=>"link", "text"=>_("Edit subnet"), "href"=>"", "class"=>"editSubnet", "dataparams"=>" data-action='edit'   data-subnetid='".$slave_subnet['id']."'  data-sectionid='".$slave_subnet['sectionId']."'", "icon"=>"pencil"];
        $links[] = ["type"=>"link", "text"=>_("Delete subnet"), "href"=>"", "class"=>"editSubnet", "dataparams"=>" data-action='delete'   data-subnetid='".$slave_subnet['id']."'  data-sectionid='".$slave_subnet['sectionId']."'", "icon"=>"times"];
        // permissions
        $links[] = ["type"=>"divider"];
        $links[] = ["type"=>"header", "text"=>_("Permissions")];
        $links[] = ["type"=>"link", "text"=>_("Edit permissions"), "href"=>"", "class"=>"showSubnetPerm", "dataparams"=>"data-subnetid='".$slave_subnet['id']."'  data-sectionid='".$slave_subnet['sectionId']."'", "icon"=>"tasks"];
    }
    if($User->get_module_permissions ("customers")>=User::ACCESS_RW) {
        $links[] = ["type"=>"divider"];
        $links[] = ["type"=>"header", "text"=>_("Unlink")];
        $links[] = ["type"=>"link", "text"=>_("Unlink object"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/customers/unlink.php' data-class='700'data-object='subnets' data-id='{$slave_subnet['id']}'", "icon"=>"unlink"];
    }
    // print links
    print $User->print_actions($User->user->compress_actions, $links);
    print "</td>";

	print '</tr>' . "\n";
}
print "</tbody>";
print '</table>'. "\n";

}
else {
	$Result->show("info", _("No objects"));
}
