<div class="container">

<div id='dashboard' class="tools-all">
<div class="row">

<?php
# print
foreach($tools_menu as $k=>$tool) {

	# headers
	print "<h4>"._($k)."</h4>";
	print "<hr>";

	# items
	foreach($tool as $t) {
		print "	<div class='col-xs-12 col-md-6 col-lg-6 widget-dash'>";
		print "	<div class='inner thumbnail'>";
		print "		<div class='hContent'>";
		print "			<div class='icon'><a href='".create_link("tools",$t['href'])."'><i class='fa $t[icon]'></i></a></div>";
		print "			<div class='text'><a href='".create_link("tools",$t['href'])."'>"._($t['name'])."</a><hr><span class='text-muted'>"._($t['description'])."</span></div>";
		print "		</div>";
		print "	</div>";
		print "	</div>";
	}

	# clear and break
	print "<div class='clearfix'></div>";
}
?>
</div>
</div>
</div>