<?php

/**
 * Show Subnet Summary in a condensed, sharable format (as in to provide customers their subnet info)
 ***********************************************************************/

# set rowspan
$rowSpan = 10 + sizeof($custom_fields);
?>

<h4>
    <?php print _('Subnet Summary'); ?>
    <div class='btn-toolbar pull-right'>
      <div class='btn-group'>
        <a class='btn btn-xs btn-default btn-default' rel='tooltip' title='<?=_('Copy Subnet Summary')?>' onclick="navigator.clipboard.writeText(document.querySelector('table.subnet_summary').innerText); $('#subnet-summary-copied').fadeTo(2000, 500).slideUp(500, function(){$('#subnet-summary-copied').slideUp(500);});"><i class='fa fa-copy'></i></a>
      </div>
    </div>
</h4>
<hr>

<table class="ipaddress_subnet subnet_summary table-condensed table-full">
	<tr>
		<th style='padding-top:2px !important;'><?php print _('Subnet Address:'); ?></th>
		<td>
                    <?php print "<b>".$Subnets->transform_address($subnet["subnet"],"dotted")."/$subnet[mask]</b>"; ?>
                </td>
	</tr>
	<tr>
		<th style='padding-top:2px !important;'><?php print _('Netmask:'); ?></th>
		<td>
                    <?php print "$subnet_detailed[netmask]"; ?>
                </td>
	</tr>
	<!-- gateway -->
	<?php
	$gateway = $Subnets->find_gateway($subnet['id']);
	if($gateway !==false) { ?>
	<tr>
		<th><?php print _('Gateway:'); ?></th>
		<td><?php print $Subnets->transform_to_dotted($gateway->ip_addr);?></td>
	</tr>
	<?php } ?>

<?php

$visual_addresses = array();
if($addresses_visual) {
        foreach($addresses_visual as $a) {
                $visual_addresses[$a->ip_addr] = (array) $a;
        }
}

$usable_addresses = $Subnets->get_all_possible_subnet_addresses($subnet);
//Remove the Gateway if its set
$usable_addresses = array_diff($usable_addresses, [$Subnets->find_gateway($subnet['id'])->ip_addr]);
if (count($usable_addresses) > 5) {
    $top_addresses = array_slice($usable_addresses, 0, 5);
    $bottom_addresses = array_slice($usable_addresses, -5);
} else {
    $top_addresses = $usable_addresses;
}
if (count($top_addresses) == 1) { ?>
<th><?=_('Customer IP:')?></th>
<td><?=$Subnets->transform_to_dotted(current($top_addresses))?></td>

<?php } else { ?>
	<!-- Usable IPs -->
<tr>
	<th><?=_('Customer IPs:')?></th>
	<td>
		<ul style="margin-left:0px; padding-left:1em;">
<?php

foreach ($top_addresses as $m) {
        $ip_addr = $Subnets->transform_to_dotted($m);
        $title = $ip_addr;
        print "<li>$ip_addr</li>";
}

if ($bottom_addresses !== null && count($bottom_addresses) > 0) {
	print "<li>...</li>";
	foreach ($bottom_addresses as $m) {
		$ip_addr = $Subnets->transform_to_dotted($m);
		$title = $ip_addr;
	        print "<li>$ip_addr</li>";
	}
}
?>
                  </ul>
                </td>
<?php  }
?>
        </tr>
	<!-- nameservers -->
	<tr>
		<th><?php print _('DNS Servers:'); ?></th>
		<td>
		<?php

		// Only show nameservers if defined for subnet
		if(!empty($subnet['nameserverId'])) {
                ?>
                  <ul style="margin-left:0px; padding-left:1em;">
                <?php
			$nameservers_string = $Tools->fetch_object("nameservers", "id", $subnet['nameserverId']);
			$nameservers = explode(';', $nameservers_string->namesrv1);
                        foreach($nameservers as $nameserver) {
                          print "<li>$nameserver</li>";
                        }
//			print str_replace(";", ", ", $nameservers->namesrv1);
		}
		?>
                  </ul>
		</td>
	</tr>


</table>
<div id="subnet-summary-copied" class="alert alert-dismissible alert-success" style="display:none;">
	<button type="button" class="close" onclick="$(this).closest('.alert').hide()" aria-label="Close">
	  <span aria-hidden="true">&times;</span>
	</button>
	Copied Subnet Information!
</div>
