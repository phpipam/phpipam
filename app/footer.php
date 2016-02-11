<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">

<table class="donate">
<tr>
	<td>
		<?php
		// set href
		$href = REVISION=="000" ? "http://phpipam.net" : "http://phpipam.net";
		?>
		<a href="<?php print $href; ?>">phpIPAM IP address management <?php print '[v'. VERSION. ']'; ?><?php if(REVISION > 0) { print " rev".REVISION; } ?></a>
	</td>

	<?php
	# exclude install
	if($_GET['page']!="install") { ?>
	<td>
		<?php print _('In case of problems please contact').' <a href="mailto:'. $User->settings->siteAdminMail .'">'. $User->settings->siteAdminName .'</a>'; ?>
	</td>
	<?php
	/* hide donations button */
	if($User->settings->donate == 0) {

print '	<td id="donate" class="hidden-xs hidden-sm" rel="tooltip" data-html="true" title="'._('phpIPAM is free, open-source project').'.<br>'._('If you like the software you can donate by clicking this button to support further development').'.">
	<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
">
<input type="image" src="css/1.2/images/btn_donate_SM.gif" name="submit">
	</td>';

	}
	}
	?>

</tr>
</table>
</form>
