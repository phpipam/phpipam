<?php

/**
 * Shared top10_widget code
 *
 * @param  string  $type_ip
 * @param  bool    $type_percentage
 * @param  integer $height
 * @param  integer $slimit
 */
function top10_widget($type_ip, $type_percentage, $height, $slimit) {

	global $Subnets;
	global $Tools;
	global $User;

	$type_name = $type_ip . "top10". ($type_percentage ? "Percentage" : "Hosts");

	$top_subnets = array();
	$all_subnets = $Tools->fetch_top_subnets($type_ip, 1000000, $type_percentage);

	# Find subnets with user access, label duplicates.
	$unique = array();
	$valid_subnets = 0;


	if($User->user->ui_theme=="dark") 	{ $text_color = "#eee"; }
	else 								{ $text_color = "#666";}


	if (is_array($all_subnets)) {
		foreach($all_subnets as $subnet) {
			if ($Subnets->check_permission($User->user, $subnet->id) == "0") continue;

			/* We've found $slimit entries */
			if ($valid_subnets >= $slimit) break;

			/* Make fields human readable */
			$subnet->subnet = $Subnets->transform_to_dotted($subnet->subnet);
			$subnet->descriptionLong = $subnet->description;
			$subnet->description = $Tools->shorten_text($subnet->description, 20);

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
	if($valid_subnets==0) {
		$msg = $type_percentage ? _("Add some hosts to subnets to calculate usage percentage") : _("Add some hosts to subnets to show graph of used hosts per subnet");
		print '<div style="' . (isset($height) ? "height:{$height}px;overflow-y:auto;" : "") . '">';
		print "<blockquote style='margin-top:20px;margin-left:20px;'>";
		print "<p>"._("No $type_ip hosts configured")."</p>";
		print "<small>".$msg."</small>";
		print "</blockquote>";
		print "</div>";
		#remove loading
		?>
		<script>
			$(document).ready(function() {
				$("#<?php print $type_name; ?>").fadeOut('fast');
			});
		</script>
		<?php
		return;
	}

	?>
	<script>
		$(function () {

			var data = [
				<?php
				foreach ($top_subnets as $m => $subnet) {
					# cast
					$subnet = (array) $subnet;

					if ($type_percentage === true) {
						# set percentage because of localisation
						$subnet['percentage'] = str_replace(",", ".", $subnet['percentage']);
						$display_item = $subnet['percentage'];
					} else {
						$display_item = $subnet['usage'];
					}

					# odd/even if more than 5 items
					if($valid_subnets > 5) {
						if ($m&1) 	{ print "['|<br>" . addslashes($subnet['description']) . "', $display_item, '" . addslashes($subnet['descriptionLong']) . " ($subnet[subnet]/$subnet[mask])'],";	}
						else	 	{ print "['" . addslashes($subnet['description']) . "', $display_item, '" . addslashes($subnet['descriptionLong']) . " ($subnet[subnet]/$subnet[mask])'],";	}
					}
					else {
						print "['" . addslashes($subnet['description']) . "', $display_item, '" . addslashes($subnet['descriptionLong']) . " ($subnet[subnet]/$subnet[mask])'],";
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
			$('#<?php print $type_name; ?>').bind('plotclick', function(event, pos, item) {
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
			$("#<?php print $type_name; ?>").bind("plothover", function (event, pos, item) {
				$("#x").text(pos.x.toFixed(2));
				$("#y").text(pos.y.toFixed(2));

				if (item) {
					if (previousPoint != item.dataIndex) {
						previousPoint = item.dataIndex;

						$("#tooltip").remove();
						var x = item.datapoint[0],
							y = item.datapoint[1];

						showTooltip(item.pageX, item.pageY,
							data[x][2] + "<br>" + y + "<?php print $type_percentage ? _("% used") : _(" hosts"); ?>");
					}

					$("#<?php print $type_name; ?>").css({'cursor':'pointer'});
				}
				else {
					$("#tooltip").remove();
					previousPoint = null;
					$("#<?php print $type_name; ?>").css({'cursor':'default'});
				}

			});

			var options = {
				series: {
					bars: {
						show: true,
						barWidth: 0.6,
						lineWidth: 1,
						align: "center",
						fillColor: "<?php print $type_percentage ? "rgba(69, 114, 167, 0.7)" : "rgba(170, 70, 67, 0.8)"; ?>"
					}
				},
				xaxis: {
					mode: "categories",
					tickLength: 0,
					color: '<?php print $text_color; ?>',
					tickLength: 1,
					show: true
				},
				yaxis: {
					<?php if ($type_percentage) print "max: ".str_replace(",", ".", $top_subnets[0]->percentage); ?>
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
				highlightColor: "<?php print $type_percentage ? '#4572A7' : '#AA4643'; ?>",
				colors: [ "<?php print $type_percentage ? '#4572A7' : '#AA4643'; ?>" ],
				grid: {
					show: true,
					aboveData: false,
					color: "<?php print $text_color; ?>",
					backgroundColor: "transparent",
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
			$.plot($("#<?php print $type_name; ?>"), [ data ], options);
		});
	</script>

	<div id="<?php print $type_name; ?>" class="top10" style="height: <?php print $height; ?>px; width: 95%; margin-left: 3%; padding: 0px; position: relative; ">
		<div style="text-align:center;padding-top:50px;"><strong><?php print _('Loading statistics'); ?></strong><br><i class='fa fa-spinner fa-spin'></i></div>
	</div>
<?php
}
