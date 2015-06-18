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
<button class="btn btn-sm btn-default wedit" data-action='add' style="margin-bottom:10px;"><i class="fa fa-plus"></i> <?php print _("Create new widget"); ?></button>

<table class="table table-striped table-auto table-top" style="min-width:400px;">

	<!-- Language list -->
	<?php
	/* no results */
	if($widgets===false) { ?>
		<tr>
			<td colspan="4"><div class="alert alert-info alert-nomargin"><?php print _('No widgets created yet'); ?></div></td>
		</tr>
	<?php } else {
		# headers
		print "<tr>";
		print "	<th>"._('Title')."</th>";
		print "	<th>"._('Description')."</th>";
		print "	<th>"._('File')."</th>";
		print "	<th>"._('Admin')."</th>";
		print "	<th>"._('Active')."</th>";
		print "	<th>"._('Validity')."</th>";
		print "	<th></th>";
		print "</tr>";

		# print
		foreach($widgets as $w) {
			# cast
			$w = (array) $w;

			# verify validity
			$valid = $Tools->verify_widget($w['wfile']);

			if($valid)  { $vPrint = "<span class='alert alert-success'>"._('Valid')."</span>"; }
			else		{ $vPrint = "<span class='alert alert-danger'>"._('Invalid')."</span>"; }

			print "<tr>";
			print "	<td>"._($w['wtitle'])."</td>";
			print "	<td>"._($w['wdescription'])."</td>";
			print "	<td>$w[wfile].php</td>";
			print "	<td>"._($w['wadminonly'])."</td>";
			print "	<td>"._($w['wactive'])."</td>";
			print "	<td>$vPrint</td>";
			print "	<td>";
			print "	<div class='btn-group'>";
			print "		<button class='btn btn-xs btn-default wedit' data-action='edit' data-wid='$w[wid]'><i class='fa fa-pencil'></i></button>";
			print "		<button class='btn btn-xs btn-default wedit' data-action='delete' data-wid='$w[wid]'><i class='fa fa-times'></i></button>";
			print "	</div>";
			print "	</td>";
			print "</tr>";
		}
	}
	?>


</table>

<hr>
<div class="alert alert-info alert-block alert-auto alert-absolute">
	<?php print _('Instructions')." : "._('Create widget file in directory app/dashboard/widgets/')."."; ?>
</div>
