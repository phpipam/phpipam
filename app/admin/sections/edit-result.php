<?php

/**
 * Function to add / edit / delete section
 *************************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

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

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->csrf_cookie ("validate", "section", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";



# If confirm is not set print delete warning
if ($_POST['action']=="delete" && !isset($_POST['deleteconfirm'])) {
	//for ajax to prevent reload
	print "<div style='display:none'>alert alert-danger</div>";
	//result
	print "<div class='alert alert-warning'>";

	//fetch all subsections
	$subsections = $Sections->fetch_subsections ($_POST['id']);

	//print what will be deleted
	if(sizeof($subsections)>0) {
		$subnets  = $Subnets->fetch_section_subnets($_POST['id']);				//fetch all subnets in section
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
		$subnets  = $Subnets->fetch_section_subnets ($_POST['id']);			//fetch all subnets in section
		$num_subnets = sizeof($subnets);
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

    // init permission parameters
    $new_permissions = array();             // permissions posted
    $old_permissions = array();             // existing subnet permissions
    $removed_permissions = array();         // removed permissions
    $changed_permissions = array();         // changed permissions


    # fetch old section
    $section_old = $Sections->fetch_section ("id", $_POST['id']);
    // parse old permissions
    $old_permissions = json_decode($section_old->permissions, true);

	# set variables for update
	$values = array("id"=>@$_POST['id'],
					"name"=>@$_POST['name'],
					"description"=>@$_POST['description'],
					"strictMode"=>@$_POST['strictMode'],
					"subnetOrdering"=>@$_POST['subnetOrdering'],
					"showVLAN"=>@$_POST['showVLAN'],
					"showVRF"=>@$_POST['showVRF'],
					"masterSection"=>@$_POST['masterSection']
					);

	# set new posted permissions
	foreach($_POST as $key=>$val) {
		if(substr($key, 0,5) == "group") {
			if($val != "0") {
				$new_permissions[substr($key,5)] = $val;
			}
		}
	}

    // calculate diff
    if(is_array($old_permissions)) {
        foreach ($old_permissions as $k1=>$p1) {
            // if there is not permisison in new that remove old
            if (!array_key_exists($k1, $new_permissions)) {
                $removed_permissions[$k1] = 0;
            }
            // if change than save
            elseif ($old_permissions[$k1]!==$new_permissions[$k1]) {
                $changed_permissions[$k1] = $new_permissions[$k1];
            }
        }
    }
    // add also new groups if available
    if(is_array($new_permissions)) {
        foreach ($new_permissions as $k1=>$p1) {
            if(!array_key_exists($k1, $old_permissions)) {
                $changed_permissions[$k1] = $new_permissions[$k1];
            }
        }
    }

	// permissions for self
	$values['permissions'] = json_encode($new_permissions);

	# execute update
	if(!$Sections->modify_section ($_POST['action'], $values, @$_POST['id']))	{ $Result->show("danger",  _("Section $_POST[action] failed"), false); }
	else																		{ $Result->show("success", _("Section $_POST[action] successful"), false); }

	# delegate
	if (@$_POST['delegate']==1) {
        $Sections->delegate_section_permissions ($_POST['id'], $removed_permissions, $changed_permissions);
	}
}
?>