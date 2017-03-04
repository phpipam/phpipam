<?php

/**
 * Script to export Data
 *************************************************/

# verify that user is logged in
$User->check_user_session();
?>

<script type="text/javascript">

$(document).on('change', "select#dataType", function() {
	if (this.value == "subnets") {
		$('#dataRecompute').show();
	} else {
		$('#dataRecompute').hide();
	}
});

</script>

<h4><?php print _('Import / Export'); ?></h4>
<hr><br>

<form name="dataImportExport" id="dataImportExport">
<table id="dataImportExport" class="table table-hover table-condensed table-top table-auto">
	<tr>
		<th colspan="3"><h4><?php print _('Pick a data set type, and click on an action'); ?></h4></th>
	</tr>
	<tr>
		<td class="title"><?php print _('Data set'); ?></td>
		<td>
			<select name="dataType" id="dataType" class="form-control input-sm input-w-auto" rel='tooltip' data-placement='right' title='<?php print _('Pick data set'); ?>'>
					<option value='vrf'><?php print _('VRF'); ?></option>
					<option value='vlan'><?php print _('VLAN'); ?></option>
					<option value='l2dom'><?php print _('L2 Domains'); ?></option>
					<option value='subnets'><?php print _('Subnets'); ?></option>
					<option value='ipaddr'><?php print _('IP addresses'); ?></option>
					<option value='devices' disabled><?php print _('Devices'); ?></option>
					<option value='devtype' disabled><?php print _('Device types'); ?></option>
			</select>
		</td>
		<td class="info2"><?php print _('Not all options are available currently.'); ?></td>
	</tr>
	<tr>
		<td>Action</td>
		<td colspan="2">
			<div class="btn-group">
				<button class='dataImport btn btn-sm btn-default' rel='tooltip' data-placement='bottom' title='<?php print _('Import data entries for the selected type'); ?>'><i class='fa fa-download'></i> <?php print _('Import'); ?></button>
				<button class='dataExport btn btn-sm btn-default' rel='tooltip' data-placement='bottom' title='<?php print _('Export data entries for the selected type'); ?>'><i class='fa fa-upload'></i> <?php print _('Export'); ?></button>
			</div>
			<button class="dataRecompute btn btn-sm btn-default" id="dataRecompute" style="display: none;" rel='tooltip' data-placement='bottom' title='<?php print _('Recompute master/nested subnet relations.'); ?>'><i class="fa fa-magic"></i> <?php print _('Recompute'); ?></button>
		</td>
	</tr>
</table>
</form>



<h4 style="margin-top:100px;"><?php print _('phpIPAM database export'); ?></h4>
<hr><br>

<div class="alert alert-info alert-absolute"><?php print _('You can download MySQL dump of database or generate XLS file of IP addresses'); ?>!</div>

<!-- MySQL dump -->
<hr style="margin-top:50px;">
<h4><?php print _('Create MySQL database dump'); ?></h4>
<button class="btn btn-sm btn-default" id="MySQLdump"><i class="fa fa-upload"></i> <?php print _('Prepare MySQL dump'); ?></button>

<!-- XLS dump -->
<h4><?php print _('Create XLS file of IP addresses'); ?></h4>
<button class="btn btn-sm btn-default" id="XLSdump"><i class="fa fa-upload"></i> <?php print _('Prepare XLS dump'); ?></button>

<!-- XLS dump -->
<h4><?php print _('Create hostfile dump'); ?></h4>
<button class="btn btn-sm btn-default" id="hostfileDump"><i class="fa fa-upload"></i> <?php print _('Prepare hostfile dump'); ?></button>