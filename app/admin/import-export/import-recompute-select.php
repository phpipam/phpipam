<?php

/**
 *	Subnets master/nested recompute select form
 */

# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin 		= new Admin ($Database);
$Tools	    = new Tools ($Database);
$Sections	= new Sections ($Database);

# verify that user is logged in
$User->check_user_session();

$mtable = "subnets"; # main table where to check the fields
# predefine field list
$expfields = array ("section","subnet","mask","description","vrf");
# required fields without which we will not continue, vrf is optional - if not set we check against default VRF
$reqfields = array("section","subnet");
# we don't care about custom fields here
$custom_fields = array();
# fetch all sections
$all_sections = $Sections->fetch_all_sections();

# Lets do some reordering to show slaves!
foreach($all_sections as $s) {
	if($s->masterSection=="0") {
		# it is master
		$s->class = "master";
		$sectionssorted[] = $s;
		# check for slaves
		foreach($all_sections as $ss) {
			if($ss->masterSection==$s->id) {
				$ss->class = "slave";
				$sectionssorted[] = $ss;
			}
		}
	}
}

# set new array
$sections_sorted = @$sectionssorted;

# show sections
if(sizeof($all_sections) > 0) {
	$section_rows = ""; $last_master = 0;
	# existing sections
	foreach ($sections_sorted as $section) {
		//cast
		$section = (array) $section;
		//master Section
		if (($section['masterSection']!=0) && ($section['masterSection']==$last_master)) {
			$sect_prefix = " - ";
		} else {
			$sect_prefix = "";
			$last_master = $section['id'];
		}
		$section_rows.= "<tr>";
		$section_rows.= "<td><div class='checkbox'><label><input type='checkbox' id='recomputeSectionCheck' name='recomputeSection_".$section['id']."' checked>".$sect_prefix.str_replace('_', ' ', $section['name'])."</label></div></td>";
		$section_rows.= "<td><div class='checkbox'><label><input type='checkbox' id='recomputeIPv4Check' name='recomputeSectionIPv4_".$section['id']."' checked></label></div></td>";
		$section_rows.= "<td><div class='checkbox'><label><input type='checkbox' id='recomputeIPv6Check' name='recomputeSectionIPv6_".$section['id']."' checked></label></div></td>";
		$section_rows.= "<td><div class='checkbox'><label><input type='checkbox' id='recomputeCVRFCheck' name='recomputeSectionCVRF_".$section['id']."' checked></label></div></td>";
		$section_rows.="</tr>\n";
	}
} else {
	$section_rows = "<td colspan='3'>No sections found!</td>";
}

?>

<!-- header -->
<div class="pHeader"><?php print _("Select Subnet sections, VRFs and IP version to recompute"); ?></div>

<!-- content -->
<div class="pContent">

<?php

print "<form id='selectImportFields'><div id='topmsg'>";
print '<h4>'._("Sections and IP versions").'</h4><hr>';
print _("Please choose which section and IP version to recompute:");
print "</div>";
print "<input name='expfields' type='hidden' value='".implode('|',$expfields)."' style='display:none;'>";
print "<input name='reqfields' type='hidden' value='".implode('|',$reqfields)."' style='display:none;'>";
print "<table class='table table-striped table-condensed' id='fieldstable'><tbody>";
print "<tr>	<th><input type='checkbox' id='recomputeSectionSelectAll' checked> "._("Section")."</th>
			<th><input type='checkbox' id='recomputeIPv4SelectAll' checked> IPv4</th>
			<th><input type='checkbox' id='recomputeIPv6SelectAll' checked> IPv6</th>
			<th><input type='checkbox' id='recomputeCVRFSelectAll' checked> Cross VRF</th></tr>";
print $section_rows;
print "</tbody></table>";
print "</form>";
?>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default btn-success" id="dataImportPreview" data-type="recompute"><i class="fa fa-eye"></i> <?php print _('Preview'); ?></button>
	</div>
</div>
