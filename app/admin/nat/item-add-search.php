<?php

/**
 *	remove item from nat
 ************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# validate permissions
$User->check_module_permissions ("nat", User::ACCESS_RW, true, true);

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "nat_add", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# length
if(is_blank($POST->ip))   { $Result->show("danger", _("Please enter IP address"), true); }
# id
if(!is_numeric($POST->id)) { $Result->show("danger", _("Invalid NAT item ID"), true); }
# type
if($POST->type!=="src" && $POST->type!=="dst" ) { $Result->show("danger", _("Invalid NAT direction"), true); }

# set searchterm
if(isset($POST->ip)) {
	// trim
	$POST->ip = trim($POST->ip);
	// escape
	$POST->ip = htmlspecialchars($POST->ip);

	$search_term = @$search_term=="search" ? "" : $POST->ip;
}

# change * to % for database wildchar
$search_term = trim($search_term);
$search_term = str_replace("*", "%", $search_term);

# fetch old details
$nat = $Tools->fetch_object("nat", "id", $POST->id);
$nat->src = db_json_decode($nat->src, true);
$nat->dst = db_json_decode($nat->dst, true);

// identify
$type = $Admin->identify_address( $search_term ); //identify address type

# reformat if IP address for search
if ($type == "IPv4") 		{ $search_term_edited = $Tools->reformat_IPv4_for_search ($search_term); }	//reformat the IPv4 address!
elseif($type == "IPv6") 	{ $search_term_edited = $Tools->reformat_IPv6_for_search ($search_term); }	//reformat the IPv4 address!

# search addresses
$result_addresses = $Tools->search_addresses($search_term, $search_term_edited['high'], $search_term_edited['low'], array());
# search subnets
$result_subnets   = $Tools->search_subnets($search_term, $search_term_edited['high'], $search_term_edited['low'], $POST->ip, array());

# if some found print
if(sizeof($result_addresses)>0 || sizeof($result_subnets)>0) {

    // init arrays
    $html1 = [];
    $html2 = [];


    if(sizeof($result_subnets)>0) {
        $html1[] = "<h4>Subnets</h4>";
        foreach ($result_subnets as $s) {
            if(isset($nat->src) && isset($nat->dst) && is_array($nat->src['subnets']) && is_array($nat->dst['subnets'])) {
                if(!in_array($s->id, $nat->src['subnets']) && !in_array($s->id, $nat->dst['subnets'])) {
                    $html1[] = "<a class='btn btn-xs btn-success addNatObjectFromSearch' data-id='".$POST->id."' data-object-id='$s->id' data-object-type='subnets' data-type='".$POST->type."'><i class='fa fa-plus'></i></a> ".$Tools->transform_address($s->subnet, "dotted")."/".$s->mask."<br>";
                }
            }
            $html1[] = "<a class='btn btn-xs btn-success addNatObjectFromSearch' data-id='".$POST->id."' data-object-id='$s->id' data-object-type='subnets' data-type='".$POST->type."'><i class='fa fa-plus'></i></a> ".$Tools->transform_address($s->subnet, "dotted")."/".$s->mask."<br>";

        }
        if(sizeof($html1)==1) { $html1 = []; }
    }
    if(sizeof($result_addresses)>0) {
        $html2[] = "<h4>Addresses</h4>";
        foreach ($result_addresses as $a) {
            if(isset($nat->src) && isset($nat->dst) && is_array($nat->src['ipaddresses']) && is_array($nat->dst['ipaddresses'])) {
                if(!in_array($a->id, $nat->src['ipaddresses']) && !in_array($a->id, $nat->dst['ipaddresses'])) {
                    $html2[] = "<a class='btn btn-xs btn-success addNatObjectFromSearch' data-id='".$POST->id."' data-object-id='$a->id' data-object-type='ipaddresses' data-type='".$POST->type."'><i class='fa fa-plus'></i></a> ".$Tools->transform_address($a->ip_addr, "dotted")."<br>";
                }
            }
            $html2[] = "<a class='btn btn-xs btn-success addNatObjectFromSearch' data-id='".$POST->id."' data-object-id='$a->id' data-object-type='ipaddresses' data-type='".$POST->type."'><i class='fa fa-plus'></i></a> ".$Tools->transform_address($a->ip_addr, "dotted")."<br>";
        }
        if(sizeof($html2)==1) { $html2 = []; }
    }
    // print
    print implode("\n", $html1);
    print implode("\n", $html2);
}
else {
    $Result->show("info", _("No results found"), false);
}