<?php

/**
 * print subnet masks
 */

# get masks
$masks = $Subnets->get_ipv4_masks ();
?>

<?php if(!$popup) { ?>
<div id="dashboard">
	<div class="widget-dash inner" style="position:absolute">
<?php } ?>
		<table class="<?php if(!$popup) print "subnet-mask-table";?> table table-striped <?php if($popup) print 'table-condensed'; ?> table-top <?php if(!$popup) print 'table-auto'; ?>">

		<!-- headers -->
		<thead>
			<tr>
				<th><?php print _("Bitmask"); ?></th>
				<th><?php print _("Netmask"); ?></th>
				<th><?php print _("Wildcard mask"); ?></th>
				<th class="visible-lg"><?php print _("Binary"); ?></th>
				<th><?php print _("Subnets"); ?></th>
				<th><?php print _("Hosts"); ?></th>
				<?php
				if(!$popup) {
				print "<th>"._("Subnet bits")."</th>";
				print "<th>"._("Host bits")."</th>";
				}
				?>
			</tr>
		</thead>

		<!-- values -->
		<tbody>
		<?php
		foreach($masks as $m) {
			print "<tr>";
			print "	<td>/$m->bitmask</td>";
			print "	<td>$m->netmask</td>";
			print "	<td>$m->wildcard</td>";
			print "	<td class='visible-lg'>$m->binary</td>";
			print "	<td>$m->subnets</td>";
			print "	<td>$m->hosts</td>";
			if(!$popup) {
			print "	<td>$m->subnet_bits</td>";
			print "	<td>$m->host_bits</td>";

			}
			print "</tr>";
		}
		?>
		</tbody>

		</table>

<?php if(!$popup) { ?>
	</div>
</div>
<?php } ?>