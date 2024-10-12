<?php

/**
 * Script to print subnets from selected section
 *
 *	Left menu
 */

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf_ffss = $User->Crypto->csrf_cookie ("create-if-not-exists", "find_free_section_subnets");

# ID must be numeric
if(!is_numeric($GET->section)) { $Result->show("danger",_('Invalid ID'), true); }


# Admin check, otherwise load requested subnets
if ($GET->section == 'Administration') {
    if (!$User->is_admin()) { $Result->show("danger",_('Sorry, must be admin'), true); }
    else 					{ include('admin/admin-menu.php'); }
}
# load subnets
else {
	#  check for possible subsection
	$subsections = $Sections->fetch_subsections ($GET->section);

	# permissions
	foreach($subsections as $k=>$ss) {
		$perm = $Sections->check_permission ($User->user, $ss->id);
		# remove not permitted
		if($perm==0 ) 	{ unset($subsections[$k]); }
	}

	# print belonging subsections if they exist
	if(sizeof(@$subsections)>0) {
		# title
		print "<h4>"._('Belonging subsections')."</h4><hr>";
		# table
		print "<table class='table table-noborder table-auto'>";

		foreach($subsections as $ss) {
			print "<tr>";
			print "	<td><h5 style='padding-left:10px;'><i class='fa fa-gray fa-angle-right'></i> <a href='".create_link("subnets",$ss->id)."' rel='tooltip' data-placement='right' title='$ss->description'>$ss->name</a></h5></td>";
			print "</tr>";
		}
		print "</table>";
	}


	/* print Subnets */

    # get section details
    $section = (array) $Sections->fetch_section("id", $GET->section);

    # verify permissions
	$section_permission = $Sections->check_permission ($User->user, $GET->section);

	# no access
	if($section_permission == 0) 	{ $Result->show("danger",_('You do not have access to this section'), true); }

    # invalid section id
    if(sizeof($section) == 0) 		{ $Result->show("danger",_('Section does not exist'), true); }

    # expand all folders?
    if(isset($_COOKIE['expandfolders'])) {
	    if($_COOKIE['expandfolders'] == "1")	{ $iconClass='fa-compress'; $action = 'open';}
	    else									{ $iconClass='fa-expand';  	$action = 'close'; }
    }
    else 										{ $iconClass='fa-expand';  	$action = 'close';}

    # Check if it has parent, and if so print back link
    if($section['masterSection']!=0 && $section['masterSection']!=null)	{
    	# get details
    	$master_section = (array) $Sections->fetch_section ("id", $section['masterSection']);

	    print "<div class='subnets' style='padding-top:10px;'>";
	    print "	<a href='".create_link("subnets",$master_section['id'])."'><i class='fa fa-gray fa-angle-left fa-pad-left'></i> "._('Back to')." $master_section[name]</a><hr>";
	    print "</div>";
    }

	/* print subnets menu ---------- */
    if ($section['showSubnet'] == 1) {
        # header
        print "<h4>"._('Available subnets')." <span class='pull-right' style='margin-right:5px;cursor:pointer;'><i class='fa fa-gray fa-sm $iconClass' rel='tooltip' data-placement='bottom' title='"._('Expand/compress all folders')."' id='expandfolders' data-action='$action'></i></span></h4>";
        print "<hr>";

        if (!isset($section_subnets)|| !is_array($section_subnets))
            $section_subnets = (array) $Subnets->fetch_section_subnets($GET->section, false, false, []);
        print $Subnets->print_subnets_menu($User->user, $section_subnets);
    }
	/* print VLAN menu ---------- */
	if($section['showVLAN'] == 1 && $User->get_module_permissions ("vlan")>=User::ACCESS_R) {
		$vlans = $Sections->fetch_section_vlans($GET->section);

		# if some is present
		if($vlans) {
			print "<div class='subnets'>";
				# title
				print "<hr><h4>"._('Associated VLANs')."</h4><hr>";
				# create and print menu
				if (!is_array($section_subnets))
					$section_subnets = (array) $Subnets->fetch_section_subnets($GET->section, false, false, []);
				print $Subnets->print_vlan_menu($User->user, $vlans, $section_subnets, $GET->section);
			print "</div>";
		}
	}


	/* print VRF menu ---------- */
	if($User->settings->enableVRF==1 && $section['showVRF']==1 && $User->get_module_permissions ("vrf")>=User::ACCESS_R) {
		$vrfs = $Sections->fetch_section_vrfs($GET->section);

		# if some is present
		if($vrfs) {
			print "<div class='subnets'>";
				# title
				print "<hr><h4>"._('Associated VRFs')."</h4><hr>";
				# create and print menu
				if (!is_array($section_subnets))
					$section_subnets = (array) $Subnets->fetch_section_subnets($GET->section, false, false, []);
				print $Subnets->print_vrf_menu($User->user, $vrfs, $section_subnets, $GET->section);
			print "</div>";
		}
	}
}

# add new subnet if permitted
$section_permission = $Sections->check_permission ($User->user, $GET->section);
if($section_permission == 3) {
	print "<div class='action'>";
	if(isset($GET->subnetId)) {
	print "	<button class='btn btn-xs btn-default pull-left' id='hideSubnets' rel='tooltip' title='"._('Hide subnet list')."' data-placement='right'><i class='fa fa-gray fa-sm fa-chevron-left'></i></button>";
	}
	print "	<span>";
	print "	<div class='btn-group'>";
	print "	 <button id='add_subnet' class='btn btn-xs btn-default btn-success'  rel='tooltip' data-container='body'  data-placement='top' title='"._('Add new subnet to')." $section[name]'  data-subnetId='' data-sectionId='$section[id]' data-action='add'><i class='fa fa-sm fa-plus'></i></button>";
	print "	 <button class='btn btn-xs btn-default btn-success open_popup' data-script='app/admin/subnets/find_free_section_subnets.php' data-csrf_cookie='$csrf_ffss' data-class='700' rel='tooltip' data-container='body'  data-placement='top' title='"._('Search for free subnets in section ')." $section[name]'  data-sectionId='$section[id]'><i class='fa fa-sm fa-search'></i></button>";
	# snmp
	if($User->settings->enableSNMP==1) {
    $csrf = $User->Crypto->csrf_cookie ("create", "scan");
	print "	 <button class='btn btn-xs btn-default btn-success' id='snmp-routing-section'  rel='tooltip' data-container='body' data-sectionId='$section[id]' data-subnetId='0' data-csrf-cookie='$csrf'  data-placement='top' title='"._('Search for subnets through SNMP')."'><i class='fa fa-sm fa-cogs'></i></button>";
    }
	print "	 <button id='add_folder' class='btn btn-xs btn-default btn-success'  rel='tooltip' data-container='body'  data-placement='top' title='"._('Add new folder to')." $section[name]'  data-subnetId='' data-sectionId='$section[id]' data-action='add'><i class='fa fa-sm fa-folder'></i></button>";
	print "	</div>";
	print "	</span>";
	print "</div>";
}