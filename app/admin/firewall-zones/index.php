<?php
/**
 *	firewall zone index.php
 *	list firewall zone to device mappings
 ******************************************/

# validate session parameters
$User->check_user_session();

?>


<h4><?php print _('Firewall zone management'); ?></h4>
<hr><br>

<?php
# check if the feature is activated, otherwise provide a short notice to enable this feature in the phpIPAM settings menu
if($User->settings->enableFirewallZones==1) {
?>
<!-- tabs -->
<ul class="nav nav-tabs">
	<?php
	# tabs
	$tabs = array("mapping", "zones", "settings");

	# default tab
	if(!isset($_GET['subnetId'])) {
		$_GET['subnetId'] = "mapping";
	}

	# check
	if(!in_array($_GET['subnetId'], $tabs)) 	{ $Result->show("danger", "Invalid request", true); }

	# print
	foreach($tabs as $t) {
		$class = $_GET['subnetId']==$t ? "class='active'" : "";
		print "<li role='presentation' $class><a href=".create_link("administration", "firewall-zones", "$t").">". _(ucwords($t))."</a></li>";
	}
	?>
</ul>

<div>
<?php
# include content
if(!file_exists(dirname(__FILE__) . '/'.$_GET['subnetId'].".php")) 	{ $Result->show("danger", "Invalid request", true); }
else																{ include(dirname(__FILE__) . '/'.$_GET['subnetId'].".php"); }
?>
</div>

<?php
} else {
	$Result->show("info", _('Please enable the firewall zone module under server management'), false);
}
?>
</div>