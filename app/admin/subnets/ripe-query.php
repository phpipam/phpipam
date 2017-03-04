<?php

/**
 * Function to get RIPe info for network
 ********************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Subnets	= new Subnets ($Database);
$Tools		= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# try to fetch
$res = $Subnets->resolve_ripe_arin ($_POST['subnet']);
?>

<!-- header -->
<div class="pHeader"><?php print _(ucwords($res['result'])); ?></div>

<!-- content -->
<div class="pContent">
	<?php
	// error ?
	if ($res['result']=="error") {
		$Result->show("danger", _(ucwords($res['error'])), false);
	}
	// ok, print field matching
	else {
		// fetch all fields for subnets
		$standard_fields = array("description");
		$custom_fields 	 = $Tools->fetch_custom_fields ("subnets");

		// leave only varchar and text
		foreach ($custom_fields as $k=>$f) {
			if (!(strpos($f['type'], "varchar")!==false || $f['type']=="text")) {
				unset($custom_fields[$k]);
			}
		}
		// append description
		$custom_fields = array_merge(array("description"=>array("name"=>"description")), $custom_fields);

		print "<h4>"._("Please select fields to populate:")."</h4>";
		// form
		print "<form name='ripe-fields' id='ripe-fields'>";
		print "<table class='table'>";
		// loop
		if (isset($res['data'])) {
			foreach ($res['data'] as $k=>$d) {
				print "<tr>";
				print "<td>";
				print "	<span class='text-muted'>$k</span>:  $d";
				print "</td>";

				print "<td>";
				// add +
				$d = str_replace(" ", "___", $d);
				print "<select name='$d' class='form-control input-sm'>";
				print "<option value='0'>None</option>";
				// print custom
				if (sizeof($custom_fields>0)) {
					foreach ($custom_fields as $f) {
						// replace descr with description
						if ($k=="descr")	$k = "description";

						if (strtolower($f['name'])==strtolower($k))	{ print "<option values='$f[name]' selected='selected'>$f[name]</option>"; }
						else										{ print "<option values='$f[name]'>$f[name]</option>"; }
					}
				}
				print "</select>";
				print "</td>";
				print "</tr>";
			}
		}
		else {
			$Result->show("info", _("No result"), false);
		}
		print "</table>";
		print "</form>";
	}
	?>
	</pre>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopup2"><?php print _('Cancel'); ?></button>
		<?php if($res['result']!="error") { ?>
		<button class="btn btn-sm btn-default btn-success" id="ripeMatchSubmit"><i class="fa fa-check"></i> <?php print _('fill'); ?></button>
		<?php } ?>
	</div>
</div>