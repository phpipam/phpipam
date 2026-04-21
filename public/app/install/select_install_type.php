<?php
if (!defined('VERSION_VISIBLE') || Config::ValueOf('disable_installer')) { print _("Install scripts disabled"); exit(0); }

// add prefix - install or migrate
$title_prefix = $GET->subnetId=="migrate" ? _("migration") : _("installation");
$text_prefix  = $GET->subnetId=="migrate" ? _("migrate") : _("install");
?>

<div class="widget-dash col-xs-12 col-md-8 col-md-offset-2">
<div class="inner install" style="min-height:auto;">
	<h4><?php print _("Welcome to phpipam")." ".$title_prefix; ?></h4>

	<div class="hContent">
	<div style="padding:10px;">

		<div class="text-mute2d" style="margin:10px;">
			<?php print _("We are glad you decided to")." ".$text_prefix." phpipam. "._("Before you can start to process please:"); ?><br><br>
			<ul>
				<li><?php print _("Edit settings in config.php file"); ?></li>
				<li><?php print _("Create a MySQL/MariaDB database for phpipam, you can do this three ways (select below)"); ?></li>
			</ul>
			<br>
			<?php print _("Before you start to")." ".$title_prefix." "._("please visit phpipam installation website").' <strong><a href="https://phpipam.net/documents/installation/">https://phpipam.net/documents/installation/</a></strong> '._("to get all documentation and to make sure all requirements are met for")." ".$title_prefix."."; ?>
			<br><br>

			<?php
			// migrate
			if($GET->subnetId=="migrate") { ?>
				<hr>
				<div class="alert alert-warning">
				<?php print _("You selected option to migrate from old database.")."<br>"._("Please put SQL dump file from old phpipam installation to db/MIGRATE.sql file !"); ?>
				</div>
			<?php } ?>

			<hr>
			<br><?php print _("Please select preferred type of database")." ".$title_prefix; ?>:<br><br>
			<hr>
		</div>

		<ol style="margin-top:20px;">
		<!-- automatic -->
		<li>
			<a href="<?php print create_link("install","install_automatic", $GET->subnetId); ?>" class="btn btn-sm btn-default"><?php print _("Automatic database")." ".$title_prefix; ?></a>
			<br>
			<div class="text-muted"><?php print _("phpipam installer will create database for you automatically."); ?></div>
			<br>
		</li>

		<!-- Mysql import -->
		<li>
			<a href="<?php print create_link("install","install_mysqlimport", $GET->subnetId); ?>" class="btn btn-sm btn-default"><?php print _("MySQL/MariaDB import instructions"); ?></a>
			<br>
			<div class="text-muted"><?php print _("Install DB files with mysqlimport tool."); ?></div>
			<br>
		</li>

		<!-- Manual install -->
		<li>
			<a href="<?php print create_link("install","install_manual", $GET->subnetId); ?>" class="btn btn-sm btn-default"><?php print _("Manual database")." ".$title_prefix; ?></a>
			<br>
			<div class="text-muted"><?php print _("Install database manually with SQL queries."); ?></div>
			<br>
		</li>

		</ol>

	</div>
	</div>
</div>
</div>
