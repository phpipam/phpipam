<?php
//  /app/subnets/mastersubnet-dropdown.php?section=<integer>&cidr=<string>&prev=<integer>

/* functions */
require( dirname(__FILE__) . '/../../functions/functions.php');

# initialize user object
$Database = new Database_PDO;
$User     = new User ($Database);
// $Admin    = new Admin ($Database, false);
$Sections = new Sections ($Database);
$Subnets  = new Subnets ($Database);
// $Tools    = new Tools ($Database);
// $Result   = new Result ();

# verify that user is logged in
$User->check_user_session();

/**
 * Return array of valid subnets satisfying strict subnet requirements
 * @param  Subnets      $Subnets
 * @param  integer      $sectionId
 * @param  string       $cidr
 * @param  array|string $result_fields
 * @return array
 */
function get_strict_subnets($Subnets, $sectionId, $cidr, $result_fields="*") {
	$strict_subnets = $Subnets->fetch_overlapping_subnets($cidr, 'sectionId', $sectionId, $result_fields);
	if (!is_array($strict_subnets)) return array();

	list(,$cidr_mask) = explode('/', $cidr);

	foreach ($strict_subnets as $i => $subnet) {
		if ($subnet->mask >= $cidr_mask) unset($strict_subnets[$i]); else break;
	}
	return $strict_subnets;
}


$sectionId = isset($_GET['section']) ? (int) $_GET['section'] : 0;
$cidr = isset($_GET['cidr']) ? $_GET['cidr'] : '';
$previously_selected =  isset($_GET['prev']) ? (int) $_GET['prev'] : -1;

$section = $Sections->fetch_section('id', $sectionId);
if (!is_object($section)) { return ''; }

// Don't fetch all fields
$fields = array('id','masterSubnetId','isFolder','subnet','mask','description');

$strict_subnets = get_strict_subnets($Subnets, $sectionId, $cidr, $fields);

$folders = $Subnets->fetch_multiple_objects('subnets', 'isFolder', '1', 'id', true, false, $fields);
if (!is_array($folders)) $folders = array();

// Generate HTML <options> dropdown menu
$dropdown = new MasterSubnetDropDown($Subnets, $previously_selected);

// Show overlapping subnets (possible parents)
if (!empty($strict_subnets)) {
	$dropdown->optgroup_open(_('Strict Subnets'));
	foreach($strict_subnets as $subnet) { $dropdown->subnets_add_object($subnet); }
}

// Show folders
$dropdown->optgroup_open(_('Folders'));
foreach($folders as $folder) { $dropdown->subnets_tree_add($folder); }
$dropdown->subnets_tree_render(true);


if ($section->strictMode == 0) {
	// Strict mode is disabled, allow nested chaos....
	$all_subnets = $Subnets->fetch_section_subnets($sectionId, false, false, $fields);
	if (!is_array($all_subnets)) $all_subnets = array();

	foreach($all_subnets as $subnet) {
		if ($subnet->isFolder) $subnet->disabled = 1; else break;
	}

	// Show all subnets
	$dropdown->optgroup_open(_('Subnets'));
	foreach($all_subnets as $subnet) { $dropdown->subnets_tree_add($subnet); }
	$dropdown->subnets_tree_render(false);
}

echo $dropdown->html();
