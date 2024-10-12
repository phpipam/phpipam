<?php

/**
 * Script to display last changelogs
 */

# verify that user is logged in
$User->check_user_session();

# header
print "<h4>"._('Changelog')."</h4>";

# if enabled
if($User->settings->enableChangelog == 1) {
	# set default size
	if(!isset($GET->subnetId))	{$input_climit = 50; }
	else							{$input_climit = (int) $GET->subnetId; }

	# change parameters - search string provided
	$input_cfilter = '';
	if(isset($GET->sPage)) {
		$input_cfilter = escape_input(urldecode($GET->subnetId));
		$input_climit  = (int) $GET->sPage;
	}
	elseif(isset($GET->subnetId)) {
		$input_climit  = (int) $GET->subnetId;
	}
	else {
		$input_climit  = 50;
	}
?>
	<!-- filter -->
	<div class="text-right">
	<form name='cform' id='cform' class='form-inline'>
		<div class='input-group' style='margin-bottom:20px;'>

		<div class='form-group'>
			<select name='climit' class='input-sm climit form-control'>
			<?php
			$printLimits = array(50,100,250,500);
			foreach($printLimits as $l) {
				if($l ==$input_climit)	{ print "<option value='$l' selected='selected'>$l</option>"; }
				else							{ print "<option value='$l'>$l</option>"; }
			}
			?>
			</select>
		</div>

		<div class='form-group'>
			<input class='span2 cfilter input-sm form-control' name='cfilter' value='<?php print $input_cfilter;?>' type='text' style='width:150px;'>
			<span class="input-group-btn">
				<input type='submit' class='btn btn-sm btn-default' value='<?php print _('Search');?>'>
			</span>
		</div>

		</div>
	</form>

		<!-- clear log files -->
		<button id="clearChangeLogs" class="btn btn-sm btn-default pull-left"><i class="fa fa-trash-o"></i> <?php print _('Clear logs'); ?></button>
	</div>

	<div class="normalTable logs" style="clear:both;">
	<?php
	# printout
	include_once('changelog-print.php');
}
else {
	$Result->show("info",_("Change logging is disabled. You can enable it under administration")."!", false);
} ?>
	</div>		<!-- end normalTable logs div -->
