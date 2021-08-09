
/**
 *
 * Javascript / jQuery functions
 *
 *
 */
$(document).ready(function () {

/* @general functions */

/*loading spinner functions */
function showSpinner() { $('div.loading').show(); }
function hideSpinner() { $('div.loading').fadeOut('fast'); }

/* escape hide popups */
$(document).keydown(function(e) {
    if(e.keyCode === 27) {
         if($("#popupOverlay2").is(":visible")) {
            hidePopup2 ();
         }
         else {
            hidePopup1 ();
         }
    }
});

// no enter in sortfields
$(document).on("submit", ".searchFormClass", function() {
    return false;
});

$('.show_popover').popover();


/* this functions opens popup */
/* -------------------------- */
function open_popup (popup_class, target_script, post_data, secondary) {
	// class
	secondary = typeof secondary !== 'undefined' ? secondary : false;
	// show spinner
	showSpinner();
	// post
    $.post(target_script, post_data, function(data) {
        showPopup('popup_w'+popup_class, data, secondary);
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText+"<br>Status: "+textStatus+"<br>Error: "+errorThrown); });
    // prevent reload
    return false;
}

/* this functions saves popup result */
/* --------------------------------- */
function submit_popup_data (result_div, target_script, post_data, reload) {
	// show spinner
	showSpinner();
	// set reload
	reload = typeof reload !== 'undefined' ? reload : true;
	// post
    $.post(target_script, post_data, function(data) {
        $('div'+result_div).html(data).slideDown('fast');
        //reload after 2 seconds if succeeded!
        if(reload) {
	        if(data.search("alert-danger")==-1 && data.search("error")==-1 && data.search("alert-warning")==-1 )	{ setTimeout(function (){window.location.reload();}, 1500); }
	        else                               		  																{ hideSpinner(); }
        }
        else {
	        hideSpinner();
        }
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    // prevent reload
    return false;
}

/* reload window function for ajax error checking */
function reload_window (data) {
	if(	data.search("alert-danger")==-1 &&
		data.search("error")==-1 &&
		data.search("alert-warning") == -1 )    { setTimeout(function (){window.location.reload();}, 1500); }
	else                               		  	{ hideSpinner(); }
}

/* tooltips */
if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }

/* hide error div if jquery loads ok
*********************************************/
$('div.jqueryError').hide();

/* Show / hide JS error */
function showError(errorText) {
	$('div.jqueryError').fadeIn('fast');
	if(errorText.length>0)  { $('.jqueryErrorText').html(errorText).show(); }
	hideSpinner();
}
function hideError() {
	$('.jqueryErrorText').html();
	$('div.jqueryError').fadeOut('fast');
}
//hide error popup
$(document).on("click", "#hideError", function() {
	hideError();	return false;
});
//disabled links
$('.disabled a').click(function() {	return false;
});

/* tooltip hiding fix */
function hideTooltips() { $('.tooltip').hide(); }

/* popups */
function showPopup(pClass, data, secondary) {
	showSpinner();
	// secondary - load secondary popupoverlay
	if (secondary === true) { var oclass = "#popupOverlay2";}
	else 					{ var oclass = "#popupOverlay"; }
	// show overlay
    $(oclass).fadeIn('fast');
    // load data and show it
    if (data!==false && typeof(data)!=="undefined") {
    $(oclass+' .'+pClass).html(data);
    }
	// malaiam: Weird popup_max bug loads same content in both popupOverlay and popupOverlay2, duplicating forms and URL parameter, messing things up, so we delete it
	if (secondary != true) { $('#popupOverlay2 > div').empty(); }
    $(oclass+' .'+pClass).fadeIn('fast');
    //disable page scrolling on bottom
    $('body').addClass('stop-scrolling');
    // resize
    resize_pContent ()
}
function hidePopup(pClass, secondary) {
	// secondary - load secondary popupoverlay
	if (secondary === true) { var oclass = "#popupOverlay2";}
	else 					{ var oclass = "#popupOverlay"; }
	// hide
    $(oclass+' .'+pClass).fadeOut('fast');
	// IMPORTANT: also empty loaded content to avoid issues on popup reopening
	$(oclass+' > div').empty();
    $('body').removeClass('stop-scrolling');        //enable scrolling back
}
function hidePopups() {
    $('#popupOverlay').fadeOut('fast');
    $('#popupOverlay2').fadeOut('fast');

	// IMPORTANT: also empty loaded content to avoid issues on popup reopening
	$('#popupOverlay > div').empty();
	$('#popupOverlay2 > div').empty();

    $('.popup').fadeOut('fast');
    $('body').removeClass('stop-scrolling');        //enable scrolling back
    hideSpinner();
}
function hidePopup1() {
    $('#popupOverlay').fadeOut('fast');
    $('#popupOverlay .popup').fadeOut('fast');
    // IMPORTANT: also empty loaded content to avoid issues on popup reopening
    $('#popupOverlay > div').empty();
    hideSpinner();
    $('body').removeClass('stop-scrolling');        //enable scrolling back
}
function hidePopup2() {
    $('#popupOverlay2').fadeOut('fast');
    $('#popupOverlay2 .popup').fadeOut('fast');
	// IMPORTANT: also empty loaded content to avoid issues on popup reopening
	$('#popupOverlay2 > div').empty();
    hideSpinner();
    $('body').removeClass('stop-scrolling');        //enable scrolling back
}
function hidePopupMasks() {
    $('.popup_wmasks').fadeOut('fast');
    hideSpinner();
}
$(document).on("click", ".hidePopups", function() {hidePopups(); });
$(document).on("click", ".hidePopup2", function() { hidePopup2(); });
$(document).on("click", ".hidePopupMasks", function() { hidePopupMasks(); });
$(document).on("click", ".hidePopupsReload", function() { window.location.reload(); });

//prevent loading for disabled buttons
$('a.disabled, button.disabled').click(function() { return false; });

//fix for menus on ipad
$('body').on('touchstart.dropdown', '.dropdown-menu', function (e) { e.stopPropagation(); });

/*    generate random password */
function randomPass() {
    var chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890#_-![]=~";
    var pass = "";
    var x;
    var i;
    for(x=0; x<15; x++) {
        i = Math.floor(Math.random() * 70);
        pass += chars.charAt(i);
    }
    return pass;
}

// on load
resize_pContent ()
// on resize
$(window).resize(function () {
    resize_pContent ()
});

function resize_pContent () {
    if($(".popup .pContent").is(":visible")) {
        var myheight = $(window).height() - 250;
        $(".popup .pContent").css('max-height', myheight);
    }
}

/* remove self on click */
$(document).on("click", ".selfDestruct", function() {
	$(this).parent('div').fadeOut('fast');
});


/* @cookies */
function createCookie(name,value,days) {
    var date;
    var expires;

    if (typeof days !== 'undefined') {
        date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        expires = "; expires="+date.toGMTString();
    }
    else {
        var expires = "";
    }

    document.cookie = name+"="+value+expires+"; path=/";
}
function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

/* draggeable elements */
$(function() {
	$(".popup").draggable({ handle: ".pHeader" });
});

// bootstrap-table
$('table.sorted-new')
                 .attr("data-toggle", "table")
                 .attr('data-pagination', 'true')
                 .attr('data-page-size', '250')
                 .attr('data-page-list', '[50,100,250,500,All]')
                 .attr('data-search','true')
                 .attr('data-classes','table-no-bordered')
                 .attr('data-icon-size','sm')
                 .attr('data-show-footer','false')
                 .attr('data-show-columns','true')
                 .attr('data-icons-prefix','fa')
                 .attr('data-icons','icons')
                 .attr('data-cookie','true')
                 .attr('data-sortable', 'false')

$('table.sorted')
                 .attr("data-toggle", "table")
                 .attr('data-pagination', 'true')
                 .attr('data-page-size', '250')
                 .attr('data-page-list', '[50,100,250,500,All]')
                 .attr('data-search','true')
                 .attr('data-classes','table-no-bordered')
                 .attr('data-icon-size','sm')
                 .attr('data-show-footer','false')
                 .attr('data-show-columns','true')
                 .attr('data-icons-prefix','fa')
                 .attr('data-icons','icons')
                 .attr('data-cookie', 'true')
                 .attr('data-sortable', 'false')
                 .attr('onlyInfoPagination', 'true')
                 .attr('smartDisplay', true)
                 .attr('showPaginationSwitch', true)
                 .attr('minimumCountColumns', true)

$('table.nosearch')
                 .attr('data-search','false')
                 .attr('data-show-columns','false')

$('table.nopagination')
                 .attr('data-pagination', 'false')

$('table.sortable')
                 .attr('data-sortable', 'true')

 $('table.25tall')
                 .attr('data-page-size', '25')
                 .attr('data-page-list', '[25,50,100,250,500,All]')

// tooltips, popovers
$('table.sorted').on('all.bs.table', function () {
    if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }
    $('.show_popover').popover();
})


// icons - bootstrap table
window.icons = {
    refresh : 'fa-refresh',
    toggle  : 'fa-toggle-on',
    columns : 'fa-th-list'
};


$("li.disabled a").click(function () {
   return false;
});
$('form.search-form').submit(function() {
   return false;
});





/**
 * Generic open popup scripts
 *
 * Generic function to open popup and provide data via POST attributes
 *
 * Attributes are:
 *     - data-script: script to load in popup
 *     - data-class: popup class/size (400, 700, ...)
 *     - data-secondary: open secondary popup
 *     - data-* : all data- parameters will be passed as POST params to requested script
 *
 * @return void
 */
$(document).on("click", ".open_popup", function () {
    // defaults
    var post_data     = {};
    var secondary     = false;
    var popup_class   = "400";
    var target_script = "";
    // get all data- attributes
    $.each(this.attributes, function() {
        // script
        if(this.name == "data-script") {
            target_script = this.value;
        }
        // class
        else if(this.name == "data-class") {
            popup_class = this.value;
        }
        // secondary
        else if(this.name == "data-secondary") {
            secondary = true;
        }
        // parameters
        else if(this.name.indexOf("data-") !== -1) {
            post_data[this.name.replace("data-", "")] = this.value;
        }
    });
    // checks
    if(target_script == "") {
        showError("Error: Missing target_script");
    }
    // load popup
    else {
        open_popup (popup_class, target_script, post_data, secondary);
    }
    // no reload
    return false;
});


/**
 * Generic submit popup script
 *
 * It will POST data from provided script and attributes to target
 * script and display it in target div
 *
 *
 */
$(document).on("click", ".submit_popup", function () {
    // defaults
    var post_data     = {};
    var reload        = true;
    var result_div    = "";
    var target_script = "";
    // get all data- attributes
    $.each(this.attributes, function() {
        // script
        if(this.name == "data-script") {
            target_script = this.value;
        }
        // class
        else if(this.name == "data-result_div") {
            result_div = "#"+this.value;
        }
        // secondary
        else if(this.name == "data-noreload") {
            reload = false;
        }
        // get form parameters
        else if(this.name == "data-form") {
            post_data = $('form#'+this.value).serialize ()
        }
    });
    // checks
    if(target_script == "") {
        showError("Error: Missing target_script")
    }
    else if (result_div == "") {
        showError("Error: Missing result div parameter")
    }
    // load popup
    else {
        submit_popup_data (result_div, target_script, post_data, reload)
    }
    // no reload
    return false;
});





/* @dashboard widgets ----------  */

//if dashboard show widgets
if($('#dashboard').length>0) {
	//get all boxes
	$('div[id^="w-"]').each(function(){
		var w = $(this).attr('id');
		//remove w-
		w = w.replace("w-", "");
		$.post('app/dashboard/widgets/'+w+'.php', function(data) {
			$("#w-"+w+' .hContent').html(data);
		}).fail(function(xhr, textStatus, errorThrown) {
            $.post('app/dashboard/widgets/custom/'+w+'.php', function(data) {
                $("#w-"+w+' .hContent').html(data);
            }).fail(function(xhr, textStatus, errorThrown) {
    			$("#w-"+w+' .hContent').html('<blockquote style="margin-top:20px;margin-left:20px;">File not found!</blockquote>');
            })
		});
	});
}
//remove item
$(document).on('click', "i.remove-widget", function() {
	$(this).parent().parent().fadeOut('fast').remove();
});
//add new widget form popup
$(document).on('click', '#sortablePopup li a.widget-add', function() {
	var wid   = $(this).attr('id');
	var wsize = $(this).attr('data-size');
	var wtitle= $(this).attr('data-htitle');
	//create
	var data = '<div class="row-fluid"><div class="span'+wsize+' widget-dash" id="'+wid+'"><div class="inner movable"><h4>'+wtitle+'</h4><div class="hContent"></div></div></div></div>';
	$('#dashboard').append(data);
	//load
	w = wid.replace("w-", "");
	$.post('app/dashboard/widgets/'+w+'.php', function(data) {
		$("#"+wid+' .hContent').html(data);
	}).fail(function(xhr, textStatus, errorThrown) {
		$("#"+wid+' .hContent').html('<blockquote style="margin-top:20px;margin-left:20px;">File not found!</blockquote>');
	});
	//remove item
	$(this).parent().fadeOut('fast');	return false;
});



/* @subnets list ----------  */

/* leftmenu toggle submenus */
// default hide
$('ul.submenu.submenu-close').hide();
// left menu folder delay tooltip
$('.icon-folder-close,.icon-folder-show, .icon-search').tooltip( {
    delay: {show:2000, hide:0},
    placement:"bottom"
});
// show submenus
$('ul#subnets').on("click", ".fa-folder-close-o", function() {
    //change icon
    $(this).removeClass('fa-folder-close-o').addClass('fa-folder-open-o');
    //find next submenu and hide it
    $(this).nextAll('.submenu').slideDown('fast');
	//save cookie
    update_subnet_structure_cookie ("add", $(this).attr("data-str_id"));
});
$('ul#subnets').on("click", ".fa-folder", function() {
    //change icon
    $(this).removeClass('fa-folder').addClass('fa-folder-open');
    //find next submenu and hide it
    $(this).nextAll('.submenu').slideDown('fast');
	//save cookie
    update_subnet_structure_cookie ("add", $(this).attr("data-str_id"));
});
// hide submenus
$('ul#subnets').on("click", ".fa-folder-open-o", function() {
    //change icon
    $(this).removeClass('fa-folder-open-o').addClass('fa-folder-close-o');
    //find next submenu and hide it
    $(this).nextAll('.submenu').slideUp('fast');
	//save cookie
    update_subnet_structure_cookie ("remove", $(this).attr("data-str_id"));
});
$('ul#subnets').on("click", ".fa-folder-open", function() {
    //change icon
    $(this).removeClass('fa-folder-open').addClass('fa-folder');
    //find next submenu and hide it
    $(this).nextAll('.submenu').slideUp('fast');
	//save cookie
    update_subnet_structure_cookie ("remove", $(this).attr("data-str_id"));
});


/* Function to save subnets structure left menu to cookie */
function update_subnet_structure_cookie (action, cid) {
	// read old cookie
	var s_cookie = readCookie("sstr");
	// defualt - if empty
 	if(typeof s_cookie === 'undefined' || s_cookie==null || s_cookie.length===0)	s_cookie = "|";
	// add or replace
	if (action == "add") {
		// split to array and check if it already exists
		var arr = s_cookie.split('|');
		var exists = false;
		for(var i=0;i < arr.length;i++) {
        	if(arr[i]==cid) {
	     		exists = true;
        }	}
        // new
        if(exists==false)	s_cookie += cid+"|";
	}
	else if (action == "remove")	{
		s_cookie = s_cookie.replace("|"+cid+"|", "|");
	}
	// save cookie
	createCookie("sstr",s_cookie, 365);
}

//expand/contract all
$('#expandfolders').click(function() {
    // get action
    var action = $(this).attr('data-action');
    //open
    if(action == 'close') {
        $('.subnets ul#subnets li.folder > i').removeClass('fa-folder-close-o').addClass('fa-folder-open-o');
        $('.subnets ul#subnets li.folderF > i').removeClass('fa-folder').addClass('fa-folder-open');
        $('.subnets ul#subnets ul.submenu').removeClass('submenu-close').addClass('submenu-open').slideDown('fast');
        $(this).attr('data-action','open');
        createCookie('expandfolders','1','365');
        $(this).removeClass('fa-expand').addClass('fa-compress');
    }
    else {
        $('.subnets ul#subnets li.folder > i').addClass('fa-folder-close-o').removeClass('fa-folder-open-o');
        $('.subnets ul#subnets li.folderF > i').addClass('fa-folder').removeClass('fa-folder-open');
        $('.subnets ul#subnets ul.submenu').addClass('submenu-close').removeClass('submenu-open').slideUp('fast');
        $(this).attr('data-action','close');
        createCookie('expandfolders','0','365');
        $(this).removeClass('fa-compress').addClass('fa-expand');
    }
});










/* @ipaddress list ---------- */


/*    add / edit / delete IP address
****************************************/
//show form
$(document).on("click", ".modIPaddr", function() {
    showSpinner();
    var action    = $(this).attr('data-action');
    var id        = $(this).attr('data-id');
    var subnetId  = $(this).attr('data-subnetId');
    var stopIP    = $(this).attr('data-stopIP');
    //format posted values
    var postdata = "action="+action+"&id="+id+"&subnetId="+subnetId+"&stopIP="+stopIP;
    $.post('app/subnets/addresses/address-modify.php', postdata, function(data) {
        $('#popupOverlay div.popup_w500').html(data);
        showPopup('popup_w500');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
//move orphaned IP address
$(document).on("click", "a.moveIPaddr", function() {
    showSpinner();
    var action      = $(this).attr('data-action');
    var id        = $(this).attr('data-id');
    var subnetId  = $(this).attr('data-subnetId');
    //format posted values
    var postdata = "action="+action+"&id="+id+"&subnetId="+subnetId;
    $.post('app/subnets/addresses/move-address.php', postdata, function(data) {
        $('#popupOverlay div.popup_w400').html(data);
        showPopup('popup_w400');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
//resolve DNS name
$(document).on("click", "#refreshHostname", function() {
    showSpinner();
    var ipaddress = $('input.ip_addr').val();
    var subnetId  = $(this).attr('data-subnetId');;
    $.post('app/subnets/addresses/address-resolve.php', {ipaddress:ipaddress, subnetId: subnetId}, function(data) {
        if(data.length !== 0) {
            $('input[name=hostname]').val(data);
        }
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
});
//submit ip address change
$(document).on("click", "button#editIPAddressSubmit, .editIPSubmitDelete", function() {
    //show spinner
    showSpinner();
    var postdata = $('form.editipaddress').serialize();

    //append deleteconfirm
	if($(this).attr('id') == "editIPSubmitDelete") { postdata += "&deleteconfirm=yes&action=delete"; }
    //replace delete if from visual
    if($(this).attr('data-action') == "all-delete" ) { postdata += '&action-visual=delete';}

    $.post('app/subnets/addresses/address-modify-submit.php', postdata, function(data) {
        $('div.addnew_check').html(data);
        $('div.addnew_check').slideDown('fast');
        //reload after 2 seconds if succeeded!
        reload_window (data);
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
//ping check
$(document).on("click", ".ping_ipaddress", function() {
	showSpinner();
	var id       = $(this).attr('data-id');
	var subnetId = $(this).attr('data-subnetId');
	// new ip?
	if ($(this).hasClass("ping_ipaddress_new")) { id = $("input[name=ip_addr]").val(); }
	//check
	$.post('app/subnets/addresses/ping-address.php', {id:id, subnetId:subnetId}, function(data) {
        $('#popupOverlay2 div.popup_w400').html(data);
        showPopup('popup_w400', false, true);
		hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });	return false;
});


/*    send notification mail
********************************/
//show form
$(document).on("click", "a.mail_ipaddress", function() {
    //get IP address id
    var IPid = $(this).attr('data-id');
    $.post('app/subnets/addresses/mail-notify.php', { id:IPid }, function(data) {
        $('#popupOverlay div.popup_w700').html(data);
        showPopup('popup_w700');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
//send mail with IP details!
$(document).on("click", "#mailIPAddressSubmit", function() {
    showSpinner();
    var mailData = $('form#mailNotify').serialize();
    //post to check script
    $.post('app/subnets/addresses/mail-notify-check.php', mailData, function(data) {
        $('div.sendmail_check').html(data).slideDown('fast');
        //reload after 2 seconds if succeeded!
        reload_window (data);
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
/*    send notification mail - subnet
********************************/
//show form
$(document).on("click", "a.mail_subnet", function() {
    //get IP address id
    var id = $(this).attr('data-id');
    $.post('app/subnets/mail-notify-subnet.php', { id:id }, function(data) {
        $('#popupOverlay div.popup_w700').html(data);
        showPopup('popup_w700');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
//send mail with IP details!
$(document).on("click", "#mailSubnetSubmit", function() {
    showSpinner();
    var mailData = $('form#mailNotifySubnet').serialize();
    //post to check script
    $.post('app/subnets/mail-notify-subnet-check.php', mailData, function(data) {
        $('div.sendmail_check').html(data).slideDown('fast');
        //reload after 2 seconds if succeeded!
        reload_window (data);
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});


/*    scan subnet
*************************/
//open popup
$('a.scan_subnet').click(function() {
	showSpinner();
	var subnetId = $(this).attr('data-subnetId');
	$.post('app/subnets/scan/subnet-scan.php', {subnetId:subnetId}, function(data) {
        $('#popupOverlay div.popup_wmasks').html(data);
        showPopup('popup_wmasks');
		hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });	return false;
});
//show telnet port
$(document).on('change', "table.table-scan select#type", function() {
	var pingType = $('select[name=type]').find(":selected").val();
	if(pingType=="scan-telnet") { $('tbody#telnetPorts').show(); }
	else 						{ $('tbody#telnetPorts').hide(); }
});
//save value to cookie
$(document).on('change', "table.table-scan select#type", function() {
    var sel = ($(this).find(":selected").val());
    createCookie("scantype",sel,32);
});

//start scanning
$(document).on('click','#subnetScanSubmit', function() {
	showSpinner();
	$('#subnetScanResult').slideUp('fast');
	var subnetId = $(this).attr('data-subnetId');
	var csrf     = $(this).attr('data-csrf-cookie');
	var type 	 = $('select[name=type]').find(":selected").val();
	if($('input[name=debug]').is(':checked'))	{ var debug = 1; }
	else										{ var debug = 0; }
	var port     = $('input[name=telnetports]').val();
	$('#alert-scan').slideUp('fast');
	$.post('app/subnets/scan/subnet-scan-execute.php', {subnetId:subnetId, type:type, debug:debug, port:port, csrf_cookie:csrf}, function(data) {
        $('#subnetScanResult').html(data).slideDown('fast');
		hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });	return false;
});
//remove result
$(document).on('click', '.resultRemove', function() {
	// if MAC table show IP that is hidden
	if ($(this).hasClass('resultRemoveMac')) {
    	// if this one is hidden dont show ip for next
    	if ($(this).parent().parent().find('span.ip-address').hasClass('hidden')) {

    	}
    	// else show
        else {
            $(this).parent().parent().next().find('span.ip-address').removeClass('hidden');
        }
	}
    // get target
	var target = $(this).attr('data-target');
	$('tr.'+target).remove();	return false;
});
//submit scanning result
$(document).on('click', 'a#saveScanResults', function() {
	showSpinner();
	var script   = $(this).attr('data-script');
	var subnetId = $(this).attr('data-subnetId');
	var postData = "type="+script;
	var postData = postData+"&subnetId="+subnetId;
	var postData = postData+"&"+$('form.'+script+"-form").serialize();
	var postData = postData+"&canary=true";

	$.post('app/subnets/scan/subnet-scan-result.php', postData, function(data) {
        $('#subnetScanAddResult').html(data);
        //hide if success!
        //reload after 2 seconds if succeeded!
        reload_window (data);
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });	return false;
});



/*    import IP addresses
*************************/
//load CSV import form
$('a.csvImport').click(function () {
    showSpinner();
    var subnetId = $(this).attr('data-subnetId');
    $.post('app/subnets/import-subnet/index.php', {subnetId:subnetId}, function(data) {
        $('div.popup_max').html(data);
        showPopup('popup_max');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
//display uploaded file
$(document).on("click", "input#csvimportcheck", function() {
    showSpinner();
    //get filetype
    var filetype = $('span.fname').html();
    var xlsSubnetId  = $('a.csvImport').attr('data-subnetId');
    $.post('app/subnets/import-subnet/print-file.php', { filetype:filetype, subnetId:xlsSubnetId }, function(data) {
        $('div.csvimportverify').html(data).slideDown('fast');
        hideSpinner();
        // add reload class
        $('.importFooter').removeClass("hidePopups").addClass("hidePopupsReload");
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
});
//import file script
$(document).on("click", "input#csvImportNo", function() {
    $('div.csvimportverify').hide('fast');
});
$(document).on("click", "input#csvImportYes", function() {
    showSpinner();
    //get filetype
    var filetype = $('span.fname').html();
    //ignore errors
    if($('input[name=ignoreErrors]').is(':checked'))    { var ignoreError = "1"; }
    else                                                { var ignoreError = "0"; }
    // get active subnet ID
    var xlsSubnetId  = $('a.csvImport').attr('data-subnetId');
    // Get CSRF cookie
    var csrf_cookie = $('input[name=csrf_cookie]').val();
    var postData = "subnetId=" + xlsSubnetId + "&filetype=" + filetype + "&ignoreError=" + ignoreError + "&csrf_cookie=" + csrf_cookie;

    $.post('app/subnets/import-subnet/import-file.php', postData, function(data) {
        $('div.csvImportResult').html(data).slideDown('fast');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
});
//download template
$(document).on("click", "#csvtemplate", function() {
    $("div.dl").remove();    //remove old innerDiv
    $('div.exportDIV').append("<div style='display:none' class='dl'><iframe src='app/subnets/import-subnet/import-template.php'></iframe></div>");
	return false;
});
//download vrf template
$(document).on("click", "#vrftemplate", function() {
    $("div.dl").remove();    //remove old innerDiv
    $('div.exportDIV').append("<div style='display:none' class='dl'><iframe src='app/admin/import-export/import-template.php?type=vrf'></iframe></div>");
	return false;
});

//download domain template
$(document).on("click", "#vlanstemplate", function() {
    $("div.dl").remove();    //remove old innerDiv
    $('div.exportDIV').append("<div style='display:none' class='dl'><iframe src='app/admin/import-export/import-template.php?type=vlans'></iframe></div>");
	return false;
});

//download domain template
$(document).on("click", "#l2domtemplate", function() {
    $("div.dl").remove();    //remove old innerDiv
    $('div.exportDIV').append("<div style='display:none' class='dl'><iframe src='app/admin/import-export/import-template.php?type=l2dom'></iframe></div>");
	return false;
});


//download vlan domain template
$(document).on("click", "#vlandomaintemplate", function() {
    $("div.dl").remove();    //remove old innerDiv
    $('div.exportDIV').append("<div style='display:none' class='dl'><iframe src='app/admin/import-export/import-template.php?type=vlandomain'></iframe></div>");
	return false;
});


//download subnet template
$(document).on("click", "#subnetstemplate", function() {
    $("div.dl").remove();    //remove old innerDiv
    $('div.exportDIV').append("<div style='display:none' class='dl'><iframe src='app/admin/import-export/import-template.php?type=subnets'></iframe></div>");
	return false;
});


//download ip address template
$(document).on("click", "#ipaddrtemplate", function() {
    $("div.dl").remove();    //remove old innerDiv
    $('div.exportDIV').append("<div style='display:none' class='dl'><iframe src='app/admin/import-export/import-template.php?type=ipaddr'></iframe></div>");
	return false;
});


//download device template
$(document).on("click", "#devicestemplate", function() {
    $("div.dl").remove();    //remove old innerDiv
    $('div.exportDIV').append("<div style='display:none' class='dl'><iframe src='app/admin/import-export/import-template.php?type=devices'></iframe></div>");	return false;
});


//download device types template
$(document).on("click", "#devtypetemplate", function() {
    $("div.dl").remove();    //remove old innerDiv
    $('div.exportDIV').append("<div style='display:none' class='dl'><iframe src='app/admin/import-export/import-template.php?type=devtype'></iframe></div>");
	return false;
});



/*    export IP addresses
*************************/
//show fields
$('a.csvExport').click(function() {
    showSpinner();
    var subnetId = $(this).attr('data-subnetId');
    //show select fields
    $.post('app/subnets/addresses/export-field-select.php', {subnetId:subnetId}, function(data) {
	    $('#popupOverlay div.popup_w400').html(data);
        showPopup('popup_w400');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
//export
$(document).on("click", "button#exportSubnet", function() {
    var subnetId = $('a.csvExport').attr('data-subnetId');
    //get selected fields
    var exportFields = $('form#selectExportFields').serialize();
    $("div.dl").remove();    //remove old innerDiv
    $('div.exportDIV').append("<div style='display:none' class='dl'><iframe src='app/subnets/addresses/export-subnet.php?subnetId=" + subnetId + "&" + exportFields + "'></iframe></div>");
    return false;
});


/*	add / remove favourite subnet
*********************************/
$(document).on('click', 'a.editFavourite', function() {
	var subnetId = $(this).attr('data-subnetId');
	var action   = $(this).attr('data-action');
	var from     = $(this).attr('data-from');
	var item     = $(this);

	//remove
	$.post('app/tools/favourites/favourite-edit.php', {subnetId:subnetId, action:action, from:from}, function(data) {
		//success - widget - remove item
		if(data=='success' && from=='widget') 	{
			$('tr.favSubnet-'+subnetId).addClass('error');
			$('tr.favSubnet-'+subnetId).delay(200).fadeOut();
		}
		//success - subnet - toggle star-empty
		else if (data=='success') 				{
			$(this).toggleClass('btn-info');
			$('a.favourite-'+subnetId+" i").toggleClass('fa-star-o');
			$(item).toggleClass('btn-info');
			//remove
			if(action=="remove") {
				$('a.favourite-'+subnetId).attr('data-original-title','Click to add to favourites');
				$(item).attr('data-action','add');
			}
			//add
			else {
				$('a.favourite-'+subnetId).attr('data-original-title','Click to remove from favourites');
				$(item).attr('data-action','remove');
			}
		}
		//fail
		else {
	        $('#popupOverlay div.popup_w500').html(data);
	        showPopup('popup_w500');
	        hideSpinner();
		}
	}).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });	return false;
});


/*    request IP address for non-admins if locked or viewer
*********************************************************/
//show request form
$('a.request_ipaddress').click(function () {
    showSpinner();
    var subnetId  = $(this).attr('data-subnetId');
    $.post('app/tools/request-ip/index.php', {subnetId:subnetId}, function(data) {
        $('#popupOverlay div.popup_w500').html(data);
        showPopup('popup_w500');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
//show request form from widget
$(document).on("click", "button#requestIP_widget", function() {
    showSpinner();
	var subnetId = $('select#subnetId option:selected').attr('value');
    var ip_addr = document.getElementById('ip_addr_widget').value;
    $.post('app/tools/request-ip/index.php', {subnetId:subnetId, ip_addr:ip_addr}, function(data) {
        $('div.popup_w500').html(data);
        showPopup('popup_w500');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
//auto-suggest first available IP in selected subnet
$(document).on("change", "select#subnetId", function() {
    showSpinner();
    var subnetId = $('select#subnetId option:selected').attr('value');
    //post it via json to request_ip_first_free.php
    $.post('app/login/request_ip_first_free.php', { subnetId:subnetId}, function(data) {
        $('input.ip_addr').val(data);
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
});

//submit request
$(document).on("click", "button#requestIPAddressSubmit", function() {
    showSpinner();
    var request = $('form#requestIP').serialize();
    $.post('app/login/request_ip_result.php', request, function(data) {
        $('div#requestIPresult').html(data).slideDown('fast');
        reload_window (data);
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});








/* @tools ----------- */


/* ipCalc */
//submit form
$('form#ipCalc').submit(function () {
    showSpinner();
    var ipCalcData = $(this).serialize();
    $.post('app/tools/ip-calculator/result.php', ipCalcData, function(data) {
        $('div.ipCalcResult').html(data).fadeIn('fast');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
//reset input
$('form#ipCalc input.reset').click(function () {
    $('form#ipCalc input[type="text"]').val('');
    $('div.ipCalcResult').fadeOut('fast');
});
//
$(document).on("click", "a.create_section_subnet_from_search", function() {
    //get details - we need Section, network and subnet bitmask
    var sectionId = $(this).attr('data-sectionId')
    var subnet    = $(this).attr('data-subnet')
    var bitmask   = $(this).attr('data-bitmask')

    // formulate postdata
    var postdata  = "sectionId=" + sectionId + "&subnet=" + subnet + "&bitmask=" + bitmask + "&action=add&location=ipcalc";

    //load add Subnet form / popup
    $.post('app/admin/subnets/edit.php', postdata , function(data) {
        $('#popupOverlay div.popup_w700').html(data);
        showPopup('popup_w700');
        hideSpinner();
    });

    return false;
})

/* search function */
function search_execute (loc) {
    showSpinner();
    // location based params
    if (loc=="topmenu") {
        var ip = $('.searchInput').val();
        var form_name = "searchSelect";
    }
    else {
        var ip = $('form#search .search').val();
        var form_name = "search";
    }
    ip = ip.replace(/\//g, "%252F");
    // parameters
    var addresses = $('#'+form_name+' input[name=addresses]').is(":checked") ? "on" : "off";
    var subnets   = $('#'+form_name+' input[name=subnets]').is(":checked") ? "on" : "off";
    var vlans     = $('#'+form_name+' input[name=vlans]').is(":checked") ? "on" : "off";
    var vrf       = $('#'+form_name+' input[name=vrf]').is(":checked") ? "on" : "off";
    var pstn      = $('#'+form_name+' input[name=pstn]').is(":checked") ? "on" : "off";
    var circuits  = $('#'+form_name+' input[name=circuits]').is(":checked") ? "on" : "off";
    var customers = $('#'+form_name+' input[name=customers]').is(":checked") ? "on" : "off";

    // set cookie json-encoded with parameters
    createCookie("search_parameters",'{"addresses":"'+addresses+'","subnets":"'+subnets+'","vlans":"'+vlans+'","vrf":"'+vrf+'","pstn":"'+pstn+'","circuits":"'+circuits+'","customers":"'+customers+'"}',365);

    //lets try to detect IEto set location
    var ua = window.navigator.userAgent;
    var msie = ua.indexOf("MSIE ");
    var edge = ua.indexOf("Edge/");
    //IE
    if (msie > 0 || edge > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)) 	{ var base = $('.iebase').html(); }
    else 																{ var base = ""; }
    //go to search page
    var prettyLinks = $('#prettyLinks').html();
	if(prettyLinks=="Yes")	{ window.location = base + "tools/search/"+ip; }
	else					{ window.location = base + "index.php?page=tools&section=search&ip="+ip; }
}
//submit form - topmenu
$('.searchSubmit').click(function () {
    search_execute ("topmenu");
    return false;
});
//submit form - topmenu
$('form#userMenuSearch').submit(function () {
    search_execute ("topmenu");
    return false;
});
//submit search form
$('form#search').submit(function () {
    search_execute ("search");
    return false;
});
// search ipaddress override
$('a.search_ipaddress').click(function() {
    // set cookie json-encoded with parameters
    createCookie("search_parameters",'{"addresses":"on","subnets":"off","vlans":"off","vrf":"off","pstn":"off","circuits":"off","customers":"off"}',365);
});

//show/hide search select fields
$(document).on("mouseenter", "#userMenuSearch", function(event){
    var object1 = $("#searchSelect");
    object1.slideDown('fast');
});
$(document).on("mouseleave", '#user_menu', function(event){
	$(this).stop();
    var object1 = $("#searchSelect");
    object1.slideUp();
});


//search export
$(document).on("click", "#exportSearch", function(event){
    var searchTerm = $(this).attr('data-post');
    $("div.dl").remove();                                                //remove old innerDiv
    $('div.exportDIVSearch').append("<div style='display:none' class='dl'><iframe src='app/tools/search/search-results-export.php?ip=" + searchTerm + "'></iframe></div>");
    return false;
});

/* hosts */
$('#hosts').submit(function() {
    showSpinner();
    var hostname = $('input.hostsFilter').val();

    var prettyLinks = $('#prettyLinks').html();
	if(prettyLinks=="Yes")	{ window.location = base + "tools/hosts/" + hostname; }
	else					{ window.location = base + "index.php?page=tools&section=hosts&ip=" + hostname; }
    return false;
});


/* user menu selfchange */
$('form#userModSelf').submit(function () {
    var selfdata = $(this).serialize();
    $('div.userModSelfResult').hide();

    $.post('app/tools/user-menu/user-edit.php', selfdata, function(data) {
        $('div.userModSelfResult').html(data).fadeIn('fast');

        if(data.search("danger")==-1) { $('div.userModSelfResult').delay(2000).fadeOut('slow'); hideSpinner(); }
        else                          { hideSpinner(); }

    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
//    Generate random pass
$(document).on("click", "#randomPassSelf", function() {
    var password = randomPass();
    $('input.userPass').val(password);
    $('#userRandomPass').html( password );
    return false;
});

/* changelog */
//submit form
$('form#cform').submit(function () {
    showSpinner();
    var limit = $('form#cform .climit').val();
    var filter = $('form#cform .cfilter').val();
    //update search page
    var prettyLinks = $('#prettyLinks').html();
	if(prettyLinks=="Yes")	{ window.location = "tools/changelog/"+filter+"/"+limit+"/"; }
	else					{ window.location = "index.php?page=tools&section=changelog&subnetId="+filter+"&sPage="+limit; }
    return false;
});

/* changePassRequired */
$('form#changePassRequiredForm').submit(function() {
	showSpinner();
    //get csrf_cookie, old + new passwords
    var postData = $('form#changePassRequiredForm').serialize();
    $.post('app/tools/pass-change/result.php', postData, function(data) {
        $('div#changePassRequiredResult').html(data).fadeIn('fast');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
// show subnet masks popup
$(document).on("click", '.show-masks', function() {
	open_popup("masks", "app/tools/subnet-masks/popup.php", {closeClass:$(this).attr('data-closeClass')}, true);	return false;
});






/* @administration ---------- */

// clear logo
$(document).on("click", ".logo-clear", function() {
     $.post('app/admin/settings/logo/logo-clear.php', "", function(data) {
        $('div.logo-current').html(data).slideDown('fast');
        //reload after 1 second if all is ok!
        reload_window (data);
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
});

/* show/hide smtp body */
$('select#mtype').change(function() {
	var type = $(this).find(":selected").val();
	//if localhost hide, otherwise show
	if(type === "localhost") 	{ $('#mailsettingstbl tbody#smtp').hide(); }
	else 						{ $('#mailsettingstbl tbody#smtp').show(); }
});


/*    Edit users
***************************/
//disable pass if domain user
$(document).on("change", "form#usersEdit select[name=authMethod]", function() {
    //get details - we need Section, network and subnet bitmask
    var type = $("select[name=authMethod]").find(":selected").val();
    //we changed to domain
    if(type == "1") { $('tbody#user_password').show(); }
    else            { $('tbody#user_password').hide(); }
});
// toggle notificaitons for user
$(document).on("change", "form#usersEdit select[name=role]", function() {
    //get details - we need Section, network and subnet bitmask
    var type = $("form#usersEdit select[name=role]").find(":selected").val();
    //we changed to domain
    if(type == "Administrator") { $('tbody#user_notifications').show(); $('tbody.module_permissions').hide(); }
    else            			{ $('tbody#user_notifications').hide(); $('tbody.module_permissions').show(); }
});

// generate random pass
$(document).on("click", "a#randomPass", function() {
    var password = randomPass();
    $('input.userPass').val(password);
    $(this).html( password );
    return false;
});
//search domain popup
$(document).on("click", ".adsearchuser", function() {
	$('#popupOverlay2 .popup_w500').load('app/admin/users/ad-search-form.php');
    showPopup('popup_w500', false, true);
    hideSpinner();
});
//search domain user result
$(document).on("click", "#adsearchusersubmit", function() {
	showSpinner();
	var dname = $('#dusername').val();
	var server = $('#adserver').find(":selected").val();
	$.post('app/admin/users/ad-search-result.php', {dname:dname, server:server}, function(data) {
		$('div#adsearchuserresult').html(data)
		hideSpinner();
	});
});
//get user data from result
$(document).on("click", ".userselect", function() {
	var uname 	 	= $(this).attr('data-uname');
	var username 	= $(this).attr('data-username');
	var email 	 	= $(this).attr('data-email');
	var server 	 	= $(this).attr('data-server');
	var server_type = $(this).attr('data-server-type');

	//fill
	$('form#usersEdit input[name=real_name]').val(uname);
	$('form#usersEdit input[name=username]').val(username);
	$('form#usersEdit input[name=email]').val(email);
	$('form#usersEdit select[name=authMethod]').val(server);
	//hide password
	$('tbody#user_password').hide();
	//check server type and fetch group membership
	if (server_type=="AD" || server_type=="LDAP") {
		$.post('app/admin/users/ad-search-result-groups-membership.php', {server:server,username:username}, function(data) {
			//some data found
			if(data.length>0) {
				// to array and check
				var groups = data.replace(/\s/g, '');
				groups = groups.split(";");
				for (m = 0; m < groups.length; ++m) {
					$("input[name='group"+groups[m]+"']").attr('checked', "checked");
				}
			}
		});
	}
	hidePopup2();	return false;
});



/* Groups
***************************/
//search AD group popup
$(document).on("click", ".adLookup", function() {
	$('#popupOverlay div.popup_w700').load('app/admin/groups/ad-search-group-form.php');

    showPopup('popup_w700');
    hideSpinner();
});
//search AD domain groups
$(document).on("click", "#adsearchgroupsubmit", function() {
	showSpinner();
	var dfilter = $('#dfilter').val();
	var server = $('#adserver').find(":selected").val();
	$.post('app/admin/groups/ad-search-group-result.php', {dfilter:dfilter, server:server}, function(data) {
		$('div#adsearchgroupresult').html(data)
		hideSpinner();
	});
});
//search domaingroup  add
$(document).on("click", ".groupselect", function() {
	showSpinner();
	var gname = $(this).attr("data-gname");
	var gdescription = $(this).attr("data-gdescription");
	var gmembers = $(this).attr("data-members");
	var gid = $(this).attr("data-gid");
	var csrf_cookie = $(this).attr("data-csrf_cookie");

	$.post('app/admin/groups/edit-group-result.php', {action:"add", g_name:gname, g_desc:gdescription, gmembers:gmembers, csrf_cookie:csrf_cookie}, function(data) {
		$('div.adgroup-'+gid).html(data)
		hideSpinner();
	});	return false;
});



/*    instructions
***********************/
$('#instructionsForm').submit(function () {
    var csrf_cookie = $("#instructionsForm input[name=csrf_cookie]").val();
    var id = $("#instructionsForm input[name=id]").val();
	var instructions = CKEDITOR.instances.instructions.getData();
	$('div.instructionsPreview').hide('fast');

    showSpinner();
    $.post('app/admin/instructions/edit-result.php', {instructions:instructions, csrf_cookie:csrf_cookie, id:id}, function(data) {
        $('div.instructionsResult').html(data).fadeIn('fast');
        if(data.search("alert-danger")==-1 && data.search("error")==-1)     	{ $('div.instructionsResult').delay(2000).fadeOut('slow'); hideSpinner(); }
        else                             	{ hideSpinner(); }
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
$('#preview').click(function () {
    showSpinner();
    var instructions = CKEDITOR.instances.instructions.getData();

    $.post('app/admin/instructions/preview.php', {instructions:instructions, csrf_cookie:$("#instructionsForm input[name=csrf_cookie]").val()}, function(data) {
        $('div.instructionsPreview').html(data).fadeIn('fast');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});


/*    log files
************************/
//display log files - selection change
$('form#logs').change(function () {
    showSpinner();
    var logSelection = $('form#logs').serialize();
    $.post('app/tools/logs/show-logs.php', logSelection, function(data) {
        $('div.logs').html(data);
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
});
//log files show details
$(document).on("click", "a.openLogDetail", function() {
    var id = $(this).attr('data-logid');
    $.post('app/tools/logs/detail-popup.php', {id:id}, function(data) {
        $('#popupOverlay div.popup_w500').html(data);
        showPopup('popup_w500');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
//log files page change
$('#logDirection button').click(function() {
    showSpinner();
    /* get severities */
    var logSelection = $('form#logs').serialize();
    /* get first or last id based on direction */
    var direction = $(this).attr('data-direction');
    /* get Id */
    var lastId;
    if (direction == "next")     { lastId = $('table#logs tr:last').attr('id'); }
    else                         { lastId = $('table#logs tr:nth-child(2)').attr('id'); }

    /* set complete post */
    var postData = logSelection + "&direction=" + direction + "&lastId=" + lastId;

    /* show logs */
    $.post('app/tools/logs/show-logs.php', postData, function(data1) {
        $('div.logs').html(data1);
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
//logs export
$('#downloadLogs').click(function() {
    showSpinner();
    $("div.dl").remove();    //remove old innerDiv
    $('div.exportDIV').append("<div style='display:none' class='dl'><iframe src='app/tools/logs/export.php'></iframe></div>");
    hideSpinner();
    //show downloading
    $('div.logs').prepend("<div class='alert alert-info' id='logsInfo'><i class='icon-remove icon-gray selfDestruct'></i> Preparing download... </div>");
    return false;
});
//logs clear
$('#clearLogs').click(function() {
    showSpinner();
    $.post('app/tools/logs/clear-logs.php', function(data) {
    	$('div.logs').html(data);
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
//logs clear
$('#clearChangeLogs').click(function() {
    showSpinner();
    $.post('app/tools/changelog/clear-logs.php', function(data) {
        $('div.logs').html(data);
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});


// commit logs
$('.log-tabs li a').click(function() {
	// navigation
	$('.log-tabs li').removeClass("active");
	$(this).parent('li').addClass("active");
	// load
	$('div.log-print').hide();
	$('div.'+$(this).attr("data-target")).show();	return false;
});

// show changelog details popup
$(document).on("click", ".openChangelogDetail", function() {
    open_popup("700", "app/tools/changelog/show-popup.php", {cid:$(this).attr('data-cid')})
})


/*    Sections
********************************/
//edit section result
$(document).on("click", "#editSectionSubmit, .editSectionSubmitDelete", function() {
    showSpinner();
    var sectionData = $('form#sectionEdit').serialize();

	//append deleteconfirm
	if($(this).attr('id') == "editSectionSubmitDelete") { sectionData += "&deleteconfirm=yes"; };

    $.post('app/admin/sections/edit-result.php', sectionData, function(data) {
        $('div.sectionEditResult').html(data).slideDown('fast');
        //reload after 2 seconds if succeeded!
        reload_window (data);
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
//section ordering save
$(document).on("click", "#sectionOrderSubmit", function() {
    showSpinner();
	//get all ids that are checked
	var m = 0;
	var lis = $('#sortableSec li').map(function(i,n) {
	var pindex = $(this).index() +1;
		return $(n).attr('id')+":"+pindex;
	}).get().join(';');

	//post
	$.post('app/admin/sections/edit-order-result.php', {position: lis}, function(data) {
		$('.sectionOrderResult').html(data).fadeIn('fast');
        //reload after 2 seconds if succeeded!
        reload_window (data);

    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });	return false;
});


/*    powerDNS
********************************/

//hide defaults
$(document).on("click", ".hideDefaults", function () {
    if ($(this).is(':checked')) { $("tbody.defaults").hide(); }
    else						{ $("tbody.defaults").show(); }
});
//submit form
$(document).on("click", "#editDomainSubmit", function() {
    //dont reload if it cane from ip addresses
    if ($(this).hasClass('editDomainSubmit2'))  {
    	// show spinner
    	showSpinner();
    	// post
        $.post("app/admin/powerDNS/domain-edit-result.php", $('form#domainEdit').serialize(), function(data) {
            $('#popupOverlay2 div.domain-edit-result').html(data).slideDown('fast');
            //reload after 2 seconds if succeeded!
	        if(data.search("alert-danger")==-1 && data.search("error")==-1 && data.search("alert-warning")==-1 ) {
    	        $.post("app/admin/powerDNS/record-edit.php", {id:$('#popupOverlay .pContent .ip_dns_addr').html(),domain_id:$('#popupOverlay .pContent strong').html(),action:"add"}, function(data2) {
        	        $("#popupOverlay .popup_w700").html(data2);
    	        });
    	        setTimeout(function (){ $('#popupOverlay2').fadeOut('fast'); }, 1500);
    	        setTimeout(function (){ hideSpinner(); }, 1500);
    	    }
	        else {
    	        hideSpinner();
    	    }
        }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
        // prevent reload
        return false;
    }
    else {
        submit_popup_data (".domain-edit-result", "app/admin/powerDNS/domain-edit-result.php", $('form#domainEdit').serialize());
    }
});

// refresh subnet PTR records
$(document).on("click", ".refreshPTRsubnet", function() {
	open_popup("700", "app/admin/powerDNS/refresh-ptr-records.php", {subnetId:$(this).attr('data-subnetId')} );	return false;
});
$(document).on("click", ".refreshPTRsubnetSubmit", function() {
	submit_popup_data (".refreshPTRsubnetResult", "app/admin/powerDNS/refresh-ptr-records-submit.php", {subnetId:$(this).attr('data-subnetId')} );	return false;
});
//edit record
$(document).on("click", ".editRecord", function() {
	open_popup("700", "app/admin/powerDNS/record-edit.php", {id:$(this).attr('data-id'),domain_id:$(this).attr('data-domain_id'), action:$(this).attr('data-action')} );	return false;
});
$(document).on("click", "#editRecordSubmit", function() {
    submit_popup_data (".record-edit-result", "app/admin/powerDNS/record-edit-result.php", $('form#recordEdit').serialize());
});
$(document).on("click", "#editRecordSubmitDelete", function() {
    var formData = $('form#recordEdit').serialize();
    // replace edit action with delete
    formData = formData.replace("action=edit", "action=delete");
    submit_popup_data (".record-edit-result", "app/admin/powerDNS/record-edit-result.php", formData);
});


/*    Firewall zones
********************************/

// firewall zone settings
$('#firewallZoneSettings').submit(function() {
    showSpinner();
    var settings = $(this).serialize();
    //load submit results
    $.post('app/admin/firewall-zones/settings-save.php', settings, function(data) {
        $('div.settingsEdit').html(data).slideDown('fast');
        //reload after 1 second if all is ok!
        reload_window (data);
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});

// zone edit menu
// load edit form
$(document).on("click", ".editFirewallZone", function() {
    open_popup("700", "app/admin/firewall-zones/zones-edit.php", {id:$(this).attr('data-id'), action:$(this).attr('data-action')} );
});

//submit form
$(document).on("click", "#editZoneSubmit", function() {
    submit_popup_data (".zones-edit-result", "app/admin/firewall-zones/zones-edit-result.php", $('form#zoneEdit').serialize());
});

// bind a subnet which is not part of a zone to an existing zone
// load edit form

$(document).on("click", ".subnet_to_zone", function() {
    showSpinner();
    var subnetId  = $(this).attr('data-subnetId');
    var operation = $(this).attr('data-operation');
    //format posted values
    var postdata = "operation="+operation+"&subnetId="+subnetId;
    $.post('app/admin/firewall-zones/subnet-to-zone.php', postdata, function(data) {
        $('#popupOverlay div.popup_w500').html(data);
        showPopup('popup_w500');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});

//submit form
$(document).on("click", "#subnet-to-zone-submit", function() {
    submit_popup_data (".subnet-to-zone-result", "app/admin/firewall-zones/subnet-to-zone-save.php", $('form#subnet-to-zone-edit').serialize());
});

// trigger the check for any mapping of the selected zone
$(document).on("change", ".checkMapping",(function () {
    showSpinner();
    var pData = $(this).serializeArray();
    pData.push({name:'operation',value:'checkMapping'});

    //load results
    $.post('app/admin/firewall-zones/ajax.php', pData, function(data) {
        $('div.mappingAdd').html(data).slideDown('fast');

    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    hideSpinner();
    return false;
}));

// add network to zone
$(document).on("click", ".editNetwork", function() {
     var pData = $('form#zoneEdit').serializeArray();
     pData.push({name:'action',value:$(this).attr('data-action')});
     pData.push({name:'subnetId',value:$(this).attr('data-subnetId')});
     $('#popupOverlay2 .popup_w500').load('app/admin/firewall-zones/zones-edit-network.php',pData);
    showPopup('popup_w500', false, true);
    hideSpinner();
});

// remove a non persitent network from the selection
$(document).on("click", ".deleteTempNetwork", function() {
    // show spinner
    showSpinner();
    var filterName = 'network['+$(this).attr("data-subnetArrayKey")+']';
    var pData =$('form#zoneEdit :input[name != "'+filterName+'"][name *= "network["]').serializeArray();
    pData.push({name:'noZone',value:1});

    // post
    $.post("app/admin/firewall-zones/ajax.php", pData , function(data) {
        $('div'+".zoneNetwork").html(data).slideDown('fast');
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    setTimeout(function (){hideSpinner();}, 500);

    return false;
});

//submit form network
$(document).on("click", "#editNetworkSubmit", function() {
    // show spinner
    showSpinner();
    // set reload
    reload = typeof reload !== 'undefined' ? reload : true;
    // post
    $.post("app/admin/firewall-zones/zones-edit-network-result.php", $('form#networkEdit :input[name != "sectionId"]').serialize(), function(data) {
        $('div'+".zones-edit-network-result").html(data).slideDown('fast');

        if(reload) {
            if(data.search("alert-danger")==-1 && data.search("error")==-1 && data.search("alert-warning") == -1 ) {
                $.post("app/admin/firewall-zones/ajax.php", $('form#networkEdit :input[name != "sectionId"]').serialize(), function(data) {
                    $('div'+".zoneNetwork").html(data).slideDown('fast');
                }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
                setTimeout(function (){hideSpinner();hidePopup2();}, 500);
            } else { hideSpinner(); }
        }
        else {
            hideSpinner();
        }
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    // prevent reload
    return false;
});

// zone edit menu - ajax request to fetch all subnets for a specific section id
$(document).on("change", ".firewallZoneSection",(function () {
    showSpinner();
    var pData = $(this).serializeArray();
    pData.push({name:'operation',value:'fetchSectionSubnets'});
    //load results
    $.post('app/admin/firewall-zones/ajax.php', pData, function(data) {
        $('div.sectionSubnets').html(data).slideDown('fast');

    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    hideSpinner();
    return false;
}));

// mapping edit menu
// load edit form
$(document).on("click", ".editMapping", function() {
    open_popup("700", "app/admin/firewall-zones/mapping-edit.php", {id:$(this).attr('data-id'), action:$(this).attr('data-action')} );
    return false;
});

//submit form
$(document).on("click", "#editMappingSubmit", function() {
    submit_popup_data (".mapping-edit-result", "app/admin/firewall-zones/mapping-edit-result.php", $('form#mappingEdit').serialize());
});

// mapping edit menu - ajax request to fetch all zone informations for the selected zone
$(document).on("change", ".mappingZoneInformation",(function() {
    showSpinner();
    var pData = $(this).serializeArray();
    pData.push({name:'operation',value:'deliverZoneDetail'});
    //load results
    $.post('app/admin/firewall-zones/ajax.php', pData, function(data) {
        $('div.zoneInformation').html(data).slideDown('fast');

    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    hideSpinner();
    return false;
}));

/*    regenerate firewall address objects
********************************************/
// execute regeneration of the address object via ajax, reload the page to refresh the data
$(document).on("click", "a.fw_autogen", function() {
    //build vars
    var subnetId = $(this).attr('data-subnetid');
    var IPId = $(this).attr('data-ipid');
    var dnsName = $(this).attr('data-dnsname');
    var action = $(this).attr('data-action');
    var operation = 'autogen';

    showSpinner();

    // send information to ajax.php to generate a new address object
    $.post('app/admin/firewall-zones/ajax.php', {subnetId:subnetId, IPId:IPId, dnsName:dnsName, action:action, operation:operation}).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });

    // hide the spinner and reload the window on success
    setTimeout(function (){hideSpinner();window.location.reload();}, 500);

    return false;
});

/*    Subnets
********************************/
//show subnets
$('table#manageSubnets button[id^="subnet-"]').click(function() {
    showSpinner();
    var swid = $(this).attr('id');                    //get id
    // change icon to down
    if( $('#content-'+swid).is(':visible') )     { $(this).children('i').removeClass('fa-angle-down').addClass('fa-angle-right'); }    //hide
    else                                         { $(this).children('i').removeClass('fa-angle-right').addClass('fa-angle-down'); }    //show
    //show content
    $('table#manageSubnets tbody#content-'+swid).slideToggle('fast');
    hideSpinner();
});
//toggle show all / none
$('#toggleAllSwitches').click(function() {
    showSpinner();
    // show
    if( $(this).children().hasClass('fa-compress') ) {
        $(this).children().removeClass('fa-compress').addClass('fa-expand');            //change icon
        $('table#manageSubnets i.fa-angle-down').removeClass('fa-angle-down').addClass('fa-angle-right');    //change section chevrons
        $('table#manageSubnets tbody[id^="content-subnet-"]').hide();                                //show content
        createCookie('showSubnets',0,30);                                                            //save cookie
    }
    //hide
    else {
        $(this).children().removeClass('fa-expand').addClass('fa-compress');
        $('table#manageSubnets tbody[id^="content-subnet-"]').show();
        $('table#manageSubnets i.fa-angle-right').removeClass('fa-angle-right').addClass('fa-angle-down');    //change section chevrons
        createCookie('showSubnets',1,30);                                                            //save cookie
    }
    hideSpinner();
});
//load edit form
$(document).on("click", ".editSubnet", function() {
    showSpinner();
    var sectionId   = $(this).attr('data-sectionid');
    var subnetId    = $(this).attr('data-subnetid');
    var action         = $(this).attr('data-action');
    //format posted values
    var postdata    = "sectionId=" + sectionId + "&subnetId=" + subnetId + "&action=" + action;

    //load edit data
    $.post("app/admin/subnets/edit.php", postdata, function(data) {
        $('#popupOverlay div.popup_w700').html(data);
        showPopup('popup_w700');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });

    return false;
});
//resize / split subnet
$(document).on("click", "#resize, #split, #truncate, .subnet-truncate", function() {
	showSpinner();
	var action = $(this).attr('id');
	var subnetId = $(this).attr('data-subnetId');
	//dimm and show popup2
    $.post("app/admin/subnets/"+action+".php", {action:action, subnetId:subnetId}, function(data) {
        showPopup('popup_w500', data, true);
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });	return false;
});
//resize save
$(document).on("click", "button#subnetResizeSubmit", function() {
	showSpinner();
	var resize = $('form#subnetResize').serialize();
	$.post("app/admin/subnets/resize-save.php", resize, function(data) {
		$('div.subnetResizeResult').html(data);
        //reload after 2 seconds if succeeded!
        reload_window (data);
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });	return false;
});
//split save
$(document).on("click", "button#subnetSplitSubmit", function() {
	showSpinner();
	var split = $('form#subnetSplit').serialize();
	$.post("app/admin/subnets/split-save.php", split, function(data) {
		$('div.subnetSplitResult').html(data);
        //reload after 2 seconds if succeeded!
        reload_window (data);
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });	return false;
});
//truncate save
$(document).on("click", "button#subnetTruncateSubmit", function() {
	showSpinner();
	var subnetId = $(this).attr('data-subnetId');
    var csrf_cookie = $(this).attr('data-csrf_cookie');
	$.post("app/admin/subnets/truncate-save.php", {subnetId:subnetId, csrf_cookie:csrf_cookie}, function(data) {
		$('div.subnetTruncateResult').html(data);
        //reload after 2 seconds if succeeded!
        reload_window (data);
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });	return false;
});
$(document).on("submit", "#editSubnetDetails", function() {	return false;
});
//save edit subnet changes
$(document).on("click", ".editSubnetSubmit, .editSubnetSubmitDelete", function() {

    showSpinner();
    var subnetData = $('form#editSubnetDetails').serialize();

    //if ipaddress and delete then change action!
    if($(this).hasClass("editSubnetSubmitDelete")) {
        subnetData = subnetData.replace("action=edit", "action=delete");
    }
	//append deleteconfirm
	if($(this).attr('id') == "editSubnetSubmitDelete") { subnetData += "&deleteconfirm=yes"; };

    //load results
    $.post("app/admin/subnets/edit-result.php", subnetData, function(data) {
        $('div.manageSubnetEditResult').html(data).slideDown('fast');

        //reload after 2 seconds if all is ok!
        if(data.search("alert-danger")==-1 && data.search("error")==-1) {
            showSpinner();
            var sectionId;
            var subnetId;
            var parameter;
            //reload IP address list if request came from there
            if(subnetData.search("IPaddresses") != -1) {
                //from ipcalc - load ip list
                sectionId = $('form#editSubnetDetails input[name=sectionId]').val();
                subnetId  = $('form#editSubnetDetails input[name=subnetId]').val();
	            //check for .subnet_id_new if new subnet id present and set location
	            if($(".subnet_id_new").html()!=="undefined") {
		            var subnet_id_new = $(".subnet_id_new").html();
		            if (subnet_id_new % 1 === 0) {
			            // section
			            var section_id_new = $(".section_id_new").html();
						//lets try to detect IEto set location
					    var ua = window.navigator.userAgent;
					    var msie = ua.indexOf("MSIE ");
					    var edge = ua.indexOf("Edge/");
					    //IE
					    if (msie > 0 || edge > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)) 	{ var base = $('.iebase').html(); }
					    else 																{ var base = ""; }
					    //go to search page
					    var prettyLinks = $('#prettyLinks').html();
						if(prettyLinks=="Yes")	{ setTimeout(function (){window.location = base + "subnets/"+section_id_new+"/"+subnet_id_new+"/";}, 1500); }
						else					{ setTimeout(function (){window.location = base + "index.php?page=subnets&section="+section_id_new+"&subnetId="+subnet_id_new;}, 1500); }
		            }
		            else {
		            	setTimeout(function (){window.location.reload();}, 1500);
	            	}
	            }
	            else {
		             setTimeout(function (){window.location.reload();}, 1500);
	            }
            }
            //from free space
            else if(subnetData.search("freespace") != -1) {
	            setTimeout(function (){window.location.reload();}, 1500);
            }
            //from ipcalc - ignore
            else if (subnetData.search("ipcalc") != -1) {
            }
            //from admin
            else {
                //reload
                setTimeout(function (){window.location.reload();}, 1500);
            }
        }
        //hide spinner - error
        else {
            hideSpinner();
        }
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});

//get subnet info from ripe database
$(document).on("click", "#get-ripe", function() {
	showSpinner();
	var subnet = $('form#editSubnetDetails input[name=subnet]').val();

	$.post("app/admin/subnets/ripe-query.php", {subnet: subnet}, function(data) {
        showPopup('popup_w500', data, true);
		hideSpinner();
	}).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });	return false;
});
// fill ripe fields
$(document).on('click', "#ripeMatchSubmit", function() {
	var cfields_temp = $('form#ripe-fields').serialize();
	// to array
	var cfields = cfields_temp.split("&");
	// loop
	for (index = 0; index < cfields.length; ++index) {
		// check for =0match and ignore
		if (cfields[index].indexOf("=0") > -1) {}
		else {
			console.log(cfields[index]);
			var cdata = cfields[index].split("=");
			$('form#editSubnetDetails input[name='+cdata[1]+']').val(cdata[0].replace(/___/g, " "));
		}
	}
	// hide
	hidePopup2();
});
//change subnet permissions
$(document).on("click", ".showSubnetPerm", function () {
	showSpinner();
	var subnetId  = $(this).attr('data-subnetId');
	var sectionId = $(this).attr('data-sectionId');

	$.post("app/admin/subnets/permissions-show.php", {subnetId:subnetId, sectionId:sectionId}, function(data) {
        $('#popupOverlay div.popup_w500').html(data);
        showPopup('popup_w500');
		hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });	return false;
});
//submit permission change
$(document).on("click", ".editSubnetPermissionsSubmit", function() {
	showSpinner();
	var perms = $('form#editSubnetPermissions').serialize();
	$.post('app/admin/subnets/permissions-submit.php', perms, function(data) {
		$('.editSubnetPermissionsResult').html(data);
        //reload after 2 seconds if succeeded!
        reload_window (data);
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });	return false;
});
//auto-suggest possible slaves select
$(document).on("click", ".dropdown-subnets li a", function() {
	var subnet = $(this).attr('data-cidr');
	var inputfield = $('form#editSubnetDetails input[name=subnet]');
	// fill
	$(inputfield).val(subnet);
	// hide
	$('.dropdown-subnets').parent().removeClass("open");	return false;
});

// linked subnets
$('.editSubnetLink').click(function() {
    showSpinner();
	$.post("app/admin/subnets/linked-subnet.php", {subnetId:$(this).attr('data-subnetId')}, function(data) {
        $('#popupOverlay div.popup_w500').html(data);
        showPopup('popup_w500');
		hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });

   return false;
});
$(document).on('click', '.linkSubnetSave', function() {
    showSpinner();
	$.post('app/admin/subnets/linked-subnet-submit.php', $('form#editLinkedSubnet').serialize(), function(data) {
		$('.linkSubnetSaveResult').html(data);
        //reload after 2 seconds if succeeded!
        reload_window (data);
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });	return false;
});


/*    Add subnet from IPCalc result
*********************************/
$(document).on("click", "#createSubnetFromCalc", function() {
    $('tr#selectSection').show();
});
$(document).on("change", "select#selectSectionfromIPCalc", function() {
    //get details - we need Section, network and subnet bitmask
    var sectionId = $(this).val();
    var subnet      = $('table.ipCalcResult td#sub2').html();
    var bitmask      = $('table.ipCalcResult td#sub4').html();
    // ipv6 override
    if ($("table.ipCalcResult td#sub0").html() == "IPv6") {
    	var postdata  = "sectionId=" + sectionId + "&subnet=" + $('table.ipCalcResult td#sub3').html() + "&bitmask=&action=add&location=ipcalc";
    } else {
	    var postdata  = "sectionId=" + sectionId + "&subnet=" + subnet + "&bitmask=" + bitmask + "&action=add&location=ipcalc";
    }
    //make section active
    $('table.newSections ul#sections li#' + sectionId ).addClass('active');
    //load add Subnet form / popup
    $.post('app/admin/subnets/edit.php', postdata , function(data) {
        $('#popupOverlay div.popup_w700').html(data);
        showPopup('popup_w700');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
});
$(document).on("click", ".createfromfree", function() {
    //get details - we need Section, network and subnet bitmask
    var sectionId = $(this).attr('data-sectionId');
    var cidr      = $(this).attr('data-cidr');
    var freespaceMSISD = $(this).attr('data-masterSubnetId');
    var cidrArr   = cidr.split('/');
    var subnet    = cidrArr[0];
    var bitmask   = cidrArr[1];
    var postdata  = "sectionId=" + sectionId + "&subnet=" + subnet + "&bitmask=" + bitmask + "&freespaceMSID=" + freespaceMSISD + "&action=add&location=ipcalc";
    //load add Subnet form / popup
    $.post('app/admin/subnets/edit.php', postdata , function(data) {
        $('#popupOverlay div.popup_w700').html(data);
        showPopup('popup_w700');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});

/*    Edit subnet from ip address list
************************************/
$(document).on("click", '.edit_subnet, button.edit_subnet, button#add_subnet', function() {
    var subnetId  = $(this).attr('data-subnetId');
    var sectionId = $(this).attr('data-sectionId');
    var action    = $(this).attr('data-action');

    //format posted values
    var postdata     = "sectionId="+sectionId+"&subnetId="+subnetId+"&action="+action+"&location=IPaddresses";
    //load add Subnet form / popup
    $.post('app/admin/subnets/edit.php', postdata , function(data) {
        $('#popupOverlay div.popup_w700').html(data);
        showPopup('popup_w700');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});


/* Show add new VLAN on subnet add/edit on-thy-fly
***************************************************/
$(document).on("change", "select[name=vlanId]", function() {
    var domain = $("select[name=vlanId] option:selected").attr('data-domain');
    if($(this).val() == 'Add') {
        showSpinner();
        $.post('app/admin/vlans/edit.php', {action:"add", fromSubnet:"true", domain:domain}, function(data) {
            showPopup('popup_w400', data, true);
            hideSpinner();
		}).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    }
    return false;
});
//Submit new VLAN on the fly
$(document).on("click", ".vlanManagementEditFromSubnetButton", function() {
    showSpinner();
    //get new vlan details
    var postData = $('form#vlanManagementEditFromSubnet').serialize();
	//add to save script
    $.post('app/admin/vlans/edit-result.php', postData, function(data) {
        $('div.vlanManagementEditFromSubnetResult').html(data).show();
        // ok
        if(data.search("alert-danger")==-1 && data.search("error")==-1) {
            var vlanId	  = $('#vlanidforonthefly').html();
            var sectionId = $('#editSubnetDetails input[name=sectionId]').val();
            $.post('app/admin/subnets/edit-vlan-dropdown.php', {vlanId:vlanId, sectionId:sectionId} , function(data) {
                $('.editSubnetDetails td#vlanDropdown').html(data);
                hideSpinner();
			}).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
            //hide popup after 1 second
            setTimeout(function (){ hidePopup('popup_w400', true); hidePopup2(); parameter = null;}, 1000);
        }
        else                      { hideSpinner(); }
    });
    return false;
});
// filter vlans
$('.vlansearchsubmit').click(function() {
	showSpinner();
	var search = $('input.vlanfilter').val();
	var location = $('input.vlanfilter').attr('data-location');
    //go to search page
    var prettyLinks = $('#prettyLinks').html();
	if(prettyLinks=="Yes")	{ setTimeout(function (){window.location = location +search+"/";}, 500); }
	else					{ setTimeout(function (){window.location = location + "&sPage="+search;}, 500); }


    //go to search page
    var prettyLinks = $('#prettyLinks').html();
	if(prettyLinks=="Yes")	{ setTimeout(function (){window.location = base + "subnets/"+section_id_new+"/"+subnet_id_new+"/";}, 1500); }
	else					{ setTimeout(function (){window.location = base + "index.php?page=subnets&section="+section_id_new+"&subnetId="+subnet_id_new;}, 1500); }	return false;
});






/*	Folders
************************************/
//create new folder popup
$(document).on("click", "#add_folder, .add_folder", function() {
	showSpinner();
    var subnetId  = $(this).attr('data-subnetId');
    var sectionId = $(this).attr('data-sectionId');
    var action    = $(this).attr('data-action');
    //format posted values
    var postdata     = "sectionId="+sectionId+"&subnetId="+subnetId+"&action="+action+"&location=IPaddresses";

    $.post('app/admin/subnets/edit-folder.php', postdata, function(data) {
        $('#popupOverlay div.popup_w700').html(data);
        showPopup('popup_w700');
        hideSpinner();
	}).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });

    return false;
});
//submit folder changes
$(document).on("click", ".editFolderSubmit", function() {
	showSpinner();
	var postData = $('form#editFolderDetails').serialize();
	$.post('app/admin/subnets/edit-folder-result.php', postData, function(data) {
		$('.manageFolderEditResult').html("").html(data);
        //reload after 2 seconds if succeeded!
        reload_window (data);
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });	return false;
});
//delete folder
$(document).on("click", ".editFolderSubmitDelete", function() {
	showSpinner();
    var subnetId  = $(this).attr('data-subnetId');
    var description = $('form#editFolderDetails #field-description').val();
    var csrf_cookie = $('form#editFolderDetails input[name=csrf_cookie]').val();
    //format posted values
    var postData     = "subnetId="+subnetId+"&description="+description+"&action=delete"+"&csrf_cookie="+csrf_cookie;
	//append deleteconfirm
	if($(this).attr('id') == "editFolderSubmitDelete") { postData += "&deleteconfirm=yes"; };
	$.post('app/admin/subnets/edit-folder-result.php', postData, function(data) {
		$('.manageFolderEditResult').html(data);
        //reload after 2 seconds if succeeded!
        reload_window (data);
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });	return false;
});




/* ---- Devices ----- */
//submit form
$(document).on("click", "#editSwitchsubmit", function() {
    submit_popup_data (".switchManagementEditResult", "app/admin/devices/edit-result.php", $('form#switchManagementEdit').serialize());
});
//submit form
$(document).on("click", "#editSwitchSNMPsubmit", function() {
    submit_popup_data (".switchSNMPManagementEditResult", "app/admin/devices/edit-snmp-result.php", $('form#switchSNMPManagementEdit').serialize());
});
//snmp test
$(document).on("click", "#test-snmp", function() {
	open_popup ("700", "app/admin/devices/edit-snmp-test.php", $('form#switchSNMPManagementEdit').serialize(), true);	return false;
});
//snmp route query popup
$(document).on("click", "#snmp-routing", function() {
    open_popup ("700", "app/subnets/scan/subnet-scan-execute.php", {type:'snmp-route', csrf_cookie:$(this).attr('data-csrf-cookie')}, true);
    return false;
});

//snmp vlan query popup
$(document).on("click", "#snmp-vlan", function() {
    open_popup ("700", "app/admin/vlans/vlans-scan.php", {domainId:$(this).attr('data-domainid')}, true);
    return false;
});
//snmp vlan query execute
$(document).on("click", ".show-vlan-scan-result", function() {
    submit_popup_data (".vlan-scan-result", "app/admin/vlans/vlans-scan-execute.php", $('form#select-devices-vlan-scan').serialize(), true);
    return false;
});
// submit vlan query result
$(document).on("click", "#saveVlanScanResults", function() {
    submit_popup_data ("#vlanScanAddResult", "app/admin/vlans/vlans-scan-result.php", $('form#scan-snmp-vlan-form').serialize());
    return false;
});

//snmp vrf query popup
$(document).on("click", "#snmp-vrf", function() {
    open_popup ("700", "app/admin/vrf/vrf-scan.php", {}, true);
    return false;
});
//snmp vrf query execute
$(document).on("click", ".show-vrf-scan-result", function() {
    submit_popup_data (".vrf-scan-result", "app/admin/vrf/vrf-scan-execute.php", $('form#select-devices-vrf-scan').serialize(), true);
    return false;
});
// submit vrf query result
$(document).on("click", "#saveVrfScanResults", function() {
    submit_popup_data ("#vrfScanAddResult", "app/admin/vrf/vrf-scan-result.php", $('form#scan-snmp-vrf-form').serialize());
    return false;
});

//snmp select subnet to add to new subnet
$(document).on("click", ".select-snmp-subnet", function() {
    $('form#editSubnetDetails input[name=subnet]').val($(this).attr('data-subnet')+"/"+$(this).attr('data-mask'));
    hidePopup2();
    return false;
});
//snmp route query popup - section search
$(document).on("click", "#snmp-routing-section", function() {
    open_popup ("masks", "app/subnets/scan/subnet-scan-execute.php", {type:'snmp-route-all', sectionId:$(this).attr('data-sectionId'), subnetId:$(this).attr('data-subnetId'), csrf_cookie:$(this).attr('data-csrf-cookie'), ajax_loaded:'true'});
    return false;
});
//remove all results for device
$(document).on("click", ".remove-snmp-results", function () {
    $("tbody#"+$(this).attr('data-target')).remove();
    $(this).parent().remove();
});
//remove subnet from found subnet list
$(document).on("click", ".remove-snmp-subnet", function() {
   $('#editSubnetDetailsSNMPallTable tr#tr-' + $(this).attr('data-target-subnet')).remove();
   return false;
});
///add subnets to section
$(document).on("click", "#add-subnets-to-section-snmp", function() {
    var postData = "type=snmp-route-all";
    var postData = postData+"&"+$('form#editSubnetDetailsSNMPall').serialize();
    var postData = postData+"&canary=true";
    submit_popup_data (".add-subnets-to-section-snmp-result", "app/subnets/scan/subnet-scan-result.php", postData);
    return false;
});



/* ---- Device types ----- */
//load edit form
$(document).on("click", ".editDevType", function() {
	open_popup("400", "app/admin/device-types/edit.php", {tid:$(this).attr('data-tid'), action:$(this).attr('data-action')} );
});
//submit form
$(document).on("click", "#editDevTypeSubmit", function() {
    submit_popup_data (".devTypeEditResult", "app/admin/device-types/edit-result.php", $('form#devTypeEdit').serialize());
});

/* ---- RACKS ----- */
//load edit form
$(document).on("click", ".editRack", function() {
	open_popup("400", "app/admin/racks/edit.php", {rackid:$(this).attr('data-rackid'), action:$(this).attr('data-action')} );	return false;
});
//load edit rack devices form
$(document).on("click", ".editRackDevice", function() {
	open_popup("400", "app/admin/racks/edit-rack-devices.php", {rackid:$(this).attr('data-rackid'), deviceid:$(this).attr('data-deviceid'), devicetype:$(this).attr('data-devicetype'), action:$(this).attr('data-action'),csrf_cookie:$(this).attr('data-csrf')} );	return false;
});
//submit edit rack devices form
$(document).on("click", "#editRackDevicesubmit", function() {
    submit_popup_data (".rackDeviceManagementEditResult", "app/admin/racks/edit-rack-devices-result.php", $('form#rackDeviceManagementEdit').serialize());
});
//show popup image
$(document).on("click", ".showRackPopup", function() {
	open_popup("400", "app/tools/racks/show-rack-popup.php", {rackid:$(this).attr('data-rackid'), deviceid:$(this).attr('data-deviceid')}, true );	return false;
});


/* ---- Locations ----- */
//submit form
$(document).on("click", "#editLocationSubmit", function() {
    submit_popup_data (".editLocationResult", "app/admin/locations/edit-result.php", $('form#editLocation').serialize());
    return false;
});



/* ---- PSTN ---- */
//load edit form
$(document).on("click", ".editPSTN", function() {
	open_popup("700", "app/tools/pstn-prefixes/edit.php", {id:$(this).attr('data-id'), action:$(this).attr('data-action')} );
    return false;
});
//submit form
$(document).on("click", "#editPSTNSubmit", function() {
    submit_popup_data (".editPSTNResult", "app/tools/pstn-prefixes/edit-result.php", $('form#editPSTN').serialize());
    return false;
});
//load edit form
$(document).on("click", ".editPSTNnumber", function() {
	open_popup("700", "app/tools/pstn-prefixes/edit-number.php", {id:$(this).attr('data-id'), action:$(this).attr('data-action')} );
    return false;
});
//submit form
$(document).on("click", "#editPSTNnumberSubmit", function() {
    submit_popup_data (".editPSTNnumberResult", "app/tools/pstn-prefixes/edit-number-result.php", $('form#editPSTNnumber').serialize());
    return false;
});




/* ---- NAT ----- */
//load edit form
$(document).on("click", ".editNat", function() {
	open_popup("700", "app/admin/nat/edit.php", {id:$(this).attr('data-id'), action:$(this).attr('data-action')} );
    return false;
});
//load edit form from subnets/addresses
$(document).on("click", ".mapNat", function() {
	open_popup("700", "app/admin/nat/edit-map.php", {id:$(this).attr('data-id'), object_type:$(this).attr('data-object-type'), object_id:$(this).attr('data-object-id')} );
    return false;
});
//submit form
$(document).on("click", "#editNatSubmit", function() {
    // action
    var action = $('form#editNat input[name=action]').val();

    if (action!=="add") {
        submit_popup_data (".editNatResult", "app/admin/nat/edit-result.php", $('form#editNat').serialize());
    }
    else {
        $.post("app/admin/nat/edit-result.php", $('form#editNat').serialize(), function(data) {
            $('.editNatResult').html(data);
            if(data.search("alert-danger")==-1 && data.search("error")==-1) {
                setTimeout(function (){ open_popup("700", "app/admin/nat/edit.php", {id:$('div.new_nat_id').html(), action:"edit"} ); hidePopup2(); parameter = null;}, 1000);
            }
            else {
                hideSpinner();
            }
        }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
        return false;
    }
});
// remove item
$(document).on("click", ".removeNatItem", function() {
    var id = $(this).attr('data-id');
    showSpinner();

    $.post("app/admin/nat/item-remove.php", {id:$(this).attr('data-id'), type:$(this).attr('data-type'), item_id:$(this).attr('data-item-id'), csrf_cookie:$('form#editNat input[name=csrf_cookie]').val()}, function(data) {
        $('#popupOverlay2 div.popup_w500').html(data);
        showPopup('popup_w700', data, true);

        if(data.search("alert-danger")==-1 && data.search("error")==-1) {
            setTimeout(function (){ open_popup("700", "app/admin/nat/edit.php", {id:id, action:"edit"} ); hidePopup2(); parameter = null;}, 1000);
        }
        else {
            hideSpinner();
        }
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
// add item popup
$(document).on("click", ".addNatItem", function() {
	open_popup("700", "app/admin/nat/item-add.php", {id:$(this).attr('data-id'), type:$(this).attr('data-type'), object_type:$(this).attr('data-object-type'), object_id:$(this).attr('data-object-id')}, true);
    return false;
});
// search item
$(document).on("submit", "form#search_nats", function() {
    showSpinner();
    $.post("app/admin/nat/item-add-search.php", $(this).serialize(), function(data) {
        $('#nat_search_results').html(data);
        hideSpinner();
    });
    return false;
})
// search result item select
$(document).on("click", "a.addNatObjectFromSearch", function() {
    var id = $(this).attr('data-id');
    var reload = $(this).attr('data-reload');
    showSpinner();
    $.post("app/admin/nat/item-add-submit.php", {id:$(this).attr('data-id'), type:$(this).attr('data-type'), object_type:$(this).attr('data-object-type'), object_id:$(this).attr('data-object-id')}, function(data) {
        $('#nat_search_results_commit').html(data);
        if(data.search("alert-danger")==-1 && data.search("error")==-1) {
            if(reload == "true") {
                reload_window (data);
            }
            else {
                setTimeout(function (){ open_popup("700", "app/admin/nat/edit.php", {id:id, action:"edit"} ); hidePopup2(); parameter = null;}, 1000);
            }
        }
        else {
            hideSpinner();
        }
    });
    return false;
})



/* ---- tags ----- */
//load edit form
$(document).on("click", ".editType", function() {
	open_popup("400", "app/admin/tags/edit.php", {id:$(this).attr('data-id'), action:$(this).attr('data-action')} );
});
//submit form
$(document).on("click", "#editTypesubmit", function() {
    submit_popup_data (".editTypeResult", "app/admin/tags/edit-result.php", $('form#editType').serialize());
});


/* ---- VLANs ----- */
//submit form
$(document).on("click", "#editVLANsubmit", function() {
    submit_popup_data (".vlanManagementEditResult", "app/admin/vlans/edit-result.php", $('form#vlanManagementEdit').serialize());
});


/* ---- VLAN domains ----- */
//submit form
$(document).on("click", "#editVLANdomainsubmit", function() {
    submit_popup_data (".domainEditResult", "app/admin/vlans/edit-domain-result.php", $('form#editVLANdomain').serialize());
});


/* ---- VRF ----- */
//submit form
$(document).on("click", "#editVRF", function() {
    submit_popup_data (".vrfManagementEditResult", "app/admin/vrf/edit-result.php", $('form#vrfManagementEdit').serialize());
});

/* ---- Nameservers ----- */
// add new
$(document).on("click", "#add_nameserver", function() {
	showSpinner();
	//get old number
	var num = $(this).attr("data-id");
	// append
	$('table#nameserverManagementEdit2 tbody#nameservers').append("<tr id='namesrv-"+num+"'><td>Nameserver "+num+"</td><td><input type='text' class='rd form-control input-sm' name='namesrv-"+num+"'></input><td><button class='btn btn-sm btn-default' id='remove_nameserver' data-id='namesrv-"+num+"'><i class='fa fa-trash-o'></i></buttom></td></td></tr>");
	// add number
	num++;
	$(this).attr("data-id", num);

	hideSpinner();	return false;
});
// remove
$(document).on("click", "#remove_nameserver", function() {
	showSpinner();
	//get old number
	var id = $(this).attr("data-id");
	// append
	var el = document.getElementById(id);
	el.parentNode.removeChild(el);

	hideSpinner();	return false;
});

/* ---- IP requests ----- */
//submit form
$(document).on("click", "button.manageRequest", function() {
    var postValues = $('form.manageRequestEdit').serialize();
    var action     = $(this).attr('data-action');
    var postData   = postValues+"&action="+action;
    // submit
    submit_popup_data (".manageRequestResult", "app/admin/requests/edit-result.php", postData);
});


/* ---- Share subnet ----- */
//remove temp
$(document).on("click", ".removeSharedTemp", function() {
	showPopup("popup_w400");
    submit_popup_data ("#popupOverlay .popup_w400", "app/tools/temp-shares/delete-result.php", {code:$(this).attr('data-code')});
    hideSpinner();
});



/*    Ripe AS import
****************************/
//get subnets form AS
$('form#ripeImport').submit(function() {
    showSpinner();
    var as = $(this).serialize();
    $.post('app/admin/ripe-import/ripe-telnet.php', as, function(data) {
        $('div.ripeImportTelnet').html(data).fadeIn('fast');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
// remove as line
$(document).on("click", "table.asImport .removeSubnet", function() {
    $(this).parent('tr').remove();
    hideTooltips();
});
// add selected to db
$(document).on("submit", "form#asImport", function() {
    showSpinner();
    //get subnets to add
    var importData = $(this).serialize();
    $.post('app/admin/ripe-import/import-subnets.php', importData, function(data) {
        $('div.ripeImportResult').html(data).slideDown('fast');
        //hide after 2 seconds
        if(data.search("alert-danger")==-1 && data.search("error")==-1)     { $('table.asImport').delay(1000).fadeOut('fast'); hideSpinner(); }
        else                             { hideSpinner(); }
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});




/*    custom fields - general
************************************/

//show edit form
$(document).on("click", ".edit-custom-field", function() {
    showSpinner();
    var action    = $(this).attr('data-action');
    var fieldName = $(this).attr('data-fieldname');
    var table	  = $(this).attr('data-table');
    $.post('app/admin/custom-fields/edit.php',  {action:action, fieldName:fieldName, table:table}, function(data) {
        $('#popupOverlay div.popup_w400').html(data);
        showPopup('popup_w400');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
//submit change
$(document).on("click", "#editcustomSubmit", function() {
    showSpinner();
    var field = $('form#editCustomFields').serialize();
    $.post('app/admin/custom-fields/edit-result.php', field, function(data) {
        $('div.customEditResult').html(data).slideDown('fast');
        //reload after 2 seconds if succeeded!
        reload_window (data);
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
//field reordering
$('table.customIP button.down').click(function() {
    showSpinner();
    var current  = $(this).attr('data-fieldname');
    var next     = $(this).attr('data-nextfieldname');
    var table	 = $(this).attr('data-table');
    $.post('app/admin/custom-fields/order.php', {current:current, next:next, table:table}, function(data) {
        $('div.'+table+'-order-result').html(data).slideDown('fast');
        //reload after 2 seconds if succeeded!
        reload_window (data);
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
//filter
$('.edit-custom-filter').click(function() {
	showSpinner();
	var table = $(this).attr('data-table');
    $.post('app/admin/custom-fields/filter.php',  {table:table}, function(data) {
        $('#popupOverlay div.popup_w500').html(data);
        showPopup('popup_w500');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
$(document).on("click", "#editcustomFilterSubmit", function() {
    showSpinner();
    var field = $('form#editCustomFieldsFilter').serialize();
    $.post('app/admin/custom-fields/filter-result.php', field, function(data) {
        $('div.customEditFilterResult').html(data).slideDown('fast');
        //reload after 2 seconds if succeeded!
        reload_window (data);
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});




/* API, agents regenerate code
*********/
//regenerate API key
$(document).on('click', "#regApiKey", function() {
	showSpinner();
    $.post('app/admin/api/generate-key.php', function(data) {
        $('input#appcode').val(data);
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});
//regenerate agent key
$(document).on('click', "#regAgentKey", function() {
	showSpinner();
    $.post('app/admin/api/generate-key.php', function(data) {
        $('input[name=code]').val(data);
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});





/*    Search and replace
************************/
$('button#searchReplaceSave').click(function() {
    showSpinner();
    var searchData = $('form#searchReplace').serialize();
    $.post('app/admin/replace-fields/result.php', searchData, function(data) {
        $('div.searchReplaceResult').html(data);
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});


/*  Data Import / Export
*************************/
// dump database
$('button#XLSdump, button#MySQLdump, button#hostfileDump').click(function () {
    showSpinner();
    var script = ""
    // define script
    if ($(this).attr('id')=="XLSdump")              { script = "generate-xls.php"; }
    else if ($(this).attr('id')=="MySQLdump")       { script = "generate-mysql.php"; }
    else if ($(this).attr('id')=="hostfileDump")    { script = "generate-hosts.php"; }

    $("div.dl").remove();    //remove old innerDiv
    $('div.exportDIV').append("<div style='display:none' class='dl'><iframe src='app/admin/import-export/"+script+"'></iframe></div>");
    hideSpinner();
});


//Export Section
$('button.dataExport').click(function () {
	var implemented = ["vrf","vlan","subnets","ipaddr", "l2dom", "devices", "devtype"]; var popsize = {};
	popsize["subnets"] = "w700";
	popsize["ipaddr"] = "w700";
	popsize["devices"] = "max";
	var dataType = $('select[name=dataType]').find(":selected").val();
	hidePopups();
    //show popup window
	if (implemented.indexOf(dataType) > -1) {
		showSpinner();
		$.post('app/admin/import-export/export-' + dataType + '-field-select.php', function(data) {
		if (popsize[dataType] !== undefined) {
			$('div.popup_'+popsize[dataType]).html(data);
			showPopup('popup_'+popsize[dataType]);
		} else {
			$('#popupOverlay div.popup_w400').html(data);
			showPopup('popup_w400');
		}
		hideSpinner();
		}).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
	} else {
		$.post('app/admin/import-export/not-implemented.php', function(data) {
		$('#popupOverlay div.popup_w400').html(data);
		showPopup('popup_w400');
		}).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
	}
    return false;
});
//export buttons
$(document).on("click", "button#dataExportSubmit", function() {
    //get selected fields
	var dataType = $(this).attr('data-type');
    var exportFields = $('form#selectExportFields').serialize();
	//show popup window
	switch(dataType) {
		case 'vrf':
			$("div.dl").remove();    //remove old innerDiv
			$('div.exportDIV').append("<div style='display:none' class='dl'><iframe src='app/admin/import-export/export-vrf.php?" + exportFields + "'></iframe></div>");
			setTimeout(function (){hidePopups();}, 1500);
			break;
		case 'vlan':
			var exportDomains = $('form#selectExportDomains').serialize();
			$("div.dl").remove();    //remove old innerDiv
			$('div.exportDIV').append("<div style='display:none' class='dl'><iframe src='app/admin/import-export/export-vlan.php?" + exportDomains + "&" + exportFields + "'></iframe></div>");
			setTimeout(function (){hidePopups();}, 1500);
			break;
		case 'subnets':
			var exportSections = $('form#selectExportSections').serialize();
			$("div.dl").remove();    //remove old innerDiv
			$('div.exportDIV').append("<div style='display:none' class='dl'><iframe src='app/admin/import-export/export-subnets.php?" + exportSections + "&" + exportFields + "'></iframe></div>");
			setTimeout(function (){hidePopups();}, 1500);
			break;
		case 'ipaddr':
			var exportSections = $('form#selectExportSections').serialize();
			$("div.dl").remove();    //remove old innerDiv
			$('div.exportDIV').append("<div style='display:none' class='dl'><iframe src='app/admin/import-export/export-ipaddr.php?" + exportSections + "&" + exportFields + "'></iframe></div>");
			setTimeout(function (){hidePopups();}, 1500);
			break;
		case 'l2dom':
			var exportSections = $('form#selectExportSections').serialize();
			$("div.dl").remove();    //remove old innerDiv
			$('div.exportDIV').append("<div style='display:none' class='dl'><iframe src='app/admin/import-export/export-l2dom.php?" + exportSections + "&" + exportFields + "'></iframe></div>");
			setTimeout(function (){hidePopups();}, 1500);
			break;
		case 'devices':
			var exportSections = $('form#selectExportSections').serialize();
			$("div.dl").remove();    //remove old innerDiv
			$('div.exportDIV').append("<div style='display:none' class='dl'><iframe src='app/admin/import-export/export-devices.php?" + exportSections + "&" + exportFields + "'></iframe></div>");
			setTimeout(function (){hidePopups();}, 1500);
			break;
		case 'devtype':
			var exportSections = $('form#selectExportSections').serialize();
			$("div.dl").remove();    //remove old innerDiv
			$('div.exportDIV').append("<div style='display:none' class='dl'><iframe src='app/admin/import-export/export-devtype.php?" + exportSections + "&" + exportFields + "'></iframe></div>");
			setTimeout(function (){hidePopups();}, 1500);
			break;
	}
    return false;
});
// Check/uncheck all
$(document).on("click", "input#exportSelectAll", function() {
	if(this.checked) { // check select status
		$('input#exportCheck').each(function() { //loop through each checkbox
			this.checked = true;  //deselect all checkboxes with same class
		});
	}else{
		$('input#exportCheck').each(function() { //loop through each checkbox
			this.checked = false; //deselect all checkboxes with same class
		});
	}
});
// Check/uncheck all
$(document).on("click", "input#recomputeSectionSelectAll", function() {
	if(this.checked) { // check select status
		$('input#recomputeSectionCheck').each(function() { //loop through each checkbox
			this.checked = true;  //select all checkboxes with same class
		});
	}else{
		$('input#recomputeSectionCheck').each(function() { //loop through each checkbox
			this.checked = false; //deselect all checkboxes with same class
		});
	}
});
// Check/uncheck all
$(document).on("click", "input#recomputeIPv4SelectAll", function() {
	if(this.checked) { // check select status
		$('input#recomputeIPv4Check').each(function() { //loop through each checkbox
			this.checked = true;  //select all checkboxes with same class
		});
	}else{
		$('input#recomputeIPv4Check').each(function() { //loop through each checkbox
			this.checked = false; //deselect all checkboxes with same class
		});
	}
});
// Check/uncheck all
$(document).on("click", "input#recomputeIPv6SelectAll", function() {
	if(this.checked) { // check select status
		$('input#recomputeIPv6Check').each(function() { //loop through each checkbox
			this.checked = true;  //select all checkboxes with same class
		});
	}else{
		$('input#recomputeIPv6Check').each(function() { //loop through each checkbox
			this.checked = false; //deselect all checkboxes with same class
		});
	}
});
// Check/uncheck all
$(document).on("click", "input#recomputeCVRFSelectAll", function() {
	if(this.checked) { // check select status
		$('input#recomputeCVRFCheck').each(function() { //loop through each checkbox
			this.checked = true;  //select all checkboxes with same class
		});
	}else{
		$('input#recomputeCVRFCheck').each(function() { //loop through each checkbox
			this.checked = false; //deselect all checkboxes with same class
		});
	}
});
//Import Section
$('button.dataImport').click(function () {
	var implemented = ["vrf","vlan","subnets","recompute","ipaddr", "l2dom", "devices", "devtype"]; var popsize = {};
	popsize["subnets"] = "max";
	popsize["ipaddr"] = "max";
	popsize["devices"] = "max";
	var dataType = $('select[name=dataType]').find(":selected").val();
	hidePopups();
    //show popup window, if implemented
	if (implemented.indexOf(dataType) > -1) {
		showSpinner();
		$.post('app/admin/import-export/import-' + dataType + '-select.php', function(data) {
		if (popsize[dataType] !== undefined) {
			$('div.popup_'+popsize[dataType]).html(data);
			showPopup('popup_'+popsize[dataType]);
		} else {
			$('#popupOverlay div.popup_w700').html(data);
			showPopup('popup_w700');
		}
		hideSpinner();
		}).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
	} else {
		$.post('app/admin/import-export/not-implemented.php', function(data) {
		$('#popupOverlay div.popup_w400').html(data);
		showPopup('popup_w400');
		}).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
	}
    return false;
});
//import buttons
$(document).on("click", "button#dataImportPreview", function() {
    //get data from previous window
	var implemented = ["vrf","vlan","subnets","recompute","ipaddr", "l2dom", "devices", "devtype" ]; var popsize = {};
	popsize["subnets"] = "max";
	popsize["recompute"] = "max";
	popsize["ipaddr"] = "max";
	popsize["devices"] = "max";

	var dataType = $(this).attr('data-type');
    var importFields = $('form#selectImportFields').serialize();
	hidePopups();
    //show popup window, if implemented
	if (implemented.indexOf(dataType) > -1) {
		showSpinner();
		$.post('app/admin/import-export/import-' + dataType + '-preview.php?' + importFields, function(data) {
		if (popsize[dataType] !== undefined) {
			$('div.popup_'+popsize[dataType]).html(data);
			showPopup('popup_'+popsize[dataType]);
		} else {
			$('#popupOverlay div.popup_w700').html(data);
			showPopup('popup_w700');
		}
		hideSpinner();
		}).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
	} else {
		$.post('app/admin/import-export/not-implemented.php', function(data) {
		$('#popupOverlay div.popup_w400').html(data);
		showPopup('popup_w400');
		}).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
	}
    return false;
});
$(document).on("click", "button#dataImportSubmit", function() {
    //get data from previous window
	var implemented = ["vrf","vlan","subnets","recompute","ipaddr", "l2dom", "devices", "devtype" ]; var popsize = {};
	popsize["subnets"] = "max";
	popsize["recompute"] = "max";
	popsize["ipaddr"] = "max";
	popsize["devices"] = "max";
	var dataType = $(this).attr('data-type');
    var importFields = $('form#selectImportFields').serialize();
	hidePopups();
    //show popup window, if implemented
	if (implemented.indexOf(dataType) > -1) {
		showSpinner();
		$.post('app/admin/import-export/import-' + dataType + '.php?' + importFields, function(data) {
		if (popsize[dataType] !== undefined) {
			$('div.popup_'+popsize[dataType]).html(data);
			showPopup('popup_'+popsize[dataType]);
		} else {
			$('#popupOverlay div.popup_w700').html(data);
			showPopup('popup_w700');
		}
		hideSpinner();
		}).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
	} else {
		$.post('app/admin/import-export/not-implemented.php', function(data) {
		$('#popupOverlay div.popup_w400').html(data);
		showPopup('popup_w400');
		}).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
	}
    return false;
});
// recompute button
$('button.dataRecompute').click(function () {
	showSpinner();
	$.post('app/admin/import-export/import-recompute-select.php', function(data) {
	$('#popupOverlay div.popup_w700').html(data);
	showPopup('popup_w700');
	hideSpinner();
	}).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});


/*	Fix database
***********************/
$(document).on('click', '.btn-tablefix', function() {
	var tableid = $(this).attr('data-tableid');
	var fieldid = $(this).attr('data-fieldid');
	var type 	= $(this).attr('data-type');
    $.post('app/admin/verify-database/fix.php', {tableid:tableid, fieldid:fieldid, type:type}, function(data) {
        $('div#fix-result-'+tableid+fieldid).html(data).fadeIn('fast');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
    return false;
});


/* Bootstap table
***********************/
$('table#manageSubnets').on('click','button.editSubnet', function() {
    showSpinner();
    var sectionId   = $(this).attr('data-sectionid');
    var subnetId    = $(this).attr('data-subnetid');
    var action         = $(this).attr('data-action');
    //format posted values
    var postdata    = "sectionId=" + sectionId + "&subnetId=" + subnetId + "&action=" + action;

    //load edit data
    $.post("app/admin/subnets/edit.php", postdata, function(data) {
        $('#popupOverlay div.popup_w700').html(data);
        showPopup('popup_w700');
        hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); }); return false;
});
//change subnet permissions
$('table#manageSubnets').on('click','button.showSubnetPerm', function() {
	showSpinner();
	var subnetId  = $(this).attr('data-subnetId');
	var sectionId = $(this).attr('data-sectionId');

	$.post("app/admin/subnets/permissions-show.php", {subnetId:subnetId, sectionId:sectionId}, function(data) {
        $('#popupOverlay div.popup_w500').html(data);
        showPopup('popup_w500');
		hideSpinner();
    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); }); return false;
});
$('table#manageSubnets').on('click','button.add_folder', function() {
	showSpinner();
    var subnetId  = $(this).attr('data-subnetId');
    var sectionId = $(this).attr('data-sectionId');
    var action    = $(this).attr('data-action');
    //format posted values
    var postdata     = "sectionId="+sectionId+"&subnetId="+subnetId+"&action="+action+"&location=IPaddresses";

    $.post('app/admin/subnets/edit-folder.php', postdata, function(data) {
        $('#popupOverlay div.popup_w700').html(data);
        showPopup('popup_w700');
        hideSpinner();
	}).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); }); return false;
});

return false;
});
