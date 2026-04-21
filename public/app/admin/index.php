<div class="container">

<div id='dashboard' class="tools-all">
<div class="row">

<div class="col-lg-6 col-xs-12"></div>
<div class="col-lg-6 col-xs-12" style="padding:5px 20px;">
    <input type="text" class="form-control tools-filter-string" placeholder="<?php print _("Filter string"); ?>">
</div>
</div>

<?php
# print
foreach($admin_menu as $k=>$menu) {

	# headers
	print "<h4>".$k."</h4>";
	print "<hr class='hr-h4'>";

	# items
	foreach($menu as $t) {
		print "	<div class='col-xs-12 col-md-6 col-lg-6 widget-dash'>";
		print "	<div class='inner thumbnail'>";
		print "		<div class='hContent'>";
		print "			<div class='icon'><a href='".create_link("administration",$t['href'])."'><i class='fa $t[icon]'></i></a></div>";
		print "			<div class='text'><a href='".create_link("administration",$t['href'])."'>".$t['name']."</a><hr><span class='text-muted'>".$t['description']."</span></div>";
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

<script type="text/javascript">
$(document).ready(function () {
	// focus search on load
	$('.tools-filter-string').focus();

	// start search
	$('.tools-filter-string').keyup(function (e) {
		doFilter()
	});
	// focus out filter
	$('.tools-filter-string').focusout(function () {
		doFilter()
	});

	// function
	function doFilter () {
	    var searchStringTools = $('.tools-filter-string').val().toLowerCase();

	    // if null show all
	    if(searchStringTools == "") {
	        $('.widget-dash').show()
	        $('.tools-all h4').show()
	        $('.tools-all .hr-h4').show()
	        return
	    }

	    // hide all titles
	    $('.tools-all h4').hide();
	    $('.tools-all .hr-h4').hide();

	    // search
	    $( ".widget-dash" ).each(function( index ) {
	        var content = $(this).text().toLowerCase();
	        // show or hide
	        if(content.search(searchStringTools)===-1)  { $(this).hide() }
	        else                                        { $(this).show() }
	    });
	}

})
</script>