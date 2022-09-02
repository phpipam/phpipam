<?php

/*
 * Scan subnet for new hosts
 ***************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Subnets	= new Subnets ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create-if-not-exists", "scan");

# ID must be numeric
if(!is_numeric($_POST['subnetId']))										{ $Result->show("danger", _("Invalid ID"), true, true); }

# verify that user has write permissionss for subnet
if($Subnets->check_permission ($User->user, $_POST['subnetId']) != 3) 	{ $Result->show("danger", _('You do not have permissions to modify hosts in this subnet')."!", true, true); }

# Check if scanning has been disabled
if($User->settings->scanPingType=="none") { $Result->show("danger", _('Scanning disabled').' (scanPingType=None)', true, true); }

# fetch subnet details
$subnet = $Subnets->fetch_subnet (null, $_POST['subnetId']);
$subnet!==false ? : $Result->show("danger", _("Invalid ID"), true, true);

# IPv6 scanning is not supported
if ( $Subnets->identify_address($subnet->subnet) == "IPv6") 			{ $Result->show("danger", _('IPv6 scanning is not supported').'!', true, true); }

# fix description
$subnet->description = strlen($subnet->description)>0 ? "(".$subnet->description.")" : "";
?>

<!-- header -->
<div class="pHeader"><?php print _('Scan subnet'); ?></div>


<!-- content -->
<div class="pContent">
	<table class="table table-noborder table-condensed table-scan">
    <!-- subnet -->
    <tr>
        <td class="middle"><?php print _('Subnet'); ?></td>
        <td><?php print $Subnets->transform_to_dotted($subnet->subnet)."/$subnet->mask $subnet->description"; ?></td>
    </tr>
    <!-- Scan type -->
    <tr>
    	<td><?php print _('Select Scan type'); ?></td>
    	<td>
    		<select name="type" id="type" class="form-control input-sm input-w-auto">
    			<!-- Discovery scans -->
	    		<optgroup label="<?php print _('Discovery scans');?>">
		    		<option value="scan-icmp"   <?php if(@$_COOKIE['scantype']=="scan-icmp") print "selected"; ?>><?php print _('Discovery scans');?>: Ping <?php print _('scan');?></option>
		    		<option value="scan-telnet" <?php if(@$_COOKIE['scantype']=="scan-telnet") print "selected"; ?>><?php print _('Discovery scans');?>: Telnet <?php print _('scan');?></option>
		    		<option value="snmp-route-all"    <?php if(@$_COOKIE['scantype']=="snmp-route-all") print "selected"; ?>><?php print _('Discovery scans');?>: SNMP nested subnets <?php print _('scan');?></option>
		    		<option value="scan-snmp-arp"     <?php if(@$_COOKIE['scantype']=="scan-snmp-arp") print "selected"; ?>><?php print _('Discovery scans');?>: SNMP ARP <?php print _('scan');?></option>
		    		<option value="snmp-mac"    <?php if(@$_COOKIE['scantype']=="snmp-mac") print "selected"; ?>><?php print _('Discovery scans');?>: SNMP MAC address <?php print _('scan');?></option>
	    		</optgroup>
    			<!-- Status update scans -->
	    		<optgroup label="<?php print _('Status update scans');?>">
		    		<option value="update-icmp"     <?php if(@$_COOKIE['scantype']=="update-icmp") print "selected"; ?>><?php print _('Status update scans');?>: Ping <?php print _('scan');?></option>
		    		<option value="update-snmp-arp" <?php if(@$_COOKIE['scantype']=="update-snmp-arp") print "selected"; ?>><?php print _('Status update scans');?>: SNMP ARP <?php print _('scan');?></option>
	    		</optgroup>

			</select>
    	</td>
    </tr>
    <!-- telnet ports -->
    <tbody id="telnetPorts" style="border-top:0px;display:none;">
    <tr>
    	<td><?php print _('Ports'); ?></td>
    	<td>
	    	<input type="text" name="telnetports" class="form-control input-sm input-w-200" placeholder="<?php print _("Separate multiple ports with ;"); ?>">
    	</td>
    </tr>
    </tbody>

    <tbody style="border:0px;">
    <tr>
    	<td><?php print _('Debug');?></td>
    	<td>
    		<input type="checkbox" name="debug">
    	</td>
    </tr>
    </tbody>

    </table>

    <!-- warning -->
    <div class="alert alert-warning alert-block" id="alert-scan">
    &middot; <?php print _('Discovery scans discover new hosts');?><br>
    &middot; <?php print _('Status update scans update alive status for whole subnet');?><br>
    </div>

    <!-- result -->
	<div id="subnetScanResult"></div>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default btn-success" id="subnetScanSubmit" data-subnetId='<?php print $_POST['subnetId']; ?>' data-csrf-cookie='<?php print $csrf; ?>'><i class="fa fa-gears"></i> <?php print _('Scan subnet'); ?></button>
	</div>

	<div class="subnetTruncateResult"></div>
</div>