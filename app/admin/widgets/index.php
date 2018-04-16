<?php

/**
 * Script to manage widgets
 ****************************************/

# verify that user is logged in
$User->check_user_session();

# fetch all widgets
$widgets = $Admin->fetch_all_objects("widgets", "wid");
?>


<h4><?php print _('Widgets'); ?></h4>
<hr>
<?php
print "<p class='muted'>";
print _('You can manage widgets here').".<br>";
print "</p>";
?>

<!-- Add new -->
<button class='btn btn-sm btn-default open_popup' style="margin-bottom:10px;" data-script='app/admin/widgets/edit.php' data-class='700' data-action='add'><i class='fa fa-plus'></i> <?php print _('Create new widget'); ?></button>

<table class="table sorted table-striped table-top" style="min-width:400px;" data-cookie-id-table="widgets">

	<!-- Language list -->
	<?php
	/* no results */
	if($widgets===false) { ?>
		<tr>
			<td colspan="4"><div class="alert alert-info alert-nomargin"><?php print _('No widgets created yet'); ?></div></td>
		</tr>
	<?php } else {
		# headers
		print "<thead>";
		print "<tr>";
		print "	<th>"._('Title')."</th>";
		print "	<th>"._('Description')."</th>";
		print "	<th>"._('File')."</th>";
		print "	<th>"._('Admin')."</th>";
		print "	<th>"._('Active')."</th>";
		print "	<th>"._('Parameters')."</th>";
		print "	<th>"._('Validity')."</th>";
		print "	<th></th>";
		print "</tr>";
		print "</thead>";

        print "<tbody>";
		# print
		foreach($widgets as $w) {
			# cast
			$w = (array) $w;

			# verify validity
			$valid = $Tools->verify_widget($w['wfile']);

			if($valid)  { $vPrint = "<span class='badge badge1 badge5 alert-success'>"._('Valid')."</span>"; }
			else		{ $vPrint = "<span class='badge badge1 badge5 alert-danger'>"._('Invalid')."</span>"; }

			print "<tr>";
			print "	<td>"._($w['wtitle'])."</td>";
			print "	<td>"._($w['wdescription'])."</td>";
			print "	<td>$w[wfile].php</td>";
			print "	<td>"._($w['wadminonly'])."</td>";
			print "	<td>"._($w['wactive'])."</td>";
			print "	<td>"._($w['wparams'])."</td>";
			print "	<td>$vPrint</td>";
			print "	<td>";
			print "	<div class='btn-group'>";
			print "		<button class='btn btn-xs btn-default open_popup' data-wid='$w[wid]' data-script='app/admin/widgets/edit.php' data-class='700' data-action='edit'><i class='fa fa-pencil'></i></button>";
			print "		<button class='btn btn-xs btn-default open_popup' data-wid='$w[wid]' data-script='app/admin/widgets/edit.php' data-class='700' data-action='delete'><i class='fa fa-times'></i></button>";
			print "	</div>";
			print "	</td>";
			print "</tr>";
		}
		print "</tbody>";
	}
	?>
</table>

<hr>
<div class="alert alert-info alert-block alert-auto alert-absolute">
	<?php print _('Instructions')." : "._('Create widget file in directory app/dashboard/widgets/')."."; ?>
</div>