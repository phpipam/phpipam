<?php
// add prefix - install or migrate
$title_prefix = @$_GET['subnetId']=="migrate" ? "migration" : "installation";
$filename	  = @$_GET['subnetId']=="migrate" ? "MIGRATE" : "SCHEMA";
?>

<div class="widget-dash col-xs-12 col-md-8 col-md-offset-2">
<div class="inner install" style="min-height:auto;">
	<h4>Manual database <?php print $title_prefix; ?></h4>

	<div class="hContent">
	<div style="padding:10px;">

		<!-- Back -->
		<a href="<?php print create_link("install"); ?>" class="btn btn-sm btn-default"><i class='fa fa-angle-left'></i> Back</a>
		<!-- Instructions -->
		<div style="margin-top:10px;padding:30px 20px;">
			For importing database file with mysqlimport tool please follow below instructions:<hr>
			<ol>
				<li>Set variables for database connection in config.php</li>
				<li>Open mysql connection, create database
					<pre>mysql -u root -p</pre>
				</li>
				<li>
					Copy below SQL queries and paste them to mysql
				</li>
				<li>Finished ! Now login with <strong>Admin/ipamadmin</strong> to webpage<br>
				<a href="<?php print create_link(null,null,null,null,null,true); ?>" class="btn btn-sm btn-info">Login</a>
				</li>
			</ol>
			<hr>
		</div>

		<?php
		// file check
		if(@$_GET['subnetId']=="migrate") {
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
$file  = "# Create phpipam database\n";
$file .= "# ------------------------------------------------------------\n";
$file .= "CREATE DATABASE $db[name];\n\n";

$file .= "# Set permissions for phpipam user\n";
$file .= "# ------------------------------------------------------------\n";
$file .= "GRANT ALL on `$db[name]`.* to $db[user]@localhost identified by '$db[pass]';\n\n";

$file .= "# Select created database\n";
$file .= "# ------------------------------------------------------------\n";
$file .= "USE `$db[name]`;\n\n\n";

$file .= "# Create tables and import data\n";
$file .= "# ------------------------------------------------------------\n\n\n\n";

if(@$_GET['subnetId']=="migrate") {
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
