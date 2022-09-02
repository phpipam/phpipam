<?php
/*
 * Script to check for new version!
 *************************************************/

# verify that user is logged in
$User->check_user_session();

print "<h4>phpIPAM version check</h4><hr>";

# get latest version */
if(!$version = $Tools->check_latest_phpipam_version(true)) { $Result->show("danger", _("Version check failed").'!', false); }
else {
	//print result
	if(VERSION_VISIBLE == $version) 		{ $Result->show("success", _('Latest version').' ('. VERSION_VISIBLE .') '._('already installed').'!', false); }
	else if (VERSION_VISIBLE > $version) 	{ $Result->show("success", _('Development version').' ('. VERSION_VISIBLE .') '._('installed! Latest production version is').' '. $version, false);}
	else 									{ $Result->show("danger",  _('New version of phpipam available').':</b><hr>'._('Installed version').': '.VERSION_VISIBLE."<br>"._('Available version').': '. $version."<br><br>"._('You can download new version'). " <a href='https://github.com/phpipam/phpipam/releases/tag/v$version'>"._('GitHub').'</a>' . ' ( '._('archive').' <a href="https://sourceforge.net/projects/phpipam/files/current/phpipam-'. $version .'.tar/download">'._('SourceForge').'</a> ).', false); }
}

# release and commit logs
print "<ul class='nav nav-tabs log-tabs'>";
print "<li role='presentation' class='active'><a href='' data-target='changelog'>Change log</a></li>";
if(!is_null($Tools->phpipam_releases))
print "<li role='presentation'>				  <a href='' data-target='releaselog'>Release log</a></li>";
if (VERSION_VISIBLE > $version)
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
	$prerelease = !is_numeric(str_replace(array("Version", "."), "", $r->title)) ? "<span class='label label-danger'>Prerelease</span>" : "";

	// title
	print "<h5><i class='fa fa-angle-double-right'></i> $r->title $prerelease</h5>";
	// date
	print "<div style='padding-left:20px;margin-bottom:20px;'>";
	print "<span class='text-muted'>Released on ".date("Y-M-d", strtotime($r->updated))."</span> ";
	print "<div style='padding-top:10px;'>$r->content</div>";
	// tag
	print "<a class='btn btn-xs btn-default' href='".$r->link->{'@attributes'}->href."'>Download (GitHub)</a>";
	print "</div>";
}
print "</div>";
}

# commit log for devel
if (VERSION_VISIBLE > $version) {

	print "<div class='log-print gitlog' style='display:none'>";

	# check
	$commit_log = shell_exec("git log -n100");

	if (!is_string($commit_log)) {
		$Result->show("info", "Git not available", false);
	}
	else {
		// title
		print "<h4 style='margin-top:40px;'>Commit log (local) [Last 100]</h4><hr>";
		// split commits
		$commit_log = preg_split('/\r?\ncommit /', "\n".$commit_log, null, PREG_SPLIT_NO_EMPTY);

		// loop
		foreach ($commit_log as $commit) {
			// lines to array
			$lines = explode("\n", $commit);
			// commit
			unset($out);
			$out['commit'] = $lines[0];
			// remove unneeded
			foreach ($lines as $k=>$l) {
				if     (strpos($l, "Author: ")===0)	{ $out['author'] = substr($l, 7);	unset($lines[$k]); }
				elseif (strpos($l, "Date: ")===0)	{ $out['date'] = substr($l, 7);     unset($lines[$k]); }
				elseif (strpos($l, "Merge: ")===0)	{ $out['pr'] = $l;	unset($lines[$k]); }
				elseif (strlen(trim($l))==0)		{ unset($lines[$k]); }
				unset($lines[0]);
			}
			// merge
			foreach ($lines as $i => $line)
				$lines[$i] = escape_input($line);
			$lines = implode("<br>", array_filter($lines));

			// title
			print "<h5><i class='fa fa-angle-double-right'></i> ".escape_input($out['commit'])."</h5>";
			// date
			print "<div style='padding-left:20px;margin-bottom:20px;'>";
			print escape_input($out['author'])." <span class='text-muted'>(pushed on ".escape_input($out['date']).")</span>";
			print "<div style='padding:10px;max-width:400px;border-radius:6px;border:1px solid #ddd;'>".$lines."</div>";
			// tag
			print "<a class='btn btn-xs btn-default' style='margin-top:3px;' href='https://github.com/phpipam/phpipam/commit/$out[commit]' target='_blank'>View</a>";
			print "</div>";
		}
	}

	print "</div>";
}