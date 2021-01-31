<?php
$db = Config::ValueOf('db');

// add prefix - install or migrate
$title_prefix = @$_GET['subnetId']=="migrate" ? _("migration") : _("installation");
$text_prefix  = @$_GET['subnetId']=="migrate" ? _("migrate") : _("install");
$filename	  = @$_GET['subnetId']=="migrate" ? "MIGRATE" : "SCHEMA";
?>

<div class="widget-dash col-xs-12 col-md-8 col-md-offset-2">
<div class="inner install" style="min-height:auto;">
	<h4><?php print ucwords($title_prefix)." "._("with mysqlimport tool"); ?></h4>

	<div class="hContent">
	<div style="padding:10px;">

		<!-- Back -->
		<a href="<?php print create_link("install"); ?>" class="btn btn-sm btn-default"><i class='fa fa-angle-left'></i> <?php print _("Back"); ?></a>
		<!-- Instructions -->
		<div style="margin-top:10px;padding:30px 20px;">
			<?php print _("Please follow below steps for")." ".$title_prefix." "._("with mysqlimport:"); ?><hr>
			<ol>
				<li><?php print _("Set variables for database connection in config.php"); ?></li>
				<li><?php print _("Open mysql connection"); ?>
					<pre>mysql -u root -p
Enter password:</pre>
				</li>

				<li><?php print _("Create database"); ?>
					<pre>CREATE DATABASE `<?php print $db['name']; ?>`;
exit</pre>
				</li>

				<li><?php print _("Import SQL file"); ?>
					<pre>mysql -u root -p <?php print $db['name']; ?> &lt; db/<?php print $filename;?>.sql</pre>
				</li>

				<?php
				// file check
				if(@$_GET['subnetId']=="migrate") {
					if(!file_exists(dirname(__FILE__)."/../../db/MIGRATE.sql")) {
						print "<div class='alert alert-danger'>"._("Cannot access file db/MIGRATE.sql!")."</div>";
					}
				}
				elseif (!file_exists(dirname(__FILE__)."/../../db/SCHEMA.sql")) {
						print "<div class='alert alert-danger'>"._("Cannot access file db/SCHEMA.sql!")."</div>";
				}
				else {
				?>

				<li><?php print _("Set permissions for phpipam user"); ?>
				<pre>GRANT ALL on `<?php print $db['name'];?>`.* to <?php print $db['user'];?>@localhost identified by '<?php print $db['pass'];?>';</pre>
				</li>

				<li><?php print _("Finished ! Now login with <strong>Admin/ipamadmin</strong> to webpage."); ?><br>
				<a href="<?php print create_link(null,null,null,null,null,true); ?>" class="btn btn-sm btn-info"><?php print _("Login"); ?></a>
				</li>
				<?php } ?>
			</ol>
			<hr>
		</div>

	</div>
	</div>
</div>
</div>
