<?php

/*
 * Print truncate subnet
 *********************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Subnets	= new Subnets ($Database);
$Addresses	= new Addresses ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "truncate");


# id must be numeric
if(!is_numeric($_POST['subnetId']))			{ $Result->show("danger", _("Invalid ID"), true, true); }

# get subnet details
$subnet = $Subnets->fetch_subnet (null, $_POST['subnetId']);

# verify that user has write permissions for subnet
$subnetPerm = $Subnets->check_permission ($User->user, $subnet->id);
if($subnetPerm < 3) 						{ $Result->show("danger", _('You do not have permissions to resize subnet').'!', true, true); }

# on empty subnet it means it came from database validity check, fake id !
if($subnet===false) {
    $subnet = new StdClass ();
    $subnet->id = $_POST['subnetId'];
}

# set prefix - folder or subnet
$prefix = $subnet->isFolder=="1" ? "folder" : "subnet";

# reformat description
$subnet->description = !is_blank($subnet->description) ? "($subnet->description)" : "";
# set subnet
$subnet->description = $subnet->isFolder=="1" ? $subnet->description : $Subnets->transform_to_dotted($subnet->subnet)."/$subnet->mask $subnet->description";
?>

<!-- header -->
<div class="pHeader"><?php print _("Truncate $prefix"); ?></div>

<!-- content -->
<div class="pContent">
	<table class="table table-noborder table-condensed">

    <!-- subnet -->
    <tr>
        <td class="middle"><?php print _(ucwords($prefix)); ?></td>
        <td><?php print $subnet->description; ?></td>
    </tr>
    <!-- Mask -->
    <tr>
        <td class="middle"><?php print _('Number of IP addresses'); ?></td>
        <td><?php print $Addresses->count_subnet_addresses ($subnet->id); ?></td>
    </tr>
    </table>

    <!-- warning -->
    <div class="alert alert-warning">
    <?php print _('Truncating network will remove all IP addresses, that belong to selected subnet!'); ?>
    </div>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopup2"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default btn-danger" id="subnetTruncateSubmit" data-subnetId='<?php print $subnet->id; ?>' data-csrf_cookie="<?php print $csrf; ?>"><i class="fa fa-trash-o"></i> <?php print _('Truncate subnet'); ?></button>
	</div>

	<div class="subnetTruncateResult"></div>
</div>