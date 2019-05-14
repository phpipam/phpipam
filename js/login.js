/**
 *
 * Javascript / jQuery login functions
 *
 *
 */


$(document).ready(function() {

/* hide error div if jquery loads ok
*********************************************/
$('div.jqueryError').hide();
$('div.loading').hide();


/*	loading spinner functions
*******************************/
function showSpinner() {
    $('div.loading').show();
}
function hideSpinner() {
    $('div.loading').fadeOut('fast');
}

/*	Login redirect function if success
****************************************/
function loginRedirect() {
	var base = $('.iebase').html();
	window.location=base;
}

/*	submit login
*********************/
$('form#login').submit(function() {
	//show spinner
	showSpinner();
    //stop all active animations
    $('div#loginCheck').stop(true,true);

    var logindata = $(this).serialize();

    $('div#loginCheck').hide();
    //post to check form
    $.post('app/login/login_check.php', logindata, function(data) {
        $('div#loginCheck').html(data).fadeIn('fast');
        //reload after 2 seconds if succeeded!
        if(data.search("alert alert-success") != -1) {
            showSpinner();
            //search for redirect
            if($('form#login input#phpipamredirect').length > 0) { setTimeout(function (){window.location=$('form#login input#phpipamredirect').val();}, 1000); }
            else 												 { setTimeout(loginRedirect, 1000);	}
        }
        else {
	        hideSpinner();
        }
    });
    return false;
});

/*  Check 2fs
*********************/
function submit_2fs (showerror) {
    if (typeof(showerror)==='undefined') showerror = true;
    //show spinner
    showSpinner();
    //stop all active animations
    $('div#twofaCheck').stop(true,true);

    var code = $('form#login_2fs input#2fa_code').val();
    var csrf = $('form#login_2fs input#csrf_cookie').val();

    $('div#twofaCheck').hide();
    //post to check form
    $.post('app/login/2fa/2fa_validate.php', {"code":code, "csrf_cookie":csrf, "show_error":showerror}, function(data) {
        $('div#twofaCheck').html(data).fadeIn('fast');
        //reload after 2 seconds if succeeded!
        if(data.search("alert alert-success") != -1) {
            $('.login_2fs .btn-group').hide();
            //search for redirect
            if($('form#login input#phpipamredirect').length > 0) { setTimeout(function (){window.location=$('form#login_2fs input#phpipamredirect').val();}, 1000); }
            else                                                 { setTimeout(loginRedirect, 1000); }
        }
        else {
            hideSpinner();
        }
    });
}

/* Submit form */
$('form#login_2fs').submit(function() {
    submit_2fs (true);
    return false;
});

/* Submit on keyup */
$(document).keyup(function(e) {
    var codeval = $('form#login_2fs input#2fa_code').val();
    if (codeval != null) {
        if(codeval.length == 6) {
            submit_2fs (true);
        }
        else if (codeval.length > 0) {
            $('div#twofaCheck').fadeOut('fast');
        }
    }
});

/*	submit IP request
*****************************************/
$(document).on("submit", "#requestIP", function() {
	var subnet = $('#requestIPsubnet').serialize();
	var IPdata = $(this).serialize();
	var postData = subnet + "&" + IPdata;

	showSpinner();

    //post to check form
    $.post('app/login/request_ip_result.php', postData, function(data) {
        $('div#requestIPresult').html(data).slideDown('fast');
        hideSpinner();
        //reset sender to prevent duplicates on success
        if(data.search("alert alert-success") != -1) {
        	$('form#requestIP input[type="text"]').val('');
			$('form#requestIP textarea').val('');
        }
    });
	return false;
});
// clear request field
$(".clearIPrequest").click(function() {
	$('form#requestIP input[type="text"]').val('');
	$('form#requestIP textarea').val('');

});

});


