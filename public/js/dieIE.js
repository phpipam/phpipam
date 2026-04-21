/**
 *
 * Die IF IE 6 and 7
 *
 *
 */

$(document).ready(function () {

//set text
var html;
html  = "<strong>phpIPAM only works on newer browsers!</strong><br>Please use at least IE9, IE10 is recommended (if you have to use IE :/)<hr>You can get browsers here:";
html += "<ul>";
html += "<li><a href='https://www.google.com/intl/en/chrome/browser/' alt='chrome' target='self'>Google chrome</a></li>";
html += "<li><a href='http://www.mozilla.org/en-US/firefox/new/' alt='chrome' target='self'>Firefox</a></li>";
html += "<li><a href='http://www.apple.com/safari/' alt='chrome' target='self'>Safari</a></li>";

html += "</ul>";

$('body').css('overflow','hidden');
$('div.jqueryError').addClass('dieIE').html('<div class="alert alert-danger">'+html+'</div>').show();

return false;
});