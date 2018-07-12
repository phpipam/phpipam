<?php
# verify that user is logged in
$User->check_user_session();
?>

<!-- sections -->
<ul class="nav navbar-nav sections">

	<?php
	# if section is not set
	if(!isset($_GET['section'])) { $_GET['section'] = ""; }

	# first item - subnets or admin
    print "<li class='first-item'>";
    print " <a href='".create_link("subnets")."'><i class='fa fa-angle-right'></i> "._('Subnets')."</a>";
    print "</li>";

	# printout
	if($sections!==false) {
		# loop
		foreach($sections as $section) {
			# check permissions for user
			$perm = $Sections->check_permission ($User->user, $section->id);
			if($perm > 0 ) {
				# print only masters!
				if($section->masterSection=="0" || empty($section->masterSection)) {

					# check if has slaves
					unset($sves);
					foreach($sections as $s) {
						if($s->masterSection==$section->id) { $sves[$s->id] = $s; }
					}

					# slaves?
					if(isset($sves)) {

						print "<li class='dropdown'>";

						print " <a class='dropdown-toggle' data-toggle='dropdown'>$section->name<b class='caret' style='maring-top:0px;margin-left:5px;'></b></a>";
						print "	<ul class='dropdown-menu tools'>";

						# section
						if($_GET['section']==$section->id)		{ print "<li class='active'><a href='".create_link("subnets",$section->id)."'>$section->name</a></li>"; }
						else									{ print "<li>				<a href='".create_link("subnets",$section->id)."'>$section->name</a></li>"; }

						print "	<li class='divider'></li>";

						# subsections
						foreach($sves as $sl) {
							if($_GET['section']==$sl->id) 		{ print "<li class='active'><a href='".create_link("subnets",$sl->id)."'><i class='fa fa-angle-right'></i> $sl->name</a></li>"; }
							else								{ print "<li>				<a href='".create_link("subnets",$sl->id)."'><i class='fa fa-angle-right'></i> $sl->name</a></li>"; }
						}

						print "	</ul>";
						print "</li>";
					}
					# no slaves
					else {
						if( ($section->name == $_GET['section']) || ($section->id == $_GET['section']) ) 	{ print "<li class='active'>"; }
						else 																				{ print "<li>"; }

						print "	<a href='".create_link("subnets",$section->id)."' rel='tooltip' data-placement='bottom' title='$section->description'>$section->name</a>";
						print "</li>";
					}
				}
			}
		}
	}
	else {
		print "<div class='text-muted'>"._("No sections available!")."</div>";
	}

	?>
</ul>
