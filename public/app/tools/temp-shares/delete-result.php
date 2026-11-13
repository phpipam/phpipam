<?php

/**
 * Script to remove temp access
 *************************************/

/* functions */
require_once __DIR__.'/../../../../functions/functions.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Subnets = new Subnets($Database);
$Admin = new Admin($Database, false);
$Result = new Result;

// verify that user is logged in
$User->check_user_session();

$User->Crypto->csrf_validate($POST->csrf_cookie) === false ? $Result->die('danger', _('Invalid CSRF cookie')) : '';

print '<div class="pHeader">'._('Remove temporary share').'</div>';
print '<div class="pContent">';

/* checks */
if ($User->settings->tempShare != 1) {
    $Result->die('danger', _('Temporary sharing disabled'));
}
if (strlen((string) $POST->code) != 32) {
    $Result->die('danger', _('Invalid code'));
}

// remove object
$old_access = db_json_decode($User->settings->tempAccess, true);
// check that it exists
if (! isset($old_access[$POST->code])) {
    $Result->die('danger', _('Code does not exist'));
}
// remove
unset($old_access[$POST->code]);

// reset
$new_access = ! is_array($old_access) ? '' : json_encode(array_filter($old_access));

// execute
if (! $Admin->object_modify('settings', 'edit', 'id', ['id' => 1, 'tempAccess' => $new_access])) {
    $Result->die('danger', _('Temporary share delete error'));
} else {
    $Result->warn('success', _('Temporary share deleted'));
}

?>
</div>

<div class="pFooter">
    <div class="btn-group">
        <button class="btn btn-sm btn-default hidePopups"><?php print _('Close'); ?></button>
    </div>
</div>