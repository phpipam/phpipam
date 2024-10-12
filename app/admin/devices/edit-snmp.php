<?php

/**
 *	Edit device snmp
 ************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

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
$csrf = $User->Crypto->csrf_cookie ("create", "device_snmp");

# fetch custom fields
$custom = $Tools->fetch_custom_fields('devices');

# ID must be numeric
if(!is_numeric($POST->switchid))		     { $Result->show("danger", _("Invalid ID"), true, true); }

# fetch device details
$device = $Admin->fetch_object("devices", "id", $POST->switchid);
if ($device===false)                         { $Result->show("danger", _("Invalid ID"), true, true);  }

// set show
if ($device->snmp_version=="1" || $device->snmp_version=="2")   { $display=''; $display_v3 = 'display:none'; }
elseif ($device->snmp_version=="3")                             { $display=''; $display_v3 = ''; }
else                                                            { $display=''; $display_v3 = ''; }

// default values
if (is_blank($device->snmp_timeout))   { $device->snmp_timeout = 1000; }
?>

<script>
$(document).ready(function(){
     if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }
});
// form change
$('#switchSNMPManagementEdit').change(function() {
   if($('#switchSNMPManagementEdit select[name=snmp_version]').val() == "0") { $('tbody#details').hide(); }
   else                                                                      { $('tbody#details').show(); }
});
$('#switchSNMPManagementEdit').change(function() {
   if($('#switchSNMPManagementEdit select[name=snmp_version]').val() == "3") { $('tr.details3').show(); }
   else                                                                      { $('tr.details3').hide(); }
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
		<td><?php print _('Community/User'); ?></td>
		<td>
    		<input name="snmp_community" class="form-control" placeholder="SNMP <?php print _('Community'); ?>" value='<?php print $device->snmp_community; ?>'>
		</td>
	</tr>

	<tr class="details3" style="<?php print $display_v3; ?>">
    	<th colspan="2">
    	    <hr><?php print _("SNMP v3 details"); ?>
    	</th>
	</tr>

	<!-- v3 -->
	<tr class="details3" style="<?php print $display_v3; ?>">
		<td><?php print _('Security level'); ?></td>
		<td>
    		<select name="snmp_v3_sec_level" class="form-control input-w-auto" >
        		<option value="none"><?php print _("Not used"); ?></option>
        		<option value="noAuthNoPriv" <?php if($device->snmp_v3_sec_level=="noAuthNoPriv") print "selected"; ?>>noAuthNoPriv</option>
        		<option value="authNoPriv" <?php if($device->snmp_v3_sec_level=="authNoPriv") print "selected"; ?>>authNoPriv</option>
        		<option value="authPriv" <?php if($device->snmp_v3_sec_level=="authPriv") print "selected"; ?>>authPriv</option>
    		</select>
		</td>
	</tr>

	<!-- v3  -->
	<tr class="details3" style="<?php print $display_v3; ?>">
		<td><?php print _('Auth Protocol'); ?></td>
		<td>
    		<select name="snmp_v3_auth_protocol" class="form-control input-w-auto">
        		<option value="none"><?php print _("Not used"); ?></option>
        		<option value="MD5" <?php if($device->snmp_v3_auth_protocol=="MD5") print "selected"; ?>>MD5</option>
        		<option value="SHA" <?php if($device->snmp_v3_auth_protocol=="SHA") print "selected"; ?>>SHA</option>
    		</select>
		</td>
	</tr>

	<!-- v3 -->
	<tr class="details3" style="<?php print $display_v3; ?>">
		<td><?php print _('Password'); ?></td>
		<td>
    		<input type='text' name="snmp_v3_auth_pass" class="form-control" placeholder="SNMPv3 <?php print _('Password'); ?>" value='<?php print $Tools->strip_xss($device->snmp_v3_auth_pass); ?>'>
		</td>
	</tr>

	<!-- v3  -->
	<tr class="details3" style="<?php print $display_v3; ?>">
		<td><?php print _('Privacy Protocol'); ?></td>
		<td>
    		<select name="snmp_v3_priv_protocol" class="form-control input-w-auto">
        		<option value="none"><?php print _("Not used"); ?></option>
        		<option value="DES" <?php if($device->snmp_v3_priv_protocol=="DES") print "selected"; ?>>DES</option>
        		<option value="AES" <?php if($device->snmp_v3_priv_protocol=="AES") print "selected"; ?>>AES-128</option>
    		</select>
		</td>
	</tr>

	<!-- v3 -->
	<tr class="details3" style="<?php print $display_v3; ?>">
		<td><?php print _('Privacy passphrase'); ?></td>
		<td>
    		<input type='text' name="snmp_v3_priv_pass" class="form-control" placeholder="SNMP <?php print _('Privacy passphrase'); ?>" value='<?php print $Tools->strip_xss($device->snmp_v3_priv_pass); ?>'>
		</td>
	</tr>

	<!-- v3 -->
	<tr class="details3" style="<?php print $display_v3; ?>">
		<td><?php print _('Context name'); ?></td>
		<td>
    		<input type='text' name="snmp_v3_ctx_name" class="form-control" placeholder="SNMP <?php print _('Context name'); ?>" value='<?php print $Tools->strip_xss($device->snmp_v3_ctx_name); ?>'>
		</td>
	</tr>

	<!-- v3 -->
	<tr class="details3" style="<?php print $display_v3; ?>">
		<td><?php print _('Context engine ID'); ?></td>
		<td>
    		<input type='text' name="snmp_v3_ctx_engine_id" class="form-control" placeholder="SNMP <?php print _('Context engine ID'); ?>" value='<?php print $Tools->strip_xss($device->snmp_v3_ctx_engine_id); ?>'>
		</td>
	</tr>

	<tr class="details3" style="<?php print $display_v3; ?>">
    	<td colspan="2">
    	    <hr>
    	</td>
	</tr>

	<!-- port -->
	<tr>
		<td><?php print _('Port'); ?></td>
		<td>
    		<input type="number" name="snmp_port" class="form-control" placeholder="161" value='<?php print $Tools->strip_xss($device->snmp_port); ?>'>
		</td>
	</tr>

	<!-- timeout -->
	<tr>
		<td><?php print _('Timeout'); ?> [ms]</td>
		<td>
    		<input type="number" name="snmp_timeout" class="form-control" placeholder="500000" value='<?php print $Tools->strip_xss($device->snmp_timeout); ?>'>
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
		$queries = pf_explode(";", $device->snmp_queries);
		$queries = is_array($queries) ? $queries : array();
        // loop
		foreach($Snmp->snmp_queries as $k=>$m) {
			if(in_array($k, $queries)) 	{ print '<div class="checkbox" style="margin:0px;"><input type="checkbox" name="query-'. $k .'" value="on" checked> '. $k .'</div>'. "\n"; }
			else 						{ print '<div class="checkbox" style="margin:0px;"><input type="checkbox" name="query-'. $k .'" value="on">'. $k .'</span></div>'. "\n"; }
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
