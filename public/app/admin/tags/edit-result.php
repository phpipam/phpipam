<?php

/**
 * Edit tag
 *************************************/

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

// fetch old values
if ($POST->action == 'delete') {
    $old_tag = $Admin->fetch_object('ipTags', 'id', $POST->id);
} else {
    $old_tag = new StdClass;
}

/* checks */
if ($POST->action == 'delete' && $old_tag->locked != 'No') {
    $Result->die('danger', _('Cannot delete locked tag'));
}

if ($POST->action != 'delete') {
    if (strlen((string) $POST->type) < 3) {
        $Result->die('danger', _('Invalid tag name'));
    }

    if (strlen((string) $POST->bgcolor) < 4) {
        $Result->die('danger', _('Invalid bg color'));
    }

    if (strlen((string) $POST->fgcolor) < 4) {
        $Result->die('danger', _('Invalid fg color'));
    }
}

// create array of values for modification
$values = ['id' => $POST->id,
    'type' => $POST->type,
    'bgcolor' => $POST->bgcolor,
    'fgcolor' => $POST->fgcolor,
    'showtag' => $POST->showtag,
    'compress' => $POST->compress,
    'updateTag' => $POST->updateTag,
];

// execute
if (! $Admin->object_modify('ipTags', $POST->action, 'id', $values)) {
    $Result->die('danger', _('Tag').' '.$User->get_post_action()._(' error'));
} else {
    $Result->warn('success', _('Tag').' '.$User->get_post_action()._(' success'));
}

// reset if delete to online
if ($POST->action == 'delete') {
    $Admin->update_object_references('ipaddresses', 'state', $old_tag->id, 0);
}
