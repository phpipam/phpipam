<?php

/**
 * Define which fields are to be displayed
 *
 ***************************************************/

# verify that user is logged in
$User->check_user_session();

// get all fields in IP table
foreach($Tools->fetch_standard_fields("ipaddresses") as $s) {
	$standard_fields[$s] = $s;
}

// get all selected fields and put them to array
$selected_fields = $Tools->explode_filtered(";", $User->settings->IPrequired);

// unset fields that are excluded
unset($standard_fields['id'],
      $standard_fields['subnetId'],
      $standard_fields['lastSeen'],
      $standard_fields['ip_addr'],
      $standard_fields['excludePing'],
      $standard_fields['editDate'],
      $standard_fields['is_gateway'],
      $standard_fields['PTR'],
      $standard_fields['PTRignore'],
      $standard_fields['state'],
      $standard_fields['firewallAddressObject']
      );
// append extra
?>


<h4><?php print _('Set required IP address fields'); ?></h4>
<hr>

<div class="alert alert-info alert-absolute"><?php print _('Select which fields are mandatory to be filled in when creating IP address.'); ?></div>

<div>
<form id="required_ip" name="required_ip" style="margin-top:50px;clear:both;">
<table class="required_ip table table-auto table-top" style='width:auto'>

	<tr>
		<th colspan="2"><?php print _('Check all fields that are required'); ?>:</th>
	</tr>

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
			<a class='btn btn-xs btn-success submit_popup' data-script="app/admin/required-fields/submit.php" data-result_div="required_ip_result" data-form='required_ip'>
				<i class="fa fa-check"></i> <?php print _('Save'); ?>
			</a>
		</td>
	</tr>


</table>
</form>

<div id="required_ip_result">