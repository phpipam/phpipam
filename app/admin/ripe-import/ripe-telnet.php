<?php

/**
 * Search IRPE databse for AS imports
 *************************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Subnets	= new Subnets ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();


//strip AS if provided, to get just the number
if(substr($_POST['as'], 0,2)=="AS" || substr($_POST['as'], 0,2)=="as") {
	$_POST['as'] = substr($_POST['as'], 2);
};


# fetch subnets form ripe
$subnet   = $Subnets->ripe_fetch_subnets ($_POST['as']);

# fetch all sections
$sections = $Admin->fetch_all_objects ("sections", "id");
$vlans	  = $Admin->fetch_all_objects ("vlans", "vlanId");
$vrfs	  = $Admin->fetch_all_objects ("vrf", "vrfId");

//none found
if(sizeof(@$subnet) == 0) {
	print "<hr>";
	$Result->show("danger alert-absolute", _('No subnets found').'!', true);
}
else {
	//form
	print '<form name="asImport" id="asImport">';
	//table
	print '<table class="asImport table table-striped table-condensed table-top table-auto">';
	//headers
	print '<tr>';
	print '	<th colspan="5">'._('I found the following routes belonging to AS').' '.$_POST['as'].':</th>';
	print '</tr> ';

	print "<tr>";
	print "	<th></th>";
	print "	<th>"._('Subnet')."</th>";
	print "	<th>"._('select section')."</th>";
	print "	<th>"._('Description')."</th>";
	print "	<th>"._('VLAN')."</th>";
	print "	<th>"._('VRF')."</th>";
	print "	<th>"._('Show name')."</th>";

	print "</tr>";

	//print found subnets
	$m = 0;
	foreach ($subnet as $route) {
		# only not empty
		if(strlen($route)>2) {
			print '<tr>'. "\n";

			//delete
			print '<td class="removeSubnet">'. "\n";
			print '	<button class="btn btn-xs btn-default btn-danger" rel="tooltip" title="'._('Remove this subnet').'"><i class="fa fa-times"></i></button>'. "\n";
			print '</td>'. "\n";
			//subnet
			print '<td>'. "\n";
			print '<input type="text" class="form-control input-sm" name="subnet-'. $m .'" value="'. $route .'">'. "\n";
			print '</td>'. "\n";
			//section
			print '<td>'. "\n";
			print '<select name="section-'. $m .'" class="form-control input-sm input-w-auto">'. "\n";
			foreach($sections as $section) {
				print '<option value="'. $section->id .'">'. $section->name .'</option>';
			}
			print '</select>'. "\n";
			print '</td>'. "\n";
			//description
			print '<td>'. "\n";
			print '<input type="text" class="form-control input-sm input-w-250" name="description-'. $m .'">'. "\n";
			print '</td>'. "\n";
			//VLAN
			print '<td>'. "\n";
			print '<select name="vlan-'. $m .'" class="form-control input-sm input-w-auto">'. "\n";
			print '<option value="0">No VLAN</option>';
			if(sizeof(@$vlans)>0) {
				foreach($vlans as $vlan) {
					# set description
					$vlan_description = strlen($vlan->description)>0 ? " (".$vlan->description.")" : "";
					print '<option value="'.$vlan->vlanId.'">'.$vlan->number.$vlan_description.'</option>';
				}
			}
			//VRF
			print '<td>'. "\n";
			print '<select name="vrf-'. $m .'" class="form-control input-sm input-w-auto">'. "\n";
			print '<option value="0">No VRF</option>';
			if(sizeof(@$vrfs)>0) {
				foreach($vrfs as $vrf) {
					# set description
					$vrf_description = strlen($vrf->description)>0 ? " (".$vrf->description.")" : "";
					print '<option value="'.$vrf->vrfId.'">'.$vrf->name.$vrf_description.'</option>';
				}
			}
			//show name
			print '<td>'. "\n";
			print '<select name="showName-'. $m .'" class="form-control input-sm input-w-auto">'. "\n";
			print '<option value="0">'._('No') .'</option>';
			print '<option value="1">'._('Yes').'</option>';
			print '</td>'. "\n";

			print '</tr>'. "\n";
		}
		$m++;
	}

	//submit
	print '<tr style="border-top:1px solid white" class="th">'. "\n";
	print '<td colspan="7" style="text-align:right">'. "\n";
	print '	<input type="submit" class="btn btn-sm btn-default" value="'._('Import to database').'">'. "\n";
	print '</td>'. "\n";
	print '</tr>'. "\n";

	print '</table>'. "\n";
	print '</form>'. "\n";
}
?>
<div class="ripeImportResult"></div>