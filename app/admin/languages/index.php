<?php

/**
 * Script to manage languages
 ****************************************/

# verify that user is logged in
$User->check_user_session();

# fetch all APIs
$languages = $Admin->fetch_all_objects("lang", "l_id");
?>


<h4><?php print _('Languages'); ?></h4>
<hr>
<?php print "<p class='muted'>"._('You can edit different language translations here')."</p>"; ?>

<!-- Add new -->
<button class='btn btn-sm btn-default open_popup' style="margin-bottom:10px;" data-script='app/admin/languages/edit.php' data-class='700' data-action='add'><i class='fa fa-plus'></i> <?php print _('Create new language'); ?></button>


<table class="table sorted nosearch nopagination table-striped table-auto table-top" data-cookie-id-table="admin_lang" style="min-width:400px;">
	<!-- Language list -->
	<?php
	/* no results */
	if($languages==false) { ?>
		<tr>
			<td colspan="4"><div class="alert alert-info alert-nomargin"><?php print _('No languages present created yet'); ?></div></td>
		</tr>
	<?php } else {
		# headers
		print "<thead>";
		print "<tr>";
		print "	<th>"._('Language code')."</th>";
		print "	<th>"._('Language name')."</th>";
		print "	<th>"._('Validity')."</th>";
		print "	<th>"._('Version')."</th>";
		print "	<th></th>";
		print "</tr>";
		print "</thead>";

		# print
		print "<tbody>";
		foreach($languages as $lang) {
			//cast
			$lang = (array) $lang;

			# verify validity
			$valid = $Tools->verify_translation($lang['l_code']);
			# check version
			$tversion = $valid===true ? $Tools->get_translation_version ($lang['l_code']) : "NA";

			# set valid text
			if($valid)  { $vPrint = "<span class='alert alert-success btn-sm'>"._('Valid')."</span>"; }
			else		{ $vPrint = "<span class='alert alert-danger  btn-sm'>"._('Invalid')."</span>"; }

			print "<tr>";
			print "	<td>$lang[l_code]</td>";
			print "	<td>$lang[l_name]</td>";
			print "	<td>$vPrint</td>";
			print "	<td>$tversion</td>";
			print "	<td>";
			print "	<div class='btn-group'>";
			print "		<button class='btn btn-xs btn-default open_popup' data-langid='$lang[l_id]' data-script='app/admin/languages/edit.php' data-class='700' data-action='edit'><i class='fa fa-pencil'></i></button>";
			print "		<button class='btn btn-xs btn-default open_popup' data-langid='$lang[l_id]' data-script='app/admin/languages/edit.php' data-class='700' data-action='delete'><i class='fa fa-times'></i></button>";
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
	<?php print _('Instructions'); ?>:<hr>
	<ol>
		<li><?php print _('Add translation file to directory functions/locale/ in phpipam'); ?></li>
		<li><?php print _('Create new language with same code as translation file'); ?></li>
	</ol>
</div>
