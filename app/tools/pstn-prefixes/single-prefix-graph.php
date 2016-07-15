<?php
# verify that user is logged in
$User->check_user_session();
?>


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
    	if(isset($Tools->address_types)) {
    	foreach($Tools->address_types as $t) {
	    if($details[$t['type']."_percent"]>0) {
    		$details[$t['type']."_percent"] = str_replace(",", ".", $details[$t['type']."_percent"]);
    		print "{ label: '"._($t['type'])."', data: ".$details[$t["type"]."_percent"].", color: '".$t['bgcolor']."' }, ";
	    }
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