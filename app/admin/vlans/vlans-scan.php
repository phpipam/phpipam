<?php

/*
 * Select devices for SNMP VLAN query
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
# check maintaneance mode
$User->check_maintaneance_mode ();
# perm check popup
$User->check_module_permissions ("vlan", User::ACCESS_RWA, true, true);
# validate csrf cookie
$csrf = $User->Crypto->csrf_cookie ("create", "scan");

# scan disabled
if ($User->settings->enableSNMP!="1")           { $Result->show("danger", _("SNMP module disbled"), true, true); }

# domain Id must be int
if (!is_numeric($_POST['domainId']))            { $Result->show("danger", _("Invalid domain Id"), true, true); }
# fetch domain
$domain = $Tools->fetch_object ("vlanDomains", "id", $_POST['domainId']);
if ($domain===false)                            { $Result->show("danger", _("Invalid domain Id"), true, true); }

# fetch devices that use get_routing_table query
$scan_devices = $Tools->fetch_multiple_objects ("devices", "snmp_queries", "%get_vlan_table%", "id", true, true);

// if none set die
if ($scan_devices===false)                      { $Result->show("danger", _("No devices for SNMP VLAN query available"), true, true); }

?>

<!-- header -->
<div class='pHeader'><?php print _("Select devices"); ?></div>

<!-- content -->
<div class='pContent'>
    <h4><?php print _("Select devices to query VLAN table from"); ?></h4><hr>

    <div style="padding: 20px;">
        <form name="select-devices" id="select-devices-vlan-scan">
        <?php
        // loop
        foreach ($scan_devices as $d) {
            $description = !is_blank($d->description) ? "<span class='text-muted'>$d->description</span>" : "";
            print " <input type='checkbox' name='device-$d->id' checked> $d->hostname ($d->ip_addr) $description<br>";
        }
        ?>
        <input type="hidden" name="domainId" value="<?php print escape_input($_POST['domainId']); ?>">
        <input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
        </form>
    </div>
    <hr>

    <!-- scan result -->
    <div class="vlan-scan-result"></div>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-success show-vlan-scan-result"><?php print _('Scan'); ?></button>

	</div>
</div>