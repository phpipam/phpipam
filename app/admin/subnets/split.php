<?php

/*
 * Print resize split
 *********************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Subnets	= new Subnets ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->create_csrf_cookie ();


# ID must be numeric
if(!is_numeric($_POST['subnetId']))		{ $Result->show("danger", _("Invalid ID"), true, true); }

# get subnet details
$subnet = $Subnets->fetch_subnet (null, $_POST['subnetId']);

# verify that user has write permissions for subnet
$subnetPerm = $Subnets->check_permission ($User->user, $subnet->id);
if($subnetPerm < 3) 					{ $Result->show("danger", _('You do not have permissions to resize subnet').'!', true, true); }

# check if it has slaves - if yes it cannot be splitted!
if($Subnets->has_slaves($subnet->id))	{ $Result->show("danger", _('Only subnets that have no nested subnets can be splitted')."!", true, true); }

# calculate max mask
$max_new_mask = $Subnets->identify_address($Subnets->transform_to_dotted($subnet->subnet))=="IPv4" ? 32 : 128;


# die if too small
if($max_new_mask < $subnet->mask)		{ $Result->show("danger", _("Subnet too small to be splitted"), true, true); }

$n = 2;		# step
$m = 0;		# array id

//set mask options
for($mask=($subnet->mask+1); $mask<=$max_new_mask; $mask++) {
	# set vars
	$opts[$m]['mask']   = $mask;
	$opts[$m]['number'] = $n;
	$opts[$m]['max']    = $Subnets->get_max_hosts ($mask, $Subnets->identify_address($Subnets->transform_to_dotted($subnet->subnet)));

	# next
	$m++;
	$n = $n * 2;

	# max number = 16!
	if($n > 256) {
		$mask = 1000;
	}
}
?>

<!-- header -->
<div class="pHeader"><?php print _('Split subnet'); ?></div>


<!-- content -->
<div class="pContent">

	<form id="subnetSplit">
	<table class="table table-noborder table-condensed">

    <!-- subnet -->
    <tr>
        <td class="middle"><?php print _('Subnet'); ?></td>
        <td><?php print $Subnets->transform_to_dotted($subnet->subnet) . "/$subnet->mask ($subnet->description)"; ?></td>
    </tr>

    <!-- number of new subnets -->
    <tr>
        <td class="middle"><?php print _('Number of subnets'); ?></td>
        <td style="vertical-align:middle">
	    	<select name="number" class="form-control input-sm input-w-auto">
	    	<?php
	    	foreach($opts as $line) {
		    	print "<option value='$line[number]'>$line[number]x /$line[mask] subnet ($line[number]x $line[max] hosts)</option>";
	    	}
	    	?>
	    	</select>
	    	<input type="hidden" name="subnetId" value="<?php print $subnet->id; ?>">
	    	<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
        </td>
    </tr>

    <!-- Group under current -->
    <tr>
        <td class="middle"><?php print _('Group under current'); ?></td>
        <td>
	        <select name="group" class="form-control input-sm input-w-auto">
	        	<option value="yes"><?php print _('Yes'); ?></option>
	        	<option value="no" ><?php print _('No'); ?></option>
	        </select>
        </td>
    </tr>

    <!-- strict mode -->
    <tr>
    	<td><?php print _('Strict mode'); ?></td>
    	<td>
	    	<input type="checkbox" name="strict" value="yes" checked="checked">
    	</td>
    </tr>

    <!-- Prefix -->
    <tr>
    	<td><?php print _('Name prefix'); ?></td>
    	<td>
	    	<input type="text" name="prefix" value="<?php print $subnet->description."_"; ?>">
    	</td>
    </tr>

    </table>
    </form>

    <!-- warning -->
    <div class="alert alert-warning">
    <?php print _('You can split subnet to smaller subnets by specifying new subnets. Please note:'); ?>
    <ul>
    	<li><?php print _('Existing IP addresses will be assigned to new subnets'); ?></li>
    	<li><?php print _('Group under current will create new nested subnets under current one'); ?></li>
    	<li><?php print _('If existing IP will fall to subnet/broadcast of new subnets split will fail, except if strict mode is disabled'); ?></li>
    </ul>
    </div>

</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopup2"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default btn-success" id="subnetSplitSubmit"><i class="fa fa-ok"></i> <?php print _('Split subnet'); ?></button>
	</div>

	<div class="subnetSplitResult"></div>
</div>