<?php
/*
 * Print threshold subnets
 *
 * 		Inout must be IPv4 or IPv6!
 **********************************************/


# user must be authenticated
$User->check_user_session ();

# no errors!
//ini_set('display_errors', 0);

# header
print "<h4>"._("Threshold")."</h4><hr>";


if ($User->settings->enableThreshold=="1") {
    # get thresholded subnets
    $threshold_subnets = $Subnets->fetch_threshold_subnets (1000);

    # any found ?
    if ($threshold_subnets !== false) {
        # loop
        foreach ($threshold_subnets as $s) {
            # check permission of user
            $sp = $Subnets-> check_permission ($User->user, $s->id);
            if($sp != "0") {
                $out[] = $s;
            }
        }
    }
}

# disabled
if ($User->settings->enableThreshold!="1") {
	print "<blockquote style='margin-top:20px;margin-left:20px;'>";
	print "<p>"._("Threshold module disabled")."</p>";
	print "<small>"._("You can enable threshold module under settings")."</small>";
	print "</blockquote>";
}
# error - none found but not permitted
elseif ($threshold_subnets===false) {
	print "<blockquote style='margin-top:20px;margin-left:20px;'>";
	print "<p>"._("No subnet is selected for threshold check")."</p>";
	print "<small>"._("You can set threshold for subnets under subnet settings")."</small>";
	print "</blockquote>";
}
# error - found but not permitted
elseif (!isset($out)) {
	print "<blockquote style='margin-top:20px;margin-left:20px;'>";
	print "<p>"._("No subnet selected for threshold check available")."</p>";
	print "<small>"._("No subnet with threshold check available")."</small>";
	print "</blockquote>";
}
# found
else {
    // count usage
    foreach ($out as $k=>$s) {
        //check if subnet has slaves and set slaves flag true/false
        $slaves = $Subnets->has_slaves ($s->id) ? true : false;

        # fetch all addresses and calculate usage
        if($slaves) {
            $addresses = $Addresses->fetch_subnet_addresses_recursive ($s->id, false);
        	$slave_subnets = (array) $Subnets->fetch_subnet_slaves ($s->id);
        	// save count
        	$addresses_cnt = gmp_strval(sizeof($addresses));

        	# full ?
        	if (sizeof($slave_subnets)>0) {
            	foreach ($slave_subnets as $ss) {
                	if ($ss->isFull==1) {
                    	# calculate max
                    	$max_hosts = $Subnets->get_max_hosts ($ss->mask, $Subnets->identify_address($ss->subnet), true);
                    	# count
                    	$count_hosts = $Addresses->count_subnet_addresses ($ss->id);
                    	# add
                    	$addresses_cnt = gmp_strval(gmp_add($addresses_cnt, gmp_sub($max_hosts, $count_hosts)));
                	}
            	}
        	}

        	$subnet_usage  = $Subnets->calculate_subnet_usage ($addresses_cnt, $s->mask, $s->subnet, $s->isFull );		//Calculate free/used etc
        }
        else {
            # fetch addresses in subnet
            $addresses_cnt = $Addresses->count_subnet_addresses ($s->id);
            # calculate usage
            $subnet_usage  = $Subnets->calculate_subnet_usage ($addresses_cnt, $s->mask, $s->subnet, $s->isFull );
        }

        # set additional threshold parameters
        $subnet_usage['usedhosts_percent'] = gmp_strval(gmp_sub(100,(int) round($subnet_usage['freehosts_percent'], 0)));
        $subnet_usage['until_threshold']   = gmp_strval(gmp_sub($s->threshold, $subnet_usage['usedhosts_percent']));

        # save
        $out[$k]->usage = (object) $subnet_usage;
    }

    // reorder - highest usage to lowest
    foreach ($out as $k => $v) {
        $used[$k] = $v->usage->usedhosts_percent;
    }
    array_multisort($used, SORT_DESC, $out);

    // table
    print "<table class='table table-threshold table-top sorted table-noborder'>";

    // headers
    print "<tr>";
    print " <th>"._('Subnet')."</th>";
    print " <th>"._('Usage')."</th>";
    print "</tr>";

    // print
    foreach ($out as $s) {
        # set class
        $aclass = $s->usage->usedhosts_percent > $s->threshold ? "progress-bar-danger" : "progress-bar-info";
        # limit description
        $s->description = strlen($s->description)>0  ? " (".$s->description.")" : "";
        # limit class
        $limit_class = $s->usage->until_threshold<0 ? "progress-limit-negative" : "progress-limit";

        print "<tr>";
        print " <td><a href='".create_link("subnets", $s->sectionId, $s->id)."'>".$Subnets->transform_address($s->subnet)."/".$s->mask."</a> ".$s->description."</td>";
        print " <td>";
        print "     <div class='progress'>";
        print "     <div class='progress-bar $aclass' role='progressbar' rel='tooltip' title='"._('Current usage').": ".$s->usage->usedhosts_percent."%' aria-valuenow='".$s->usage->usedhosts_percent."' aria-valuemin='0' style='width: ".$s->usage->usedhosts_percent."%;'>".$s->usage->usedhosts_percent."%</div>";
        print "     <div class='$limit_class' rel='tooltip'  title='"._('Threshold').": ".$s->threshold."%' style='margin-left:".$s->usage->until_threshold."%;'>&nbsp;</div>";
        print "     </div>";
        print " </td>";
        print "</tr>";
    }

    print "</table>";
}
?>
