<?php

/**
 * Script to confirm / reject IP address request
 ***********************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Addresses	= new Addresses ($Database);
$Subnets	= new Subnets ($Database);
$Tools		= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->create_csrf_cookie ();

# fetch request
$request = $Admin->fetch_object("requests", "id", $_POST['requestId']);

//fail
if($request===false) { $Result->show("danger", _("Request does not exist"), true, true); }
else				 { $request = (array) $request; }

# verify permissions
if($Subnets->check_permission($User->user, $request['subnetId']) != 3)	{ $Result->show("danger", _('You do not have permissions to process this request')."!", true, true); }


# set IP address
# if provided (requested from logged in user) check if already in use, if it is warn and set next free
# else get next free
if(strlen($request['ip_addr'])>0) {
	// check if it exists
	if ( $Addresses->address_exists ($request['ip_addr'], $request['subnetId'])) {
		$errmsg = "Requested IP address ($request[ip_addr]) already used. First available address automatically provided.";
		$errmsg_class = "warning";
		//fetch first free
		$ip_address = $Addresses->transform_to_dotted($Addresses->get_first_available_address ($request['subnetId'], $Subnets));
	}
	else {
		$ip_address = $request['ip_addr'];
	}

} else {
	// fetch first free
	$ip_address = $Addresses->transform_to_dotted($Addresses->get_first_available_address ($request['subnetId'], $Subnets));
}

// false
if ($ip_address===false) {
	$ip_address = "";
	$errmsg = "No IP addresses available in requested subnet";
	$errmsg_class = "danger";
}


# set selected address fields array
$selected_ip_fields = explode(";", $User->settings->IPfilter);
# fetch custom fields
$custom_fields = $Tools->fetch_custom_fields('ipaddresses');
?>

<!-- header -->
<div class="pHeader"><?php print _('Manage IP address request'); ?></div>

<!-- content -->
<div class="pContent">

	<?php
	// if error / warning message provided
	if (isset($errmsg)) {
		$Result->show($errmsg_class, $errmsg, false, false);
		print "<hr>";
	}
	// error check
	if (@$errmsg_class!="danger") {
	?>

	<!-- IP address request form -->
	<form class="manageRequestEdit" name="manageRequestEdit">
	<!-- edit IP address table -->
	<table id="manageRequestEdit" class="table table-striped table-condensed">

	<!-- divider -->
	<tr>
		<td colspan="2"><h4>Request details</h4><hr></td>
	</tr>

	<!-- Subnet -->
	<tr>
		<th><?php print _('Requested subnet'); ?></th>
		<td>
			<select name="subnetId" id="subnetId" class="form-control input-sm input-w-auto">
			<?php
			$request_subnets = $Admin->fetch_multiple_objects("subnets", "allowRequests", 1);

			foreach($request_subnets as $subnet) {
				$subnet = (array) $subnet;
				# print
				if($request['subnetId']==$subnet['id'])	{ print '<option value="'. $subnet['id'] .'" selected>' . $Addresses->transform_to_dotted($subnet['subnet']) .'/'. $subnet['mask'] .' ['. $subnet['description'] .']</option>'; }
				else 									{ print '<option value="'. $subnet['id'] .'">' 			. $Addresses->transform_to_dotted($subnet['subnet']) .'/'. $subnet['mask'] .' ['. $subnet['description'] .']</option>'; }
			}
			?>
			</select>
		</td>
	</tr>
	<!-- IP address -->
	<tr>
		<th><?php print _('IP address'); ?></th>
		<td>
			<input type="text" name="ip_addr" class="ip_addr form-control input-sm" value="<?php print $ip_address; ?>" size="30">
			<input type="hidden" name="requestId" value="<?php print $request['id']; ?>">
			<input type="hidden" name="requester" value="<?php print $request['requester']; ?>">
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
    	</td>
	</tr>
	<!-- description -->
	<tr>
		<th><?php print _('Description'); ?></th>
		<td>
			<input type="text" name="description" class="form-control input-sm" value="<?php print @$request['description']; ?>" size="30" placeholder="<?php print _('Enter IP description'); ?>">
		</td>
	</tr>
	<!-- DNS name -->
	<tr>
		<th><?php print _('Hostname'); ?></th>
		<td>
			<input type="text" name="dns_name" class="form-control input-sm" value="<?php print @$request['dns_name']; ?>" size="30" placeholder="<?php print _('Enter hostname'); ?>">
		</td>
	</tr>

	<?php if(in_array('state', $selected_ip_fields)) { ?>
	<!-- state -->
	<tr>
		<th><?php print _('State'); ?></th>
		<td>
			<select name="state" class="form-control input-sm input-w-auto">
			<?php
			$states = $Addresses->addresses_types_fetch ();
			# default tag
			if (!isset($request['state']))	{ $request['state'] = "2"; }
			foreach($states as $s) {
				if ($request['state']==$s['id'])	{ print "<option value='$s[id]' selected='selected'>$s[type]</option>"; }
				else								{ print "<option value='$s[id]'>$s[type]</option>"; }
			}
			?>
			</select>
		</td>
	</tr>
	<?php } ?>

	<?php if(in_array('owner', $selected_ip_fields)) { ?>
	<!-- owner -->
	<tr>
		<th><?php print _('Owner'); ?></th>
		<td>
			<input type="text" name="owner" class="form-control input-sm" id="owner" value="<?php print @$request['owner']; ?>" size="30" placeholder="<?php print _('Enter IP owner'); ?>">
		</td>
	</tr>
	<?php } ?>

	<?php if(in_array('switch', $selected_ip_fields)) { ?>
	<!-- switch / port -->
	<tr>
		<th><?php print _('Device'); ?> / <?php print _('port'); ?></th>
		<td>
			<select name="switch" class="form-control input-sm input-w-100">
				<option disabled><?php print _('Select device'); ?>:</option>
				<option value="" selected><?php print _('None'); ?></option>
				<?php
				$devices = $Tools->fetch_devices();
				//loop
				foreach($devices as $device) {
					//cast
					$device = (array) $device;

					if($device['id'] == @$request['switch']) { print '<option value="'. $device['id'] .'" selected>'. $device['hostname'] .'</option>'. "\n"; }
					else 									 { print '<option value="'. $device['id'] .'">'. 		 $device['hostname'] .'</option>'. "\n"; }
				}
				?>
			</select>
			<?php if(in_array('port', $selected_ip_fields)) { ?>
			/
			<input type="text" name="port" class="form-control input-sm input-w-100" value="<?php print @$request['port']; ?>"  placeholder="<?php print _('Port'); ?>">
		</td>
	</tr>
	<?php } ?>
		</td>
	</tr>
	<?php } ?>

	<?php if(in_array('note', $selected_ip_fields)) { ?>
	<!-- note -->
	<tr>
		<th><?php print _('Note'); ?></th>
		<td>
			<input type="text" name="note" class="form-control input-sm" id="note" placeholder="<?php print _('Write note'); ?>" size="30">
		</td>
	</tr>
	<?php } ?>

	<!-- Custom fields -->
	<?php
	if(sizeof(@$custom_fields) > 0) {
	    # count datepickers
	    $timeP = 0;

	    # all my fields
	    foreach($custom_fields as $myField) {
	        # replace spaces with |
	        $myField['nameNew'] = str_replace(" ", "___", $myField['name']);

	        # required
	        if($myField['Null']=="NO")  { $required = "*"; }
	        else                        { $required = ""; }

	        print '<tr>'. "\n";
	        print ' <td>'. $myField['name'] .' '.$required.'</td>'. "\n";
	        print ' <td>'. "\n";

	        //set type
		    if(substr($myField['type'], 0,3) == "set" || substr($myField['type'], 0,4) == "enum") {
				//parse values
				$tmp = substr($myField['type'], 0,3)=="set" ? explode(",", str_replace(array("set(", ")", "'"), "", $myField['type'])) : explode(",", str_replace(array("enum(", ")", "'"), "", $myField['type']));
	            //null
	            if($myField['Null']!="NO") { array_unshift($tmp, ""); }

	            print "<select name='$myField[nameNew]' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$myField[Comment]'>";
	            foreach($tmp as $v) {
	                if($v==@$details[$myField['name']])  { print "<option value='$v' selected='selected'>$v</option>"; }
	                else                                 { print "<option value='$v'>$v</option>"; }
	            }
	            print "</select>";
	        }
	        //date and time picker
	        elseif($myField['type'] == "date" || $myField['type'] == "datetime") {
	            // just for first
	            if($timeP==0) {
	                print '<link rel="stylesheet" type="text/css" href="css/1.2/bootstrap/bootstrap-datetimepicker.min.css">';
	                print '<script type="text/javascript" src="js/1.2/bootstrap-datetimepicker.min.js"></script>';
	                print '<script type="text/javascript">';
	                print '$(document).ready(function() {';
	                //date only
	                print ' $(".datepicker").datetimepicker( {pickDate: true, pickTime: false, pickSeconds: false });';
	                //date + time
	                print ' $(".datetimepicker").datetimepicker( { pickDate: true, pickTime: true } );';

	                print '})';
	                print '</script>';
	            }
	            $timeP++;

	            //set size
	            if($myField['type'] == "date")  { $size = 10; $class='datepicker';      $format = "yyyy-MM-dd"; }
	            else                            { $size = 19; $class='datetimepicker';  $format = "yyyy-MM-dd"; }

	            //field
	            if(!isset($details[$myField['name']]))  { print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $myField['nameNew'] .'" maxlength="'.$size.'" rel="tooltip" data-placement="right" title="'.$myField['Comment'].'">'. "\n"; }
	            else                                    { print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $myField['nameNew'] .'" maxlength="'.$size.'" value="'. @$details[$myField['name']]. '" rel="tooltip" data-placement="right" title="'.$myField['Comment'].'">'. "\n"; }
	        }
	        //boolean
	        elseif($myField['type'] == "tinyint(1)") {
	            print "<select name='$myField[nameNew]' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$myField[Comment]'>";
	            $tmp = array(0=>"No",1=>"Yes");
	            //null
	            if($myField['Null']!="NO") { $tmp[2] = ""; }

	            foreach($tmp as $k=>$v) {
	                if(strlen(@$details[$myField['name']])==0 && $k==2)  { print "<option value='$k' selected='selected'>"._($v)."</option>"; }
	                elseif($k==@$details[$myField['name']])              { print "<option value='$k' selected='selected'>"._($v)."</option>"; }
	                else                                                 { print "<option value='$k'>"._($v)."</option>"; }
	            }
	            print "</select>";
	        }
	        //text
	        elseif($myField['type'] == "text") {
	            print ' <textarea class="form-control input-sm" name="'. $myField['nameNew'] .'" placeholder="'. $myField['name'] .'" rowspan=3 rel="tooltip" data-placement="right" title="'.$myField['Comment'].'">'. $details[$myField['name']]. '</textarea>'. "\n";
	        }
	        //default - input field
	        else {
	            print ' <input type="text" class="ip_addr form-control input-sm" name="'. $myField['nameNew'] .'" placeholder="'. $myField['name'] .'" value="'. @$details[$myField['name']]. '" size="30" rel="tooltip" data-placement="right" title="'.$myField['Comment'].'">'. "\n";
	        }

	        print ' </td>'. "\n";
	        print '</tr>'. "\n";
	    }
	}
	?>

	<!-- divider -->
	<tr>
		<td colspan="2"><h4>Additional information</h4><hr></td>
	</tr>

	<!-- requested by -->
	<tr>
		<th><?php print _('Requester email'); ?></th>
		<td>
			<input type="text" disabled="disabled" class="form-control" value="<?php print @$request['requester']; ?>">
		</td>
	</tr>
	<!-- comment -->
	<tr>
		<th><?php print _('Requester comment'); ?></th>
		<td>
			<input type="text" disabled="disabled" class="form-control" value="<?php print @$request['comment']; ?>">
			<?php print "<input type='hidden' name='comment' value='".@$request['comment']."'>"; ?></i></td>
	</tr>
	<!-- Admin comment -->
	<tr>
		<th><?php print _('Comment approval/reject'); ?>:</th>
		<td>
			<textarea name="adminComment" rows="3" cols="30" class="form-control input-sm" placeholder="<?php print _('Enter reason for reject/approval to be sent to requester'); ?>"></textarea>
		</td>
	</tr>

	</table>
	</form>
	<?php } ?>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<?php if (@$errmsg_class!="danger") { ?>
		<button class="btn btn-sm btn-default btn-danger manageRequest" data-action='reject'><i class="fa fa-times"></i> <?php print _('Reject'); ?></button>
		<button class="btn btn-sm btn-default btn-success manageRequest" data-action='accept'><i class="fa fa-check"></i> <?php print _('Accept'); ?></button>
		<?php } ?>
	</div>

	<!-- result -->
	<div class="manageRequestResult"></div>
</div>