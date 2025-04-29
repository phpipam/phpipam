<?php

/**
 * Script to print edit / delete / new IP address
 *
 * Fetches info from database
 *************************************************/


# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects
$Database 	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);
$Subnets	= new Subnets ($Database);
$Tools	    = new Tools ($Database);
$Addresses	= new Addresses ($Database);

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $POST->action=="add"||$POST->action=="all-add" ? $User->Crypto->csrf_cookie ("create", "address_add") : $User->Crypto->csrf_cookie ("create", "address_".$POST->id);

# validate action
$Tools->validate_action(false);

# validate post
is_numeric($POST->subnetId) ?:						$Result->show("danger", _("Invalid subnet ID"), true, true);
is_numeric($POST->id) || is_blank($POST->id) ?:	$Result->show("danger", _("Invalid ID"), true, true);

# get posted values
$subnetId= escape_input($POST->subnetId);
$action  = escape_input($POST->action);
$id      = escape_input($POST->id);

# fetch subnet
$subnet = (array) $Subnets->fetch_subnet(null, $subnetId);
if (strpos($_SERVER['HTTP_REFERER'], "verify-database")==0)
sizeof($subnet)>0 ?:			$Result->show("danger", _("Invalid subnet"), true, true);

# set and check permissions
$subnet_permission = $Subnets->check_permission($User->user, $subnet['id']);
$subnet_permission > 1 ?:		$Result->show("danger", _('Cannot edit IP address details').'! <br>'._('You do not have write access for this network'), true, true);

// set selected address and required addresses fields array
$selected_ip_fields = $Tools->explode_filtered(";", $User->settings->IPfilter);
$required_ip_fields = $Tools->explode_filtered(";", $User->settings->IPrequired);																			//format to array

# get all custom fields
$custom_fields = $Tools->fetch_custom_fields ('ipaddresses');

# if subnet is full we cannot any more ip addresses
if (($action=="add" || $action=="all-add") && ($subnet['isFull']==1 && $subnet['isFolder']!=1))   $Result->show("warning", _("Cannot add address as subnet is market as used"), true, true);


# if action is not add then fetch current details, otherwise fetch first available IP address
if ($action == "all-add") {
	$address['ip_addr'] = $Subnets->transform_address($id, "dotted");
	$address['id'] = 0;
	$address['PTR'] = null;
	$address['location'] = null;
	$address['customer_id'] = null;
}
elseif ($action == "add") {
	# get first available IP address
	$first = $Addresses->get_first_available_address ($subnetId);
	$first = !$first ? "" : $Subnets->transform_address($first, "dotted");

	$address['ip_addr'] = $first;
	$address['id'] = 0;
	$address['PTR'] = null;
	$address['location'] = null;
	$address['customer_id'] = null;
}
else {
	$address = (array) $Addresses->fetch_address(null, $id);
	// save old mac for multicast check
	$address['mac_old'] = @$address['mac'];
}

# Set action and button text
if ($action == "add") 			{ $btnName = _("add");		$act = "add"; }
else if ($action == "all-add")	{ $btnName = _("add");  	$act = "add"; }
else if ($action == "edit") 	{ $btnName = _("edit"); 	$act = "edit"; }
else if ($action == "all-edit")	{ $btnName = _("edit"); 	$act = "edit"; }
else if ($action == "delete")	{ $btnName = _("delete"); 	$act = "delete"; }
else							{ $btnName = ""; }

# set delete flag
if($act=="delete")	{ $delete = "readonly='readonly'"; }
else				{ $delete = ""; }

# multicast MAC address check
if ($act=="add" && $User->settings->enableMulticast==1) {
    if ($Subnets->is_multicast ($address['ip_addr'])) {
        // propose mac
        $address['mac'] = $Subnets->create_multicast_mac ($address['ip_addr']);
        // if add validate
        if ($act=="add") {
            $mcast_err_check = $Subnets->validate_multicast_mac ($address['mac'], $subnet['sectionId'], $subnet['vlanId'], MCUNIQUE, 0);
            // check if proposed already exists
            if ($mcast_err_check !== true) {
                $mcast_err = $mcast_err_check;
            }
        }
    }
}

# all locations
if($User->settings->enableLocations=="1")
$locations = $Tools->fetch_all_objects ("locations", "name");

?>

<script>
$(document).ready(function() {
/* bootstrap switch */
var switch_options = {
	onText: "Yes",
	offText: "No",
    onColor: 'default',
    offColor: 'default',
    size: "mini"
};
var switch_options_danger = {
	onText: "Yes",
	offText: "No",
    onColor: 'danger',
    offColor: 'default',
    size: "mini"
};

$(".input-switch").bootstrapSwitch(switch_options);
$(".input-switch-danger").bootstrapSwitch(switch_options_danger);


<?php if (($act=="add"||$act=="edit") && $User->settings->enableMulticast==1) { ?>
$("input[name=ip_addr]").focusout(function() {
    // update mac
    $.post('app/tools/multicast-networks/create_mac.php', {ip:$(this).val()}, function(data) {
        if (data!="False") {
            $("input[name=mac]").val(data);
            validate_mac ( $("input[name=ip_addr]").val(), data, $("input[name=section]").val(), $("input[name=subnetvlan]").val(), $("input[name=addressId]").val());
        }
    });
});
$("input[name=mac]").focusout(function() {
    //validate
    validate_mac ($("input[name=ip_addr]").val(), $(this).val(), $("input[name=section]").val(), $("input[name=subnetvlan]").val(), $("input[name=addressId]").val());
});
//validatemac
function validate_mac (ip, mac, sectionId, vlanId, id) {
    $.post('app/tools/multicast-networks/validate_mac.php', {ip:ip, mac:mac, sectionId:sectionId, vlanId:vlanId, id:id}, function(data) {
        if (data==="True") {
            $("input[name=mac]").parent().removeClass("has-error");
            $('#helpBlock2').remove();
        }
        else {
            $("input[name=mac]").parent().addClass("has-error");
            if($('#helpBlock2').length)    { $("#helpBlock2").html(data); }
            else                           { $("input[name=mac]").parent().append("<span id='helpBlock2' class='help-block'>"+data+"</span>"); }
        }
    });
}
<?php } ?>

});
</script>

<!-- header -->
<div class="pHeader"><?php print ucwords($btnName); ?> <?php print _('IP address'); ?></div>

<!-- content -->
<div class="pContent editIPAddress">

	<!-- IP address modify form -->
	<form class="editipaddress" role="form" name="editipaddress">

	<?php
	if($config['split_ip_custom_fields']===true) {
		print "<div class='row'>";
		print "<div class='col-xs-12 col-md-6' style='border-right:1px solid #eee'>";
		print "<h4>"._("Standard fields")."</h4><hr>";
	}
	?>

	<!-- edit IP address table -->
	<table id="editipaddress" class="table table-noborder table-condensed">

	<!-- IP address -->
	<tr>
		<td><?php print _('IP address'); ?> *</td>
		<td>
		<div class="input-group">
			<input type="text" name="ip_addr" class="ip_addr form-control input-sm" value="<?php print $Subnets->transform_address($address['ip_addr'], "dotted");; if(is_numeric($POST->stopIP)>0) print "-".$Subnets->transform_address($POST->stopIP,"dotted"); ?>" placeholder="<?php print _('IP address'); ?>">
    		<span class="input-group-addon" style="border-left:none;">
    			<a class="ping_ipaddress ping_ipaddress_new" data-subnetid="<?php print $subnetId; ?>" data-id="" href="#" rel="tooltip" data-container="body" title="" data-original-title="<?php print _('Check availability'); ?>">
 					<i class="fa fa-gray fa-cogs"></i>
    			</a>
 			</span>
			<span class="input-group-addon">
    			<i class="fa fa-gray fa-info" rel="tooltip" data-html='true' data-placement="right" title="<?php print _('You can add,edit or delete multiple IP addresses<br>by specifying IP range (e.g. 10.10.0.0-10.10.0.25)'); ?>"></i>
    		</span>
			</div>

   			<input type="hidden" name="action" 	 	value="<?php print $act; 	?>">
			<input type="hidden" name="id" 		 	value="<?php print $id; 		?>">
			<input type="hidden" name="subnet"   	value="<?php print $subnet['ip']."/".$subnet['mask']; 	?>">
			<input type="hidden" name="subnetId" 	value="<?php print $subnetId; 	?>">
			<input type="hidden" name="section" 	value="<?php print $subnet['sectionId']; ?>">
			<input type="hidden" name="subnetvlan" 	value="<?php print $subnet['vlanId']; ?>">
			<input type="hidden" name="ip_addr_old" value="<?php print $address['ip_addr']; ?>">
			<input type="hidden" name="mac_old"     value="<?php print @$address['mac_old']; ?>">
			<input type="hidden" name="PTR" 		value="<?php print $address['PTR']; ?>">
			<input type="hidden" name="addressId" 	value="<?php print $address['id']; ?>">
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
			<?php
			if (strpos($_SERVER['HTTP_REFERER'], "verify-database")!=0) { print "<input type='hidden' name='verifydatabase' value='yes'>"; }
			?>
    	</td>
	</tr>


	<!-- DNS name -->
	<?php
	if(!isset($address['hostname'])) {$address['hostname'] = "";}
		// set star if field is required
		$required = in_array("hostname", $required_ip_fields) ? " *" : "";

		print '<tr>'. "\n";
		print '	<td>'._('Hostname').$required.'</td>'. "\n";
		print '	<td>'. "\n";
		print '	<div class="input-group">';
		print ' <input type="text" name="hostname" class="ip_addr form-control input-sm" placeholder="'._('Hostname').'" value="'. $address['hostname']. '" '.$delete.'>'. "\n";
		print '	 <span class="input-group-addon">'."\n";
		print "		<i class='fa fa-gray fa-repeat' id='refreshHostname' data-subnetId='$subnetId' rel='tooltip' data-placement='right' title='"._('Click to check for hostname')."'></i></span>";
		print "	</span>";
		print "	</div>";
		print '	</td>'. "\n";
		print '</tr>'. "\n";
	?>

	<!-- description -->
	<tr>
		<td>
			<?php
			// set star if field is required
			$required = in_array("description", $required_ip_fields) ? " *" : "";
			print _('Description').$required;
			?>
		</td>
		<td>
			<input type="text" name="description" class="ip_addr form-control input-sm" value="<?php if(isset($address['description'])) {print $address['description'];} ?>" size="30"
			<?php if ( $act == "delete" ) { print " readonly";} ?>
			placeholder="<?php print _('Description'); ?>">
		</td>
	</tr>

	<!-- MAC address -->
	<?php
	if(in_array('mac', $selected_ip_fields)) {
		if(!isset($address['mac'])) {$address['mac'] = "";}

		// set star if field is required
		$required = in_array("mac", $required_ip_fields) ? " *" : "";

		print '<tr class="text-top">'. "\n";
		print '	<td style="padding-top:7px;">'._('MAC address').$required.'</td>'. "\n";
		print '	<td>'. "\n";

		# multicast selection
		if ($User->settings->enableMulticast==1) {
    		# initial mcast validation
    		if (isset($mcast_err))  { $mcast_class='has-error'; $mcast_help_block = "<span id='helpBlock2' class='help-block'>$mcast_err</span>"; }
    		else                    { $mcast_class=""; $mcast_help_block = ""; }

     		if ($User->is_admin (false)) {
        		print ' <div class="form-group '.$mcast_class.'" style="margin-bottom:0px;">';
        		print ' <input type="text" name="mac" class="ip_addr form-control input-sm" placeholder="'._('MAC address').'" value="'. $address['mac']. '" size="30" '.$delete.'>'.$mcast_help_block;
        		print ' </div>';
    		}
    		else {
         		print ' <div class="form-group '.$mcast_class.'" style="margin-bottom:0px;">';
        		print ' <input type="text" name="mac" class="ip_addr form-control input-sm" placeholder="'._('MAC address').'" value="'. $address['mac']. '" size="30" '.$delete.' disabled="disabled">'.$mcast_help_block;
        		print ' <input type="hidden" name="mac" value="'. $address['mac']. '">';
        		print ' </div>';
    		}
		}
		else {
        		print ' <div class="form-group" style="margin-bottom:0px;">';
        		print ' <input type="text" name="mac" class="ip_addr form-control input-sm" placeholder="'._('MAC address').'" value="'. $address['mac']. '" size="30" '.$delete.'>'. "\n";
        		print ' </div>';
		}
        print '	</td>'. "\n";
    	print '</tr>'. "\n";
	}

	// tag
	# fetch all states
	$ip_types = (array) $Addresses->addresses_types_fetch();
	# default type
	if(!is_numeric(@$address['state'])) 		{ $address['state'] = 2; } // online

	print '<tr>'. "\n";
	print '	<td>'._('Tag').'</td>'. "\n";
	print '	<td>'. "\n";
	print '		<select name="state" '.$delete.' class="ip_addr form-control input-sm input-w-auto">'. "\n";
	# printout
	foreach($ip_types as $k=>$type) {
		if($address['state']==$k)				{ print "<option value='$k' selected>"._($type['type'])."</option>"; }
		else									{ print "<option value='$k'>"._($type['type'])."</option>"; }
	}
	print '		</select>'. "\n";
	print '	</td>'. "\n";
	print '</tr>'. "\n";



	?>
	<tr>
		<td><?php print _("Is gateway"); ?></td>
		<td>
			<input type="checkbox" name="is_gateway" class="input-switch" value="1" <?php if(@$address['is_gateway']==1) print "checked"; ?> <?php print $delete; ?>>
		</td>
	</tr>

	<!-- exclude Ping -->
	<?php
	if($subnet['pingSubnet']==1) {
		//we can exclude individual IP addresses from ping
		if(@$address['excludePing'] == "1")	{ $checked = "checked='checked'"; }
		else								{ $checked = ""; }

		print '<tr>';
	 	print '<td>'._("Ping exclude").'</td>';
	 	print '<td>';
		print ' <input type="checkbox" class="ip_addr input-switch" name="excludePing" value="1" '.$checked.' '.$delete.'> <span class="text-muted">'. _('Exclude from ping status checks')."</span>";
	 	print '</td>';
	 	print '</tr>';
	}
	?>
	<?php
	// ignore PTR
	if ($User->settings->enablePowerDNS==1) {
		//we can exclude individual IP addresses from PTR creation
		if(@$address['PTRignore'] == "1")	{ $checked = "checked='checked'"; }
		else								{ $checked = ""; }

		print '<tr>';
	 	print '<td>'._("PTR exclude").'</td>';
	 	print '<td>';
		print ' 	<input type="checkbox" class="ip_addr input-switch" name="PTRignore" value="1" '.$checked.' '.$delete.'> <span class="text-muted">'. _('Dont create PTR records').'</span>';
	 	print '</td>';
	 	print '</tr>';

		//remove all associated queries if delete
		if ($POST->action=="delete" || $POST->action=="all-edit") {
    		// check
    		$PowerDNS = new PowerDNS ($Database);
    		$records  = $PowerDNS->search_records ("name", $address['hostname'], 'name', true);
    		$records2 = $PowerDNS->search_records ("content", $address['ip'], 'content', true);

    		if (is_array($records) || is_array($records2)) {
        		// form
        		print '<tr>';
        	 	print '<td>'._("Remove DNS records").'</td>';
        	 	print '<td>';
        		print ' 	<input type="checkbox" class="ip_addr input-switch-danger alert-danger" data-on-color="danger" name="remove_all_dns_records" value="1" checked> <span class="text-muted">'. _('Remove all associated DNS records:').'</span>';
        	 	print '</td>';
        	 	print '</tr>';
        	 	// records
        		print '<tr>';
        	 	print '<td></td>';
        	 	print '<td>';
        	 	print "<hr>";

        	 	// hostname records
        	 	if (is_array($records)) {
            	 	print " <div style='margin-left:60px'>";
            	 	$dns_records[] = $address['hostname'];
            	 	$dns_records[] = "<ul class='submenu-dns'>";
            	 	foreach ($records as $r) {
    					if($r->type!="SOA" && $r->type!="NS")
                        $dns_records[]   = "<li><i class='icon-gray fa fa-gray fa-angle-right'></i> <span class='badge badge1 badge2'>$r->type</span> $r->content </li>";

            	 	}
            	 	$dns_records[] = "</ul>";
            	 	print implode("\n", $dns_records);
            	 	print " </div>";
                     unset($dns_records);
        	 	}

        	 	// IP records
        	 	if (is_array($records2)) {
            	 	print " <div style='margin-left:60px'>";
            	 	$dns_records[] = $address['ip'];
            	 	$dns_records[] = "<ul class='submenu-dns'>";
            	 	foreach ($records2 as $r) {
    					if($r->type!="SOA" && $r->type!="NS")
                        $dns_records[]   = "<li><i class='icon-gray fa fa-gray fa-angle-right'></i> <span class='badge badge1 badge2'>$r->type</span> $r->name </li>";

            	 	}
                    //search also for CNAME records
                    $dns_cname_unique = array();
                    $dns_records_cname = $PowerDNS->seach_aliases ($r->name);
                    if(is_array($dns_records_cname)) {
                        foreach ($dns_records_cname as $cn) {
                            if (!in_array($cn->name, $dns_cname_unique)) {
                                $cname[] = "<li><i class='icon-gray fa fa-gray fa-angle-right'></i> <span class='badge badge1 badge2 editRecord' data-action='edit' data-id='$cn->id' data-domain_id='$cn->domain_id'>$cn->type</span> $cn->name </li>";
                                $dns_cname_unique[] = $cn->name;
                            }
                        }
                    }
                    // merge cnames
                    if (isset($cname)) {
                        foreach ($cname as $cna) {
                            $dns_records[] = $cna;
                        }
                    }

            	 	$dns_records[] = "</ul>";
            	 	print implode("\n", $dns_records);
            	 	print " </div>";
        	 	}

        	 	print '</td>';
        	 	print '</tr>';
    	 	}
	 	}
	}



	// divider
	print "<tr>";
    print " <td colspan='2'><hr></td>";
	print "</tr>";

	// customer
	if ($User->settings->enableCustomers=="1" && $User->get_module_permissions ("customers")>=User::ACCESS_R) {

		print '<tr>'. "\n";
		print '	<td>'._('Customer').'</td>'. "\n";
		print '	<td>'. "\n";

		print '<select name="customer_id" class="ip_addr form-control input-sm input-xs input-w-auto" '.$delete.'>'. "\n";
		print '<option disabled>'._('Select customer').':</option>'. "\n";
		print '<option value="0" selected>'._('None').'</option>'. "\n";

		// fetch devices
		$customers = $Tools->fetch_all_objects("customers", "title");
        if ($customers!==false) {
    		foreach($customers as $customer) {
    			//if same
    			if($customer->id == $address['customer_id']) 	{ print '<option value="'. $customer->id .'" selected>'. $customer->title .'</option>'. "\n"; }
    			else 											{ print '<option value="'. $customer->id .'">'. $customer->title .'</option>'. "\n";			 }
    		}
		}
		print '</select>'. "\n";
		print '	</td>'. "\n";
		print '</tr>'. "\n";
	}

	// owner
	if(in_array('owner', $selected_ip_fields)) {

		if(!isset($address['owner'])) {$address['owner'] = "";}

		// set star if field is required
		$required = in_array("owner", $required_ip_fields) ? " *" : "";

		print '<tr>'. "\n";
		print '	<td>'._('Owner').$required.'</td>'. "\n";
		print '	<td>'. "\n";
		print ' <input type="text" name="owner" class="ip_addr form-control input-sm" id="owner" placeholder="'._('IP address owner').'" value="'. $address['owner']. '" size="30" '.$delete.'>'. "\n";
		print '	</td>'. "\n";
		print '</tr>'. "\n";
	}

	// switch / port
	if(!isset($address['switch']))  {$address['switch'] = "";}
	if(!isset($address['port'])) 	{$address['port'] = "";}

	# both are active
	if(in_array('switch', $selected_ip_fields) && $User->get_module_permissions ("devices")>=User::ACCESS_R) {

		// set star if field is required
		$required = in_array("switch", $required_ip_fields) ? " *" : "";

		print '<tr>'. "\n";
		print '	<td>'._('Device').$required.'</td>'. "\n";
		print '	<td>'. "\n";

		print '<select name="switch" class="ip_addr form-control input-sm input-w-auto" '.$delete.'>'. "\n";
		print '<option disabled>'._('Select device').':</option>'. "\n";
		if($required=="")
		print '<option value="0" selected>'._('None').'</option>'. "\n";

		// fetch devices
		$devices = $Tools->fetch_all_objects("devices", "hostname");
        if ($devices!==false) {
    		foreach($devices as $device) {
    			$device = (array) $device;
    			//check if permitted in this section!
    			$sections=pf_explode(";", $device['sections']);
    			if(in_array($subnet['sectionId'], $sections)) {
    			//if same
    			if($device['id'] == $address['switch']) { print '<option value="'. $device['id'] .'" selected>'. $device['hostname'] .'</option>'. "\n"; }
    			else 									{ print '<option value="'. $device['id'] .'">'. $device['hostname'] .'</option>'. "\n";			 }
    			}
    		}
		}
		print '</select>'. "\n";
		print '	</td>'. "\n";
		print '</tr>'. "\n";
	}



	# Port
	if(in_array('port', $selected_ip_fields)) {

		if(!isset($address['port'])) {$address['port'] = "";}

		// set star if field is required
		$required = in_array("port", $required_ip_fields) ? " *" : "";

		print '<tr>'. "\n";
		print '	<td>'._('Port').$required.'</td>'. "\n";
		print '	<td>'. "\n";
		print ' <input type="text" name="port"  class="ip_addr form-control input-sm input-w-150"  id="port"   placeholder="'._('Port').'"   value="'. $address['port']. '" size="30" '.$delete.'>'. "\n";
		print '	</td>'. "\n";
		print '</tr>'. "\n";
	}


    // location
    if($User->settings->enableLocations=="1" && $User->get_module_permissions ("locations")>=User::ACCESS_R) { ?>
	<tr>
		<td>
			<?php
			// set star if field is required
			$required = in_array("location", $required_ip_fields) ? " *" : "";
			print _('Location').$required;
			?>
			</td>
		<td>
			<select name="location" class="form-control input-sm input-w-auto">
				<?php if($required=="") { ?>
    			<option value="0"><?php print _("None"); ?></option>
    			<?php } ?>
    			<?php
                if(is_array($locations)) {
        			foreach($locations as $l) {
        				if($address['location'] == $l->id)	{ print "<option value='$l->id' selected='selected'>$l->name</option>"; }
        				else					            { print "<option value='$l->id'>$l->name</option>"; }
        			}
    			}
    			?>
			</select>
		</td>
	</tr>
	<?php } ?>

	<!-- note -->
	<?php
	if(in_array('note', $selected_ip_fields)) {

		if(!isset($address['note'])) {$address['note'] = "";}

		// set star if field is required
		$required = in_array("note", $required_ip_fields) ? " *" : "";

		print '<tr>'. "\n";
		print '	<td>'._('Note').$required.'</td>'. "\n";
		print '	<td class="note">'. "\n";
		print ' <textarea name="note" class="ip_addr form-control input-sm" cols="23" rows="2" placeholder="'._('Additional notes about IP address').'" '.$delete.'>'. $address['note'] . '</textarea>'. "\n";
		print '	</td>'. "\n";
		print '</tr>'. "\n";
	}
	?>

	<tr>
		<td colspan="2"><hr></td>
	</tr>
	<!-- Custom fields -->
	<?php
	if(sizeof($custom_fields) > 0) {

		if($config['split_ip_custom_fields']===true) { ?>

			<style type='text/css'>
			.popup_w500 {
				width: 900px;
				margin-left: -450px;
			}
			</style>

				</table>
			</div>
			<div class='col-xs-12 col-md-6'>
				<h4><?php print _("Custom fields"); ?></h4><hr>
				<table id='editipaddress' class='table table-noborder table-condensed'>
		<?php
		}

		# count datepickers
		$timepicker_index = 0;
		foreach($custom_fields as $field) {
			$custom_input = $Tools->create_custom_field_input ($field, $address, $timepicker_index);
			$timepicker_index = $custom_input['timepicker_index'];

			print '<tr>'. "\n";
			print " <td>".ucwords($Tools->print_custom_field_name ($field['name']))." ".$custom_input['required']."</td>";
			print " <td>".$custom_input['field']."</td>";
			print '</tr>'. "\n";
		}
	}
	?>

    <?php if ($action!=="delete") {  ?>
    <tr>
        <td colspan="2"><hr></td>
    </tr>

    <tr>
        <td><?php print _('Unique'); ?></td>
    <td>
        <div class='checkbox info2'>
        <input type="checkbox" name="unique" value="1" <?php print $delete; ?>><?php print _('Unique hostname'); ?>
        </div>
        </td>
    </tr>
    <?php } ?>

</table>	<!-- end edit ip address table -->
<?php if($config['split_ip_custom_fields']===true) {
	print "</div>";
	print "</div>";
}?>
</form>		<!-- end IP address edit form -->




</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<?php
		# add delete if it came from visual edit!
		if($action == 'all-edit') {
		print "<button class='btn btn-sm btn-default btn-danger' id='editIPAddressSubmit' data-action='all-delete'><i class='fa fa-trash-o'></i> "._('Delete IP')."</button>";
		}
		?>
		<button class="btn btn-sm btn-default <?php if($action=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editIPAddressSubmit" data-action='<?php print $action; ?>'><i class="fa <?php if($act=="add") { print "fa-plus"; } else if ($act=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords($btnName); ?> IP</button>
	</div>

	<!-- holder for result -->
	<div class="addnew_check"></div>
</div>
