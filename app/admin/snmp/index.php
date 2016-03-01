<?php

/**
 * Manage snmp queries
 *************************************************/

# verify that user is logged in
$User->check_user_session();

# fetch all APIs
$all_queries = $Admin->fetch_all_objects("snmp");

?>

<!-- display existing groups -->
<h4><?php print _('SNMP queries'); ?></h4>
<hr><br>

<div class="btn-group">
	<a href="<?php print create_link("administration", "devices"); ?>" class="btn btn-sm btn-default"><i class='fa fa-angle-left'></i> <?php print _('Devices'); ?></a>
	<?php if($User->settings->enableSNMP==1) { ?>
	<button class='btn btn-sm btn-default edit-snmp-methods' style="margin-bottom:10px;" data-action='add'><i class='fa fa-plus'></i> <?php print _('Create SNMP query'); ?></button>
	<?php } ?>
</div>

<!-- only IF aPI enabled -->
<?php if($User->settings->enableSNMP==1) { ?>
	<!-- Add new -->

	<?php
	/* print existing APIs */
	if($all_queries!==false) {

		print '<table id="userPrint" class="table table-striped table-top table-auto">';
		# headers
		print '<tr>';
	    print "<th>"._('Name').'</th>';
		print "<th>"._('OID').'</th>';
	    print "<th>"._('Method').'</th>';
	    print "<th>"._('Description').'</th>';
	    print '<th></th>';
		print '</tr>';

		# loop
		foreach ($all_queries as $a) {
			# cast
			$a = (array) $a;

			print '<tr>' . "\n";

			// format description
			$a['description'] = str_replace("\n", "<br>", $a['description']);

			# override permissions if user
			if($a['app_security']=="user")	{ $a['app_permissions']="<span class='text-muted'>"._('Per user')."</span>"; }

			print '	<td>' . $a['name'] . '</td>'. "\n";
			print '	<td>' . $a['oid'] . '</td>'. "\n";
			print '	<td>' . $a['method'] . '</td>'. "\n";
			print '	<td>' . $a['description'] . '</td>'. "\n";


			# add/remove SNMP
			print "	<td class='actions'>";
			print "	<div class='btn-group'>";
			print "		<button class='btn btn-xs btn-default edit-snmp-methods'  data-container='body' data-snmpid='$a[id]' data-action='edit'   rel='tooltip' title='"._('edit snmp details')."'>	<i class='fa fa-pencil'></i></button>";
			print "		<button class='btn btn-xs btn-default edit-snmp-methods'  data-container='body' data-snmpid='$a[id]' data-action='delete' rel='tooltip' title='"._('remove snmp')."'>		<i class='fa fa-times'></i></button>";
			print "	</div>";
			print "</td>";

			print '</tr>' . "\n";
		}
		print "</table>";
	}
	else {
		$Result->show("info alert-nomargin", _("No SNMP queries available")."!", false);
	}
	?>

	<?php
	# print error if extensions are not available on server!
	$requiredExt  = array("snmp");
	$availableExt = get_loaded_extensions();
	# check for missing ext
	$missingExt = array();
	foreach ($requiredExt as $extension) {
	    if (!in_array($extension, $availableExt)) {
	        $missingExt[] = $extension;
	    }
	}
	# print warning if missing
	if (sizeof($missingExt) > 0) {
	    print "<div class='alert alert alert-danger'><strong>"._('The following PHP extensions for SNMP are missing').":</strong><br><hr>";
	    print '<ul>' . "\n";
	    foreach ($missingExt as $missing) {
	        print '<li>'. $missing .'</li>' . "\n";
	    }
	    print '</ul>';
	    print _('Please recompile PHP to include missing extensions for SNMP support') . "\n";
	    print "</div>";
	}
	?>
	<hr>

<?php
} else {
	$Result->show("info", _('Please enable SNMP module under server management'), false);
}
?>