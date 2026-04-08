<?php

/**
 * print subnet masks
 */

# default mask
$posted_mask = "10";

# get masks
if(isset($GET->mask)) {
	if($GET->mask<10 || $GET->mask>32) {
		$masks = $Subnets->get_ipv4_masks ();
	}
	else {
		$masks = $Subnets->get_ipv4_masks_for_subnet ($GET->mask);
		$posted_mask = $GET->mask;
	}
}
else {
	$masks = $Subnets->get_ipv4_masks ();
}
?>

<?php
if(!$popup) {
    print "<h4>"._('Subnet masks')."</h4><hr>";
    $colspan = "8";
}
else {
    $colspan = "6";
}
?>


<div class='subnet_table_overlay'>
<table class="<?php if(!$popup) print "subnet-mask-table sorted";?> table nosearch nopagination table-noborder1 table-hover table-condensed table-top <?php if(!$popup) print 'table-auto'; ?>" data-cookie-id-table="masks">

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
	if($m->bitmask<31 && $m->bitmask>7) {
		print "<tr>";
		print "	<td>$m->bitmask</td>";
		print "	<td>$m->netmask</td>";
		print "	<td>$m->wildcard</td>";
		print "	<td class='visible-lg'>".$m->binary."</td>";
		print "	<td>$m->subnets</td>";
		print "	<td>$m->hosts</td>";
		if(!$popup) {
		print "	<td>$m->subnet_bits</td>";
		print "	<td>$m->host_bits</td>";

		}
		print "</tr>";
	}
}
?>

<tr>
<td colspan="<?php print $colspan; ?>">
	<form>
    <div class="input-group pull-right">
      <input type="text" class="form-control input-sm" name='mask' placeholder="<?php print _("Enter mask"); ?>" value='<?php print $posted_mask; ?>'>
      <span class="input-group-btn">
        <button class="btn btn-default input-sm" type="submit"><?php print _("Update"); ?></button>
      </span>
    </div>
	</form>
</td>
</tr>

</tbody>
</table>
</div>


<?php if($popup) { ?>
<script type="text/javascript">
$(document).ready(function () {
	$('form').submit(function () {
		$('#popupOverlay2 div.popup_wmasks').load("app/tools/subnet-masks/popup.php?mask="+$('input[name=mask]').val()+"&closeClass=hidePopup2");
		return false;
	})
});
</script>
<?php } ?>