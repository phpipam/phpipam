<?php
# verify that user is logged in
$User->check_user_session();

// valid tabs
$tabs = array("subnets", "leases", "reservations", "settings", "config");
?>
<div class="DHCP">

<!-- display existing groups -->
<h4><?php print _('DHCP management'); ?></h4>
<hr><br>

<?php
# perm check
if ($User->get_module_permissions ("dhcp")==User::ACCESS_NONE) {
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}
elseif($User->settings->enableDHCP==1) { ?>

    <?php
    # validate DHCP settings - JSON
    if ($Tools->validate_json_string($User->settings->DHCP)===false) {
        $Result->show("danger", "Error parsing DHCP settings: ".$Tools->json_error, false);

        // settings
        include(dirname(__FILE__) . "/settings.php");
    }
    else {
        # parse and verify settings
        $dhcp_db = db_json_decode($User->settings->DHCP, true);

        # DHCP wrapper class
        $DHCP	= new DHCP ($dhcp_db['type'], $dhcp_db['settings']);

        // read config
        $config = $DHCP->read_config ();
        ?>
        <!-- tabs -->
        <ul class="nav nav-tabs">
        	<?php
        	// default tab
        	if(!isset($GET->subnetId)) {
        		$GET->subnetId = "subnets";
        	}

        	// check
        	if(!in_array($GET->subnetId, $tabs)) 	{ $Result->show("danger", "Invalid request", true); }

        	// print
        	foreach($tabs as $t) {
        		$title = str_replace('_', ' ', $t);
        		$class = $GET->subnetId==$t ? "class='active'" : "";
        		print "<li role='presentation' $class><a href=".create_link("administration", "dhcp", "$t").">". _(ucwords(str_replace("_", " ", $title)))."</a></li>";
        	}
        	?>
        </ul>

        <div>
        <?php
        // include file
        if(!file_exists(dirname(__FILE__) . "/".$GET->subnetId.".php")) 	{ $Result->show("danger", "Invalid request", true); }
        elseif (!in_array($GET->subnetId, $tabs))                    { $Result->show("danger", "Invalid request", true); }
        else													        { include(dirname(__FILE__) . "/".$GET->subnetId.".php"); }
        ?>
        </div>
<?php
}
} else {
	$Result->show("info", _('Please enable DHCP module under server management'), false);
}
?>
</div>