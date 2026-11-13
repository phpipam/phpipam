<?php

/**
 * Script to edit domain
 ***************************/

/* functions */
require_once __DIR__.'/../../../../functions/functions.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Admin = new Admin($Database, false);
$Tools = new Tools($Database);
$Result = new Result;
$PowerDNS = new PowerDNS($Database);

// verify that user is logged in
$User->check_user_session();
// check maintaneance mode
$User->check_maintaneance_mode();

// perm check popup
if ($POST->action == 'edit') {
    $User->check_module_permissions('pdns', User::ACCESS_RW, true, false);
} else {
    $User->check_module_permissions('pdns', User::ACCESS_RWA, true, false);
}

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// checks / validation
if ($POST->action != 'delete') {
    // fqdn
    if ($POST->action == 'add') {
        if ($Tools->validate_hostname($POST->name) === false) {
            $Result->die('danger', _('Invalid domain name'));
        }
    }

    // master
    if (! is_blank($POST->master)) {
        // if multiple masters
        if (strpos((string) $POST->master, ',') !== false) {
            // to array and trim, check each
            $masters = array_filter(explode(',', (string) $POST->master));
            foreach ($masters as $m) {
                if (! filter_var($m, FILTER_VALIDATE_IP)) {
                    $Result->die('danger', _('Master must be an IP address').' - '.$m);
                }
            }
        } else {
            if (! filter_var($POST->master, FILTER_VALIDATE_IP)) {
                $Result->die('danger', _('Master must be an IP address').' - '.escape_input($POST->master));
            }
        }
    }

    // type
    if (! in_array($POST->type, (array) $PowerDNS->domain_types)) {
        $Result->die('danger', _('Invalid domain type'));
    }

    // new domain
    if ($POST->action == 'add' && ! isset($POST->manual)) {
        if ($Tools->validate_email($POST->hostmaster) === false) {
            $Result->die('danger', _('Invalid domain hostmaster'));
        }
    }

    // if slave master must be present
    if ($POST->type == 'SLAVE') {
        if (is_blank($POST->master)) {
            $Result->die('danger', _('Please set master server(s) if domain type is SLAVE'));
        } else {
            if (strpos((string) $POST->master, ',') !== false) {
                // to array and trim, check each
                $masters = array_filter(explode(',', (string) $POST->master));
                foreach ($masters as $m) {
                    if (! filter_var($m, FILTER_VALIDATE_IP)) {
                        $Result->die('danger', _('Master must be an IP address').' - '.$m);
                    }
                }
            } else {
                if (! filter_var($POST->master, FILTER_VALIDATE_IP)) {
                    $Result->die('danger', _('Master must be an IP address').' - '.escape_input($POST->master));
                }
            }
        }
    }

    // if update sve old domain !
    if ($POST->action == 'edit') {
        $old_domain = $PowerDNS->fetch_domain($POST->id);
    }
}

// set update array
$values = ['id' => $POST->id,
    'master' => $POST->master,
    'type' => $POST->type,
];
// name only on add
if ($POST->action == 'add') {
    $values['name'] = $POST->name;
}

// remove all references if delete
if ($POST->action == 'delete') {
    $PowerDNS->remove_all_records($values['id']);
}

// for creation validate default records before creating them ! => true means check only
if ($POST->action == 'add' && ! isset($POST->manual)) {
    $PowerDNS->create_default_records($POST->as_array(), true);
}

// update
if (! $PowerDNS->domain_edit($POST->action, $values)) {
    $Result->die('danger', _('Failed to').' '.$User->get_post_action().' '._('domain').'!');
} else {
    $Result->warn('success', _('Domain').' '.$User->get_post_action().' '._('successful').'!');
}

// create default records
if ($POST->action == 'add' && ! isset($POST->manual)) {
    $PowerDNS->create_default_records($POST->as_array());
}
