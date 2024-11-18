<?php
if (!defined('VERSION_VISIBLE') || Config::ValueOf('disable_installer')) { print _("Install scripts disabled"); exit(0); }

$db = Config::ValueOf('db');

// add prefix - install or migrate
$title_prefix = $GET->subnetId=="migrate" ? _("migration") : _("installation");
$filename	  = $GET->subnetId=="migrate" ? "MIGRATE" : "SCHEMA";
?>

<div class="widget-dash col-xs-12 col-md-8 col-md-offset-2">
<div class="inner install" style="min-height:auto;">
	<h4><?php print _("Manual database")." ".$title_prefix; ?></h4>

	<div class="hContent">
	<div style="padding:10px;">

		<!-- Back -->
		<a href="<?php print create_link("install"); ?>" class="btn btn-sm btn-default"><i class='fa fa-angle-left'></i> <?php print _("Back"); ?></a>
		<!-- Instructions -->
		<div style="margin-top:10px;padding:30px 20px;">
			<?php print _("For importing database file with mysqlimport tool please follow the instructions below:"); ?><hr>
			<ol>
				<li><?php print _("Set variables for database connection in config.php"); ?></li>
				<li><?php print _("Open mysql connection, create database"); ?>
					<pre>mysql -u root -p</pre>
				</li>
				<li>
					<?php print _("Copy the SQL queries below and paste them into mysql"); ?>
				</li>
				<li><?php print _("Finished ! Now log in with <strong>Admin/ipamadmin</strong> to web page."); ?><br>
				<a href="<?php print create_link(null,null,null,null,null,true); ?>" class="btn btn-sm btn-info"><?php print _("Login"); ?></a>
				</li>
			</ol>
			<hr>
		</div>

		<?php
		// file check
		if($GET->subnetId=="migrate") {
			if(!file_exists(dirname(__FILE__)."/../../db/MIGRATE.sql")) {
				print "<div class='alert alert-danger'>Cannot access file db/MIGRATE.sql!</div>";
			}
		}
		elseif (!file_exists(dirname(__FILE__)."/../../db/SCHEMA.sql")) {
				print "<div class='alert alert-danger'>Cannot access file db/SCHEMA.sql!</div>";
		}
		?>

		<!-- show file -->
		<div>
		<pre>
<?php

$esc_user = escape_input($db['user']);
$esc_pass = escape_input(_("<YOUR SECRET PASSWORD FROM config.php>"));
$esc_webhost = is_string($db['webhost']) && strlen($db['webhost']) ? escape_input($db['webhost']) : 'localhost';
$db_name  = escape_input($db['name']);

$file  = "# Create phpipam database\n";
$file .= "# ------------------------------------------------------------\n";
$file .= "CREATE DATABASE $db_name;\n\n";

$file .= "# Set permissions for phpipam user\n";
$file .= "# ------------------------------------------------------------\n";
$file .= "CREATE USER '$esc_user'@'$esc_webhost' IDENTIFIED BY '$esc_pass';\n";
$file .= "GRANT ALL ON $db_name.* TO '$esc_user'@'$esc_webhost';\n";
$file .= "FLUSH PRIVILEGES;\n\n";

$file .= "# Select created database\n";
$file .= "# ------------------------------------------------------------\n";
$file .= "USE `$db_name`;\n\n\n";

$file .= "# Create tables and import data\n";
$file .= "# ------------------------------------------------------------\n\n\n\n";

if($GET->subnetId=="migrate") {
	if(file_exists(dirname(__FILE__)."/../../db/MIGRATE.sql")) {
		$file .= file_get_contents(dirname(__FILE__)."/../../db/MIGRATE.sql");
	}
}
else {
		$file .= file_get_contents(dirname(__FILE__)."/../../db/SCHEMA.sql");
}
print_r($file); ?>
</pre>
		</div>

	</div>
	</div>
</div>
</div>
