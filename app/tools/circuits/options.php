<?php

# Check we have been included and not called directly
require( dirname(__FILE__) . '/../../../functions/include-only.php' );

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
	$html[] = _("Here you can manage options for circuit types").":<br>";

	# get all available set options
	$circuit_options = $Tools->fetch_all_objects ("circuitTypes", "ctname");

    foreach($circuit_options as $v) {
        $html[] = " <a class='open_popup' data-script='app/admin/circuits/edit-options.php' data-action='delete' data-type='type' data-op_id='$v->id' data-value='$v->ctname' data-color='$v->ctcolor' data-pattern='$v->ctpattern' href='' rel='tooltip' data-placement='right' title='"._("Remove option")."'>";
        $html[] = "    <span class='badge badge1' style='color:white;background:$v->ctcolor !important'><i class='fa fa-remove'></i> $v->ctname ($v->ctpattern Line)</span>";
        $html[] = "</a><br>";
    }
    $html[] = "<br>";
    $html[] = " <a class='open_popup' data-script='app/admin/circuits/edit-options.php' data-action='add' data-type='type' data-op_id='' data-value='' data-color=''  data-pattern=''  href='' rel='tooltip' data-placement='right'  title='"._("Add option")."'>";
    $html[] = "    <span class='badge badge1 alert-success'><i class='fa fa-plus'></i> "._('Add option')."</span>";
    $html[] = "</a>";
}
// user is not admin
else {
    $html[] = $Result->show("danger", _("Administrative privileges required"), false);
}

// print HTML
print implode("\n", $html);



/* fetch all custom fields */
$custom_tables = array(
						"circuitProviders" => _("Circuit providers"),
						"circuits"         => _("Circuits"),
						"circuitsLogical"  => _("Logical circuits")
						);
# create array
foreach($custom_tables as $k=>$f) {
	$custom_fields[$k]            = $Tools->fetch_custom_fields($k);
	$custom_fields_numeric[$k]    = $Tools->fetch_custom_fields_numeric($k);
	$custom_fields[$k]['title']   = _("Custom")." ".$f." "._("fields");
	$custom_fields[$k]['tooltip'] = _("Add new custom")." ".$f." "._("field");
}
?>

<br><br><br><br><br>
<h4><?php print _('Custom fields'); ?></h4>
<hr>


<table class="customIP table table-striped table-auto table-top" style="min-width:400px;">

<tr>
	<td></td>
	<td><?php print _('Title'); ?></td>
	<td><?php print _('Description'); ?></td>
	<td><?php print _('Field type'); ?></td>
	<td><?php print _('Default'); ?></td>
	<td><?php print _('Required'); ?></td>
	<td><?php print _('Visible'); ?></td>
	<td></td>
</tr>

<?php
# printout each
foreach($custom_fields as $k=>$cf) {

	# save vars and unset
	$title   = $cf['title'];
	$tooltip = $cf['tooltip'];

	unset($cf['title']);
	unset($cf['tooltip']);

	# set key
	$table = $k;

	# get custom fields
	$ffields = pf_json_decode($User->settings->hiddenCustomFields, true);
	$ffields = is_array(@$ffields[$table]) ? $ffields[$table] : array();

	print "<tbody id='custom-$k'>";

	//title
	print "	<tr>";
	print "	<th colspan='8'>";
	print "		<h5>"._($title)."</h5>";
	print "	</th>";
	print "	</tr>";

	//empty
	if(sizeof($cf) == 0) {
	print "	<tr>";
	print "	<td colspan='8'>";
	print "		<div class='alert alert-info alert-nomargin'>"._('No custom fields created yet')."</div>";
	print "	</td>";
	print "	</tr>";
	}
	//content
	else {
		$size = sizeof($cf);		//we must remove title
		$m=0;

		foreach($cf as $f)
		{
			# space?
			$class = !preg_match('/^(\p{L}|\p{N})[(\p{L}|\p{N}) _.-]+$/u', $f['name']) ? "alert-danger" : "";

			print "<tr class='$class'>";

			# ordering
			if (( ($m+1) != $size) ) 	{ print "<td style='width:10px;'><button class='btn btn-xs btn-default down' data-direction='down' data-table='$table' rel='tooltip' title='"._("Move down")."' data-fieldname='".$custom_fields_numeric[$table][$m]['name']."' data-nextfieldname='".$custom_fields_numeric[$table][$m+1]['name']."'><i class='fa fa-chevron-down'></i></button></td>";	}
			else 						{ print "<td style='width:10px;'></td>";}

			print "<td class='name'>".$Tools->print_custom_field_name ($f['name'])."</td>";

			# description
			print "<td>$f[Comment]</td>";

			# type
			print "<td>$f[type]</td>";

			# default
			print "<td>$f[Default]</td>";


			# NULL
			if(@$f['Null']=="NO")		{ print "<td>"._('Required')."</td>"; }
			else						{ print "<td></td>"; }

			# visible
			if(in_array($f['name'], $ffields))	{ print "<td><span class='text-danger'>"._('No')."</span></td>"; }
			else								{ print "<td><span class='text-success'>"._('Yes')."</span></td>"; }

			#actions
			print "<td class='actions'>";
			print "	<div class='btn-group'>";
			print "		<button class='btn btn-xs btn-default edit-custom-field' data-action='edit'   data-fieldname='$f[name]' data-table='$table'><i class='fa fa-pencil'></i></button>";
			print "		<button class='btn btn-xs btn-default edit-custom-field' data-action='delete' data-fieldname='$f[name]' data-table='$table'><i class='fa fa-times'></i></button>";
			print "	</div>";

			# warning for older versions
			if((is_numeric(substr($f['name'], 0, 1))) || (!preg_match('/^(\p{L}|\p{N})[(\p{L}|\p{N}) _.-]+$/u', $f['name'])) ) { print '<span class="alert alert-warning"><strong>Warning</strong>: '._('Invalid field name').'!</span>'; }

			print "</td>";
			print "</tr>";

			$prevName = $f['name'];
			$m++;
		}
	}

	//add
	print "<tr>";
	print "<td colspan='8' style='padding-right:0px;'>";
	print "	<button class='btn btn-xs btn-default pull-right edit-custom-field' data-action='add'  data-fieldname='' data-table='$table' rel='tooltip' data-placement='right' title='"._($tooltip)."'><i class='fa fa-plus'></i></button>";
	print "</td>";
	print "</tr>";

	//filter
	print "<tr>";
	print "<td colspan='8' style='padding-right:0px;'>";
	print "	<button class='btn btn-xs btn-info pull-right edit-custom-filter' data-table='$table' rel='tooltip' data-placement='right' title='"._("Set which field to display in table")."'><i class='fa fa-filter'></i> Filter</button>";
	print "</td>";
	print "</tr>";

	//result
	print "<tr>";
	print "	<td colspan='8' class='result'>";
	print "		<div class='$table-order-result'></div>";
	print "</td>";
	print "</tr>";


	print "</tbody>";
}
?>

</table>
