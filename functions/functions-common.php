<?php

/**
 *
 *	functions for general use
 *
 */


/**
 * detect missing gettext and fake function
 */
if(!function_exists('gettext')) {
	function gettext ($text) 	{ return $text; }
	function _($text) 			{ return $text; }
}

/**
 * create links function
 *
 *	if rewrite is enabled in settings use rewrite, otherwise ugly links
 *
 *	levels: page=$1&section=$2&subnetId=$3&sPage=$4&ipaddrid=$5
 */
function create_link ($l1 = null, $l2 = null, $l3 = null, $l4 = null, $l5 = null, $install = false ) {
	# get settings
	global $User;

	# set rewrite
	if($User->settings->prettyLinks=="Yes") {
		if(!is_null($l5))		{ $link = "$l1/$l2/$l3/$l4/$l5/"; }
		elseif(!is_null($l4))	{ $link = "$l1/$l2/$l3/$l4/"; }
		elseif(!is_null($l3))	{ $link = "$l1/$l2/$l3/"; }
		elseif(!is_null($l2))	{ $link = "$l1/$l2/"; }
		elseif(!is_null($l1))	{ $link = "$l1/"; }
		else					{ $link = ""; }

		# prepend base
		$link = BASE.$link;
	}
	# normal
	else {
		if(!is_null($l5))		{ $link = "?page=$l1&section=$l2&subnetId=$l3&sPage=$l4&ipaddrid=$l5"; }
		elseif(!is_null($l4))	{ $link = "?page=$l1&section=$l2&subnetId=$l3&sPage=$l4"; }
		elseif(!is_null($l3))	{ $link = "?page=$l1&section=$l2&subnetId=$l3"; }
		elseif(!is_null($l2))	{ $link = "?page=$l1&section=$l2"; }
		elseif(!is_null($l1))	{ $link = "?page=$l1"; }
		else					{ $link = ""; }

		# prepend base
		$link = BASE.$link;
	}

	# result
	return $link;
}





/**
 *	@breadcrumbs functions
 * ------------------------
 */

/**
 *	print breadcrumbs
 */
function print_breadcrumbs ($Section, $Subnet, $req, $Address=null) {
	# subnets
	if($req['page'] == "subnets")		{ print_subnet_breadcrumbs  ($Section, $Subnet, $req, $Address); }
	# folders
	if($req['page'] == "folder")		{ print_folder_breadcrumbs  ($Section, $Subnet, $req); }
	# admin
	else if($req['page'] == "admin")	{ print_admin_breadcrumbs   ($Section, $Subnet, $req); }
	# tools
	else if ($req['page'] == "tools") 	{ print_tools_breadcrumbs   ($Section, $Subnet, $req); }
}

/**
 *	print address breadcrumbs
 */
function print_subnet_breadcrumbs ($Section, $Subnet, $req, $Address) {
	if(isset($req['subnetId'])) {
		# get all parents
		$parents = $Subnet->fetch_parents_recursive ($req['subnetId']);
		print "<ul class='breadcrumb'>";
		# remove root - 0
		array_shift($parents);

		# section details
		$section = (array) $Section->fetch_section(null, $req['section']);

		# section name
		print "	<li><a href='".create_link("subnets",$section['id'])."'>$section[name]</a> <span class='divider'></span></li>";

		# all parents
		foreach($parents as $parent) {
			$parent = $parent;
			$subnet = (array) $Subnet->fetch_subnet(null,$parent);
			if($subnet['isFolder']==1) {
				print "	<li><a href='".create_link("folder",$section['id'],$parent)."'><i class='icon-folder-open icon-gray'></i> $subnet[description]</a> <span class='divider'></span></li>";
			} else {
				print "	<li><a href='".create_link("subnets",$section['id'],$parent)."'>$subnet[description] ($subnet[ip]/$subnet[mask])</a> <span class='divider'></span></li>";
			}
		}
		# parent subnet
		$subnet = (array) $Subnet->fetch_subnet(null,$req['subnetId']);
		# ip set
		if(isset($req['ipaddrid'])) {
			$ip = (array) $Address->fetch_address (null, $req['ipaddrid']);
			print "	<li><a href='".create_link("subnets",$section['id'],$subnet['id'])."'>$subnet[description] ($subnet[ip]/$subnet[mask])</a> <span class='divider'></span></li>";
			print "	<li class='active'>$ip[ip]</li>";			//IP address
		}
		else {
			print "	<li class='active'>$subnet[description] ($subnet[ip]/$subnet[mask])</li>";		//active subnet

		}
		print "</ul>";
	}
}

/**
 *	prints admin breadcrumbs
 */
function print_admin_breadcrumbs ($Section, $Subnet, $req) {
	# nothing here
}

/**
 *	prints folder breadcrumbs
 */
function print_folder_breadcrumbs ($Section, $Subnet, $req) {
	if(isset($req['subnetId'])) {
		# get all parents
		$parents = $Subnet->fetch_parents_recursive ($req['subnetId']);
		print "<ul class='breadcrumb'>";
		# remove root - 0
		array_shift($parents);

		# section details
		$section = (array) $Section->fetch_section(null, $req['section']);

		# section name
		print "	<li><a href='".create_link("subnets",$section['id'])."'>$section[name]</a> <span class='divider'></span></li>";

		# all parents
		foreach($parents as $parent) {
			$parent = (array) $parent;
			$subnet = (array) $Subnet->fetch_subnet(null,$parent['id']);
			print "	<li><a href='".create_link("subnets",$section['id'],$parent)."'><i class='icon-folder-open icon-gray'></i> $subnet[description]</a> <span class='divider'></span></li>";
		}
		# parent subnet
		$subnet = (array) $Subnet->fetch_subnet(null,$req['subnetId']);
		print "	<li>$subnet[description]</li>";																		# active subnet
		print "</ul>";
	}
}

/**
 *	print tools breadcrumbs
 */
function print_tools_breadcrumbs ($Section, $Subnet, $req) {
	if(isset($req['tpage'])) {
		print "<ul class='breadcrumb'>";
		print "	<li><a href='".create_link("tools")."'>"._('Tools')."</a> <span class='divider'></span></li>";
		print "	<li class='active'>$req[tpage]></li>";
		print "</ul>";
	}
}









/**
 *	@scan helper functions
 * ------------------------
 */

/**
 *	Ping address helper for CLI threading
 */
function ping_address ($address) {
	global $Scan;
	//scan
	return $Scan->ping_address ($address);
}

/**
 *	Telnet address helper for CLI threading
 */
function telnet_address ($address, $port) {
	global $Scan;
	//scan
	return $Scan->telnet_address ($address, $port);
}

/**
 *	fping subnet helper for fping threading
 */
function fping_subnet ($subnet_cidr, $return = true) {
	global $Scan;
	//scan
	return $Scan->ping_address_method_fping_subnet ($subnet_cidr, $return);
}





/*
to rewrite

	rewrite methods in tools to general

	write_changelog triggers email

	on subnet creation option to scan subnet for new addresses

	changelog on edit ip, edit_object in admin
*/

?>