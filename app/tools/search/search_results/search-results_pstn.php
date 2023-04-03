<?php

/*
 * Script to display search results
 **********************************/

# verify that user is logged in
$User->check_user_session();

# get all custom fields
$custom_pstn_fields  = $Params->pstn=="on"      ? $Tools->fetch_custom_fields ("pstnPrefixes") : array();
$custom_pstnn_fields = $Params->pstn=="on"      ? $Tools->fetch_custom_fields ("pstnNumbers") : array();

$hidden_pstn_fields  = is_array(@$hidden_fields['pstnPrefixes']) ? $hidden_fields['pstnPrefixes'] : array();
$hidden_pstnn_fields = is_array(@$hidden_fields['pstnNumbers']) ? $hidden_fields['pstnNumbers'] : array();

# search pstn prefixes
$result_pstn  = $Tools->search_pstn_refixes($searchTerm, $custom_pstn_fields);
$result_pstnn = $Tools->search_pstn_numbers($searchTerm, $custom_pstnn_fields);
?>

<!-- search result table -->
<br>
<h4><?php print _('Search results (PSTN Prefixes)');?>:</h4>
<hr>

<table class="searchTable sorted table table-striped table-condensed table-top" data-cookie-id-table="search_pstn">

<!-- headers -->
<thead>
<tr id="searchHeader">
	<th><?php print _('Prefix');?></th>
	<th><?php print _('Name');?></th>
	<th><?php print _('Range');?></th>
	<th><?php print _('Device');?></th>
	<?php
	if(sizeof($custom_pstn_fields) > 0) {
		foreach($custom_pstn_fields as $field) {
			if(!in_array($field['name'], $hidden_pstn_fields)) {
				print "	<th class='hidden-xs hidden-sm'>".$Tools->print_custom_field_name ($field['name'])."</th>";
			}
		}
	}
	?>
	<th></th>
</tr>
</thead>

<tbody>
<?php
if(sizeof($result_pstn) > 0) {
	# print vlans
	foreach($result_pstn as $pstn) {
		print "<tr class='nolink'>";
		print " <td><dd><a class='btn btn-xs btn-default' href='".create_link("tools","pstn-prefixes",$pstn->id)."'>$pstn->prefix</a></dd></td>";
		print " <td><dd>$pstn->name</dd></td>";
		print " <td><dd>".$pstn->prefix.$pstn->start." - ".$pstn->prefix.$pstn->stop."</dd></td>";
		//device										{
		if(!is_blank($pstn->deviceId) && $pstn->deviceId!="0") {
			$switch = $Tools->fetch_object("devices", "id", $pstn->deviceId);
			$pstn->deviceId = $switch===false ? "/" : "<a href='".create_link("tools", "devices", $switch->id)."'>".$switch->hostname."</a>";
		}
		else {
			$pstn->deviceId = "/";
		}

		print ' <td class="hidden-sm hidden-xs">'. $pstn->deviceId  .'</td>' . "\n";

		# custom fields
		if(sizeof($custom_pstn_fields) > 0) {
			foreach($custom_pstn_fields as $field) {
				if(!in_array($field['name'], $hidden_pstn_fields)) {
					$pstn->{$field['name']} = $Tools->create_links ($pstn->{$field['name']}, $field['type']);
					print "	<td class='hidden-xs hidden-sm'>".$pstn->{$field['name']}."</td>";
				}
			}
		}
		# for admins print link
		print " <td class='actions'>";
		if($User->is_admin(false)) {
		print '<div class="btn-group">';
		print '	<a class="btn btn-xs btn-default editPSTN" data-action="edit"   data-id="'.$pstn->id.'"><i class="fa fa-gray fa-pencil"></i></a>';
		print '	<a class="btn btn-xs btn-default editPSTN" data-action="delete" data-id="'.$pstn->id.'"><i class="fa fa-gray fa-times"></i></a>';
		print '</div>';
		}
		print "</td>";
		print '</tr>'. "\n";
    }
}
?>
</tbody>
</table>

<?php
if(sizeof($result_pstn) == 0) {
	$Result->show("info", _("No results"), false);
}
?>



<!-- search result table -->
<br>
<h4><?php print _('Search results (PSTN Numbers)');?>:</h4>
<hr>

<table class="searchTable sorted table table-striped table-condensed table-top" data-cookie-id-table="search_pstn_refixes">

<!-- headers -->
<thead>
<tr id="searchHeader">
	<th><?php print _('Number');?></th>
	<th><?php print _('Name');?></th>
	<th><?php print _('Owner');?></th>
	<th><?php print _('Device');?></th>
	<?php
	if(sizeof($custom_pstnn_fields) > 0) {
		foreach($custom_pstnn_fields as $field) {
			if(!in_array($field['name'], $hidden_pstnn_fields)) {
				print "	<th class='hidden-xs hidden-sm'>".$Tools->print_custom_field_name ($field['name'])."</th>";
			}
		}
	}
	?>
	<th></th>
</tr>
</thead>

<tbody>
<?php
if(sizeof($result_pstnn) > 0) {
	# print vlans
	foreach($result_pstnn as $pstnn) {
		print "<tr class='nolink'>";
		print " <td><dd><a class='btn btn-xs btn-default' href='".create_link("tools","pstn-prefixes",$pstnn->prefix)."'>$pstnn->number</a></dd></td>";
		print " <td><dd>$pstnn->name</dd></td>";
		print " <td><dd>$pstnn->owner</dd></td>";
		//device										{
		if(!is_blank($pstnn->deviceId) && $pstnn->deviceId!="0") {
			$switch = $Tools->fetch_object("devices", "id", $pstnn->deviceId);
			$pstnn->deviceId = $switch===false ? "/" : "<a href='".create_link("tools", "devices", $switch->id)."'>".$switch->hostname."</a>";
		}
		else {
			$pstnn->deviceId = "/";
		}
		print ' <td class="hidden-sm hidden-xs">'. $pstnn->deviceId  .'</td>' . "\n";

		# custom fields
		if(sizeof($custom_pstnn_fields) > 0) {
			foreach($custom_pstnn_fields as $field) {
				if(!in_array($field['name'], $hidden_pstnn_fields)) {
					$pstnn->{$field['name']} = $Tools->create_links ($pstnn->{$field['name']}, $field['type']);
					print "	<td class='hidden-xs hidden-sm'>".$pstnn->{$field['name']}."</td>";
				}
			}
		}
		# for admins print link
		print " <td class='actions'>";
		if($User->is_admin(false)) {
		print '<div class="btn-group">';
		print '	<a class="btn btn-xs btn-default editPSTNnumber" data-action="edit"   data-id="'.$pstnn->id.'"><i class="fa fa-gray fa-pencil"></i></a>';
		print '	<a class="btn btn-xs btn-default editPSTNnumber" data-action="delete" data-id="'.$pstnn->id.'"><i class="fa fa-gray fa-times"></i></a>';
		print '</div>';
		}
		print "</td>";
		print '</tr>'. "\n";
    }
}
?>
</tbody>
</table>
<?php
if(sizeof($result_pstnn) == 0) {
	$Result->show("info", _("No results"), false);
}