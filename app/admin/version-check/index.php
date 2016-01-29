<?php
/*
 * Script to check for new version!
 *************************************************/

# verify that user is logged in
$User->check_user_session();

print "<h4>phpIPAM version check</h4><hr>";

# get latest version */
if(!$version = $Tools->check_latest_phpipam_version()) { $Result->show("danger", _("Version check failed").'!', false); }
else {
	//print result
	if($User->settings->version == $version) 		{ $Result->show("success", _('Latest version').' ('. $User->settings->version .') '._('already installed').'!', false); }
	else if ($User->settings->version > $version) 	{ $Result->show("success", _('Development version').' ('. $User->settings->version .') '._('installed! Latest production version is').' '. $version, false);}
	else 											{ $Result->show("danger",  _('New version of phpipam available').':</b><hr>'._('Installed version').': '.$User->settings->version."<br>"._('Available version').': '. $version."<br><br>"._('You can download new version').' <a href="https://sourceforge.net/projects/phpipam/files/current/phpipam-'. $version .'.tar/download">'._('here').'</a>.', false); }
}

# release and commit logs
print "<ul class='nav nav-tabs log-tabs'>";
print "<li role='presentation' class='active'><a href='' data-target='changelog'>Change log</a></li>";
if(!is_null($Tools->phpipam_releases))
print "<li role='presentation'>				  <a href='' data-target='releaselog'>Release log</a></li>";
if ($User->settings->version > $version)
print "<li role='presentation'>				  <a href='' data-target='gitlog'>Commit log (local)</a></li>";
print "</ul>";


# changelog
print "<div class='log-print changelog'>";
// title
print "<h4 style='margin-top:40px;'>Changelog</h4><hr>";
print "	<pre>";
$handle = fopen( dirname(__FILE__) . "/../../../misc/CHANGELOG", "r" );
print fread($handle, 102400);
print "	</pre>";
print "</div>";


# release log
if(!is_null($Tools->phpipam_releases)) {
print "<div class='log-print releaselog' style='display:none'>";
print "<h4 style='margin-top:40px;'>Release log</h4><hr>";
foreach ($Tools->phpipam_releases as $r) {
	// pre-release ?
	$prerelease = !is_numeric(str_replace("Version", "", $r->title)) ? "<span class='label label-danger'>Prerelease</span>" : "";

	// title
	print "<h5><i class='fa fa-angle-double-right'></i> $r->title $prerelease</h5>";
	// date
	print "<div style='padding-left:20px;margin-bottom:20px;'>";
	print "<span class='text-muted'>Released on ".date("Y-M-d", strtotime($r->updated))."</span> ";
	print "<div style='padding-top:10px;'>$r->content</div>";
	// tag
	print "<a class='btn btn-xs btn-default' href='http://github.com".$r->link->{'@attributes'}->href."'>Download (GitHub)</a>";
	print "</div>";
}
print "</div>";
}

# commit log for devel
if ($User->settings->version > $version) {

	print "<div class='log-print gitlog' style='display:none'>";

	# check
	$commit_log = shell_exec("git log");

	if ($commit_log=="NULL") {
		$Result->show("info", "Git not available", false);
	}
	else {
		// title
		print "<h4 style='margin-top:40px;'>Commit log (local)</h4><hr>";
		// split commits
		$commit_log = array_filter(explode("commit ", $commit_log));

		// loop
		foreach ($commit_log as $commit) {
			// lines to array
			$lines = explode("\n", $commit);
			// commit
			unset($out);
			$out['commit'] = $lines[0];
			// remove unneeded
			foreach ($lines as $k=>$l) {
				if (strpos($l, "Author")!==false)		{ $out['author'] = substr($l, 7);	unset($lines[$k]); }
				elseif (strpos($l, "Date")!==false)		{ $out['date'] = substr($l, 7);     unset($lines[$k]); }
				elseif (strpos($l, "Merge:")!==false)	{ $out['pr'] = $l;	unset($lines[$k]); }
				elseif (strlen(trim($l))==0)			{ unset($lines[$k]); }
				unset($lines[0]);
			}
			// merge
			$lines = implode("<br>", array_filter($lines));

			// title
			print "<h5><i class='fa fa-angle-double-right'></i> $out[commit]</h5>";
			// date
			print "<div style='padding-left:20px;margin-bottom:20px;'>";
			print "$out[author] <span class='text-muted'>(pushed on $out[date])</span>";
			print "<div style='padding:10px;background:white;max-width:400px;border-radius:6px;border:1px solid #ddd;'>$lines</div>";
			// tag
			print "<a class='btn btn-xs btn-default' style='margin-top:3px;' href='https://github.com/phpipam/phpipam/commit/$out[commit]' target='_blank'>View</a>";
			print "</div>";
		}
	}

	print "</div>";
}
?>
