<?php
/*
 * Print graph of Top IPv4 / IPv6 hosts by percentage
 *
 * 		Inout must be IPv4 or IPv6!
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

# no errors!
//ini_set('display_errors', 0);

# set size parameters
$height = 200;
$slimit = 10;			//we dont need this, we will recalculate


# if direct request include plot JS
if($_SERVER['HTTP_X_REQUESTED_WITH']!="XMLHttpRequest")	{
	# get widget details
	if(!$widget = $Tools->fetch_object ("widgets", "wfile", $_REQUEST['section'])) { $Result->show("danger", _("Invalid widget"), true); }
	# reset size and limit
	$height = 350;
	$slimit = 20;
	# include flot JS
	print '<script language="javascript" type="text/javascript" src="js/1.2/flot/jquery.flot.js"></script>';
	print '<script language="javascript" type="text/javascript" src="js/1.2/flot/jquery.flot.categories.js"></script>';
	print '<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="js/1.2/flot/excanvas.min.js"></script><![endif]-->';
	# and print title
	print "<div class='container'>";
	print "<h4 style='margin-top:40px;'>$widget->wtitle</h4><hr>";
	print "</div>";
}

# get subnets statistic
$type = 'IPv4';
$top_subnets = $Tools->fetch_top_subnets($type, 1000000, false);


/* detect duplicates */
$unique = array();
$numbering = array();
$m = 0;
foreach($top_subnets as $subnet) {
	# cast
	$subnet = (array) $subnet;
	# check if already in array
	if(in_array($subnet['description'], $unique)) {
		@$numbering[$subnet['description']]++;
		$top_subnets[$m]->description = $subnet['description'].' #'.$numbering[$subnet['description']];
	}
	$unique[] = $top_subnets[$m]->description;
	$m++;
}

# only print if some hosts exist
if(sizeof($top_subnets)>0) {
?>
<script type="text/javascript">
$(function () {

    var data = [
    <?php
	$m=0;
	$unique_descriptions = array();
	// loop
	foreach ($top_subnets as $subnet) {
		# cast
		$subnet = (array) $subnet;
		if($m < $slimit) {
			# verify user access
			$sp = $Subnets-> check_permission ($User->user, $subnet['id']);
			if($sp != "0") {
				$subnet['subnet'] = $Subnets->transform_to_dotted($subnet['subnet']);
				// save description - full
				$subnet['descriptionLong'] = $subnet['description'];
                //length check
                $subnet['description'] = strlen($subnet['description'])>20 ? substr($subnet['description'], 0,20)."..." : $subnet['description'];

                // if desc already exists append index
                if (in_array($subnet['description'], $unique_descriptions)) {
                    while (in_array($subnet['description']."_$n", $unique_descriptions)) {
                        $n++;
                    }
                    $subnet['description'] = $subnet['description']."_$n";
                }

                // save unique description
                $unique_descriptions[] = $subnet['description'];

				# odd/even if more than 5 items
				if(sizeof($top_subnets) > 5) {
					if ($m&1) 	{ print "['|<br>" . addslashes($subnet['description']) . "', $subnet[usage], '" . addslashes($subnet['descriptionLong']) . " ($subnet[subnet]/$subnet[mask])'],";	}
					else	 	{ print "['" . addslashes($subnet['description']) . "', $subnet[usage], '" . addslashes($subnet['descriptionLong']) . " ($subnet[subnet]/$subnet[mask])'],";	}
				}
				else {
								{ print "['" . addslashes($subnet['description']) . "', $subnet[usage], '" . addslashes($subnet['descriptionLong']) . " ($subnet[subnet]/$subnet[mask])'],";	}
				}
				# next
				$m++;
			}
		}
	}
	?>
	];

    //set JS array for clickable event
    <?php
    $allLinks = json_encode($top_subnets);
    echo "var all_links = ". $allLinks. ";\n";
    ?>

	//open link
	$('#<?php print $type; ?>top10Hosts').bind('plotclick', function(event, pos, item) {
		//set prettylinks of not
		if ($('#prettyLinks').html()=="Yes")	{ var plink = $("div.iebase").html()+"subnets/"+all_links[item.datapoint[0]]['sectionId']+"/"+ all_links[item.datapoint[0]]['id']+"/"; }
		else									{ var plink = $("div.iebase").html()+"?page=subnets&section="+all_links[item.datapoint[0]]['sectionId']+"&subnetId="+all_links[item.datapoint[0]]['id'] + ""; }
		//open
		document.location = plink;
	});

	//show tooltips
    function showTooltip(x, y, contents) {
        $('<div id="tooltip">' + contents + '</div>').css( {
            position: 'absolute',
            display: 'none',
            top: y - 35,
            left: x,
            border: '1px solid white',
            'border-radius': '4px',
            padding: '4px',
            'font-size': '11px',
            'background-color': 'rgba(0,0,0,0.7)',
            color: 'white'
        }).appendTo("body").fadeIn(500);
    }

    var previousPoint = null;
    $("#<?php print $type; ?>top10Hosts").bind("plothover", function (event, pos, item) {
        $("#x").text(pos.x.toFixed(2));
        $("#y").text(pos.y.toFixed(2));

            if (item) {
                if (previousPoint != item.dataIndex) {
                    previousPoint = item.dataIndex;

                    $("#tooltip").remove();
                    var x = item.datapoint[0],
                        y = item.datapoint[1];

                    showTooltip(item.pageX, item.pageY,

                                data[x][2] + "<br>" + y + " hosts");
                }

                $("#<?php print $type; ?>top10Hosts").css({'cursor':'pointer'});
            }
            else {
                $("#tooltip").remove();
                previousPoint = null;
                $("#<?php print $type; ?>top10Hosts").css({'cursor':'default'});
            }

    });

		var options = {
        series: {
            bars: {
                show: true,
                barWidth: 0.6,
                lineWidth: 1,
                align: "center",
                fillColor: "rgba(170, 70, 67, 0.8)"
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
        shadowSize: 10,
        highlightColor: '#AA4643',
        colors: ['#AA4643' ],
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
    $.plot($("#<?php print $type; ?>top10Hosts"), [ data ], options);
    <?php } else { ?>
    $("#IPv4top10Hosts").hide();
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
	print "<small>"._("Add some hosts to subnets to show graph of used hosts per subnet")."</small>";
	print "</blockquote>";

	#remove loading
	?>
	<script type="text/javascript">
	$(document).ready(function() {
		$("#IPv4top10Hosts").fadeOut('fast');
	});
	</script>
	<?php
}
?>

<div id="IPv4top10Hosts" class="top10" style="height: <?php print $height; ?>px; width: 95%; margin-left: 3%; padding: 0px; position: relative; ">
	<div style="text-align:center;padding-top:50px;"><strong><?php print _('Loading statistics'); ?></strong><br><i class='fa fa-spinner fa-spin'></i></div>
</div>
