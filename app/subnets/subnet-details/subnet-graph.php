<?php

/*
 * Script to print pie graph for subnet usage
 ********************************************/

# get usage
$details = $Subnets->calculate_subnet_usage ($subnet);

# set free color
$unused_color = $User->user->ui_theme=="dark" ? "rgba(0,0,0,0.1)" : "white";
?>


<h4><?php print _('Usage graph'); ?></h4>
<hr>
<div id="pieChart" style="height:220px;width:100%;"></div>

<!-- charts -->
<script src="js/flot/jquery.flot.js"></script>
<script src="js/flot/jquery.flot.pie.js"></script>
<!--[if lte IE 8]><script src="js/flot/excanvas.min.js"></script><![endif]-->


<script>
$(function () {
	//data
    var data = [
    	<?php
		# first free hosts
     	if($details['freehosts_percent']>0)  {
    		$details['freehosts_percent'] = str_replace(",", ".", $details['freehosts_percent']);
    		print "{ label: '"._('Free')."', data: $details[freehosts_percent], color: '$unused_color' }, ";		# free hosts
    	}
    	# than all other percentages
    	foreach($Subnets->address_types as $t) {
			$type_percent = $t['type']."_percent";
			if(isset($details[$type_percent]) && $details[$type_percent]>0) {
				$details[$type_percent] = str_replace(",", ".", $details[$type_percent]);
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