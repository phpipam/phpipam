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

<strong>Transfer time (h:m:s):</strong>
<div class='res_val'><?php print $User->sec2hms($time); ?></div>

<?php if(!isset($_POST['widget'])) { ?>
<div class="clearfix"></div>

<br><br>
<strong>Calculation parameters:</strong>
<ul>
	<li> TCP window size: <?php print $tcp; ?></li>
	<li> Delay: <?php print $delay; ?> ms</li>
	<li> Speed: <?php print $mbps; ?> MBps</li>
	<li> Newtork type: <?php print $type; ?></li>
	<li> File size: <?php print $fsize; ?> MB</li>
</ul>
</p>
<br>
<?php } ?>