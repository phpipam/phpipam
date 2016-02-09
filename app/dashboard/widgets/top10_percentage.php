<?php
/*
 * Print graph of Top IPv4 hosts by percentage
 **********************************************/

# required functions
if(!is_object(@$User)) {
	require( dirname(__FILE__) . '/../../../functions/functions.php' );
	# classes
	$Database	= new Database_PDO;
	$User 		= new User ($Database);
	$Tools 		= new Tools ($Database);
	$Subnets 	= new Subnets ($Database);
	$Result		= new Result ();
}

# user must be authenticated
$User->check_user_session ();

# set size parameters
$height = 200;
$slimit = 10;

# if direct request include plot JS
if($_SERVER['HTTP_X_REQUESTED_WITH']!="XMLHttpRequest")	{
	# get widget details
	if(!$widget = $Tools->fetch_widget ("wfile", $_REQUEST['section'])) { $Result->show("danger", _("Invalid widget"), true); }
	# reset size and limit
	$height = 350;
	$slimit = 20;
	# include flot JS
	print '<script language="javascript" type="text/javascript" src="js/1.2/flot/jquery.flot.js"></script>';
	print '<script language="javascript" type="text/javascript" src="js/1.2/flot/jquery.flot.categories.js"></script>';
	print '<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="js/1.2/flot/excanvas.min.js"></script><![endif]-->';
	# and print title
	print "<div class='container'>";
	print "<h4 style='margin-top:40px;'>$widget[wtitle]</h4><hr>";
	print "</div>";
}

$type = "IPv4";

# get subnets statistic
$top_subnets = $Tools->fetch_top_subnets($type, 1000000, true);

/* detect duplicates */
$unique = array();
$numbering = array();
$m = 0;
foreach($top_subnets as $subnet) {
	# cast
	$subnet = (array) $subnet;
	# check if already in array
	if(in_array($subnet['description'], $unique)) {
		$numbering[$subnet['description']]++;
		$top_subnets[$m]->description = $subnet['description'].' #'.$numbering[$subnet['description']];
	}
	$unique[] = $top_subnets[$m]->description;
	$m++;
}

# set maximum for graph
$max = str_replace(",", ".", $top_subnets[0]->percentage);


# only print if some hosts exist
if(sizeof($top_subnets)>0) {
?>
<script type="text/javascript">
$(function () {

    var data = [
    <?php
	$m=0;
	foreach ($top_subnets as $subnet) {
		# cast
		$subnet = (array) $subnet;
		if($m < $slimit) {
			# verify user access
			$sp = $Subnets->check_permission($User->user, $subnet['id']);
			if($sp != 0) {
				$subnet['subnet'] = $Subnets->transform_to_dotted($subnet['subnet']);
				$subnet['descriptionLong'] = $subnet['description'];

				# set percentage because of localisation
				$subnet['percentage'] = str_replace(",", ".", $subnet['percentage']);
				$subnet['percentage'] = $subnet['percentage'];

				# odd/even if more than 5 items
				if(sizeof($top_subnets) > 5) {
					if ($m&1) 	{ print "['|<br>" . addslashes($subnet['description']) . "', $subnet[usage], '" . addslashes($subnet['descriptionLong']) . " ($subnet[subnet]/$subnet[mask])'],";	}
					else	 	{ print "['" . addslashes($subnet['description']) . "', $subnet[usage], '" . addslashes($subnet['descriptionLong']) . " ($subnet[subnet]/$subnet[mask])'],";	}
				}
				else {
								{ print "['" . addslashes($subnet['description']) . "', $subnet[usage], '" . addslashes($subnet['descriptionLong']) . " ($subnet[subnet]/$subnet[mask])'],";	}
				}
				$m++;
			}
		}
	}
	?>
	];

	//show tooltips
    function showTooltip(x, y, contents) {
        $('<div id="tooltip">' + contents + '</div>').css( {
            position: 'absolute',
            display: 'none',
            top: y - 29,
            left: x,
            border: '1px solid white',
            'border-radius': '4px',
            padding: '4px',
            'font-size': '11px',
            'background-color': 'rgba(0,0,0,0.7)',
            color: 'white'
        }).appendTo("body").fadeIn(500);
    }

    //set JS array for clickable event
    <?php
    $allLinks = json_encode($top_subnets);
    echo "var all_links = ". $allLinks. ";\n";
    ?>

	//open link
	$('#IPv4top10').bind('plotclick', function(event, pos, item) {
		//set prettylinks of not
		if ($('#prettyLinks').html()=="Yes")	{ var plink = $("div.iebase").html()+"subnets/"+all_links[item.datapoint[0]]['sectionId']+"/"+ all_links[item.datapoint[0]]['id']+"/"; }
		else									{ var plink = $("div.iebase").html()+"?page=subnets&section="+all_links[item.datapoint[0]]['sectionId']+"&subnetId="+all_links[item.datapoint[0]]['id'] + ""; }
		//open
		document.location = plink;
	});

    var previousPoint = null;
    $("#<?php print $type; ?>top10").bind("plothover", function (event, pos, item) {
        $("#x").text(pos.x.toFixed(2));
        $("#y").text(pos.y.toFixed(2));

            if (item) {
                if (previousPoint != item.dataIndex) {
                    previousPoint = item.dataIndex;

                    $("#tooltip").remove();
                    var x = item.datapoint[0],
                        y = item.datapoint[1];

                    showTooltip(item.pageX, item.pageY,

                                data[x][2] + "<br>" + y + "% used");
                }
                $("#<?php print $type; ?>top10").css({'cursor':'pointer'});
            }
            else {
                $("#tooltip").remove();
                previousPoint = null;
                $("#<?php print $type; ?>top10").css({'cursor':'default'});
            }

    });

		var options = {
        series: {
            bars: {
                show: true,
                barWidth: 0.6,
                lineWidth: 1,
                align: "center",
                fillColor: "rgba(69, 114, 167, 0.7)"
            }
        },
        xaxis: {
            mode: "categories",
            tickLength: 0,
            color: '#666',
            tickLength: 1,
            show: true
        },
        yaxis: {
        	max: <?php print $max; ?>
        },
        margin: {
	        top: 10,
	        left: 30,
	        bottom: 10,
	        right: 10
	    },
	    grid: {
		  	hoverable: true,
		  	clickable: true
	    },
	    bars: {
		    barWidth: 0.9
	    },
        legend: {
	        show: false
	    },
        shadowSize: 2,
        highlightColor: '#4572A7',
        colors: ['#4572A7' ],
        grid: {
	        show: true,
	        aboveData: false,
	        color: "#666",
	        backgroundColor: "white",
/*     margin: number or margin object */
/*     labelMargin: number */
/*     axisMargin: number */
/*     markings: array of markings or (fn: axes -> array of markings) */
    		borderWidth: 0,
    		borderColor: null,
    		minBorderMargin: null,
    		clickable: true,
    		hoverable: true,
    		autoHighlight: true,
    		mouseActiveRadius: 3
    		}
    };

	<?php
	if($m!=0) {
	?>
    $.plot($("#<?php print $type; ?>top10"), [ data ], options);
    <?php } else { ?>
    $("#IPv4top10").hide();
    <?php } ?>
});
</script>

<?php
if($m==0) {
	print "<hr>";

	print "<blockquote style='margin-top:20px;margin-left:20px;'>";
	print "<p>"._("No $type hosts configured")."</p>";
	print "<small>"._("Add some hosts to subnets to show graph of used hosts per subnet")."</small>";
	print "</blockquote>";
}
?>

<?php
}
else {
	print "<hr>";
	print "<blockquote style='margin-top:20px;margin-left:20px;'>";
	print "<p>"._("No $type hosts configured")."</p>";
	print "<small>"._("Add some hosts to subnets to calculate usage percentage")."</small>";
	print "</blockquote>";

	#remove loading
	?>
	<script type="text/javascript">
	$(document).ready(function() {
		$("#IPv4top10").fadeOut('fast');
	});
	</script>
	<?php
}
?>


<div id="IPv4top10" class="top10"  style="height:<?php print $height; ?>px;width:95%;margin-left:3%;">
	<div style="text-align:center;padding-top:50px;"><strong><?php print _('Loading statistics'); ?></strong><br><i class='fa fa-spinner fa-spin'></i></div>
</div>
