<?php

/**
 *	Preview Subnets master/nested recompute data
 ************************************/

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects
$Database 	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);
$Admin 		= new Admin ($Database);

# verify that user is logged in
$User->check_user_session();

# Load subnets and recompute the master/nested relations
include 'import-recompute-logic.php';

?>

<!-- header -->
<div class="pHeader"><?php print _("Subnets master/nested recompute data preview"); ?></div>

<!-- content -->
<div class="pContent">
<?php

print '<h4>'._("Recomputed data").'</h4><hr>';
print _("The entries marked with ")."<i class='fa ".$icons['edit']."'></i>, "._("have new masters and will be updated,
	the ones marked with ")."<i class='fa ".$icons['skip']."'></i>, "._("didn't change the master.");

print "<form id='selectImportFields'>".$pass_inputs."</form>";
print "<table class='table table-condensed table-hover' id='previewtable'><tbody>";
print "<tr class='active'><th></th><th>Section</th><th>Subnet</th><th>Description</th><th>VRF</th><th>Master</th><th>Action</th></tr>";
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
		<button class="btn btn-sm btn-default" id="dataImportSubmit" data-type="recompute" disabled><i class="fa fa-magic"></i> <?php print _('Save'); ?></button>
	</div>
</div>

<?php
if ($counters['edit'] > 0) {
?>

	<script type="text/javascript">
	$(function(){
		$('#dataImportSubmit').removeAttr('disabled');
		$('#dataImportSubmit').removeClass('btn-default');
		$('#dataImportSubmit').addClass('btn-success');
	});
	</script>
<?php
}
?>