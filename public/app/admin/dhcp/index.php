<?php
// verify that user is logged in
$User->check_user_session();
// perm check
$User->check_module_permissions('dhcp', User::ACCESS_R, true, false);

// valid tabs
$tabs = ['subnets', 'leases', 'reservations', 'settings', 'config'];
?>
<div class="DHCP">

<!-- display existing groups -->
<h4><?php print _('DHCP management'); ?></h4>
<hr><br>

<?php
// perm check
if ($User->get_module_permissions('dhcp') == User::ACCESS_NONE) {
    $Result->warn('danger', _('You do not have permissions to access this module'));
} elseif ($User->settings->enableDHCP == 1) { ?>
    <?php
    // validate DHCP settings - JSON
    if ($Tools->validate_json_string($User->settings->DHCP) === false) {
        $Result->warn('danger', 'Error parsing DHCP settings: '.$Tools->json_error);

        // settings
        include __DIR__.'/settings.php';
    } else {
        // parse and verify settings
        $dhcp_db = db_json_decode($User->settings->DHCP, true);

        // DHCP wrapper class
        $DHCP = new DHCP($dhcp_db['type'], $dhcp_db['settings']);

        // read config
        $config = $DHCP->read_config();
        ?>
        <!-- tabs -->
        <ul class="nav nav-tabs">
            <?php
            // default tab
            if (! isset($GET->subnetId)) {
                $GET->subnetId = 'subnets';
            }

        // check
        if (! in_array($GET->subnetId, $tabs)) {
            $Result->die('danger', 'Invalid request');
        }

        // print
        foreach ($tabs as $t) {
            $title = str_replace('_', ' ', $t);
            $class = $GET->subnetId == $t ? "class='active'" : '';
            print "<li role='presentation' $class><a href=".create_link('administration', 'dhcp', "$t").'>'._(ucwords(str_replace('_', ' ', $title))).'</a></li>';
        }
        ?>
        </ul>

        <div>
        <?php
        // include file
        if (! file_exists(__DIR__.'/'.$GET->subnetId.'.php')) {
            $Result->die('danger', 'Invalid request');
        } elseif (! in_array($GET->subnetId, $tabs)) {
            $Result->die('danger', 'Invalid request');
        } else {
            include __DIR__.'/'.$GET->subnetId.'.php';
        }
        ?>
        </div>
        <?php
    }
} else {
    $Result->warn('info', _('Please enable DHCP module under server management'));
}
?>
</div>