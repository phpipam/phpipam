<?php
/**
 *	firewall zone index.php
 *	list firewall zone to device mappings
 ******************************************/

# validate session parameters
$User->check_user_session();
# perm check
if ($User->get_module_permissions ("fwzones")==User::ACCESS_NONE) {
	$Result->show("danger", _("You do not have permissions to access this module"), false);
}
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
	if(!isset($GET->subnetId)) {
		$GET->subnetId = "mapping";
	}

	# check
	if(!in_array($GET->subnetId, $tabs)) 	{ $Result->show("danger", "Invalid request", true); }

	# print
	foreach($tabs as $t) {
		$class = $GET->subnetId==$t ? "class='active'" : "";
		print "<li role='presentation' $class><a href=".create_link("administration", "firewall-zones", "$t").">". _(ucwords($t))."</a></li>";
	}
	?>
</ul>

<div>
<?php
# include content
if(!file_exists(__DIR__ . '/'.$GET->subnetId.".php")) 	{ $Result->show("danger", "Invalid request", true); }
else																{ include(__DIR__ . '/'.$GET->subnetId.".php"); }
?>
</div>

<?php
} else {
	$Result->show("info", _('Please enable the firewall zone module under server management'), false);
}
?>
</div>