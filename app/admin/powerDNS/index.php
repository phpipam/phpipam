<div class="powerDNS">
<?php

/**
 * Script to edit / add / delete groups
 *************************************************/

# verify that user is logged in
$User->check_user_session();
?>

<!-- display existing groups -->
<h4><?php print _('PowerDNS management'); ?></h4>
<hr><br>

<?php if($User->settings->enablePowerDNS==1) { ?>

<?php
# powerDNS class
$PowerDNS = new PowerDNS ($Database);

// check connection
$test = $PowerDNS->db_check();
// save settings for powerDNS default
$pdns = $PowerDNS->db_settings;

?>
<!-- tabs -->
<ul class="nav nav-tabs">
	<?php
	// tabs
	$tabs = array("domains", "settings", "defaults");

	//default tab
	if(!isset($_GET['subnetId'])) {
		if(!$test)	{ $_GET['subnetId'] = "settings"; }
		else		{ $_GET['subnetId'] = "domains"; }
	}

	// check
	if(!in_array($_GET['subnetId'], $tabs)) 	{ $Result->show("danger", "Invalid request", true); }

	// print
	foreach($tabs as $t) {
		$class = $_GET['subnetId']==$t ? "class='active'" : "";
		print "<li role='presentation' $class><a href=".create_link("administration", "powerDNS", "$t").">". _(ucwords($t))."</a></li>";
	}
	?>
</ul>

<div>
<?php
// include content
if(!file_exists(dirname(__FILE__) . '/'.$_GET['subnetId'].".php")) 	{ $Result->show("danger", "Invalid request", true); }
else																{ include(dirname(__FILE__) . '/'.$_GET['subnetId'].".php"); }
?>
</div>

<?php
} else {
	$Result->show("info", _('Please enable powerDNS module under server management'), false);
}
?>
</div>