<?php

# verify that user is logged in
$User->check_user_session();

# must be numeric
if(!is_numeric($GET->subnetId))	{ $Result->show("danger", _("Invalid ID"), true); }
if(!is_numeric($GET->section))	{ $Result->show("danger", _("Invalid ID"), true); }

# save folder ID
$folderId = $GET->subnetId;

# get custom subnet fields
$cfields = $Tools->fetch_custom_fields ('subnets');

# fetch subnet details!
$folder	= $Subnets->fetch_subnet ("id", $folderId);
if($folder==false) 				{ header("Location: ".create_link("subnets", $GET->section)); die(); }	//redirect if false

// to array
$folder = (array) $folder;

# permissions
$folder_permission  = $Subnets->check_permission($User->user, $folder['id']);						//subnet permission
$folder_permission_section = $Sections->check_permission($User->user, $folder['sectionId']);				//section permission
if($folder_permission == 0)			{ $Result->show("danger", _('You do not have permission to access this network'), true); }

# get all slaves and addresses
$slaves = $Subnets->fetch_subnet_slaves ($folderId);
$addresses = $Addresses->fetch_subnet_addresses ($folder['id'], @$sort['field'], @$sort['direction']);

# print Folder details
print "<div class='subnetDetails'>";
include_once("folder-menu.php");
print "</div>";