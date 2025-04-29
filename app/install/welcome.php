<?php if (!defined('VERSION_VISIBLE') || Config::ValueOf('disable_installer')) { print _("Install scripts disabled"); exit(0); } ?>

<div class="widget-dash col-xs-12 col-md-8 col-md-offset-2">
<div class="inner install" style="min-height:auto;">
	<h4><?php print _("Welcome to phpipam"); ?></h4>

	<div class="hContent">
	<div style="padding:10px;">

		<div class="text-mute2d" style="margin:10px;">
			<h3><?php print _("Welcome to phpipam installation wizard!")."</h3> "._("Please select one of the options below:"); ?>
			<br>
		</div>
		<hr>

		<ol style="margin-top:20px;">

			<!-- new -->
			<li>
				<a href="<?php print create_link("install","select_type"); ?>" class="btn btn-sm btn-default"><?php print _("New phpipam installation"); ?></a>
				<br>
				<div class="text-muted"><?php print _("Select this option to install a fresh instance of phpipam."); ?></div>
				<br>
			</li>

			<!-- migrate -->
			<li>
				<a href="<?php print create_link("install","select_type","migrate"); ?>" class="btn btn-sm btn-default"><?php print _("Migrate phpipam installation"); ?></a>
				<br>
				<div class="text-muted"><?php print _("Select this option to migrate phpipam database from another server. Place SQL dump from old server to directory db and name it MIGRATE.sql (db/MIGRATE.sql)."); ?></div>
				<br>
			</li>

			<!-- existing -->
			<li>
				<a href="<?php print create_link("install","sql_error"); ?>" class="btn btn-sm btn-default"><?php print _("Working installation"); ?></a>
				<br>
				<div class="text-muted"><?php print _("Select this option if you have a working phpipam installation and this screen occured. Generally it means
					phpipam was unable to connnect to the database. This will check for connection errors."); ?></div>
				<br>
			</li>

		</ol>
		<hr>

	</div>
	</div>
</div>
</div>