<?php
$db = Config::ValueOf('db');

// add prefix - install or migrate
$title_prefix = $GET->subnetId=="migrate" ? _("migration") : _("installation");
$text_prefix  = $GET->subnetId=="migrate" ? _("migrate") : _("install");
$filename	  = $GET->subnetId=="migrate" ? "MIGRATE" : "SCHEMA";
?>

<div class="widget-dash col-xs-12 col-md-8 col-md-offset-2">
<div class="inner install" style="min-height:auto;">
	<h4><?php print _("Automatic database")." ".$title_prefix; ?></h4>

	<div class="hContent">

		<div class="text-muted" style="margin:10px;">
		<?php print _("Please provide required inputs in below form for automatic database")." ".$title_prefix.", "._("once finished click Install database."); ?>
		<br>
		<?php print _("Before you proceed to")." ".$text_prefix." "._("please fill in all settings in <strong>config.php</strong> file!"); ?>
		</div>
		<hr>

		<form name="installDatabase" id="install" class="form-inline" method="post">
		<div class="row" style="margin-top:10px;padding:20px 10px;">

			<!-- MySQL install username -->
			<div class="col-xs-12 col-md-4"><strong><?php print _("MySQL/MariaDB username"); ?></strong></div>
			<div class="col-xs-12 col-md-8">
				<input type="text" style="width:100%;" name="mysqlrootuser" class="form-control" autofocus="autofocus" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
				<input type="hidden" name="install_type" value="<?php print $text_prefix; ?>">
			</div>

			<!-- MySQL install password -->
			<div class="col-xs-12 col-md-4"><strong><?php print _("MySQL/MariaDB password"); ?></strong></div>
			<div class="col-xs-12 col-md-8">
				<input type="password" style="width:100%;" name="mysqlrootpass" class="form-control" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
				<div class="text-muted"><?php print _("* User must have permissions to create new MySQL/MariaDB database"); ?></div>
			</div>
			<hr>

			<!-- Database location -->
			<div class="col-xs-12 col-md-4"><strong><?php print _("MySQL/MariaDB database location"); ?></strong></div>
			<div class="col-xs-12 col-md-8">
				<input type="text" style="width:100%;" name="mysqllocation" class="form-control" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" disabled="disabled" value="<?php print $db['host']; ?>">
				<div class="text-muted"></div>
			</div>

			<!-- Database name -->
			<div class="col-xs-12 col-md-4"><strong><?php print _("MySQL/MariaDB database name"); ?></strong></div>
			<div class="col-xs-12 col-md-8">
				<input type="text" style="width:100%;" name="mysqltable" class="form-control" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" disabled="disabled" value="<?php print $db['name']; ?>">
				<div class="text-muted"><?php print _("* change database details on config.php"); ?></div>
			</div>

			<!-- toggle advanced options -->
			<div class="col-xs-12"><hr></div>
			<div class="col-xs-12 col-md-4"></div>
			<div class="col-xs-12 col-md-8" style="padding-top:7px;">
				<a class="btn btn-sm btn-default" id="toggle-advanced"><i class='fa fa-cogs'></i><?php print _("Show advanced options"); ?></a>
			</div>

			<!-- advanced -->
			<div class="col-xs-12" id="advanced" style="display:none;padding:20px 0px;">

			<div class="col-xs-12 col-md-4"><strong><?php print _("Drop exisitng database"); ?></strong></div>
			<div class="col-xs-12 col-md-8">
				<input type="checkbox" name="dropdb" value="on">
				<span class="text-muted"><?php print _("Drop existing database if it exists"); ?></span>
			</div>
			<div class="col-xs-12 col-md-4"><strong><?php print _("Create database"); ?></strong></div>
			<div class="col-xs-12 col-md-8">
				<input type="checkbox" name="createdb" value="on" checked="checked">
				<span class="text-muted"><?php print _("Create new database"); ?></span>
			</div>
			<div class="col-xs-12 col-md-4"><strong><?php print _("Create permissions"); ?></strong></div>
			<div class="col-xs-12 col-md-8">
				<input type="checkbox" name="creategrants" value="on" checked="checked">
				<span class="text-muted"><?php print _("Set permissions to tables"); ?></span>
			</div>
			</div>

			<?php
			// file check
			if($GET->subnetId=="migrate") {
				if(!file_exists(dirname(__FILE__)."/../../db/MIGRATE.sql")) { ?>
					<div class="col-xs-12"><hr><div class='alert alert-danger'><?php print _("Cannot access file db/MIGRATE.sql!"); ?></div></div>
			<?php }
			}
			?>

			<!-- submit -->
			<div class="col-xs-12 text-right" style="margin-top:10px;">
				<hr>
				<div class="btn-block">
					<!-- Back -->
					<a class="btn btn-sm btn-default" href="<?php print create_link("install"); ?>" ><i class='fa fa-angle-left'></i> <?php print _("Back"); ?></a>
					<a class="install btn btn-sm btn-info" version="0"><?php print ucwords($text_prefix)." "._("phpipam database"); ?> </a>
				</div>
			</div>
			<div class="clearfix"></div>

			<!-- result -->
			<div class="upgradeResult" style="margin-top:15px;">
			</div>

		</div>
		</form>


	</div>
</div>
</div>
