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
<button class="btn btn-sm btn-default lang" data-action='add' style="margin-bottom:10px;"><i class="fa fa-plus"></i> <?php print _("Create new language"); ?></button>
<table class="table table-striped table-auto table-top" style="min-width:400px;">

	<!-- Language list -->
	<?php
	/* no results */
	if($languages==false) { ?>
		<tr>
			<td colspan="4"><div class="alert alert-info alert-nomargin"><?php print _('No languages present created yet'); ?></div></td>
		</tr>
	<?php } else {
		# headers
		print "<tr>";
		print "	<th>"._('Language code')."</th>";
		print "	<th>"._('Language name')."</th>";
		print "	<th>"._('Validity')."</th>";
		print "	<th>"._('Version')."</th>";
		print "	<th></th>";
		print "</tr>";

		# print
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
			print "		<button class='btn btn-xs btn-default lang' data-action='edit' data-langid='$lang[l_id]'><i class='fa fa-pencil'></i></button>";
			print "		<button class='btn btn-xs btn-default lang' data-action='delete' data-langid='$lang[l_id]'><i class='fa fa-times'></i></button>";
			print "	</div>";
			print "	</td>";
			print "</tr>";
		}
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
