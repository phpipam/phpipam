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
 * @param  Subnets $Subnets [description]
 * @param  array $search  [description]
 * @param  string $cidr    [description]
 * @return array          [description]
 */
function get_strict_subnets($Subnets, $search, $cidr) {
	$strict_subnets = array();
	if ( $Subnets->verify_cidr_address($cidr) !== true) { return array(); }

	list($cidr_addr, $cidr_mask) = explode('/', $cidr);

	$bmask = $Subnets->get_network_bitmasks();

	$cidr_decimal = $Subnets->transform_to_decimal($cidr_addr);
	$cidr_type = $Subnets->identify_address($cidr_addr);

	// Calculate what the parent subnet decimal address would be for each mask and check if it exists
	$search_mask = $cidr_mask - 1;
	while($search_mask >= 0) {
		 $search_subnet= gmp_strval(gmp_and($cidr_decimal, $bmask[$cidr_type][$search_mask]['lo']));

		if (is_array($search[$cidr_type][$search_mask][$search_subnet])) {
			$strict_subnets = array_merge($strict_subnets, $search[$cidr_type][$search_mask][$search_subnet]);
		}
		$search_mask--;
	}

	return $strict_subnets;
}



$sectionId = isset($_GET['section']) ? (int) $_GET['section'] : 0;
$cidr = isset($_GET['cidr']) ? $_GET['cidr'] : '';
$previously_selected =  isset($_GET['prev']) ? (int) $_GET['prev'] : -1;

$section = $Sections->fetch_section('id', $sectionId);
if (!is_object($section)) { return ''; }

$folders = array();
$search = array();
$all_subnets = $Subnets->fetch_section_subnets ($sectionId, array('id','masterSubnetId','isFolder','subnet','mask','description'));

foreach($all_subnets as $subnet) {
	if ($subnet->isFolder) {
		$folders[] = clone $subnet;
		$subnet->disabled = 1;
	} else {
		$subnet->type = $Subnets->identify_address($subnet->subnet);
		$search[$subnet->type][$subnet->mask][$subnet->subnet][] = $subnet;
	}
}

$strict_subnets = get_strict_subnets($Subnets, $search, $cidr);


// Generate HTML <options> dropdown menu
$dropdown = new MasterSubnetDropDown($Subnets, $previously_selected);

if (!empty($strict_subnets)) {
	$dropdown->optgroup_open(_('Strict Subnets'));
	foreach($strict_subnets as $subnet) { $dropdown->subnets_add_object($subnet); }
}

$dropdown->optgroup_open(_('Folders'));
foreach($folders as $folder) { $dropdown->subnets_tree_add($folder); }
$dropdown->subnets_tree_render(true);

if ($section->strictMode == 0) {
	$dropdown->optgroup_open(_('Subnets'));
	foreach($all_subnets as $subnet) { $dropdown->subnets_tree_add($subnet); }
	$dropdown->subnets_tree_render(false);
}

echo $dropdown->html();
