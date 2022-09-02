<div class="container">

<div id='dashboard' class="tools-all">
<div class="row">

<?php
# print
foreach($tools_menu as $k=>$tool) {

	# only if some - permissions
	if (sizeof($tool)>0) {
		# headers
		print "<h4>".$k."</h4>";
		print "<hr>";

		# items
		foreach($tool as $t) {
			$href = explode("/", $t['href']);
			print "	<div class='col-xs-12 col-md-6 col-lg-6 widget-dash'>";
			print "	<div class='inner thumbnail'>";
			print "		<div class='hContent'>";
			if ($href[0]=="autodb") {
				print "			<div class='icon'><a href='/".$href[0]."/index.php?page=".$href[1]."&section=".$href[2]."'><i class='fa $t[icon]'></i></a></div>";
				print "			<div class='text'><a href='/".$href[0]."/index.php?page=".$href[1]."&section=".$href[2]."'>"._($t['name'])."</a><hr><span class='text-muted'>"._($t['description'])."</span></div>";
			}
			elseif(sizeof($href)>0) {
				if(isset($href[1])){
					print "			<div class='icon'><a href='".create_link("tools", $href[0], $href[1])."'><i class='fa $t[icon]'></i></a></div>";
					print "			<div class='text'><a href='".create_link("tools", $href[0], $href[1])."'>"._($t['name'])."</a><hr><span class='text-muted'>"._($t['description'])."</span></div>";
				}
				else {
					print "			<div class='icon'><a href='".create_link("tools", $href[0])."'><i class='fa $t[icon]'></i></a></div>";
					print "			<div class='text'><a href='".create_link("tools", $href[0])."'>"._($t['name'])."</a><hr><span class='text-muted'>"._($t['description'])."</span></div>";
				}
			}
			else {
					print "			<div class='icon'><a href='".create_link("tools",$t['href'])."'><i class='fa $t[icon]'></i></a></div>";
					print "			<div class='text'><a href='".create_link("tools",$t['href'])."'>"._($t['name'])."</a><hr><span class='text-muted'>"._($t['description'])."</span></div>";
			}			
			print "		</div>";
			print "	</div>";
			print "	</div>";
		}
	}

	# clear and break
	print "<div class='clearfix'></div>";
}
?>
</div>
</div>
</div>