<table class="donate">
<tr>
	<td>
		<a href="http://phpipam.net">phpIPAM IP address management <?php print '[v'. VERSION_VISIBLE. ']'; ?></a>
		<?php
		// Display SCHEMA database version in development builds
		if(PUBLISHED===false) {
			print " dbversion ".DBVERSION;
		}
		// show github tree ref (Docker builds)
		if (defined("GIT_VCS_REF")) {
			print " git <a href='https://github.com/phpipam/phpipam/tree/".GIT_VCS_REF."'>".GIT_VCS_REF."</a>";
		}
		?>
	</td>

	<?php
	# footer messages
	if(isset($config['footer_message']) && !is_blank($config['footer_message'])) {
		print '<td> '.$config['footer_message'].' </td>';
	}
	if (isset($_SESSION['footer_warnings'])) {
		foreach ($_SESSION['footer_warnings'] as $msg) {
			print '<td><b>' . _('WARNING') . ': ' . $msg . '</b></td>';
		}
	}

	# exclude install
	if($GET->page!="install") { ?>
	<td>
		<?php print _('In case of problems please contact').' <a href="mailto:'. $User->settings->siteAdminMail .'">'. $User->settings->siteAdminName .'</a>'; ?>
	</td>
	<?php
	/* hide donations button */
	if($User->settings->donate == 0) {

print '	<td id="donate" class="hidden-xs hidden-sm" rel="tooltip" data-html="true" title="'._('phpIPAM is free, open-source project').'.<br>'._('If you like the software you can donate by clicking this button to support further development').'.">
		<a href="https://phpipam.net/donate/" target="_blank"><input type="image" src="css/images/btn_donate_SM.gif" alt="'._("Donation button").'" name="submit"></a>
	</td>';

	}
	}
	?>
</tr>
</table>
