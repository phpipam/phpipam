<?php

/**
 *	Print all available nameserver sets
 ************************************************/

# verify that user is logged in
$User->check_user_session();

# fetch all vrfs
$all_nameservers = $Admin->fetch_all_objects("nameservers", "id");
?>

<h4><?php print _('Manage Nameserver sets'); ?></h4>
<hr><br>

<button class='btn btn-sm btn-default open_popup' data-script='app/admin/nameservers/edit.php' data-class='700' data-action='add' style='margin-bottom:10px;'><i class='fa fa-plus'></i> <?php print _('Add nameserver set'); ?></button>

<!-- nameserver sets -->
<?php

# first check if they exist!
if($all_nameservers===false) { $Result->show("danger", _("No nameserver sets defined")."!", true);}
else {
	print '<table id="nameserverManagement" class="table sorted table-striped table-top table-hover table-td-top" data-cookie-id-table="admin_ns">'. "\n";

	# headers
	print "<thead>";
	print '<tr>'. "\n";
	print '	<th>'._('Nameserver set').'</th>'. "\n";
	print '	<th>'._('Nameservers').'</th>'. "\n";
	print '	<th>'._('Sections').'</th>'. "\n";
	print '	<th>'._('Description').'</th>'. "\n";
	print '	<th></th>'. "\n";
	print '</tr>'. "\n";
	print "</thead>";

    print "<tbody>";
	# loop
	foreach ($all_nameservers as $nameservers) {
		//cast
		$nameservers = (array) $nameservers;

		unset($permitted_sections);
		$permitted_sections = array();

		// sections
		if (!is_null($nameservers['permissions'])) {
			$sections = array_filter(pf_explode(";", $nameservers['permissions']));
			// some
			if (sizeof($sections)>0) {
				foreach($sections as $id) {
					$sect = $Admin->fetch_object ("sections", "id", $id);
					// exists
					if ($sect!==false) {
						$permitted_sections[] = "<span class='badge badge1 badge5'>".$sect->name."</span>";
					}
				}
			}
			else {
				$permitted_sections[] = "/";
			}
		}
		// none
		else {
			$permitted_sections[] = "/";
		}

		// merge all nmeservers
		$all_nameservers = pf_explode(";", $nameservers['namesrv1']);

		//print details
		print '<tr>'. "\n";
		print '	<td class="name"><strong>'. $nameservers['name'] .'</strong></td>'. "\n";
		print '	<td class="namesrv1">'. implode("<br>", $all_nameservers) .'</td>'. "\n";
		print '	<td class="sections">'. implode("<br>", $permitted_sections).'</td>'. "\n";
		print '	<td class="description">'. $nameservers['description'] .'</td>'. "\n";
		print "	<td class='actions'>";
		print "	<div class='btn-group'>";
		print "		<button class='btn btn-xs btn-default open_popup' data-script='app/admin/nameservers/edit.php' data-class='700' data-action='edit'   data-nameserverid='$nameservers[id]'><i class='fa fa-pencil'></i></button>";
		print "		<button class='btn btn-xs btn-default open_popup' data-script='app/admin/nameservers/edit.php' data-class='700' data-action='delete' data-nameserverid='$nameservers[id]'><i class='fa fa-times' ></i></button>";
		print "	</div>";
		print "	</td>";
		print '</tr>'. "\n";
	}
	print "</tbody>";
	print '</table>'. "\n";
}
?>

<!-- edit result holder -->
<div class="nameserverManagementEdit"></div>
