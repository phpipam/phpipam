<?php
// add prefix - install or migrate
$title_prefix = @$_GET['subnetId']=="migrate" ? "migration" : "installation";
$text_prefix  = @$_GET['subnetId']=="migrate" ? "migrate" : "install";
?>

<div class="widget-dash col-xs-12 col-md-8 col-md-offset-2">
<div class="inner install" style="min-height:auto;">
	<h4>Welcome to phpipam <?php print $title_prefix; ?></h4>

	<div class="hContent">
	<div style="padding:10px;">

		<div class="text-mute2d" style="margin:10px;">
			We are glad you decided to <?php print $text_prefix; ?> phpipam. Before you can start process please:<br><br>
			<ul>
				<li>Edit settings in config.php file</li>
				<li>Create MySQL database for phpipam, you can do this three ways (select below)</li>
			</ul>
			<br>
			Before you start <?php print $title_prefix; ?> please visit phpipam installation website <strong><a href="https://phpipam.net/documents/installation/">https://phpipam.net/documents/installation/</a></strong> to get all documentation and to make sure all requirements for <?php print $title_prefix; ?> are met.
			<br><br>

			<?php
			// migrate
			if($_GET['subnetId']=="migrate") { ?>
				<hr>
				<div class="alert alert-warning">
				You selected option to migrate from old database.<br>Please put SQL dump file from old phpipam installation to db/MIGRATE.sql file !
				</div>
			<?php } ?>

			<hr>
			<br>Please select preferred database <?php print $title_prefix; ?> type:<br><br>
			<hr>
		</div>

		<ol style="margin-top:20px;">
		<!-- automatic -->
		<li>
			<a href="<?php print create_link("install","install_automatic", $_GET['subnetId']); ?>" class="btn btn-sm btn-default">Automatic database <?php print $title_prefix; ?></a>
			<br>
			<div class="text-muted">phpipam installer will create database for you automatically.</div>
			<br>
		</li>

		<!-- Mysql inport -->
		<li>
			<a href="<?php print create_link("install","install_mysqlimport", $_GET['subnetId']); ?>" class="btn btn-sm btn-default">MySQL import instructions</a>
			<br>
			<div class="text-muted">Install DB files with mysqlimport tool.</div>
			<br>
		</li>

		<!-- Manual install -->
		<li>
			<a href="<?php print create_link("install","install_manual", $_GET['subnetId']); ?>" class="btn btn-sm btn-default">Manual database <?php print $title_prefix; ?></a>
			<br>
			<div class="text-muted">Install database manually with SQL queries.</div>
			<br>
		</li>

		</ol>

	</div>
	</div>
</div>
</div>