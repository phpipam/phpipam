<?php
# verify that user is logged in
$User->check_user_session();
?>

<h4><?php print _('IPv4v6 calculator');?></h4>
<hr>

<!-- ipCalc form -->
<form name="ipCalc" id="ipCalc">
<table class="ipCalc table">

    <!-- IP address input -->
    <tr>
        <td><?php print _('IP address');?> / <?php print _('mask');?></td>
        <td>
            <input type="text" class="form-control" name="cidr" size="40" autofocus="autofocus">
        </td>
        <td>
            <div class="info2" style="margin-bottom:0px;"><?php print _('Please enter IP address and mask in CIDR format');?></div>
        </td>
    </tr>

    <!-- Submit -->
    <tr class="th">
        <td></td>
        <td>
        	<div class="btn-group">
            	<button type="submit" class="btn btn-sm btn-default"><i class="fa fa-check"></i> <?php print _('Calculate');?></button>
				<input type="button" class="btn btn-sm btn-default reset" value="<?php print _('Reset');?>">
        	</div>
        </td>
        <td></td>
    </tr>


</table>
</form>


<!-- result -->
<br>
<div class="ipCalcResult"></div>