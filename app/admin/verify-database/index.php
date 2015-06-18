<?php

/**
 * Script to verify database structure
 ****************************************/

# admin user is required
$User->is_admin(true);

# title
print "<h4>"._('Database structure verification').'</h4><hr>';

# check for possible errors
if(sizeof($errors = $Tools->verify_database())>0) {

	//tables
	if (isset($errors['tableError'])) {
		print '<div class="alert alert-danger alert-absolute" style="text-align:left;">'. "\n";
		print '<b>'._('Missing table').'s:</b>'. "\n";
		print '<ul class="fix-table">'. "\n";

		foreach ($errors['tableError'] as $table) {
			print '<li>';
			print $table." ";
			//get fix
			if(!$fix = $Tools->get_table_fix($table)) {
				$Result->show("danger", _("Cannot get fix for table")." $table!", true);
			} else {
				print "<a class='btn btn-xs btn-default btn-tablefix' style='margin-left:8px;' href='' data-tableid='$table' data-fieldid='' data-type='table'><i class='fa fa-magic fa-pad-right'></i>"._("Fix table")."</a>";
				print "<div id='fix-result-$table' style='display:none'></div>";
			}
			print '</li>'. "\n";
		}

		print '</ul>'. "\n";
		print '</div>'. "\n";
	}

	//fields
	if (isset($errors['fieldError'])) {
		print '<div class="alert alert-danger alert-absolute" style="text-align:left;">'. "\n";
		print '<b>'._('Missing fields').':</b>'. "\n";
		print '<ul class="fix-field">'. "\n";

		foreach ($errors['fieldError'] as $table=>$field) {
			print '<li>';
			print 'Table `'. $table .'`: missing field `'. $field .'`;';
			//get fix
			if(!$fix = $Tools->get_field_fix($table, $field)) {
				$Result->show("danger", _("Cannot get fix for table field ")." `$table` `$field`!", true);
			} else {
				print "<a class='btn btn-xs btn-default btn-tablefix' style='margin-left:8px;'  href='' data-tableid='$table' data-fieldid='$field' data-type='field'><i class='fa fa-magic fa-pad-right'></i>"._("Fix field")."</a>";
				print "<div id='fix-result-$table$field' style='display:none'></div>";
			}
			print '</li>'. "\n";
		}

		print '</ul>'. "\n";
		print '</div>'. "\n";
	}


}
else {
	$Result->show("success alert-absolute", _('All tables and fields are installed properly'), false);
}
?>