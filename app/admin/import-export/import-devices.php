<?php
/*
 * IP Addresses Import
 ************************************************/

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User = new User ($Database);

# verify that user is logged in, to guard against direct access of page and possible exploits
$User->check_user_session();

# load data from uploaded file
include 'import-load-data.php';
# check data and mark the entries to import/update
include 'import-devices-check.php';

?>

<!-- header -->
<div class="pHeader"><?php print _("Devices Addresses import"); ?></div>

<!-- content -->
<div class="pContent">

<?php

$msg = "";
$rows = "";

# import a device
foreach ($data as &$cdata) {
	if (($cdata['action'] == "add") || ($cdata['action'] == "edit")) {
	

		// # set update array

		$values = array(
            'id'	    =>$cdata['id'],
            'hostname'	    =>$cdata['hostname'],
            'ip_addr'	    =>$cdata['ip_addr'],
            'type'	    =>$cdata['type'],
            'description'	    =>$cdata['description'],
            'sections'	    =>$cdata['sections'],
#            'snmp_community'	    =>$cdata['snmp_community'],
#            'snmp_version'	    =>$cdata['snmp_version'],
#            'snmp_port'	    =>$cdata['snmp_port'],
#            'snmp_timeout'	    =>$cdata['snmp_timeout'],
#            'snmp_queries'	    =>$cdata['snmp_queries'],
#            'snmp_v3_sec_level'	    =>$cdata['snmp_v3_sec_level'],
#            'snmp_v3_auth_protocol'	    =>$cdata['snmp_v3_auth_protocol'],
#            'snmp_v3_auth_pass'	    =>$cdata['snmp_v3_auth_pass'],
#            'snmp_v3_priv_protocol'	    =>$cdata['snmp_v3_priv_protocol'],
#            'snmp_v3_priv_pass'	    =>$cdata['snmp_v3_priv_pass'],
#            'snmp_v3_ctx_name'	    =>$cdata['snmp_v3_ctx_name'],
#            'snmp_v3_ctx_engine_id'	    =>$cdata['snmp_v3_ctx_engine_id'],
            'rack'	    =>$cdata['rack'],
            'rack_start'	    =>$cdata['rack_start'],
            'rack_size'	    =>$cdata['rack_size'],
            'location'	    =>$cdata['location'],
            'editDate'	    =>$cdata['editDate']
        );


		# add custom fields
		if(sizeof($custom_fields) > 0) {
			foreach($custom_fields as $myField) {
				if(isset($cdata[$myField['name']])) { $values[$myField['name']] = $cdata[$myField['name']]; }
			}
		}

		# update
		$cdata['result'] = $Admin->object_modify("devices", $cdata['action'], "id", $values);

		if ($cdata['result']) {
			$trc = $colors[$cdata['action']];
			$msg = "Devices  ".$cdata['action']." successful.";
		} else {
			$trc = "danger";
			$msg = "Devices  ".$cdata['action']." failed.";
		}

		$rows.="<tr class='".$trc."'><td><i class='fa ".$icons[$action]."' rel='tooltip' data-placement='bottom' title='"._($msg)."'></i></td>";
		foreach ($expfields as $cfield) { $rows.= "<td>".$cdata[$cfield]."</td>"; }
		$rows.= "<td>"._($msg)."</td></tr>";

	}
}
print "<table class='table table-condensed table-hover' id='resultstable'><tbody>";
print "<tr class='active'>".$hrow."<th>Result</th></tr>";
print $rows;
print "</tbody></table><br>";
?>

</div>

<!-- footer -->
<div class="pFooter">
	<button class="btn btn-sm btn-default hidePopups"><?php print _('Close'); ?></button>
</div>
