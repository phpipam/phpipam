<?php

/*
 * Script to print some stats on home page....
 *********************************************/

# required functions if requested via AJAX
if(!isset($User)) {
	require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
	# classes
	$Database	= new Database_PDO;
	$User 		= new User ($Database);
	$Tools 		= new Tools ($Database);
	$Result 	= new Result ();
}

# user must be authenticated
$User->check_user_session ();

# if direct request that redirect to tools page
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest")	{
	header("Location: ".create_link("tools","customers"));
}

# verify module permissions
$User->check_module_permissions ("customers", User::ACCESS_R, true);
# filter customers or fetch print all?
$customers = $Tools->fetch_all_objects("customers", "title");

# fetch widget parameters
$wparam = $Tools->get_widget_params("customers");
$height = filter_var($wparam->height, FILTER_VALIDATE_INT, ['options' => ['default' => null, 'min_range' => 1, 'max_range' => 800]]);

# table
print '<div style="width:98%;margin-left:1%;' . (isset($height) ? "height:{$height}px;overflow-y:auto;" : "") . '">';
print '<table id="customers" class="table sorted table-striped table-top" data-cookie-id-table="customers">';

#headers
print "<thead>";
print '<tr>';
print "	<th>"._('Title')."</th>";
print "	<th>"._('Address').'</th>';
print "	<th>"._('Contact').'</th>';
print '</tr>';
print "</thead>";

// no customers
if($customers===false) {
	print "<tr>";
	print "	<td colspan='3'>".$Result->show('info', _('No results')."!", false, false, true)."</td>";
	print "</tr>";
}
// result
else {
	foreach ($customers as $customer) {
		// print details
		print '<tr>'. "\n";
		print "	<td><strong><a class='btn btn-sm btn-default' href='".create_link("tools","customers",$customer->title)."'>$customer->title</a></strong></td>";
		print "	<td>$customer->address, $customer->postcode $customer->city, $customer->state</td>";
		print " <td><a href='mailto:$customer->contact_mail'>$customer->contact_person</a> ($customer->contact_phone)</td>";
		print '</tr>';
	}
}
print '</table>';
print '</div>';
