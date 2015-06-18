<?php
/*
 * Script to check for new version!
 *************************************************/

# verify that user is logged in
$User->check_user_session();

# get latest version */
if(!$version = $Tools->check_latest_phpipam_version()) { $Result->show("danger", _("Version check failed").'!', true); }


print "<h4>phpIPAM version check</h4><hr>";

//print result
if($User->settings->version == $version) 		{ $Result->show("success alert-absolute", _('Latest version').' ('. $User->settings->version .') '._('already installed').'!', false); }
else if ($User->settings->version > $version) 	{ $Result->show("success alert-absolute", _('Development version').' ('. $User->settings->version .') '._('installed! Latest production version is').' '. $version, false);}
else 											{ $Result->show("danger alert-absolute",  _('New version of phpipam available').':</b><hr>'._('Installed version').': '.$User->settings->version."<br>"._('Available version').': '. $version."<br><br>"._('You can download new version').' <a href="https://sourceforge.net/projects/phpipam/files/current/phpipam-'. $version .'.tar/download">'._('here').'</a>.', false); }
?>