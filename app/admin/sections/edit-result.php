<?php

/**
 * Function to add / edit / delete section
 *************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Sections	= new Sections ($Database);
$Subnets	= new Subnets ($Database);
$Addresses	= new Addresses ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check if site is demo
$User->is_demo();
# check maintaneance mode
$User->check_maintaneance_mode ();
# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "section", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";



# If confirm is not set print delete warning
if ($POST->action=="delete" && !isset($POST->deleteconfirm)) {
	//for ajax to prevent reload
	print "<div style='display:none'>alert alert-danger</div>";
	//result
	print "<div class='alert alert-warning'>";

	//fetch all subsections
	$subsections = $Sections->fetch_subsections ($POST->id);

	//print what will be deleted
	if(sizeof($subsections)>0) {
		$subnets  = $Subnets->fetch_section_subnets($POST->id);				//fetch all subnets in section
		$num_subnets = sizeof($subnets);										//number of subnets to be deleted
		if(sizeof($subnets)>0) {
			foreach($subnets as $s) {
				$out[] = $s;
			}
		}
		//fetch subsection subnets
		foreach($subsections as $ss) {
			$subsection_subnets = $Subnets->fetch_section_subnets($ss->id);	//fetch all subnets in subsection
			if(sizeof($subsection_subnets)>0) {
				foreach($subsection_subnets as $sss) {
					$out[] = $sss;
				}
			}
			$num_subnets = $num_subnets + sizeof($subsection_subnets);
			//count all addresses that will be deleted!
			$ipcnt = $Addresses->count_addresses_in_multiple_subnets($out);
		}
	}
	# no subsections
	else {
		$subnets  = $Subnets->fetch_section_subnets ($POST->id);			//fetch all subnets in section
		$num_subnets = is_array($subnets) ? sizeof($subnets) : 0;
		$ipcnt = $Addresses->count_addresses_in_multiple_subnets($subnets);
	}

	# printout
	print "<strong>"._("Warning")."</strong>: "._("I will delete").":<ul>";
	print "	<li>$num_subnets "._("subnets")."</li>";
	if($ipcnt>0) {
	print "	<li>$ipcnt "._("IP addresses")."</li>";
	}
	print "</ul>";

	print "<hr><div style='text-align:right'>";
	print _("Are you sure you want to delete above items?")." ";
	print "<div class='btn-group'>";
	print "	<a class='btn btn-sm btn-danger editSectionSubmitDelete' id='editSectionSubmitDelete'>"._("Confirm")."</a>";
	print "</div>";
	print "</div>";
	print "</div>";
}
# ok, update section
else {

    # fetch old section
    $section_old = $Sections->fetch_section ("id", $POST->id);
	if (!is_object($section_old)) {
		$section_old = new Params();
	}
    // parse old permissions
    $old_permissions = db_json_decode($section_old->permissions, true);

	list($removed_permissions, $changed_permissions, $new_permissions) = $Sections->get_permission_changes ($POST->as_array(), $old_permissions);

	# set variables for update
	$values = array(
					"id"               => $POST->id,
					"name"             => $POST->name,
					"description"      => $POST->description,
					"strictMode"       => $POST->strictMode,
					"subnetOrdering"   => $POST->subnetOrdering,
					"showSubnet"       => $POST->showSubnet,
					"showVLAN"         => $POST->showVLAN,
					"showVRF"          => $POST->showVRF,
					"showSupernetOnly" => $POST->showSupernetOnly,
					"masterSection"    => $POST->masterSection,
					"permissions"      => json_encode($new_permissions)
					);

	# execute update
	if(!$Sections->modify_section ($POST->action, $values, $POST->id))	{ $Result->show("danger", _("Section")." ".$User->get_post_action()." "._("failed"), false); }
	else { $Result->show("success", _("Section")." ".$User->get_post_action()." "._("successful"), false); }

	# delegate
	if ($POST->delegate==1) {
		// fetch section subnets (use $subnets object to prime its cache)
		$section_subnets = $Subnets->fetch_multiple_objects ("subnets", "sectionId", $POST->id);
		if (!is_array($section_subnets)) $section_subnets = array();

		// apply permission changes
		$Subnets->set_permissions ($section_subnets, $removed_permissions, $changed_permissions);
	}
}