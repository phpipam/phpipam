<?php

/**
 * Script to edit / add / delete groups
 *************************************************/

# verify that user is logged in
$User->check_user_session();

# fetch domains
$type = $_GET['subnetId'];

// fetch required domains
switch ($type) {
	// fetch forward domains
	case 'domains':
		$title = _("Domains");
		$domains = $PowerDNS->fetch_all_forward_domains ();
		break;
	// fetch v4 reverse domains
	case 'reverse_v4':
		$title = _("IPv4 reverse domains");
		$domains = $PowerDNS->fetch_reverse_v4_domains ();
		break;
	// fetch v6 reverse domains
	case 'reverse_v6':
		$title = _("IPv6 reverse domains");
		$domains = $PowerDNS->fetch_reverse_v6_domains ();
		break;
	// error
	default:
		$Result->show("danger", "Invalid request", true);
		break;
}

// if serach blank unset
if (strlen(@$_POST['domain-filter'])==0)	{ unset($_POST['domain-filter']); }

// if search filter out hits
if ($_GET['sPage']=="search" && strlen(@$_POST['domain-filter'])>0) {
	// loop domains
	foreach ($domains as $k=>$d) {
		// search through records, if no hits unset
		$hit = false;
		foreach ($d as $dd) {
			if (preg_match("/".$_POST['domain-filter']."/", $dd)) {
				$hit = true;
				break;
			}
		}
		// no hit
		if ($hit===false) {
			unset($domains[$k]);
		}
	}
	// unset if null
	if( sizeof($domains) == 0) {
		$domains = false;
	}
}

?>

<br>
<h4><?php print $title; ?></h4><hr>

<!-- Back -->
<div class="btn-group" style="margin-bottom:10px;margin-top: 10px;">
    <?php if ($domains===false && isset($_POST['domain-filter'])) { ?>
    <a class='btn btn-sm btn-default btn-default'  href="<?php print create_link ("tools", "powerDNS", $_GET['subnetId']); ?>"><i class='fa fa-angle-left'></i> <?php print _('Back'); ?></a>
    <?php } ?>
    <?php if ($User->is_admin()) { ?>
    <!-- Add new -->
    <a class='btn btn-sm btn-default btn-default btn-success'  href="<?php print create_link ("administration", "powerDNS"); ?>"><i class='fa fa-pencil'></i> <?php print _('Manage domains'); ?></a>
   <?php } ?>
</div>
<br>


<?php
// none - filtered
if($domains===false && isset($_POST['domain-filter']))	{ $Result->show("info alert-absolute", _("No records found for filter ")."'".$_POST['domain-filter']."'", false); }
// none
elseif($domains===false) 								{ $Result->show("info alert-absolute", _("No domains configured"), false); }
else {


// set default number of records per page
$pagination = 50;

//split to chunks
$domains_split = array_chunk($domains, $pagination);

//if search we can only do 1 page!
if (isset($_POST['domain-filter'])) { $domains_split = array($domains); }
?>

<!-- table -->
<table id="zonesPrint" class="table sorted table-striped table-top">

<!-- Headers -->
<thead>
<tr>
    <th><?php print _('Domain'); ?></th>
    <th><?php print _('Type'); ?></th>
    <th><?php print _('Master NS'); ?></th>
    <th><?php print _('Records'); ?></th>
    <th><?php print _('Serial number'); ?></th>
</tr>
</thead>

<!-- domains -->
<tbody>
<?php

// default page
if(!isset($_GET['ipaddrid']))	{ $_GET['ipaddrid'] = 1; }
$real_page = $_GET['ipaddrid']-1;

// paginate
foreach ($domains_split as $k=>$split) {
	// new index
	$k2 = $k+1;

	// invalid page
	if (!isset($domains_split[$real_page])) {
		print "<tbody>";
		print "	<tr><td colspan='6'><div class='alert alert-danger'>"._("Invalid page")."</div></td></tr>";
		print "</tbody>";
	}
	// match
	elseif ($_GET['ipaddrid']==$k2) {
		/* prints domain records */
		foreach ($split as $d) {
			// nulls
			foreach($d as $k=>$v) {
				if (strlen($v)==0)	$d->$k = "<span class='muted'>/</span>";
			}
			// cont records
			$cnt = $PowerDNS->count_domain_records ($d->id);
			// get SOA record
			$soa = $PowerDNS->fetch_domain_records_by_type ($d->id, "SOA");
			$serial = explode(" ", $soa[0]->content);
			$serial = $serial[2];

			print "<tr>";
			// content
			print "	<td><a href='".create_link("tools", "powerDNS", $_GET['subnetId'], "records", $d->name)."'>$d->name</a></td>";
			print "	<td><span class='badge badge1'>$d->type</span></td>";
			print "	<td>$d->master</td>";
			print "	<td><span class='badge'>$cnt</span></td>";
			print "	<td>$serial</td>";

			print "</tr>";
		}
	}
}
?>
</tbody>
</table>
<?php } ?>