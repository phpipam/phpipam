<?php

/**
 * Script to edit nameserver sets
 ***************************/

/* functions */
require_once __DIR__.'/../../../../functions/functions.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Admin = new Admin($Database);
$Result = new Result;

// verify that user is logged in
$User->check_user_session();
// admin check
$User->is_admin();

// check maintaneance mode
$User->check_maintaneance_mode();
if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// Name and primary nameserver must be present!
if ($POST->action != 'delete') {
    $m = 1;
    $nservers_reindexed = [];
    // reindex
    foreach ($POST as $k => $v) {
        if (strpos((string) $k, 'namesrv-') !== false) {
            $nservers_reindexed['namesrv-'.$m] = $v;
            $m++;
            unset($POST->{$k});
        }
    }

    // join
    $POST->read($nservers_reindexed);

    if ($POST->name == '') {
        $Result->die('danger', _('Name is mandatory'));
    }

    if (trim($POST->namesrv - 1) == '') {
        $Result->die('danger', _('Primary nameserver is mandatory'));
    }
}

// merge nameservers
foreach ($POST as $key => $line) {
    if (! is_blank(strstr((string) $key, 'namesrv-'))) {
        if (! is_blank($line)) {
            $all_nameservers[] = trim((string) $line);
        }
    }
}

$POST->namesrv1 = isset($all_nameservers) ? implode(';', $all_nameservers) : '';

// set sections
$temp = [];
foreach ($POST as $key => $line) {
    if (! is_blank(strstr((string) $key, 'section-'))) {
        $key2 = str_replace('section-', '', (string) $key);
        $temp[] = $key2;
        unset($POST->{$key});
    }
}

// glue sections together
$POST->permissions = count($temp) > 0 ? implode(';', $temp) : null;

// set update array
$values = ['id' => $POST->nameserverid,
    'name' => $POST->name,
    'permissions' => $POST->permissions,
    'namesrv1' => $POST->namesrv1,
    'description' => $POST->description,
];
// update
if (! $Admin->object_modify('nameservers', $POST->action, 'id', $values)) {
    $Result->die('danger', _('Failed to').' '.$User->get_post_action().' '._('nameserver set').'!');
} else {
    $Result->warn('success', _('Nameserver set').' '.$User->get_post_action().' '._('successful').'!');
}

// remove all references if delete
if ($POST->action == 'delete') {
    $Admin->remove_object_references('subnets', 'nameserverid', $POST->nameserverid);
}
