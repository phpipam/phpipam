/**
 *
 * Javascript / jQuery login functions
 *
 *
 */

/*  loading spinner functions
*******************************/
function showSpinner(hide_res = true) {
    $('div.loading').show();
    $('input[type=submit]').addClass("disabled");
    $('button.passkey_login').addClass("disabled");
    if (hide_res) {
        $('#loginCheckPasskeys').fadeOut('fast');
        $('#loginCheck').fadeOut('fast');
    }
}
function hideSpinner() {
    $('div.loading').fadeOut('fast');
    $('input[type=submit]').removeClass("disabled");
    $('button.passkey_login').removeClass("disabled");
}



$(document).ready(function() {

/* hide error div if jquery loads ok
*********************************************/
$('div.jqueryError').hide();
$('div.loading').hide();




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

    // post to check form
    $.post('app/login/login_check.php', logindata, function(data) {
        $('div#loginCheck').html(data).fadeIn('fast');
        //reload after 2 seconds if succeeded!
        if(data.search("alert alert-success") != -1) {
            showSpinner(false);
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


// passkey login
$('.passkey_login').click(function() {
    if(!$(this).hasClass('disabled')) {
        // execute passkey login
        startLogin();
    }
    // prevent submit
    return false;
})

});



function loginRedirect2() {
    var base = $('.iebase').html();
    window.location=base;
}


const startLogin = async (e) => {

    // check if browser supports webauthn
    if (!window.PublicKeyCredential) {
        return
    }

    // show login window
    showSpinner();

    try {
        // get and parse challenge
        const challengeReq = await fetch('app/tools/user-menu/passkey_challenge.php')
        const challengeB64 = await challengeReq.json()
        const challenge    = atob(challengeB64) // base64-decode

        // Format for WebAuthn API
        const getOptions = {
            publicKey: {
                challenge: Uint8Array.from(challenge, c => c.charCodeAt(0)),
                allowCredentials: [],
                mediation: 'conditional',
            },
        }

        // Call the WebAuthn browser API and get the response. This may throw, which you
        // should handle. Example: user cancels or never interacts with the device.
        const credential = await navigator.credentials.get(getOptions)
        // console.log(credential)

        // Format the credential to send to the server. This must match the format
        // handed by the ResponseParser class. The formatting code below can be used
        // without modification.
        const dataForResponseParser = {
            rawId: Array.from(new Uint8Array(credential.rawId)),
            keyId: credential.id,
            type: credential.type,
            authenticatorData: Array.from(new Uint8Array(credential.response.authenticatorData)),
            clientDataJSON: Array.from(new Uint8Array(credential.response.clientDataJSON)),
            signature: Array.from(new Uint8Array(credential.response.signature)),
            userHandle: Array.from(new Uint8Array(credential.response.userHandle)),
        }

        // Send this to your endpoint - adjust to your needs.
        const request = new Request('app/login/passkey_login_check.php', {
            body: JSON.stringify(dataForResponseParser),
            headers: {
                'Content-type': 'application/json',
            },
            method: 'POST',
        })
        const result = await fetch(request)

        // process result by http status returned from passkey_login_check
        if(result.status==200) {
            $('#loginCheckPasskeys').html("<div class='alert alert-success'>Passkey authentication successfull</div>").show();
            setTimeout(loginRedirect2, 1000)
        }
        else {
            $('#loginCheckPasskeys').html("<div class='alert alert-danger'>Passkey authentication failed!</div>").show();
            console.log(result)
            hideSpinner()
        }
    }
    // handle throwable error
    catch(err) {
        $('#loginCheckPasskeys').html("<div class='alert alert-danger'>Passkey authentication failed!</div>").show();
        console.log(err)
        hideSpinner();
    }
}