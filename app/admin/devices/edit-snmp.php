<?php

/**
 *	Edit device snmp
 ************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Snmp       = new phpipamSNMP ();
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->csrf_cookie ("create", "device_snmp");

# fetch custom fields
$custom = $Tools->fetch_custom_fields('devices');

# ID must be numeric
if(!is_numeric($_POST['switchId']))		     { $Result->show("danger", _("Invalid ID"), true, true); }

# fetch device details
$device = $Admin->fetch_object("devices", "id", $_POST['switchId']);
if ($device===false)                         { $Result->show("danger", _("Invalid ID"), true, true);  }

// set show
if ($device->snmp_version=="1" || $device->snmp_version=="2")   { $display=''; }
else                                                            { $display='display:none'; }

// default values
if (strlen($device->snmp_timeout)==0)   { $device->snmp_timeout = 1000000; }
?>

<script type="text/javascript">
$(document).ready(function(){
     if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }
});
// form change
$('#switchSNMPManagementEdit').change(function() {
   if($('#switchSNMPManagementEdit select[name=snmp_version]').val() == "0") { $('tbody#details').hide(); }
   else                                                                      { $('tbody#details').show(); }
});
</script>


<!-- header -->
<div class="pHeader"><?php print _("Edit"); ?> <?php print _('device'); ?> SNMP</div>


<!-- content -->
<div class="pContent">

	<form id="switchSNMPManagementEdit">
	<table class="table table-noborder table-condensed">

    <tbody id="version">
    <!-- device -->
    <tr>
        <th colspan="2"><?php print $device->hostname.": ".$device->ip_addr; ?></th>
    </tr>
    <tr>
        <td colspan="2"><hr></td>
    </tr>

	<!-- version -->
	<tr>
		<td><?php print _('Version'); ?></td>
		<td>
    		<select class="form-control" name="snmp_version">
        		<option value="0"><?php print _("Not used"); ?></option>
        		<option value="1" <?php if($device->snmp_version=="1") print "selected"; ?>>SNMP v1</option>
        		<option value="2" <?php if($device->snmp_version=="2") print "selected"; ?>>SNMP v2c</option>
        		<option value="3" <?php if($device->snmp_version=="3") print "selected"; ?>>SNMP v3</option>
    		</select>
    		<input type="hidden" name="device_id" value="<?php print $device->id; ?>">
    		<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
		</td>
	</tr>
    </tbody>


    <tbody id="details" style="<?php print $display; ?>">
	<!-- version -->
	<tr>
		<td><?php print _('Community'); ?></td>
		<td>
    		<input name="snmp_community" class="form-control" placeholder="SNMP <?php print _('Community'); ?>" value='<?php print $device->snmp_community; ?>'>
		</td>
	</tr>

	<!-- port -->
	<tr>
		<td><?php print _('Port'); ?></td>
		<td>
    		<input type="number" name="snmp_port" class="form-control" placeholder="161" value='<?php print $device->snmp_port; ?>'>
		</td>
	</tr>

	<!-- timeout -->
	<tr>
		<td><?php print _('Timeout'); ?> [ms]</td>
		<td>
    		<input type="number" name="snmp_timeout" class="form-control" placeholder="500000" value='<?php print $device->snmp_timeout; ?>'>
		</td>
	</tr>

	<!-- associated queries -->
	<tr>
    	<td colspan="2">
    	    <hr>
    	</td>
	</tr>
  	<tr>
		<td><?php print _('Queries'); ?></td>
		<td style="text-align:top">
		<?php
		# select queries
		$queries = explode(";", $device->snmp_queries);
		$queries = is_array($queries) ? $queries : array();
        // loop
		foreach($Snmp->snmp_queries as $k=>$m) {
			if(in_array($k, $queries)) 	{ print '<div class="checkbox" style="margin:0px;"><input type="checkbox" name="query-'. $k .'" value="on" checked> '. $k .'</div>'. "\n"; }
			else 							{ print '<div class="checkbox" style="margin:0px;"><input type="checkbox" name="query-'. $k .'" value="on">'. $k .'</span></div>'. "\n"; }
		}
		?>
		</td>
	</tr>

	<!-- test -->
	<tr>
    	<td colspan="2">
    	    <hr>
    	</td>
	</tr>
    <tr>
        <td></td>
        <td class="text-right">
            <a href="#" id="test-snmp" class="btn btn-sm btn-info"><?php print _("Test"); ?></a>
        </td>
    </tr>


    </tbody>

	</table>
	</form>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default btn-success" id="editSwitchSNMPsubmit"><i class="fa fa-check"></i> <?php print _("Edit"); ?></button>
	</div>

	<!-- result -->
	<div class="switchSNMPManagementEditResult"></div>
</div>