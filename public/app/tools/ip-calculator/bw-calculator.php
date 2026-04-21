<?php
# verify that user is logged in
$User->check_user_session();
?>

<!-- CSS for slider -->
<link rel="stylesheet" type="text/css" href="css/jquery-ui/jquery-ui.min.css?v=<?php print SCRIPT_PREFIX; ?>">


<style type="text/css">
.slider_overlay {
	width:400px;
	border-bottom: 10px;
}
.res_sel {
	width:260px;
	width: 60%;
	margin:15px;
	float:left
}
.res_val {
	background: white;
	padding: 5px 10px;
	border-radius: 4px;
	border: 1px solid #c5c5c5;
	margin-top: 7px;
	float: left;
	font-size: 13px;
}
p {
	margin-bottom: 0px;
}
#result .res_val {
	font-size: 32px;
}
<?php if(isset($widget)) { ?>
#result {
	position: absolute;
	right: 10px;
	top: 20px;
}
#result hr {
	display: none;
}
.slider_overlay {
	width:auto;
}
<?php } ?>
</style>


<script>
$(document).ready(function () {

	// set default indexes
	var def_wsize = 7;
	var def_delay = 19;
	var def_fsize = 10;

	// get default values
	wsize = remap_wsize(def_wsize)
	delay = remap_delay(def_delay)
	fsize = remap_fsize(def_fsize)
	recalculate_result()

	// slider function
	$( function() {
		// setup window size
		$( "#slider1" ).slider({
			max: 8,
			min: 1,
			value: def_wsize,
			slide: function ( event, ui ) {
				$('#slider1-val').html(remap_wsize(ui.value))
				wsize = remap_wsize(ui.value)
				def_wsize = ui.value
				recalculate_result ()
			}
		});

		// delay
		$( "#slider2" ).slider({
			max: 67,
			min: 1,
			value: def_delay,
			slide: function ( event, ui ) {
				$('#slider2-val').html(remap_delay(ui.value)+" ms")
				delay = remap_delay(ui.value)
				def_delay = ui.value
				recalculate_result ()
			}
		});

		// filesize
		$( "#slider3" ).slider({
			max: 13,
			min: 1,
			value: def_fsize,
			slide: function ( event, ui ) {
				$('#slider3-val').html(remap_fsize_text(ui.value))
				fsize = remap_fsize(ui.value)
				def_fsize = ui.value
				recalculate_result ()
			}
		});
	} );

	// recalculate delay
	function recalculate_result () {
		$.post("app/tools/ip-calculator/bw-calculator-result.php", {wsize:wsize, delay:delay, fsize:fsize <?php if(isset($widget)) print ", widget:'widget'" ?>}, function(data) {
			$('#result').html(data)
		})
	}


	// remap window size
	function remap_wsize (wsize_index) {
		switch (wsize_index) {
			case 1 	: return "1024";	break;
			case 2 	: return "2048";	break;
			case 3 	: return "4096";	break;
			case 4 	: return "8192";	break;
			case 5 	: return "16384";	break;
			case 6 	: return "32768";	break;
			case 7 	: return "50000";	break;
			case 8 	: return "65536";	break;

		}
	}

	// remap delays to values
	function remap_delay (delay_index) {
		switch (delay_index) {
			case 1  : return "0.1"; break;
			case 2  : return "0.2"; break;
			case 3  : return "0.3"; break;
			case 4  : return "0.4"; break;
			case 5  : return "0.5"; break;
			case 6  : return "0.6"; break;
			case 7  : return "0.7"; break;
			case 8  : return "0.8"; break;
			case 9  : return "0.9"; break;
			case 10 : return "1"; break;
			case 11 : return "2"; break;
			case 12 : return "3"; break;
			case 13 : return "4"; break;
			case 14 : return "5"; break;
			case 15 : return "6"; break;
			case 16 : return "7"; break;
			case 17 : return "8"; break;
			case 18 : return "9"; break;
			case 19 : return "10"; break;
			case 20 : return "20"; break;
			case 21 : return "25"; break;
			case 22 : return "30"; break;
			case 23 : return "40"; break;
			case 24 : return "50"; break;
			case 25 : return "60"; break;
			case 26 : return "70"; break;
			case 27 : return "80"; break;
			case 28 : return "90"; break;
			case 29 : return "100"; break;
			case 30 : return "110"; break;
			case 31 : return "120"; break;
			case 32 : return "130"; break;
			case 33 : return "140"; break;
			case 34 : return "150"; break;
			case 35 : return "160"; break;
			case 36 : return "170"; break;
			case 37 : return "180"; break;
			case 38 : return "190"; break;
			case 39 : return "200"; break;
			case 40 : return "210"; break;
			case 41 : return "220"; break;
			case 42 : return "230"; break;
			case 43 : return "240"; break;
			case 44 : return "250"; break;
			case 45 : return "260"; break;
			case 46 : return "270"; break;
			case 47 : return "280"; break;
			case 48 : return "290"; break;
			case 49 : return "300"; break;
			case 50 : return "310"; break;
			case 51 : return "320"; break;
			case 52 : return "330"; break;
			case 53 : return "340"; break;
			case 54 : return "350"; break;
			case 55 : return "360"; break;
			case 56 : return "370"; break;
			case 57 : return "380"; break;
			case 58 : return "390"; break;
			case 59 : return "400"; break;
			case 60 : return "425"; break;
			case 61 : return "450"; break;
			case 62 : return "500"; break;
			case 63 : return "600"; break;
			case 64 : return "700"; break;
			case 65 : return "800"; break;
			case 66 : return "900"; break;
			case 67 : return "1000"; break;
		}
	}

	// remap filesize slider index to values
	function remap_fsize (fsize_index) {
		switch (fsize_index) {
			case 1 	: return "102.4";	break;
			case 2 	: return "204.8";	break;
			case 3 	: return "307.2";	break;
			case 4 	: return "409.6";	break;
			case 5 	: return "512";		break;
			case 6 	: return "614.4";	break;
			case 7 	: return "716.8";	break;
			case 8 	: return "819.2";	break;
			case 9 	: return "921.6";	break;
			case 10 : return "1024";	break;
			case 11 : return "2048";	break;
			case 12 : return "3072";	break;
			case 13 : return "4096";	break;
		}
	}

	// remap filesize to text
	function remap_fsize_text (fsize_index) {
		switch (fsize_index) {
			case 1 	: return "100 Mbyte";	break;
			case 2 	: return "200 Mbyte";	break;
			case 3 	: return "300 Mbyte";	break;
			case 4 	: return "400 Mbyte";	break;
			case 5 	: return "500 Mbyte";	break;
			case 6 	: return "600 Mbyte";	break;
			case 7 	: return "700 Mbyte";	break;
			case 8 	: return "800 Mbyte";	break;
			case 9 	: return "900 Mbyte";	break;
			case 10 : return "1 Gbyte";		break;
			case 11 : return "2 Gbyte";		break;
			case 12 : return "3 Gbyte";		break;
			case 13 : return "4 Gbyte";		break;
		}
	}
})
</script>


<?php if(!isset($widget)) { ?>
<h4><?php print _('Bandwidth calculator');?></h4>
<hr>
<?php print _("Select TCP window size, delay and file size to calculate how long file transfer will take."); ?>

<br><br><br>
<?php } ?>

<div class="slider_overlay">
	<p><?php print _("TCP window size").":"; ?></p>
	<div class='res_sel' id="slider1"></div>
	<div class='res_val' id="slider1-val">50000</div>
	<br>
</div>

<div class="clearfix"></div>

<div class="slider_overlay">
	<p><?php print _("Delay").":"; ?></p>
	<div class='res_sel' id="slider2"></div>
	<div class='res_val' id="slider2-val">10 ms</div>
	<br>
</div>

<div class="clearfix"></div>

<div class="slider_overlay">
	<p><?php print _("File size").":"; ?></p>
	<div class='res_sel' id="slider3"></div>
	<div class='res_val' id="slider3-val">1 GByte</div>
	<br>
</div>

<div class="clearfix"></div>

<div class="slider_overlay" id="result"></div>

<div class="clearfix"></div>