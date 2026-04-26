<?php

// process permissions
$permissions = db_json_decode($user['module_permissions'], true);
// loop
if (is_array($permissions)) {
    if (sizeof($permissions)>0) {
        foreach ($permissions as $module=>$perm) {
            $user['perm_'.$module] = $perm;
        }
    }
}

// admin fix
foreach ($User->get_modules_with_permissions() as $m) {
    if($user['role']=="Administrator") {
        $user['perm_'.$m] = 3;
    }
    else {
        if(!isset($user['perm_'.$m])) {
        $user['perm_'.$m] = 0;
        }
    }
}

$perm_names = $User->get_modules_with_permissions_prefix_perm();

// user page
if(($GET->page=="administration" && $GET->section=="users" && $GET->sPage=="modules") || ($GET->section=="user-menu")) {

    print '<div class="panel panel-default" style="max-width:600px;min-width:350px;">';
    print '<div class="panel-heading">'._("User permissions for phpipam modules").'</div>';
    print ' <ul class="list-group">';

    foreach ($user as $key=>$u) {
        if(strpos((string) $key, "perm_")!==false && array_key_exists((string) $key, $perm_names)) {
            print '<li class="list-group-item">';
            // title
            print "<span style='padding-top:8px;' class='pull-l1eft'>";
            print "<strong>"._($perm_names[$key])."</strong>";
            print "</span>";
            // perms
            print ' <strong class="btn-group pull-right">';
            print $User->print_permission_badge($user[$key]);
            print ' </strong>';
            print '</li>';

            print "<div class='clearfix'></div>";
        }
    }
    print ' </ul>';
    print '</div>';
}
else {
    print "<table class='table-noborder popover_table'>";
    foreach ($user as $key=>$u) {
        if(strpos((string) $key, "perm_")!==false && array_key_exists((string) $key, $perm_names)) {
            print "<tr><td>"._($perm_names[$key])."</td><td>".$User->print_permission_badge($user[$key])."</td></tr>";
        }
    }
    print "</table>";
}