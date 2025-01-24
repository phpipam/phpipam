<?php

/**
 * Script to print selected logs
 **********************************/

/* required functions */
if(!isset($User) || !is_object($User)) {

	/* functions */
	require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

	# initialize user object
	$Database 	= new Database_PDO;
	$User 		= new User ($Database);
	$Tools	 	= new Tools ($Database);
	$Admin		= new Admin ($Database);
	$Result 	= new Result ();
	$Log		= new Logging ($Database);

	# verify that user is logged in
	$User->check_user_session();
}

# if nothing is provided display all
if ( empty($POST->Informational) && empty($POST->Notice) && empty($POST->Warning) ) {
    $POST->Informational = "Informational";
    $POST->Notice        = "Notice";
    $POST->Warning       = "Warning";
}
?>

<script>
$(document).ready(function() {
	if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }

	return false;
});
</script>


<table id="logs" class="table sorted nosearch nopagination table-condensed table-hover table-top" style="margin-top:10px;" data-cookie-id-table="show_logs">

<!-- print headers -->
<thead>
<tr>
    <th class="date" style="width:130px;white-space:nowrap"><?php print _('Date'); ?></th>
    <th><?php print _('Severity'); ?></th>
    <th><?php print _('Username'); ?></th>
    <th><?php print _('IP address'); ?></th>
    <th><?php print _('Event'); ?></th>
    <th></th>
</tr>
</thead>

<tbody>
<!-- print logs -->
<?php

//fetch 40 logs at once
$logCount = 40;

//set severity queries
$informational = $POST->Informational=="Informational" ? "on" : "off";
$notice 	   = $POST->Notice=="Notice" ? "on" : "off";
$warning 	   = $POST->Warning=="Warning" ? "on" : "off";

//get highest lastId */
$highestId = $Log->log_fetch_highest_id();
if(empty($POST->lastId) || ($POST->lastId == "undefined")) 	{ $POST->lastId = $highestId; }

//set empty direction
if(!isset($POST->direction)) 									{ $POST->direction = ""; }

/* get requested logs */
$logs = $Log->fetch_logs($logCount, $POST->direction, $POST->lastId, $highestId, $informational, $notice, $warning);
if (!is_array($logs)) { $logs = array(); }

$x = 0;
foreach ($logs as $log) {
	//cast
	$log = (array) $log;

    //set classes based on severity
    if ($log['severity'] == 0) {
        $log['severityText'] = _("Informational");
        $color = "success";
    }
    elseif ($log['severity'] == 1) {
        $log['severityText'] = _("Notice");
        $color = "warning";
    }
    elseif ($log['severity'] == 2) {
        $log['severityText'] = _("Warning");
        $color = "danger";
    }
    else {
        $log['severityText'] = _("Unknown");
        $color = "info";
    }

	/* reformat details */
	$log['details'] = str_replace("\"", "'", $log['details']);

    print '<tr class="'.$color.' '. $log['severityText'] .'" id="'. $log['id'] .'">'. "\n";
 	print '	<td class="date">'. $log['date']     .'</td>'. "\n";
    print '	<td class="severity"><span>'. $log['severity'] .'</span>'. $log['severityText'] .'</td>'. "\n";
	print '	<td class="username">'. $log['username'] .'</td>'. "\n";
	print '	<td class="ipaddr">'. $log['ipaddr'] .'</td>'. "\n";
    print '	<td class="command"><a href="" class="openLogDetail" data-logid="'.$log['id'].'">'. $log['command']  .'</a></td>'. "\n";
    print '	<td class="detailed">';
    /* details */
    if(!empty($log['details'])) { print '	<i class="fa fa-comment fa-gray" rel="tooltip" data-html="true" data-placement="left" title="<b>'._('Event details').'</b>:<hr>'. $log['details'] .'"></i></td>'. "\n"; }
    print '	</td>'. "\n";
	print '</tr>'. "\n";
}
?>
</tbody>
</table>	<!-- end filter table -->


<?php if(sizeof($logs)== 0) { $Result->show("info", _('No logs available')."!", true); } ?>