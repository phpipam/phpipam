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

# validate csrf cookie
$_POST['csrf_cookie']==$_SESSION['csrf_cookie'] ? :                      $Result->show("danger", _("Invalid CSRF cookie"), true);



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
	# set variables for update
	$values = array("id"=>@$_POST['id'],
					"name"=>@$_POST['name'],
					"description"=>@$_POST['description'],
					"strictMode"=>@$_POST['strictMode'],
					"subnetOrdering"=>@$_POST['subnetOrdering'],
					"showVLAN"=>@$_POST['showVLAN'],
					"showVRF"=>@$_POST['showVRF'],
					"masterSection"=>@$_POST['masterSection'],
					);

	# set permissions
	foreach($_POST as $key=>$val) {
		if(substr($key, 0,5) == "group") {
			if($val != "0") {
				$perm[substr($key,5)] = $val;
			}
		}
	}
	$values['permissions'] = isset($perm) ? json_encode($perm) : "";

	# delegate to all subnets?
	if(isset($_POST['delegate'])) {
		if($_POST['delegate']==1) { $values['delegate']=1; }
		else					  { $values['delegate']=0; }
	}

	# execute update
	if(!$Sections->modify_section ($_POST['action'], $values, @$_POST['id']))	{ $Result->show("danger",  _("Section $_POST[action] failed"), true); }
	else																		{ $Result->show("success", _("Section $_POST[action] successful"), true); }
}
?>