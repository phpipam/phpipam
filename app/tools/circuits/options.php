<?php

/**
 *
 * Circuit settings
 *
 */

# verify that user is logged in
$User->check_user_session();

// admin only
if($User->is_admin(false)) {
	# title
	$html[] = "<h4>"._('Circuit options')."</h4>";
	$html[] = "<hr>";

	// options for circuits
	$html[] = _("Here you can manage options for circuit types").":<hr>";

	# get all available set options
	$circuit_options      = $Database->getFieldInfo ("circuits", "type");
	# parse and remove type
	$circuit_option_values      = explode("'", str_replace(array("(", ")", ","), "", $circuit_options->Type));
	unset($circuit_option_values[0]);
	// reindex and remove empty
	$circuit_option_values      = array_values(array_filter($circuit_option_values));


    foreach($circuit_option_values as $v) {
    $html[] = "<a class='open_popup' data-script='app/admin/circuits/edit-options.php' data-action='delete' data-type='type' data-value='$v' href='' rel='tooltip' data-placement='right' title='Remove option'>";
    $html[] = "    <span class='badge badge1'><i class='fa fa-minus alert-danger'></i> $v</span>";
    $html[] = "</a><br>";
    }
    $html[] = "<hr>";
    $html[] = "<a class='open_popup' data-script='app/admin/circuits/edit-options.php' data-action='add' data-type='type' data-value=''  href='' rel='tooltip' data-placement='right'  title='Add option'>";
    $html[] = "    <span class='badge badge1 alert-success'><i class='fa fa-plus'></i> "._('Add option')."</span>";
    $html[] = "</a>";
}
// user is not admin
else {
    $html[] = $Result->show("danger", _("Administrative privileges required"), false);
}

// print HTML
print implode("\n", $html);