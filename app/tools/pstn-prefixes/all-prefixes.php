 <h4><?php print _('List of PSTN prefixes'); ?></h4>
<hr>

<div class="btn-group">
    <?php if($User->get_module_permissions ("pstn")>=User::ACCESS_RWA) { ?>
	<a href="" class='btn btn-sm btn-default editPSTN' data-action='add' data-id='0' style='margin-bottom:10px;'><i class='fa fa-plus'></i> <?php print _('Add prefix'); ?></a>
	<?php }?>
</div>
<br>

<?php

/**
 * Script to print locations
 ***************************/

# verify that user is logged in
$User->check_user_session();

# perm check
if ($User->get_module_permissions ("pstn")==User::ACCESS_NONE) {
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}
# check that location support isenabled
elseif ($User->settings->enablePSTN!="1") {
    $Result->show("danger", _("PSTN prefixes module disabled."), false);
}
else {
    # fetch all locations
    $all_prefixes = $Tools->fetch_all_prefixes();

    $colspan = $User->get_module_permissions ("pstn")>=User::ACCESS_RWA ? 9 : 8;

    // table
    print "<table id='manageSubnets' class='table sorted table-striped table-top table-td-top' data-cookie-id-table='pstn_p'>";
    // headers
    print "<thead>";
    print "<tr>";
    print " <th>"._('Prefix')."</th>";
    print " <th>"._('Name')."</th>";
    print " <th>"._('Range')."</th>";
    print " <th>"._('Start')."</th>";
    print " <th>"._('Stop')."</th>";
    print " <th>"._('Numbers')."</th>";
    if($User->get_module_permissions ("devices")>=User::ACCESS_RW)
    print " <th>"._('Device')."</th>";
	if(sizeof($custom) > 0) {
		foreach($custom as $field) {
			if(!in_array($field['name'], $hidden_custom_fields)) {
				print "<th class='hidden-xs hidden-sm hidden-md'>".$Tools->print_custom_field_name ($field['name'])."</th>";
				$colspan++;
			}
		}
	}
    if($User->get_module_permissions ("pstn")>=User::ACCESS_RW)
    print " <th style='width:80px'></th>";
    print "</tr>";
    print "</thead>";

    print "<tbody>";

    # if none than print
    if($all_prefixes===false) {
        print "<tr>";
        print " <td colspan='$colspan'>".$Result->show("info",_("No PSTN prefixes configured"), false, false, true)."</td>";
        print "</tr>";
    }
    else {

        $html = $Tools->print_menu_prefixes ( $User->user, $all_prefixes, $custom );
        if($html!==false)
        print implode("\n", $html);

        else {
            print "<tr>";
            print " <td colspan='$colspan'>".$Result->show("info",_("No PSTN prefixes available"), false, false, true)."</td>";
            print "</tr>";
        }
    }
    print "</tbody>";
    print "</table>";
}