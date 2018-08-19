<h4><?php print _("Racks"); ?></h4>
<hr>
<span class="text-muted"><?php print _("All Racks belonging to customer"); ?>.</span>

<script type="text/javascript">
/* fix for ajax-loading tooltips */
$('body').tooltip({ selector: '[rel=tooltip]' });
</script>


<?php

# only if set
if (isset($objects["racks"])) {

	# assign racks
	$racks = $objects['racks'];

	# fetch custom fields
	$custom = $Tools->fetch_custom_fields('racks');

	# get hidden fields
	$hidden_custom_fields = json_decode($User->settings->hiddenCustomFields, true);
	$hidden_custom_fields = is_array(@$hidden_custom_fields['racks']) ? $hidden_custom_fields['racks'] : array();

    // table
    print "<table class='table sorted table-striped table-top table-td-top' data-cookie-id-table='rack_list'>";
    // headers
    print "<thead>";
    print "<tr>";
    print " <th>"._('Name')."</th>";
    print " <th>"._('Size')."</th>";
    print " <th>"._('Back side')."</th>";
    print " <th>"._('Devices')."</th>";
    print " <th>"._('Description')."</th>";
    $colspan = 6;
	if(sizeof($custom) > 0) {
		foreach($custom as $field) {
			if(!in_array($field['name'], $hidden_custom_fields)) {
				print "<th class='hidden-xs hidden-sm hidden-md'>".$Tools->print_custom_field_name ($field['name'])."</th>";
                $colspan++;
			}
		}
	}
    print " <th style='width:80px'></th>";
    print "</tr>";
    print "</thead>";

    print "<tbody>";

    // loop
    foreach ($racks	 as $r) {
        // back
        $r->back = $r->hasBack!="0" ? "Yes" : "No";
        // cht devices
        $cnt = $Tools->count_database_objects ("devices", "rack", $r->id);

        // fix possible null
        if(strlen($r->location)==0) $r->location = 0;

        // print
        print "<tr>";

        print " <td><a class='btn btn-xs btn-default' href='".create_link($_GET['page'], "racks", $r->id)."'><i class='fa fa-bars prefix'></i> $r->name</a></td>";
        print " <td>$r->size U</td>";
        print " <td>"._($r->back)."</td>";
        print " <td>$cnt "._("devices")."</td>";
        print " <td>$r->description</td>";

		//custom
		if(sizeof($custom) > 0) {
			foreach($custom as $field) {
				if(!in_array($field['name'], $hidden_custom_fields)) {
					print "<td class='hidden-xs hidden-sm hidden-md'>";
                    $Tools->print_custom_field ($field['type'], $r->{$field['name']});
					print "</td>";
				}
			}
		}

        // links
        print " <td class='actions'>";
        print " <div class='btn-group'>";
        print "     <a href='' class='btn btn-xs btn-default editRack' data-action='edit' rel='tooltip' title='"._("Edit")."' data-rackid='$r->id'><i class='fa fa-pencil'></i></a>";
        print "     <a href='' class='btn btn-xs btn-default showRackPopup' data-rackId='$r->id' data-deviceId='0' rel='tooltip' title='"._("Show")."'><i class='fa fa-server'></i></a>";
        print "     <a href='' class='btn btn-xs btn-default editRack' data-action='delete' data-rackid='$r->id' rel='tooltip' title='"._("Delete")."'><i class='fa fa-times'></i></a>";
        if($User->get_module_permissions ("customers")>1)
		print "		<button class='btn btn-xs btn-default open_popup' rel='tooltip' title='Unlink object' data-script='app/admin/customers/unlink.php' data-class='700' data-object='racks' data-id='$r->id'><i class='fa fa-unlink'></i></button>";

        print " </div>";
        print " </td>";

        print "</tr>";
    }
	print "</tbody>";
	print "</table>";
}

else {
	$Result->show("info", _("No objects"));
}