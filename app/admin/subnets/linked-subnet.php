<?php

/*
 * Print edit subnet
 *********************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Sections	= new Sections ($Database);
$Subnets	= new Subnets ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->csrf_cookie ("create", "linkedsubnet");


# ID must be numeric
if(!is_numeric($_POST['subnetId']))	{ $Result->show("danger", _("Invalid ID"), true, true); }

# get all IPv6 subnets
$ipv6_subnets = $Subnets->fetch_all_subnets_search ("IPv6");

# get subnet details
$subnet = $Subnets->fetch_subnet(null, $_POST['subnetId']);
?>



<!-- header -->
<div class="pHeader"><?php print _('Link IPv4 subnet to IPv6 subnet'); ?></div>

<!-- content -->
<div class="pContent">
	<span class='muted'><?php print _('Select IPv6 subnet to link to current subnet'); ?> <?php print $Subnets->transform_address ($subnet->subnet, "botted"); ?>/<?php print $subnet->mask; ?></span>
	<hr>

	<form id="editLinkedSubnet">

    <select name="linked_subnet" class="form-control input-sm input-w-auto" style="margin-bottom: 20px;">
        <option value="0"><?php print _("Not linked"); ?></option>
    	<?php

    	# print each group
    	if($ipv6_subnets !==false) {
    		foreach($ipv6_subnets as $s) {
        		if($Subnets->has_slaves ($s->id)===false) {
            		$selected = $s->id == $subnet->linked_subnet ? "selected" : "";
            		print "<option value='$s->id' $selected>".$Subnets->transform_address($s->subnet, "dotted")."/$s->mask</option>";
        		}
    		}
        }
    	?>
    </select>
	<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
	<input type="hidden" name="subnetId" value="<?php print $subnet->id; ?>">

    </table>
    </form>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default btn-success linkSubnetSave"><i class="fa fa-check"></i> <?php print _('Link'); ?></button>
	</div>
    <div class="linkSubnetSaveResult"></div>
</div>