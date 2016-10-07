<?php

# include required scripts
require( dirname(__FILE__) . '/../functions.php' );

# limit
$limit = 80;    // 80 percent threshold

# initialize objects
$Database 	= new Database_PDO;
$Subnets	= new Subnets ($Database);
$Addresses	= new Addresses ($Database);
$Tools		= new Tools ($Database);
$Result		= new Result();

# fetch all subnets
$all_subnets = $Tools->fetch_all_objects ("subnets");

# loop and check usage for each, make sure it does not have any parent
foreach ($all_subnets as $k=>$s) {
    // marked as full should not be checked
    if ($s->isFull!=1) {
        // parent check
        if (!$Subnets-> has_slaves ($s->id)) {
            // calculate usage
            $usage = $Subnets->calculate_subnet_usage ($s);
            // if more than $threshold report
            if ($usage['freehosts_percent']<(100-$limit)) {
                // this subnet has high usage, save it to array
                $out[$k]['subnet']      = $Subnets->transform_address($s->subnet, "dotted")."/".$s->mask;
                $out[$k]['description'] = $s->description;
                $out[$k]['usage']       = $usage;
            }
        }
    }
}

# any fount
if (isset($out)) {
    // do something with output
    print_r($out);
}
?>