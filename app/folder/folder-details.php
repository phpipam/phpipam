<?php

/**
 * display folder content
 *************************/

# verify that user is logged in
$User->check_user_session();

# must be numeric
if(!is_numeric($_GET['subnetId']))	{ $Result->show("danger", _("Invalid ID"), true); }
if(!is_numeric($_GET['section']))	{ $Result->show("danger", _("Invalid ID"), true); }

# save folder ID
$folderId = $_GET['subnetId'];

# get custom subnet fields
$cfields = $Tools->fetch_custom_fields ('subnets');

# fetch subnet details!
$folder	= (array) $Subnets->fetch_subnet ("id", $folderId);

# die if empty or not folder
if(sizeof($folder) == 0) 			{ $Result->show("danger", _("Folder does not exist"), true); }
if($folder['isFolder']!=1)			{ $Result->show("danger", _("Invalid ID"), true); }

# get vlan
$vlan = $Tools->fetch_object("vlans", "vlanId", @$vlanId);

# set rowspan
$rowSpan = 10 + sizeof($cfields);

# get section permissions
$permission_section = $Sections->check_permission ($User->user, $folder['sectionId']);
# get subnet permissions
$permission = $Subnets->check_permission ($User->user, $folderId);

# if 0 die
if($permission == 0)				{ $Result->show("danger", _('You do not have permission to access this folder'), true); }

# verify that is it displayed in proper section, otherwise warn!
if($folder['sectionId']!=$_GET['section'])	{
	$sd = $Sections->fetch_section ("id", $folder['sectionId']);
									{ $Result->show("warning", "Folder is in section <a href='".create_link("folder",$sd->id,$folder['id'])."'>$sd->name</a>!", false); }
}
?>

<!-- folder details upper table -->
<h4><?php print _('Folder details'); ?></h4>
<hr>

<table class="ipaddress_subnet table-condensed table-full">

	<tr>
		<th><?php print _('Hierarchy'); ?></th>
		<td>
			<?php $Subnets->print_breadcrumbs ($Sections, $Subnets, $_GET); ?>
		</td>
	</tr>
	<tr>
		<th><?php print _('Folder name'); ?></th>
		<td><?php print $folder['description']; ?></td>
	</tr>
	<tr>
		<th><?php print _('Permission'); ?></th>
		<td><?php print $Subnets->parse_permissions($permission); ?></td>
	</tr>

	<?php
	# print custom subnet fields if any
	if(sizeof(sizeof($cfields)) > 0) {
		foreach($cfields as $key=>$field) {
			if(strlen($folder[$key]) > 0) {
			print "<tr>";
			print "	<th>$key</th>";
			print "	<td>";
				# booleans
				if($field['type']=="tinyint(1)")	{
					if($folder[$field['name']] == 0)		{ print _("No"); }
					elseif($folder[$field['name']] == 1)	{ print _("Yes"); }
				}
				else {
					print $folder[$field['name']];

				}
			print "	</td>";
			print "</tr>";
			}
		}
	}


	# action button groups
	print "<tr>";
	print "	<th>"._('Actions')."</th>";
	print "	<td class='actions'>";

	print "	<div class='btn-toolbar'>";

	/* set values for permissions */
	if($permission == 1) {
		$sp['editsubnet']= false;		//edit subnet
		$sp['editperm']  = false;		//edit permissions
		$sp['changelog'] = false;		//changelog view
	}
	else if ($permission == 2) {
		$sp['editsubnet']= false;		//edit subnet
		$sp['editperm']  = false;		//edit permissions
		$sp['changelog'] = true;		//changelog view
	}
	else if ($permission == 3) {
		$sp['editsubnet']= true;		//edit subnet
		$sp['editperm']  = true;		//edit permissions
		$sp['changelog'] = true;		//changelog view
	}


	# edit / permissions / nested
	print "<div class='btn-group'>";

		//warning
		if($permission == 1)
		print "<button class='btn btn-xs btn-default btn-danger' 	data-container='body' rel='tooltip' title='"._('You do not have permissions to edit subnet or IP addresses')."'><i class='fa fa-lock'></i></button> ";

		// edit subnet
		if($sp['editsubnet'])
		print "<a class='add_folder btn btn-xs btn-default' href='' rel='tooltip' data-container='body' title='"._('Edit folder')."' data-action='edit' data-subnetId='$folder[id]' data-sectionId='$folder[sectionId]'><i class='fa fa-pencil'></i></a>";		//edit subnet
		else
		print "<a class='btn btn-xs btn-default disabled'   href='' rel='tooltip' data-container='body' title='"._('Edit folder')."' >																					<i class='fa fa-pencil'></i></a>";		//edit subnet

		//permissions
		if($sp['editperm'])
		print "<a class='showSubnetPerm btn btn-xs btn-default' href='' rel='tooltip' data-container='body' title='"._('Manage folder permissions')."'	data-subnetId='$folder[id]' data-sectionId='$folder[sectionId]' data-action='show'>	<i class='fa fa-tasks'></i></a>";			//edit subnet
		else
		print "<a class='btn btn-xs btn-default disabled' 		href='' rel='tooltip' data-container='body' title='"._('Manage folder permissions')."'>																						<i class='fa fa-tasks'></i></a>";			//edit subnet

		// add nested subnet
		if($permission_section == 3) {
		print "<a class='edit_subnet btn btn-xs btn-default '	href='' data-container='body' rel='tooltip' title='"._('Add new nested subnet')."' 		data-subnetId='$folder[id]' data-action='add' data-id='' data-sectionId='$folder[sectionId]'> <i class='fa fa-plus-circle'></i></a> ";
		print "<a class='add_folder btn btn-xs btn-default '	href='' rel='tooltip' data-container='body' title='"._('Add new nested folder')."' 		data-subnetId='$folder[id]' data-action='add' data-id='' data-sectionId='$folder[sectionId]'> <i class='fa fa-folder-close-o'></i></a> ";		//add new child subnet
		} else {
		print "<a class='btn btn-xs btn-default disabled' 		href=''> <i class='fa fa-plus-circle'></i></a> ";
		print "<a class='btn btn-xs btn-default disabled'		href=''> <i class='fa fa-folder-close-o'></i></a> ";		//add new child subnet
		}
	print "</div>";

	# favourites / changelog
	print "<div class='btn-group'>";
		#favourite
		if($User->is_folder_favourite ($folder['id']))
		print "<a class='btn btn-xs btn-default btn-info editFavourite favourite-$folder[id]' href='' data-container='body' rel='tooltip' title='"._('Click to remove from favourites')."' data-subnetId='$folder[id]' data-action='remove'><i class='fa fa-star'></i></a> ";
		else
		print "<a class='btn btn-xs btn-default editFavourite favourite-$folder[id]' 		 href='' data-container='body' rel='tooltip' title='"._('Click to add to favourites')."' data-subnetId='$folder[id]' data-action='add'>	<i class='fa fa-star fa-star-o' ></i></a> ";
		# changelog
		if($User->settings->enableChangelog==1) {
		if($sp['changelog'])
		print "<a class='sChangelog btn btn-xs btn-default' href='".create_link("subnets",$folder['sectionId'],$folder['id'],"changelog")."' data-container='body' rel='tooltip' title='"._('Changelog')."'><i class='fa fa-clock-o'></i></a>";
		else
		print "<a class='btn btn-xs btn-default disabled'   href='' 																		 data-container='body' rel='tooltip' title='"._('Changelog')."'><i class='fa fa-clock-o'></i></a>";
		}
	print "</div>";


	print "	</div>";

	print "	</td>";
	print "</tr>";

	?>

</table>	<!-- end subnet table -->
<br>