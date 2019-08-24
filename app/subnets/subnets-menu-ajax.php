<?php

require_once( dirname(__FILE__) . '/../../functions/functions.php' );

$Database = new Database_PDO;
$Result   = new Result;
$User     = new User ($Database);
$Subnets  = new Subnets ($Database);

# verify that user is logged in
$User->check_user_session();

$section_subnets = (array) $Subnets->fetch_section_subnets($_GET['section'], false, false, array());

$subnetsTree = new SubnetsTree($Subnets, $User->user);
if (is_array($section_subnets)) {
    foreach($section_subnets as $subnet) {
        $subnetsTree->add($subnet);
    }
    $subnetsTree->walk(false);
}

$menu = new SubnetsMenu($Subnets, $_COOKIE['sstr'], $_COOKIE['expandfolders'], $_GET['subnetId']);
$menu->subnetsTree($subnetsTree);

print $menu->html();
