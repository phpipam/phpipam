<?php

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Subnets	= new Subnets ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# fetch BGP details
$bgp = $Admin->fetch_object("routing_bgp", "id", $POST->bgpid);
// false
if ($bgp===false)                                            { $Result->show("danger", _("Invalid ID"), true, true);  }

# mapped subnets
$bgp_mapped_subnets = $Tools->fetch_routing_subnets ("bgp", $bgp->id, false);

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "routing_bgp_mapping");

?>


<!-- header -->
<div class="pHeader"><?php print _("Edit"); ?> <?php print _('BGP subnet mapping'); ?></div>

<!-- content -->
<div class="pContent">
	<?php
	print "<h4>"._('BGP summary')."</h4>";
	print "<hr>";

	# circuit
	print "<table class='ipaddress_subnet table-condensed table-auto'>";

	print '<tr>';
	print " <th>". _('Peer name').'</th>';
	print " <td><strong>$bgp->peer_name</strong></td>";
	print "</tr>";

	print '<tr>';
	print " <th>". _('BGP type').'</th>';
	print " <td>$bgp->bgp_type</td>";
	print "</tr>";

	print "<tr>";
	print " <td colspan='2'><hr></td>";
	print "</tr>";

	print '<tr>';
	print " <th>". _('Local AS').'</th>';
	print " <td>$bgp->local_as</td>";
	print "</tr>";

	print '<tr>';
	print " <th>". _('Local address').'</th>';
	print " <td>$bgp->local_address</td>";
	print "</tr>";

	print "<tr>";
	print " <td colspan='2'><hr></td>";
	print "</tr>";

	print '<tr>';
	print " <th>". _('Peer AS').'</th>';
	print " <td>$bgp->peer_as</td>";
	print "</tr>";

	print '<tr>';
	print " <th>". _('Peer address').'</th>';
	print " <td>$bgp->peer_address</td>";
	print "</tr>";

	print "</table>";
	?>

	<div style='margin-top:50px;'></div>
	<?php
		include(dirname(__FILE__)."/../../tools/routing/bgp/details-subnets.php");
	?>

	<div style='margin-top:50px;'></div>
	<h4><?php print _("Map new subnet"); ?></h4><hr>

	<div class="input-group" id="bgp_subnet_mapping" style='margin-bottom:20px;width:300px;'>
		<form id="bgp_subnet_mapping">
		<input type="text" class="form-control searchInput input-sm" name="ip" placeholder="Search subnet" value="">
		<input type="hidden" name="bgp_id" value="<?php print $bgp->id; ?>">
		</form>
		<span class="input-group-btn">
        	<button class="btn btn-default btn-sm searchSubmit" type="button">Search</button>
		</span>
	</div>
	</form>

	<div class='bgp_subnet_mapping_result' id='bgp_subnet_mapping_result' style='margin-bottom:20px;'></div>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
	</div>
</div>

<script>
$(document).ready(function() {
	$('.searchSubmit').click(function() {
		var subnet = $('#bgp_subnet_mapping input[name=ip]').val();
		var bgp_id = $('#bgp_subnet_mapping input[name=bgp_id]').val();
		submit_search (subnet, bgp_id);
		return false;
	})
	$('#bgp_subnet_mapping').submit(function() {
		var subnet = $('#bgp_subnet_mapping input[name=ip]').val();
		var bgp_id = $('#bgp_subnet_mapping input[name=bgp_id]').val();
		submit_search (subnet, bgp_id);
		return false;
	})
	function submit_search (subnet, bgp_id) {
		$.post("app/admin/routing/edit-bgp-mapping-search.php", {subnet:subnet, bgp_id:bgp_id}, function(data) {
			$('.bgp_subnet_mapping_result').html(data);
		});
		return false;
	}

	$(document).on("click", ".add_bgp_mapping", function () {
		var curr_id = $(this).attr('data-curr_id');
		$.post("app/admin/routing/edit-bgp-mapping-submit.php", {subnet_id:$(this).attr('data-subnetid'), bgp_id:$(this).attr('data-bgp_id'), direction:$('select.select-'+curr_id+' option:selected').val()}, function(data) {
			$('td.result-'+curr_id).html(data);

	        if(data.search("alert-danger")==-1 && data.search("error")==-1 && data.search("alert-warning")==-1 ) { setTimeout(function (){window.location.reload();}, 500); }

		});
	})
})
</script>
