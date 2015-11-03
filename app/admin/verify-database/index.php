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
		// clearfix
		print "<div class='clearfix'></div>";
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
		// clearfix
		print "<div class='clearfix'></div>";
	}


}
else {
	$Result->show("success", _('All tables and fields are installed properly'), false);
}


# we will also check for invalid subnets and addresses
print "<h4>"._('Invalid subnets').'</h4><hr>';

$invalid_subnets = $Subnets->find_invalid_subnets();
if ($invalid_subnets===false) {
	$Result->show ("success", _("No invalid subnets detected"), false);
}
else {
	print "Found following invalid subnets (with unexisting parent subnet):<hr>";
	// loop
	foreach ($invalid_subnets as $subnet) {
		// print each subnet
		foreach ($subnet as $s) {
			print " - <a href='".create_link("subnets", $s->sectionId, $s->id)."'>$s->ip/$s->mask</a> ($s->description)"."<br>";
		}
	}
}


print "<h4>"._('Invalid addresses').'</h4><hr>';

$invalid_subnets = $Addresses->find_invalid_addresses();
if ($invalid_subnets===false) {
	$Result->show ("success", _("No invalid addresses detected"), false);
}
else {
	print "Found following invalid addresses (with unexisting subnet):<hr>";
	// loop
	foreach ($invalid_subnets as $subnet) {
		// print each subnet
		foreach ($subnet as $s) {
			print " <a class='btn btn-xs btn-danger modIPaddr' data-action='delete' data-id='$s->id' data-subnetId='$s->subnetId'><i class='fa fa-remove'></i></a> $s->ip $s->dns_name (database id: $s->id)"."<br>";
		}
	}
}


?>