<?php

/*
 * Print section subnets (JSON)
 *************************************************/
header('Content-Type: application/json');

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
// $Admin    = new Admin ($Database, false);
$Sections	= new Sections ($Database);
$Subnets    = new Subnets ($Database);
$Tools      = new Tools ($Database);
// $Result   = new Result ();

# verify that user is logged in
$User->check_user_session();

# Validate inputs
$sectionId = filter_var($_GET['sectionId'], FILTER_VALIDATE_INT);
$showSupernetOnly = filter_var($_GET['showSupernetOnly'], FILTER_VALIDATE_BOOLEAN);
$offset = filter_var($_GET['offset'], FILTER_VALIDATE_INT);
$limit  = filter_var($_GET['limit'], FILTER_VALIDATE_INT);

if ($sectionId===false || $offset===false || $limit===false) { return; }

# Ensure search is a valid CIDR address
$search_cidr = false;
if (isset($_GET['search'])) {
    if ($Subnets->verify_cidr_address($_GET['search'])===true) {
        $search_cidr = htmlentities($_GET['search']);
    }
}

# check section permission
$permission = $Sections->check_permission($User->user, $sectionId);
if ($permission == 0 ) { return; }

# set custom fields
$custom_fields = $Tools->fetch_custom_fields ('subnets');

# set hidden fields
$hidden_fields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_fields = isset($hidden_fields['subnets']) && is_array($hidden_fields['subnets']) ? $hidden_fields['subnets'] : array();

$subnetsTree = new SubnetsTree($Subnets, $User->user);
$subnetsTable = new SubnetsTable($Tools, $custom_fields, $showSupernetOnly);

if ($search_cidr === false) {
    $subnets = $Subnets->fetch_section_subnets($sectionId, false, false, array());
} else {
    $subnets = $Subnets->fetch_overlapping_subnets($search_cidr, 'sectionId', $sectionId);
}

if (is_array($subnets)) {
    foreach($subnets as $subnet) { $subnetsTree->add($subnet); }
}
$subnetsTree->walk(false);

print $subnetsTable->json_paginate($subnetsTree, $offset, $limit);
