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
		if (phpversion() >= UNSUPPORTED_PHP_VERSION) {
			print " "._('UNSUPPORTED_PHP_VERSION').":".phpversion();
		}
		?>
	</td>

	<?php
	# custom footer message
	if(isset($config['footer_message']) && strlen($config['footer_message'])>0) {
		print '<td> '.$config['footer_message'].' </td>';
	}

	# exclude install
	if($_GET['page']!="install") { ?>
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
