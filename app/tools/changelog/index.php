<?php

/**
 * Script to display last changelogs
 */

# verify that user is logged in
$User->check_user_session();

# strip tags - XSS
$_REQUEST = $User->strip_input_tags ($_REQUEST);
$_GET = $User->strip_input_tags ($_GET);

# header
print "<h4>"._('Changelog')."</h4>";

# if enabled
if($User->settings->enableChangelog == 1) {
	# set default size
	if(!isset($_REQUEST['subnetId']))	{ $_REQUEST['climit'] = 50; }
	else								{ $_REQUEST['climit'] = $_GET['subnetId']; }

	# change parameters - search string provided
	if(isset($_GET['sPage'])) {
		$_REQUEST['cfilter']  = htmlentities($_REQUEST['subnetId'], ENT_QUOTES);
		$_REQUEST['climit']  = htmlentities($_REQUEST['sPage'], ENT_QUOTES);
	}
	elseif(isset($_GET['subnetId'])) {
		$_REQUEST['climit']  = htmlentities($_REQUEST['subnetId'], ENT_QUOTES);
	}
	else {
		$_REQUEST['climit']  = 50;
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
				if($l == $_REQUEST['climit'])	{ print "<option value='$l' selected='selected'>$l</option>"; }
				else							{ print "<option value='$l'>$l</option>"; }
			}
			?>
			</select>
		</div>

		<div class='form-group'>
			<input class='span2 cfilter input-sm form-control' name='cfilter' value='<?php print @htmlentities($_REQUEST['cfilter'], ENT_QUOTES);?>' type='text' style='width:150px;'>
			<span class="input-group-btn">
				<input type='submit' class='btn btn-sm btn-default' value='<?php print _('Search');?>'>
			</span>
		</div>

		</div>
	</form>
	</div>

	<?php
	# printout
	include_once('changelog-print.php');
}
else {
	$Result->show("info",_("Change logging is disabled. You can enable it under administration")."!", false);
}
?>