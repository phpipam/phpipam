<?php

/**
 *	Subnets export
 */

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin 		= new Admin ($Database);
$Tools	    = new Tools ($Database);
$Sections	= new Sections ($Database);

# verify that user is logged in
$User->check_user_session();

# Won't check per subnet/section rights since this is an admin section, where the admin user has full access

# fetch all sections
$all_sections = $Sections->fetch_all_sections();

# Lets do some reordering to show slaves!
if($all_sections!==false) {
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
}

# get all custom fields
$custom_fields = $Tools->fetch_custom_fields('subnets');
# prepare HTML variables
$custom_fields_names = "";
$custom_fields_boxes = "";

if(sizeof($custom_fields) > 0) {
	foreach($custom_fields as $myField) {
		//change spaces to "___" so it can be used as element id
		$myField['nameTemp'] = str_replace(" ", "___", $myField['name']);
		$custom_fields_names.= "	<th>$myField[name]</th>";
		$custom_fields_boxes.= "	<td><input type='checkbox' name='$myField[nameTemp]' checked> </td>";
	}
}

?>

<!-- header -->
<div class="pHeader"><?php print _("Select sections and subnets fields to export"); ?></div>

<!-- content -->
<div class="pContent">

<?php

# print
print '<form id="selectExportFields">';
print '<h4>Fields</h4>';
# table
print "	<table class='table table-striped table-condensed'>";

print "	<tr>";
print "	<th>"._('Section')."</th>";
print "	<th>"._('Subnet')."</th>";
print "	<th>"._('Description')."</th>";
print "	<th>"._('VLAN')."</th>";
print "	<th>"._('VRF')."</th>";
print "	<th>"._('Master Subnet')."</th>";
print "	<th>"._('Requests')."</th>";
print "	<th>"._('Hosts check')."</th>";
print "	<th>"._('Discover')."</th>";
print $custom_fields_names;
print "	</tr>";

print "	<tr>";
print "	<td><input type='checkbox' name='section' checked></td>";
print "	<td><input type='checkbox' name='subnet' checked title='"._('Mandatory')."'> </td>";
print "	<td><input type='checkbox' name='description' checked> </td>";
print "	<td><input type='checkbox' name='VLAN' checked> </td>";
print "	<td><input type='checkbox' name='VRF' checked> </td>";
print "	<td><input type='checkbox' name='master'> </td>";
print "	<td><input type='checkbox' name='requests'> </td>";
print "	<td><input type='checkbox' name='hostscheck'> </td>";
print "	<td><input type='checkbox' name='discover'> </td>";
print $custom_fields_boxes;
print "	</tr>";

print '</table>';
print '</form>';

# print section form
print '<form id="selectExportSections">';

# show sections
if($all_sections!==false) {
	print '<h4>Sections</h4>';
	print "	<table class='table table-striped table-condensed'>";
	print "	<tr>";
    print '	<th><input type="checkbox" id="exportSelectAll" checked> '._('Name').'</th>';
    print "	<th>"._('Description')."</th>";
    print "	<th>"._('Parent')."</th>";
    print "	</tr>\n";

	# existing sections
	foreach ($sections_sorted as $section) {
		//cast
		$section = (array) $section;
		$section['url_name'] = urlencode($section['id']);

		print '<tr>';
		print '	<td><div class="checkbox"><label><input type="checkbox" id="exportCheck" name="exportSection__'.$section['url_name'].'" checked>'.$section['name'].'</label></div></td>';
		print '	<td>'. $section['description'] .'</td>'. "\n";
		//master Section
		if($section['masterSection']!=0) {
			# get section details
			$ssec = $Admin->fetch_object("sections", "id", $section['masterSection']);
			print "	<td>$ssec->name</td>";
		} else {
			print "	<td>/</td>";
		}
		print '</tr>'. "\n";
	}

	print '</table>';

	print '<div class="checkbox"><label><input type="checkbox" name="exportSections" checked>'._("Include the sections in a separate sheet.").'</label></div>';

}
print '</form>';
?>

</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-success" id="dataExportSubmit" data-type="subnets"><i class="fa fa-upload"></i> <?php print _('Export'); ?></button>
	</div>
</div>