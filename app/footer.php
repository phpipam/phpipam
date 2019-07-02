<table class="donate">
<tr>
	<td>
		<?php var_dump(PUBLISHED); ?>
		<a href="http://phpipam.net">phpIPAM IP address management <?php print '[v'. VERSION_VISIBLE. ']'; ?><?php if(PUBLISHED===false) { print " dbversion".DBVERSION; } ?></a>
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
		<a href="https://phpipam.net/donate/" target="_blank"><input type="image" src="css/images/btn_donate_SM.gif" name="submit"></a>
	</td>';

	}
	}
	?>
</tr>
</table>