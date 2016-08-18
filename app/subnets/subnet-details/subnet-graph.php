<?php

/*
 * Script to print pie graph for subnet usage
 ********************************************/


# get details
if($slaves) {
    $addresses_slaves = $addresses;
    // if we have slaves we need to check against every slave
    $Subnets->reset_subnet_slaves_recursive ();
    $Subnets->fetch_subnet_slaves_recursive ($subnet['id']);
    $Subnets->remove_subnet_slaves_master ($subnet['id']);
    // loop
    if (isset($Subnets->slaves_full)) {
        // set initial count
        $cnt_tmp = sizeof($addresses_slaves);
        // loop
        foreach ($Subnets->slaves_full as $ss) {
            if ($ss->isFull==1) {
                $max = $Subnets->get_max_hosts ($ss->mask, $Addresses->identify_address($ss->subnet), false);
                // add to count
                $cnt_tmp = gmp_strval(gmp_add($cnt_tmp, $max));
            }
        }
    }

    // calculate without isFull
    $details = $Subnets->calculate_subnet_usage_detailed( $subnet['subnet'], $subnet['mask'], $addresses_slaves, $subnet['isFull']);

    // add temp values for slaves, recalculate
    if (isset($cnt_tmp)) {
	    # calculate free hosts
	    $details['freehosts']         = gmp_strval( gmp_sub ($details['maxhosts'] , $cnt_tmp) );
	    # calculate use percentage for each type
	    $details['freehosts_percent'] = round( ( ($details['freehosts'] * 100) / $details['maxhosts']), 2 );
	    // add "used"
        $details["Used_percent"] = round( ( ($cnt_tmp * 100) / $details['maxhosts']), 2 );

	    # if marked as full override
	    if ($subnet['isFull']==1) {
    	    $details['Used_percent'] = $details['Used_percent'] + $details['freehosts_percent'];
    	    $details['freehosts_percent'] = 0;
	    }

    }

}
else {
    $details = $Subnets->calculate_subnet_usage_detailed( $subnet['subnet'], $subnet['mask'], $addresses, $subnet['isFull']);
}
?>


<h4><?php print _('Usage graph'); ?></h4>
<hr>
<div id="pieChart" style="height:220px;width:100%;"></div>

<!-- charts -->
<script language="javascript" type="text/javascript" src="js/1.2/flot/jquery.flot.js"></script>
<script language="javascript" type="text/javascript" src="js/1.2/flot/jquery.flot.pie.js"></script>
<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="js/1.2/flot/excanvas.min.js"></script><![endif]-->


<script type="text/javascript">
$(function () {
	//data
    var data = [
    	<?php
		# first free hosts
     	if($details['freehosts_percent']>0)  {
    		$details['freehosts_percent'] = str_replace(",", ".", $details['freehosts_percent']);
    		print "{ label: '"._('Free')."',     data: $details[freehosts_percent], color: '#ffffff' }, ";		# free hosts
    	}
    	# than all other percentages
    	foreach($Subnets->address_types as $t) {
	    if($details[$t['type']."_percent"]>0) {
    		$details[$t['type']."_percent"] = str_replace(",", ".", $details[$t['type']."_percent"]);
    		print "{ label: '"._($t['type'])."', data: ".$details[$t["type"]."_percent"].", color: '".$t['bgcolor']."' }, ";
	    }
    	}
    	?>
	];
	//options
	var options = {
    series: {
        pie: {
            show: true,
            label: {
	            show: true,
	            radius: 1,
	            threshold: 0.01	//hide < 1%
            },
            background: {
	            color: 'red'
            },
            radius: 0.9,
            stroke: {
	            color: '#ccc',
	            width: 1
            },
            offset: {
	            left: 0
            }

        }
    },
    legend: {
	    show: true,
	    backgroundColor: ""
    },
	grid: {
		hoverable: false,
	  	clickable: true
	},
    highlightColor: '#AA4643',
    grid: {
	        show: true,
	        aboveData: false,
	        color: "#666",
	        backgroundColor: "white",
    		borderWidth: 0,
    		borderColor: null,
    		minBorderMargin: null,
    		clickable: true,
    		hoverable: true,
    		autoHighlight: true,
    		mouseActiveRadius: 3
    		}
    };
	//draw
    $.plot($("#pieChart"), data, options);
});
</script>