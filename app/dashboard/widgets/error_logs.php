<?php

# required functions
if(!isset($User)) {
	require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
	# classes
	$Database	= new Database_PDO;
	$User 		= new User ($Database);
	$Tools 		= new Tools ($Database);
	$Log		= new Logging ($Database);
	$Result		= new Result ();
}

# user must be authenticated
$User->check_user_session ();
# user must be admin
$User->is_admin(true);

# if direct request that redirect to tools page
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest")	{
	header("Location: ".create_link("tools","logs"));
}

# if syslog print
if ($User->settings->log=="syslog") {
	$Result->show("warning", _("Log files are sent to syslog"));
}
else {
	# fetch widget parameters
	$wparam = $Tools->get_widget_params("error_logs");
	$max    = filter_var($wparam->max,    FILTER_VALIDATE_INT, ['options' => ['default' => 5,    'min_range' => 1, 'max_range' => 256]]);
	$height = filter_var($wparam->height, FILTER_VALIDATE_INT, ['options' => ['default' => null, 'min_range' => 1, 'max_range' => 800]]);

	# print last N access logs
	$logs = $Log->fetch_logs($max, NULL, NULL, NULL, "off", "on", "on");
	if (!is_array($logs)) { $logs = array(); }

	print '<div style="' . (isset($height) ? "height:{$height}px;overflow-y:auto;" : "") . '">';
	print "<table class='table table-condensed table-hover table-top'>";

	# headers
	print "<tr>";
	print "	<th>"._('Severity')."</th>";
	print "	<th>"._('Command')."</th>";
	print "	<th>"._('Date')."</th>";
	print "	<th>"._('Username')."</th>";
	print "</tr>";

	# logs
	foreach($logs as $log) {
		# cast
		$log = (array) $log;
		# reformat severity
		if($log['severity'] == 0)		{ $log['severityText'] = _("Info"); }
		elseif($log['severity'] == 1)	{ $log['severityText'] = _("Warn"); }
		elseif($log['severity'] == 2)	{ $log['severityText'] = _("Err"); }

		print "<tr>";
		print "	<td><span class='severity$log[severity]'>$log[severityText]</span></td>";
		print "	<td><a class='openLogDetail' data-logid='$log[id]'>$log[command]</a></td>";
		print "	<td>$log[date]</td>";
		print "	<td>$log[username]</td>";

		print "</tr>";
	}

	print "</table>";

	# print if none
	if(sizeof($logs) == 0) {
		print "<blockquote style='margin-top:20px;margin-left:20px;'>";
		print "<p>"._("No logs available")."</p>";
		print "</blockquote>";
	}

	print "</div>";
}
?>
