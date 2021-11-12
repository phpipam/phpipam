<h4><?php print _("L2 domains"); ?></h4>
<hr>

<?php
# perm check
$User->check_module_permissions ("vlan", User::ACCESS_R, true, false);
?>

<!-- Manage link -->
<div class="btn-group" style="margin-bottom:10px;">
<?php if($User->get_module_permissions ("l2dom")>=User::ACCESS_RWA) { ?>
	<button class='btn btn-sm btn-default open_popup' data-script='app/admin/vlans/edit-domain.php' data-class='700' data-action='add'><i class='fa fa-plus'></i> <?php print _('Add L2 Domain'); ?></button>
<?php } ?>
<?php if($User->get_module_permissions ("vlan")>=User::ACCESS_RWA) { ?>
	<button class='btn btn-sm btn-default open_popup' data-script='app/admin/vlans/edit.php' data-class='500' data-action='add'><i class='fa fa-plus'></i> <?php print _('Add VLAN'); ?></button>
<?php } ?>
</div>


<table class="table sorted nosearch nopagination table-striped table-top table-condensed table-auto-wide" data-cookie-id-table='tools_l2_all'>
<!-- headers -->
<thead>
<tr>
	<th><?php print _('Name'); ?></th>
	<th><?php print _('Description'); ?></th>
	<th><?php print _('Count'); ?></th>
	<th><?php print _('Sections'); ?></th>
	<th></th>
	<th></th>
</tr>
</thead>

<tbody>
<!-- all domains -->
<tr>
	<td class='border-bottom'><strong><a href="<?php print create_link($_GET['page'], $_GET['section'], "all"); ?>"> <?php print _('All domains'); ?></a></strong></td>
	<td class='border-bottom'><?php print _('List of all VLANs in all domains'); ?></td>
	<td class='border-bottom'></td>
	<td class='border-bottom'><span class='text-muted'><?php print _('All sections'); ?></span></td>
	<td class='border-bottom'><a class='btn btn-xs btn-default' href='<?php print create_link($_GET['page'], $_GET['section'], "all"); ?>'>Show VLANs</a></td>
	<?php if($User->get_module_permissions ("vlan")>=User::ACCESS_RW) { ?><td class='border-bottom'></td><?php } ?>
</tr>

<!-- content -->
<?php
foreach($vlan_domains as $domain) {
	// Check user has read level permission to l2domain
	if (!$User->check_l2domain_permissions($domain, 1, false)) continue;

	// format sections
	if($domain->id==1) {
		$sections = "All sections";
	}
	elseif(strlen(@$domain->permissions==0)) {
		$sections = "None";
	}
	else {
		//explode
		unset($sec);
		$sections_tmp = explode(";", $domain->permissions);
		foreach($sections_tmp as $t) {
			//fetch section
			$tmp_section = $Sections->fetch_section(null, $t);
			if (is_object($tmp_section)) {
				$sec[] = " &middot; ".$tmp_section->name;
			}
		}
		//implode
		$sections = implode("<br>", $sec);
	}

	// count
	$cnt = $Tools->count_database_objects ("vlans", "domainId", $domain->id);

	// print
	print "<tr class='text-top'>";
	print "	<td><a class='btn btn-xs btn-default' href='".create_link($_GET['page'], $_GET['section'], $domain->id)."'><i class='fa fa-cloud prefix'></i> $domain->name</a></strong></td>";
	print "	<td>$domain->description</td>";
	print "	<td>$cnt "._("VLANs")."</td>";
	print "	<td><span class='text-muted'>$sections</span></td>";
	print "	<td><a class='btn btn-xs btn-default' href='".create_link($_GET['page'], $_GET['section'], $domain->id)."'>Show VLANs</a></td>";

    // links
    print "<td class='actions'>";
    $links = [];
    if($User->get_module_permissions ("vlan")>=User::ACCESS_R) {
        $links[] = ["type"=>"header", "text"=>_("Show")];
        $links[] = ["type"=>"link", "text"=>_("Show domain VLANs"), "href"=>create_link($_GET['page'], "vlan", $domain->id), "icon"=>"eye", "visible"=>"dropdown"];
        $links[] = ["type"=>"divider"];
    }
    if($User->get_module_permissions ("vlan")>=User::ACCESS_RW) {
        $links[] = ["type"=>"header", "text"=>_("Manage")];
        $links[] = ["type"=>"link", "text"=>_("Edit domain"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vlans/edit-domain.php' data-class='700' data-action='edit' data-id='$domain->id'", "icon"=>"pencil"];
    }
    if($User->get_module_permissions ("vlan")>=User::ACCESS_RWA) {
        $links[] = ["type"=>"link", "text"=>_("Delete domain"), "href"=>"", "class"=>"open_popup", "dataparams"=>"data-script='app/admin/vlans/edit-domain.php' data-class='700' data-action='delete' data-id='$domain->id'", "icon"=>"times"];
        $links[] = ["type"=>"divider"];
    }
    // print links
    print $User->print_actions($User->user->compress_actions, $links);
    print "</td>";

	print "</tr>";
}
?>
</tbody>
</table>