<?php

/*
 * Select devices for SNMP vrf query
 *************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# scan disabled
if ($User->settings->enableSNMP!="1")           { $Result->show("danger", _("SNMP module disbled"), true, true); }
# perm check
$User->check_module_permissions ("vrf", User::ACCESS_RWA, true, false);

# fetch devices that use get_routing_table query
$scan_devices = $Tools->fetch_multiple_objects ("devices", "snmp_queries", "%get_vrf_table%", "id", true, true);

// if none set die
if ($scan_devices===false)                      { $Result->show("danger", _("No devices for SNMP VRF query available"), true, true); }

?>

<!-- header -->
<div class='pHeader'><?php print _("Select devices"); ?></div>

<!-- content -->
<div class='pContent'>
    <h4><?php print _("Select devices to query VRF table from"); ?></h4><hr>

    <div style="padding: 20px;">
        <form name="select-devices" id="select-devices-vrf-scan">
        <?php
        // loop
        foreach ($scan_devices as $d) {
            $description = !is_blank($d->description) ? "<span class='text-muted'>$d->description</span>" : "";
            print " <input type='checkbox' name='device-$d->id' checked> $d->hostname ($d->ip_addr) $description<br>";
        }
        ?>
        </form>
    </div>
    <hr>

    <!-- scan result -->
    <div class="vrf-scan-result"></div>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-success show-vrf-scan-result"><?php print _('Scan'); ?></button>

	</div>
</div>