<?php

/**
 * Script to edit / add / delete APIs and keys
 *************************************************/

# verify that user is logged in
$User->check_user_session();

# fetch all APIs
$all_apis = $Admin->fetch_all_objects("api");
?>

<!-- display existing groups -->
<h4><?php print _('API management'); ?></h4>
<hr><br>

<!-- only IF aPI enabled -->
<?php if($User->settings->api==1) { ?>
	<!-- Add new -->
	<button class='btn btn-sm btn-default open_popup' style="margin-bottom:10px;" data-script='app/admin/api/edit.php' data-class='700' data-action='add'><i class='fa fa-plus'></i> <?php print _('Create API key'); ?></button>

	<?php
	/* print existing APIs */
	if($all_apis!==false) {

		print '<table id="userPrint" class="table nosearch sorted table-striped table-top table-auto" data-cookie-id-table="admin_api">';
		# headers
		print "<thead>";
		print '<tr>';
	    print "<th>"._('App id').'</th>';
		print "<th>"._('App code').'</th>';
	    print "<th>"._('App permissions').'</th>';
	    print "<th>"._('App security').'</th>';
	    print "<th>"._('Transaction locking').'</th>';
	    print "<th>"._('Lock timeout').'</th>';
	    print "<th>"._('Nest custom fields').'</th>';
	    print "<th>"._('Show links').'</th>';
	    print "<th>"._('Comment').'</th>';
	    print '<th></th>';
		print '</tr>';
		print "</thead>";

		# loop
		print "<tbody>";
		foreach ($all_apis as $a) {
			# cast
			$a = (array) $a;

			# hide key if not crpt
			if($a['app_security']!="crypt")	{ $a['app_code']="<span class='text-muted'>"._('Not used')."</span>"; }
			if($a['app_security']=="ssl")	{ $a['app_security'] = "SSL"; }

			print '<tr>' . "\n";

			print '	<td>' . $a['app_id'] . '</td>'. "\n";
			print '	<td>' . $a['app_code'] . '</td>'. "\n";

			# reformat permissions
			if($a['app_permissions']==0)		{ $a['app_permissions'] = _("Disabled"); }
			elseif($a['app_permissions']==1)	{ $a['app_permissions'] = _("Read"); }
			elseif($a['app_permissions']==2)	{ $a['app_permissions'] = _("Read / Write"); }
			elseif($a['app_permissions']==3)	{ $a['app_permissions'] = _("Read / Write / Admin"); }

			# wait update
			$a['app_lock_wait'] = $a['app_lock']==1 ? $a['app_lock_wait']." sec" : "/";

			# reformat lock and nesting
			$a['app_lock']               = $a['app_lock']==1 ? _("Yes") : _("No");
			$a['app_nest_custom_fields'] = $a['app_nest_custom_fields']==1 ? _("Yes") : _("No");
			$a['app_show_links'] 		 = $a['app_show_links']==1 ? _("Yes") : _("No");

			# override permissions if user
			if($a['app_security']=="user")	{ $a['app_permissions']="<span class='text-muted'>"._('Per user')."</span>"; }

			print '	<td>' . $a['app_permissions'] . '</td>'. "\n";
			print '	<td>' . ucwords($a['app_security']) . '</td>'. "\n";
			print '	<td>' . $a['app_lock'] . '</td>'. "\n";
			print '	<td>' . $a['app_lock_wait'] . '</td>'. "\n";
			print '	<td>' . $a['app_nest_custom_fields'] . '</td>'. "\n";
			print '	<td>' . $a['app_show_links'] . '</td>'. "\n";
			print '	<td>' . $a['app_comment'] . '</td>'. "\n";

			# add/remove APIs
			print "	<td class='actions'>";
			print "	<div class='btn-group'>";
			print "		<button class='btn btn-xs btn-default open_popup' data-script='app/admin/api/edit.php' data-class='700' data-action='edit' data-appid='$a[id]' rel='tooltip' title='"._('edit app details')."'><i class='fa fa-pencil'></i></button>";
			print "		<button class='btn btn-xs btn-default open_popup' data-script='app/admin/api/edit.php' data-class='700' data-action='delete' data-appid='$a[id]' rel='tooltip' title='"._('remove app')."'><i class='fa fa-times'></i></button>";
			print "	</div>";
			print "</td>";

			print '</tr>' . "\n";
		}
		print "</tbody>";
		print "</table>";
	}
	else {
		$Result->show("info alert-nomargin", _("No Apps available")."!", false);
	}
	?>

	<?php
	# print error if extensions are not available on server!
	$requiredExt  = array("mcrypt", "curl");
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
	    print "<div class='alert alert alert-danger'><strong>"._('The following PHP extensions for API server are missing').":</strong><br><hr>";
	    print '<ul>' . "\n";
	    foreach ($missingExt as $missing) {
	        print '<li>'. $missing .'</li>' . "\n";
	    }
	    print '</ul>';
	    print _('Please recompile PHP to include missing extensions for API server') . "\n";
	    print "</div>";
	}
	?>
	<hr>

	<h4><?php print _('API documentation'); ?></h4>
	<a href="http://phpipam.net/api-documentation/">http://phpipam.net/api-documentation/</a>

<?php
} else {
	$Result->show("info", _('Please enable API module under server management'), false);
}