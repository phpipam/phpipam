<script type="text/javascript">
$(document).ready(function() {
	if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }

	return false;
});
</script>


<table id="logs" class="table table-condensed table-hover table-top" style="margin-top:10px;">

<?php

/**
 * Script to print selected logs
 **********************************/

/* required functions */
if(!is_object($User)) {

	/* functions */
	require( dirname(__FILE__) . '/../../../functions/functions.php');

	# initialize user object
	$Database 	= new Database_PDO;
	$User 		= new User ($Database);
	$Tools	 	= new Tools ($Database);
	$Result 	= new Result ();
	$Log		= new Logging ($Database);

	# verify that user is logged in
	$User->check_user_session();
}

# if nothing is provided display all
if ( empty($_POST['Informational']) && empty($_POST['Notice']) && empty($_POST['Warning']) ) {
    $_POST['Informational'] = _("Informational");
    $_POST['Notice']        = _("Notice");
    $_POST['Warning']       = _("Warning");
}
?>

<!-- print headers -->
<tr>
    <th class="date" style="width:130px;white-space:nowrap"><?php print _('Date'); ?></th>
    <th><?php print _('Severity'); ?></th>
    <th><?php print _('Username'); ?></th>
    <th><?php print _('IP address'); ?></th>
    <th colspan="2"><?php print _('Event'); ?></th>
</tr>

<!-- print logs -->
<?php

//fetch 40 logs at once
$logCount = 40;

//set severity queries
$_POST['InformationalQuery'] = @$_POST['Informational']==_("Informational") ? 0 : 10;
$_POST['NoticeQuery'] 		 = @$_POST['Notice']==_("Notice") ? 1 : 10;
$_POST['WarningQuery'] 		 = @$_POST['Warning']==_("Warning") ? 1 : 10;

//get highest lastId */
$highestId = $Log->log_fetch_highest_id();
if(empty($_POST['lastId']) || ($_POST['lastId'] == "undefined")) 	{ $_POST['lastId'] = $highestId; }

//set empty direction
if(!isset($_POST['direction'])) 									{ $_POST['direction'] = ""; }

/* get requested logs */
$logs = $Log->fetch_logs($logCount, $_POST['direction'], $_POST['lastId'], $highestId, $_POST['InformationalQuery'], $_POST['NoticeQuery'], $_POST['WarningQuery']);

$x = 0;
foreach ($logs as $log) {
	//cast
	$log = (array) $log;

	if($x < $logCount) {

	    //set classes based on severity
	    if ($log['severity'] == 0) {
	        $log['severityText'] = _("Informational");
	        $color = "success";
	    }
	    else if ($log['severity'] == 1) {
	        $log['severityText'] = _("Notice");
	        $color = "warning";
	    }
	    else {
	        $log['severityText'] = _("Warning");
	        $color = "danger";
	    }

    	if (in_array($log['severityText'], $_POST)) {
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
	}
	$x++;
}
?>

</table>	<!-- end filter table -->


<?php if(sizeof($logs)== 0) { $Result->show("info", _('No logs available')."!", true); } ?>