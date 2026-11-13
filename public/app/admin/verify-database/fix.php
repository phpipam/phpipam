<?php

/**
 * Script to fix missing db fields
 ****************************************/

/* functions */
require_once __DIR__.'/../../../../functions/functions.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Tools = new Tools($Database);
$Result = new Result;

// verify that user is logged in
$User->check_user_session();

// admin user is required
$User->is_admin();

/* verifications */
if (! isset($POST->tableid) || is_blank($POST->tableid)) {
    $Result->die('danger', _('Wrong parameters'));
} else {
    // fix table
    if ($POST->type == 'table') {
        $Tools->fix_table($POST->tableid);
        $Result->warn('success', _('Table fixed'));
    }
    // fix field
    elseif ($POST->type == 'field') {
        $Tools->fix_field($POST->tableid, $POST->fieldid);
        $Result->warn('success', _('Field fixed'));
    } else {
        $Result->die('danger', _('Wrong parameters'));
    }
}
