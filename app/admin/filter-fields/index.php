<?php

/**
 * Script to get all active IP requests
 ****************************************/

# verify that user is logged in
$User->check_user_session();


# get all fields in IP table
foreach($Tools->fetch_standard_fields("ipaddresses") as $s) {
	$standard_fields[$s] = $s;
}

# get all selected fields and put them to array
$selected_fields = explode(";", $User->settings->IPfilter);

/* unset mandatory fields -> id,subnetid,ip_addr */
unset($standard_fields['id'], $standard_fields['state'], $standard_fields['subnetId'],
      $standard_fields['ip_addr'], $standard_fields['description'], $standard_fields['dns_name'],
      $standard_fields['lastSeen'], $standard_fields['excludePing'], $standard_fields['editDate'],
      $standard_fields['is_gateway'], $standard_fields['PTR'], $standard_fields['PTRignore'],
      $standard_fields['firewallAddressObject']);
?>


<h4><?php print _('Filter which fields to display in IP list'); ?></h4>
<hr>

<div class="alert alert-info alert-absolute"><?php print _('You can select which fields are actually being used for IP management, so you dont show any overhead if not used. IP, hostname and description are mandatory'); ?>.</div>


<form id="filterIP" style="margin-top:50px;clear:both;">
<table class="filterIP table table-auto table-striped table-top">

<!-- headers -->
<tr>
	<th colspan="2"><?php print _('Check which fields to use for IP addresses'); ?>:</th>
</tr>

<!-- fields -->
<?php
foreach($standard_fields as $field) {
	# set active
	$checked = in_array($field, $selected_fields) ? "checked" : "";

	# replace switch
	$field_print = $field=="switch" ? "device" : $field;

	print '<tr>'. "\n";
	print '	<td style="width:10px;padding-left:10px;"><input type="checkbox" class="input-switch" name="'. $field .'" value="'. $field .'" '. $checked .'></td>';
	print '	<td>'. ucfirst($field_print) .'</td>';
	print '</tr>';
}
?>

<!-- submit -->
<tr>
	<td></td>
	<td>
		<button class="btn btn-sm btn-default" id="filterIPSave"><i class="fa fa-check"></i> <?php print _('Save'); ?></button>
	</td>
</tr>


</table>
</form>


<div class="filterIPResult" style="display:none"></div>