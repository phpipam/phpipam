<?php

/**
 * Usermenu - passkeys
 */

# verify that user is logged in
$User->check_user_session();
?>


<h4><?php print _('Passwordless authentication'); ?></h4>
<hr>
<span class="info2"><?php print _("Here you can manage passkey authentication for your account"); ?>. <?php print _("Passkeys are a password replacement that validates your identity using touch, facial recognition, a device password, or a PIN"); ?>.</span>
<br><br>

<?php

# tls check
if (!$Tools->isHttps()) {
	$Result->show("danger alert-absolute", _("TLS is required for passcode authentication"), false);
}
# are passkeys enabled ?
elseif (!$User->settings->dbversion >= 40 && $User->settings->{'passkeys'}=="1") {
	$Result->show("danger alert-absolute", _("Passkey authentication is disabled"), false);
}
# is composer present ?
elseif($User->composer_has_errors(["firehed/webauthn", "firehed/cbor"])) {
	$Result->show("danger alert-absolute", $User->composer_err, false);
}
# is
else {
	// get user passkeys
	$user_passkeys = $User->get_user_passkeys(false);


	# passkey 0nly ?
	if(sizeof($user_passkeys)>0 && $User->user->passkey_only=="1") {
		$Result->show("warning alert-absolute", _("You can login to your account with with passkeys only").".<hr>"._("This can be changed under Account details tab").".");
	}
	elseif($User->user->passkey_only=="1") {
		$Result->show("warning alert-absolute", _("You can login to your account with normal authentication method only untill you create passkeys".".<hr>"._("This can be changed under Account details tab.")));
	}
	else {
		$Result->show("warning alert-absolute", _("You can login to your account with normal authentication method or with passkeys".".<hr>"._("This can be changed under Account details tab.")));
	}
	print "<div class='clearfix'></div>";

	// none ?
	if (sizeof($user_passkeys)>0) {
		print '<div class="panel panel-default" style="max-width:600px">';
		print '<div class="panel-heading">'._("Your passkeys").'</div>';
		print '	<ul class="list-group">';

		foreach ($user_passkeys as $passkey) {

			// format last used and created
			$created          = date("M d, Y", strtotime($passkey->created));
			$last_used        = is_null($passkey->used) ? _("Never") : date("M d, Y", strtotime($passkey->used));
			$passkey->comment = is_null($passkey->comment) ? "-- Unknown --" : $passkey->comment;
			$this_browser	  = $passkey->keyId == @$_SESSION['keyId'] ? "<span class='badge' style='margin-bottom:2px;margin-left:10px;'>"._("You authenticated with this passkey")."</span>" : "";

			print '<li class="list-group-item">';
			print "<div>";
			print '	<div style="width:40px;float:left" class="text-muted">';
			print '	<span class="float-left text-center text-muted">
            			<svg height="40" aria-hidden="true" viewBox="0 -8 32 32" version="1.1" width="40" data-view-component="true" class="octicon octicon-passkey-fill" style="color:red !important;">
    						<path d="M9.496 2a5.25 5.25 0 0 0-2.519 9.857A9.006 9.006 0 0 0 .5 20.228a.751.751 0 0 0 .728.772h5.257c3.338.001 6.677.002 10.015 0a.5.5 0 0 0 .5-.5v-4.669a.95.95 0 0 0-.171-.551 9.02 9.02 0 0 0-4.814-3.423A5.25 5.25 0 0 0 9.496 2Z"></path>
    						<path d="M23.625 10.313c0 1.31-.672 2.464-1.691 3.134a.398.398 0 0 0-.184.33v.886a.372.372 0 0 1-.11.265l-.534.534a.188.188 0 0 0 0 .265l.534.534c.071.07.11.166.11.265v.347a.374.374 0 0 1-.11.265l-.534.534a.188.188 0 0 0 0 .265l.534.534a.37.37 0 0 1 .11.265v.431a.379.379 0 0 1-.097.253l-1.2 1.319a.781.781 0 0 1-1.156 0l-1.2-1.319a.379.379 0 0 1-.097-.253v-5.39a.398.398 0 0 0-.184-.33 3.75 3.75 0 1 1 5.809-3.134ZM21 9.75a1.125 1.125 0 1 0-2.25 0 1.125 1.125 0 0 0 2.25 0Z"></path>
						</svg>';
          	print '	</span>';
    		print "	</div>";

          	print "<div class='pull-left' style='padding-top:8px;'>";
          	print "<strong>".$User->strip_input_tags($passkey->comment)."</strong> ".$this_browser;
          	print "</div>";

			print '	<div class="btn-group pull-right" style="padding-top:8px;">';
			print '		<button class="btn btn-xs btn-default open_popup" data-script="app/tools/user-menu/passkey_edit.php" data-action="edit" data-keyId="'.$passkey->keyId.'" rel="tooltip" title="" data-original-title="'._("Rename").'"><i class="fa fa-pencil"></i></button>';
			print '		<button class="btn btn-xs btn-default open_popup" data-script="app/tools/user-menu/passkey_edit.php" data-action="delete" data-keyId="'.$passkey->keyId.'" rel="tooltip" title="" data-original-title="'._("Delete").'"><i class="fa fa-times"></i></button>';
			print '	</div>';

			// print "<div class='clearfix'></div>";
			print "<br><br>";
          	print "<span class='text-muted' style='padding-left:0px;'>"._("Added on")." ".$created." :: "._("Last used")." $last_used</span>";
			print '</div>';


			print '</li>';
		}
		print '	</ul>';
		print '</div>';
	}
	// result
	print '<div id="loginCheckPasskeys" style="max-width:600px"></div>';

	// add
	print '<button class="btn btn-sm btn-success addPasskey"><i class="fa fa-plus"></i> '._("Add a passkey").'</button>';
}
?>


<script type="text/javascript">

function loginRedirect2() {
    location.reload()
}

// register function
const startRegister = async (e) => {

	// check if browser supports webauthn
    if (!window.PublicKeyCredential) {
        return
    }

    try {
    	// get and parse challenge
		const challengeReq = await fetch('app/tools/user-menu/passkey_challenge.php')
		const challenge    = await challengeReq.json()

		// create
	    const createOptions = {
	        publicKey: {
	            rp: {
	                name: '<?php print $User->createURL (); ?>',
	            },
	            user: {
	                name: "<?php print $User->user->username; ?>",
	                displayName: "<?php print $User->user->real_name; ?>",
	                id: Uint8Array.from("<?php print $User->user->id; ?>", c => c.charCodeAt(0)),
	            },
	            // This base64-decodes the response and translates it into the Webauthn-required format.
	            // challenge: Uint8Array.from(atob(challenge), c => c.charCodeAt(0)),
	            challenge: Uint8Array.from(atob(challenge), c => c.charCodeAt(0)),
	            pubKeyCredParams: [
	                {
	                    alg: -7, // ES256
	                    type: "public-key",
	                }
	            ]
	        },
	        attestation: 'direct',
	    }

	    // Call the WebAuthn browser API and get the response. This may throw, which you
	    // should handle. Example: user cancels or never interacts with the device.
	    const credential = await navigator.credentials.create(createOptions)

	    // Format the credential to send to the server. This must match the format
	    // handed by the ResponseParser class. The formatting code below can be used
	    // without modification.
	    const dataForResponseParser = {
	        // rawId: Array.from(new Uint8Array(credential.rawId)),
	        rawId: Array.from(new Uint8Array(credential.rawId)),
	        keyId: credential.id,
	        type: credential.type,
	        attestationObject: Array.from(new Uint8Array(credential.response.attestationObject)),
	        clientDataJSON: Array.from(new Uint8Array(credential.response.clientDataJSON)),
	        transports: credential.response.getTransports(),
	    }

	    // Send this to your endpoint - adjust to your needs.
	    const request = new Request('app/tools/user-menu/passkey_save.php', {
	        body: JSON.stringify(dataForResponseParser),
	        headers: {
	            'Content-type': 'application/json',
	        },
	        method: 'POST',
	    })
	    const result = await fetch(request)

	    // process result
        if(result.status==200) {
            // $('#loginCheckPasskeys').html("<div class='alert alert-success'>New passkey registered!</div>");

            // open popup to name passkey
		    $('div.loading').show();
		    // post
		    $.post("/app/tools/user-menu/passkey_edit.php", {"keyid":credential.id, "action":"add"}, function(data) {
		        // set content
		        $('#popupOverlay .popup_w500').html(data).show();
		        // show overlay
		        $("#popupOverlay").fadeIn('fast');
		        $('#popupOverlay2 > div').empty();
		        $('div.loading').hide();
    			//disable page scrolling on bottom
    			$('body').addClass('stop-scrolling');
    			// reset size
        		var myheight = $(window).height() - 250;
        		$(".popup .pContent").css('max-height', myheight);

		    }).fail(function(jqxhr, textStatus, errorThrown) {

		    	$('div.jqueryError').fadeIn('fast');
    			$('.jqueryErrorText').html(jqxhr.statusText+"<br>Status: "+textStatus+"<br>Error: "+errorThrown).show();
    			$('div.loading').hide();
    		});
        }
        else {
            $('#loginCheckPasskeys').html("<div class='alert alert-danger'>Failed to register new passkey. <strong>Error : </strong>"+result.statusText+"</div>");
            console.log(result)
            $('div.loading').hide();
        }
	}
	catch(err) {
		$('#loginCheckPasskeys').html("<div class='alert alert-danger'>Failed to register new passkey.</div>");
		console.log(err);
	}
}


// Start registration of new passkey
$(document).ready(function() {
	// check if browser supports webauthn and disable add passkey button
    if (!window.PublicKeyCredential) {
        $('.addPasskey').addClass('disabled').removeClass('addPasskey')
    }
	// add passkey
	$('.addPasskey').click(function () {
		startRegister ()
		return false;
	})

})


</script>