<?php

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# Don't corrupt output with php errors!
disable_php_errors();

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Sections	= new Sections ($Database);
$Subnets    = new Subnets ($Database);
$Tools      = new Tools ($Database);

# verify that user is logged in
$User->check_user_session();

/*
 * Print section subnets (JSON)
 *************************************************/
header('Content-Type: application/json');

/**
 * Convert search strings into sensible CIDRs.
 * @param  string $search_cidr
 * @return string
 */
function complete_search_cidr($search_cidr) {
    if (!isset($search_cidr))
        return '';

    $search_cidr = trim($search_cidr);

    # Check if mask is already provided
    if (strpos($search_cidr, '/') !== false)
        return $search_cidr;

    # Complete the 'search' cidr by guessing the mask, IPv4 only...
    $ipv4 = array_filter(explode('.', $search_cidr), 'strlen');
    $search_cidr = implode('.', $ipv4);

    switch (sizeof($ipv4)) {
        case 1:
            return $search_cidr.'.0.0.0/8';
        case 2:
            return $search_cidr.'.0.0/16';
        case 3:
            return $search_cidr.'.0/24';
        case 4:
            return $search_cidr.'/32';
        default:
            return '';
    }
}


# Validate inputs
$search           = isset($_GET['search']) ? $_GET['search'] : null;
$sectionId        = filter_var($_GET['sectionId'], FILTER_VALIDATE_INT);
$showSupernetOnly = filter_var($_GET['showSupernetOnly'], FILTER_VALIDATE_BOOLEAN);
$offset           = filter_var($_GET['offset'], FILTER_VALIDATE_INT);
$limit            = filter_var($_GET['limit'], FILTER_VALIDATE_INT);

if ($sectionId===false || $offset===false || $limit===false) { return; }

# Try to auto-complete search string.
$search_cidr = complete_search_cidr($search);
# Ensure search is a valid CIDR address
if ($Subnets->verify_cidr_address($search_cidr)!==true)
    $search_cidr = false;

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