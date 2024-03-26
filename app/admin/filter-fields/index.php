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
$selected_fields = $Tools->explode_filtered(";", $User->settings->IPfilter);

// unset mandatory fields -> id,subnetid,ip_addr
unset($standard_fields['id'],
      $standard_fields['subnetId'],
      $standard_fields['ip_addr'],
      $standard_fields['description'],
      $standard_fields['hostname'],
      $standard_fields['lastSeen'],
      $standard_fields['excludePing'],
      $standard_fields['editDate'],
      $standard_fields['is_gateway'],
      $standard_fields['PTR'],
      $standard_fields['PTRignore'],
      $standard_fields['state']
      );
?>


<h4><?php print _('Filter which fields to display in IP list'); ?></h4>
<hr>

<div class="alert alert-info alert-absolute"><?php print _('You can select which fields are actually being used for IP management, so you dont show any overhead if not used. IP, hostname and description are mandatory'); ?>.</div>

<div>
<form id="filterIP" style="margin-top:50px;clear:both;">
<table class="filterIP table table-auto table-top" style='width:auto'>

	<tr>
		<th colspan="2"><?php print _('Check which fields to use for IP addresses'); ?>:</th>
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
			<a class='btn btn-xs btn-success submit_popup' data-script="app/admin/filter-fields/filter-result.php" data-result_div="filterIPResult" data-form='filterIP'>
				<i class="fa fa-check"></i> <?php print _('Save'); ?>
			</a>
		</td>
	</tr>


</table>
</form>

<div id="filterIPResult" style="display:none"></div>