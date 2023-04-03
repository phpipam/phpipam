<?php
# required functions
if(!isset($User)) {
	require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
	# classes
	$Database	= new Database_PDO;
	$User 		= new User ($Database);
	$Tools 		= new Tools ($Database);
	$Subnets 	= new Subnets ($Database);
	$Addresses 	= new Addresses ($Database);
}

# user must be authenticated
$User->check_user_session ();

# if direct request that redirect to tools page
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest")	{
	header("Location: ".create_link("tools","favourites"));
}
?>

<script>
$(document).ready(function() {
	if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }

	return false;
});
</script>

<?php
# fetch favourite subnets with details
$fsubnets = $User->fetch_favourite_subnets ();

# print if none
if(!$fsubnets) {
	print "<blockquote style='margin-top:20px;margin-left:20px;'>";
	print "<p>"._("No favourite subnets selected")."</p><br>";
	print "<small>"._("You can add subnets to favourites by clicking star icon in subnet details")."!</small><br>";
	print "</blockquote>";
}
else {
	print "<table class='table table-condensed table-hover table-top favs'>";

	# headers
	print "<tr>";
	print "	<th>"._('Object')."</th>";
	print "	<th>"._('Description')."</th>";
	print "	<th class='hidden-xs'>"._('Section')."</th>";
	if($User->get_module_permissions ("vlan")>=User::ACCESS_RW)
	print "	<th>"._('VLAN')."</th>";
	print "	<th></th>";
	print "</tr>";

	# subnets
	foreach($fsubnets as $f) {

		# must be either subnet or folder
		if(sizeof($f)>0) {

            # add full information
            $fullinfo = $f['isFull']==1 ? " <span class='badge badge1 badge2 badge4'>"._("Full")."</span>" : "";

			print "<tr class='favSubnet-$f[subnetId]'>";

			if($f['isFolder']==1) {
				$master = true;
				print "	<td><a href='".create_link("folder",$f['sectionId'],$f['subnetId'])."'><i class='fa fa-sfolder fa-folder'></i> $f[description]</a></td>";
			}
			else {
				//master?
				if($Subnets->has_slaves ($f['subnetId'])) { $master = true;	 print "	<td><a class='btn btn-xs btn-default' href='".create_link("subnets",$f['sectionId'],$f['subnetId'])."'><i class='fa fa-sfolder fa-folder-o prefix'></i>".$Subnets->transform_to_dotted($f['subnet'])."/$f[mask]</a> $fullinfo</td>"; }
				else 									  { $master = false; print "	<td><a class='btn btn-xs btn-default' href='".create_link("subnets",$f['sectionId'],$f['subnetId'])."'><i class='fa fa-sfolder fa-sitemap prefix' ></i>".$Subnets->transform_to_dotted($f['subnet'])."/$f[mask]</a> $fullinfo</td>"; }
			}
			print "	<td>$f[description]</td>";
			print "	<td class='hidden-xs'><a href='".create_link("subnets",$f['sectionId'])."'>$f[section]</a></td>";

			# get vlan info
			if($User->get_module_permissions ("vlan")>=User::ACCESS_R) {
			if(!is_blank($f['vlanId']) && $f['vlanId']!=0) {
				$vlan = $Tools->fetch_object("vlans", "vlanId", $f['vlanId']);
				print "	<td>$vlan->number</td>";
			} else {
				print "	<td>/</td>";
			}
			}

			# usage
			if(!$master) {
	    		$subnet_usage = $Subnets->calculate_subnet_usage ($f);
	    	}

			# add address
			if($master===true || $f['isFolder']==1 || $Subnets->reformat_number($subnet_usage['freehosts'])=="0") 	{ $disabled = "disabled"; }
			else																									{ $disabled = ""; }

			# remove
			print "	<td class='actions'>";
			print "	<div class='btn-group'>";
			print "	<a class='btn btn-xs btn-default modIPaddr btn-success $disabled' href='' data-container='body' rel='tooltip' title='"._('Add new IP address')."' data-subnetId='$f[subnetId]' data-action='add' data-id=''><i class='fa fa-plus'></i></a>";
			print "	<a class='btn btn-xs btn-default editFavourite' data-subnetId='$f[subnetId]' data-action='remove' data-from='widget'><i class='fa fa-star favourite-$f[subnetId]' rel='tooltip' title='"._('Click to remove from favourites')."'></i></a>";
			print "	</div>";
			print " </td>";

			print "</tr>";
		}
	}

	print "</table>";
}
?>