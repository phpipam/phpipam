<?php

/**
 *	Preview data IP Addresses import data
 ************************************/

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects
$Database 	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);

# verify that user is logged in
$User->check_user_session();

# load data from uploaded file
include 'import-load-data.php';
# check data and mark the entries to import/update
include 'import-ipaddr-check.php';

?>

<!-- header -->
<div class="pHeader"><?php print _("IP Addresses import data preview"); ?></div>

<!-- content -->
<div class="pContent">
<?php
$searchallvrfs = ($GET->searchallvrfs == 'on') ? 'on' : '';

print '<h4>'._("Uploaded data").'</h4><hr>';
print _("The entries marked with ")."<i class='fa ".$icons['add']."'></i>, "._("will be added,
	the ones marked with ")."<i class='fa ".$icons['edit']."'></i>, "._("will be updated
	and the ones marked with ")."<i class='fa ".$icons['skip']."'></i> "._("will be skipped.");

print "<b>"._("Summary: ")."</b>".($counters['add'] > 0 ? $counters['add'] : "no")._(" new entries.
		").($counters['edit'] > 0 ? $counters['edit'] : "no")._(" updated entries.
		").($counters['error'] > 0 ? $counters['error'] : "no")._(" entries skipped due to errors.
		").($counters['skip'] > 0 ? $counters['skip'] : "no")._(" duplicate entries.
		")._("Scroll down for details.");

print "<form id='selectImportFields'>";
print "<input name='expfields' type='hidden' value='".implode('|',$expfields)."' style='display:none;'>";
print "<input name='reqfields' type='hidden' value='".implode('|',$reqfields)."' style='display:none;'>";
print $hiddenfields;
print "<input name='filetype' id='filetype' type='hidden' value='".$filetype."' style='display:none;'>";
print "<input name='searchallvrfs' id='searchallvrfs' type='hidden' value='".$searchallvrfs."' style='display:none;'>";
print "</form>";
print "<table class='table table-condensed table-hover' id='previewtable'><tbody>";
print "<tr class='active'>".$hrow."<th>Action</th></tr>";
print $rows;
print "</tbody></table><br>";
# add some spaces so we make pContent div larger and not overlap with the absolute pFooter div
print "<br><br><br>";

?>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default" id="dataImportSubmit" data-type="ipaddr" disabled><i class="fa fa-upload"></i> <?php print _('Import'); ?></button>
	</div>
</div>

<?php
if (($counters['add'] > 0) || ($counters['edit'] > 0)) {
?>

	<script>
	$(function(){
		$('#dataImportSubmit').removeAttr('disabled');
		$('#dataImportSubmit').removeClass('btn-default');
		$('#dataImportSubmit').addClass('btn-success');
	});
	</script>
<?php
}
