<?php
# config, objects
require_once( dirname(__FILE__).'/../../../functions/functions.php' );

# initialize objects
$Database	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);
# verify that user is logged in
$User->check_user_session();

// process input values
$tcp   = $_POST['wsize'];
$delay = $_POST['delay'];
$fsize = $_POST['fsize'];

// get mbps values from config
$mbps = round($tcp/($delay/1000)/(1024*1024),2);

// Calculate transfer time
$time = round(($fsize / $mbps), 2);

// set network type
if($delay<1)		{ $type = "LAN"; }
elseif($delay<20)	{ $type = "MAN"; }
else 				{ $type = "WAN"; }
?>

<hr>
<p>

<strong><?php print _("Transfer time (h:m:s)"); ?>:</strong>
<div class='res_val'><?php print $User->sec2hms($time); ?></div>

<?php if(!isset($_POST['widget'])) { ?>
<div class="clearfix"></div>

<br><br>
<strong><?php print _("Calculation parameters"); ?>:</strong>
<ul>
	<li> <?php print _("TCP window size").": $tcp"; ?></li>
	<li> <?php print _("Delay").": $delay"." "._("ms"); ?></li>
	<li> <?php print _("Speed").": $mbps"." "._("MBps"); ?></li>
	<li> <?php print _("Newtork type").": $type"; ?></li>
	<li> <?php print _("File size").": $fsize"." "."MB"; ?></li>
</ul>
</p>
<br>
<?php } ?>