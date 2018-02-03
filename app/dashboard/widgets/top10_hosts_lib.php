<?php
/**
 * Shared code between top10_hosts_v4.php
 * and top10_hosts_v6.php
 */

# required functions
if(!is_object(@$User)) {
	require( dirname(__FILE__) . '/../../../functions/functions.php' );
	# classes
	$Database	= new Database_PDO;
	$User		= new User ($Database);
	$Tools		= new Tools ($Database);
	$Subnets	= new Subnets ($Database);
	$Result		= new Result ();
}

# user must be authenticated
$User->check_user_session ();

$top_subnets = array();
$all_subnets = $Tools->fetch_top_subnets($type, 1000000, false);

# Find subnets with user access, label duplicates.
$unique = array();
$valid_subnets = 0;
if (is_array($all_subnets)) {
    foreach($all_subnets as $subnet) {
        if ($Subnets->check_permission($User->user, $subnet->id) == "0") { continue; }

        /* We've found $slimit entries */
        if ($valid_subnets >= $slimit) { break; }

        /* Make fields human readable */
        $subnet->subnet = $Subnets->transform_to_dotted($subnet->subnet);
        $subnet->descriptionLong = $subnet->description;
        $subnet->description = strlen($subnet->description) > 20 ? substr($subnet->description,0,17).'...' : $subnet->description;

        /* detect and rename duplicates */
        if(isset($unique[$subnet->description])) {
            $subnet->description = $subnet->description.' #'.sizeof($unique[$subnet->description]);
        }
        $unique[$subnet->description][] = $valid_subnets++;

        /* Save */
        $top_subnets[] = $subnet;
    }
}

# only print if some hosts exist
if($valid_subnets>0) {
    ?>
    <script type="text/javascript">
        $(function () {

            var data = [
                <?php
                foreach ($top_subnets as $m => $subnet) {
                    # cast
                    $subnet = (array) $subnet;

                    # odd/even if more than 5 items
                    if($valid_subnets > 5) {
                        if ($m&1) 	{ print "['|<br>" . addslashes($subnet['description']) . "', $subnet[usage], '" . addslashes($subnet['descriptionLong']) . " ($subnet[subnet]/$subnet[mask])'],";	}
                        else	 	{ print "['" . addslashes($subnet['description']) . "', $subnet[usage], '" . addslashes($subnet['descriptionLong']) . " ($subnet[subnet]/$subnet[mask])'],";	}
                    }
                    else {
                        print "['" . addslashes($subnet['description']) . "', $subnet[usage], '" . addslashes($subnet['descriptionLong']) . " ($subnet[subnet]/$subnet[mask])'],";
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
                else									{ var plink = $("div.iebase").html()+"index.php?page=subnets&section="+all_links[item.datapoint[0]]['sectionId']+"&subnetId="+all_links[item.datapoint[0]]['id'] + ""; }
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
            if($valid_subnets!=0) {
            ?>
            $.plot($("#<?php print $type; ?>top10Hosts"), [ data ], options);
            <?php } else { ?>
            $("#<?php print $type; ?>top10Hosts").hide();
            <?php } ?>
        });
    </script>

    <?php
    if($valid_subnets==0) {
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
            $("#<?php print $type; ?>top10Hosts").fadeOut('fast');
        });
    </script>
    <?php
}
?>

<div id="<?php print $type; ?>top10Hosts" class="top10" style="height: <?php print $height; ?>px; width: 95%; margin-left: 3%; padding: 0px; position: relative; ">
    <div style="text-align:center;padding-top:50px;"><strong><?php print _('Loading statistics'); ?></strong><br><i class='fa fa-spinner fa-spin'></i></div>
</div>
