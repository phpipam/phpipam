<div class="widget-dash col-xs-12 col-md-8 col-md-offset-2">
<div class="inner install" style="min-height:auto;">
	<h4>Database connection check</h4>

	<div class="hContent">

		<div class="text-muted" style="margin:10px;">
		Database connection check result:
		</div>

		<?php
		// default error flag
		$error = false;
		// try to fetch
		try { $Database->getObjectsQuery("settings"); }
		catch (Exception $e) {
			$Result->show("danger", "Error:<hr>".$e->getMessage(), false);
			$error = true;

			// text
			print '<div class="text-muted" style="margin:10px;margin-bottom:20px;">';
			print 'Troubleshooting:';
			print '<ul>';
			print '	<li>Make sure all settings in config.php are correct</li>';
			print '	<li>Make sure database is running and accepting connections</li>';
			print '	<li>Make sure user defined in config.php has access to database</li>';
			print '</ul>';
			print '</div>';
		}
		if ($error===false) {
			$Result->show("success", "Database connection succesfull", false);
		}
		?>
	</div>
</div>
</div>