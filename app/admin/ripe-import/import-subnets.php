<?php

/**
 * Script to manage sections
 *************************************************/


/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Subnets	= new Subnets ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

//get size of subnets - $POST/4
$size = sizeof($POST) / 4;

//get unique keys for subnets because they are not sequential if deleted!!!
foreach($POST as $key=>$line) {
	if (!is_blank(strstr($key,"subnet"))) {
		$allSubnets[] = $key;
	}
}

# format and verify each record
foreach($allSubnets as $subnet) {
	//get sequential number
	$m = str_replace("subnet-", "", $subnet);

	//reformat subnet
	$_temp = $Subnets->cidr_network_and_mask($POST->{'subnet-' . $m});

	//set subnet details for importing
	$subnet_import['subnet'] 	   = $Subnets->transform_to_decimal($_temp[0]);
	$subnet_import['mask'] 	   	   = $_temp[1];
	$subnet_import['sectionId']    = $POST->{'section-' . $m};
	$subnet_import['description']  = $POST->{'description-' . $m};
	$subnet_import['vlanId'] 	   = $POST->{'vlan-' . $m};
	$subnet_import['vrfId'] 	   = $POST->{'vrf-' . $m};
	$subnet_import['showName']	   = $POST->{'showName-' . $m};

	//cidr
	if(strlen($err=$Subnets->verify_cidr($Subnets->transform_to_dotted($subnet_import['subnet'])."/".$subnet_import['mask']))>5) {
		$errors[] = $err;
	}
	//overlapping, only root !
	elseif (strlen($err=$Subnets->verify_subnet_overlapping ($subnet_import['sectionId'], $Subnets->transform_to_dotted($subnet_import['subnet'])."/".$subnet_import['mask'], $subnet_import['vrfId']))>5) {
		$errors[] = $err;
	}
	//set insert
	else {
		$subnets_to_insert[] = $subnet_import;
	}
}


# print errors if they exist or success
if(isset($errors)) {
	print '<div class="alert alert-danger alert-absolute">'._('Please fix the following errors before inserting').':<hr>'. "\n";
	foreach ($errors as $line) {
		print $line.'<br>';
	}
	print '</div>';
}
else {
	$errors_import_failed = 0;

	//insert if all other is ok!
	foreach($subnets_to_insert as $subnet_import) {
		//formulate insert query
		$values = array("sectionId"=>$subnet_import['sectionId'],
						"subnet"=>$subnet_import['subnet'],
						"mask"=>$subnet_import['mask'],
						"description"=>$subnet_import['description'],
						"vlanId"=>$subnet_import['vlanId'],
						"vrfId"=>$subnet_import['vrfId'],
						"masterSubnetId"=>0,
						"showName"=>$subnet_import['showName']
						);

		if(!$Admin->object_modify("subnets", "add", "id", $values)) {
			$Result->show("danger", _('Failed to import subnet').' '. $Subnets->transform_to_dotted($subnet_import['subnet'])."/".$subnet_import['mask'], false);
			$errors_import_failed++;
		}
	}
	//check if all is ok and print it!
	if($errors_import_failed == 0) 	{ $Result->show("success", _("Import successful")."!", false); }
}
