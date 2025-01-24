<?php

/**
 *	Print all available nameserver sets and configurations
 ************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database  = new Database_PDO;
$User      = new User ($Database);
$Tools     = new Tools ($Database);
$Subnets   = new Subnets ($Database);
$Addresses = new Addresses ($Database);
$Result    = new Result ();

# verify that user is logged in
$User->check_user_session();
# validate NAT permissions
$User->check_module_permissions ("nat", User::ACCESS_RWA, true, true);

# fetch all nat items
$all_nat = $Tools->fetch_all_objects ("nat");
$all_nat = $all_nat === false ? array() : $all_nat;

# set arrays
$subnet_ids  = array ();
$address_ids = array ();

# decode and save ids for each item to array
foreach ($all_nat as $nat) {
    # remove item from nat
    $nat_src = db_json_decode($nat->src, true);
    $nat_dst = db_json_decode($nat->dst, true);

    if(is_array(@$nat_src['subnets'])) {
        foreach ($nat_src['subnets'] as $s) {
            $subnet_ids[] = $s;
        }
    }
    if(is_array(@$nat_dst['subnets'])) {
        foreach ($nat_dst['subnets'] as $s) {
            $subnet_ids[] = $s;
        }
    }
    if(is_array(@$nat_src['ipaddresses'])) {
        foreach ($nat_src['ipaddresses'] as $s) {
            $address_ids[] = $s;
        }
    }
    if(is_array(@$nat_dst['ipaddresses'])) {
        foreach ($nat_dst['ipaddresses'] as $s) {
            $address_ids[] = $s;
        }
    }
}
# filter out duplicates
$subnet_ids  = array_unique($subnet_ids);
$address_ids = array_unique($address_ids);
?>

<!-- header -->
<div class="pHeader"><?php print _("NAT cleanup"); ?></div>

<!-- content -->
<div class="pContent">
    <div class="text-muted"><?php print _("Removing deleted addresses and subnets items from NAT..."); ?></div>
    <hr>

    <?php
    # init arrays
    $removed_subnets   = array ();
    $removed_addresses = array ();

    # first subnets
    if(!empty($subnet_ids)) {
        foreach ($subnet_ids as $id) {
            if ($Tools->fetch_object("subnets", "id", $id)===false) {
                $cnt = $Subnets->remove_subnet_nat_items ($id, false);
                if ($cnt>0) {
                    $removed_subnets[] = $id;
                }
            }
        }
    }

    # second addresses
    if(!empty($address_ids)) {
        foreach ($address_ids as $id) {
            if ($Tools->fetch_object("ipaddresses", "id", $id)===false) {
                $cnt = $Addresses->remove_address_nat_items ($id, false);
                if ($cnt>0) {
                    $removed_addresses[] = $id;
                }
            }
        }
    }

    # Results
    if(!empty($removed_subnets)) {
        $Result->show("info", _("Removed")." ".sizeof($removed_subnets)." "._("subnets")."<hr>Ids: ".implode("; ", $removed_subnets));
    }
    else {
        $Result->show("info", _("No subnets removed from NAT"));
    }

    if(!empty($removed_addresses)) {
        $Result->show("info", _("Removed")." ".sizeof($removed_addresses)." "._("addresses")."<hr>Ids: ".implode("; ", $removed_addresses));
    }
    else {
        $Result->show("info", _("No addresses removed from NAT"));
    }
    ?>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopupsReload"><?php print _('Close'); ?></button>
	</div>
</div>
