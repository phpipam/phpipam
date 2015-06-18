/**
 * Javascript / jQuery install functions
 */


$(document).ready(function() {

/* hide error div if jquery loads ok
*********************************************/
$('div.jqueryError').hide();
$('div.loading').hide();


/* show advanced options */
$('#toggle-advanced').on('click', function() {
	$("div.loading").fadeIn("fast");
	$("#advanced").slideToggle("fast");
	$("div.loading").fadeOut("fast");

	return false;
});

/* install database */
$(document).on("click", "a.install", function() {
	$("div.loading").fadeIn("fast");
	var postData = $("#install").serialize();
	$.post("app/install/install-execute.php", postData, function(data) {
		$("div.upgradeResult").html(data).slideDown("fast");
		$("div.loading").fadeOut("fast");
	});
});
$(document).on("click", "div.error", function() {
	$(this).stop(true,true).show();
});


/* postinstallation settings */
$('#postinstall').submit(function() {
	$("div.loading").fadeIn("fast");
	var postData = $(this).serialize();
	$.post("app/install/postinstall_submit.php", postData, function(data) {
		$("div.postinstallresult").html(data).slideDown("fast");
		$("div.loading").fadeOut("fast");
	});
	return false;
});





/* database upgrade */
$("#manualUpgrade").click(function() {
	$('#manualShow').slideToggle("fast");
	return false;
});
$(document).on("click", "input.upgrade", function() {
	$(this).removeClass("upgrade");
	$("div.loading").fadeIn("fast");
	var version = $(this).attr("version");
	$.post("app/upgrade/upgrade-execute.php", {version:version}, function(data) {
		$("div#upgradeResult").html(data).slideDown("fast");
		$("div.loading").fadeOut("fast");
	});
});
$(document).on("click", "div.error", function() {
	$(this).stop(true,true).show();
});


});


