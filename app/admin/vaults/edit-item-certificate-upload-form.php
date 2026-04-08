<?php

/**
 * Script to display vault edit result
 *************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database       = new Database_PDO;
$User           = new User ($Database);
$Admin          = new Admin ($Database, false);
$Result         = new Result ();
$Password_check = new Password_check ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

// print content
$html = [];

// public certificate only
if($POST->type=="public" || $POST->type=="pkcs12") {
	$html[] = "<tr>";
	$html[] = "<td></td>";
	$html[] = "<td>";
	$html[] = '<form id="csvimport" method="post" data-type="'.$POST->type.'" action="app/admin/vaults/import-certificate-file-verify.php" enctype="multipart/form-data">';
	$html[] = '<div id="drop">';
	$html[] = '	<input type="file" name="file" id="csvfile" style="display:none;">';
	$html[] = '<a class="btn btn-sm btn-default">'._("Select certificate").'</a>';
	$html[] = '</div>';
	$html[] = '<span class="fname" style="display:none"></span>';
	$html[] = '<ul class="progressUl" style="padding-left:0px;"></ul>';
	$html[] = '</form>';
	$html[] = "</td>";
	$html[] = "<td class='info2'>"._("Select certificate file to upload")."</td>";
	$html[] = "</tr>";
}
// public + private
elseif($POST->type=="certificate") {
    $html[] = "<tr>";
    $html[] = "<td></td>";
    $html[] = "<td>";
    $html[] = '<form id="csvimport" method="post" data-type="'.$POST->type.'" action="app/admin/vaults/import-certificate-file-verify.php" enctype="multipart/form-data">';
    $html[] = '<div id="drop">';
    $html[] = ' <input type="file" name="file" id="csvfile" style="display:none;" multiple>';
    $html[] = '<a class="btn btn-sm btn-default">'._("Select files").'</a>';
    $html[] = '</div>';
    $html[] = '<span class="fname" style="display:none"></span>';
    $html[] = '<ul class="progressUl" style="padding-left:0px;"></ul>';
    $html[] = '</form>';
    $html[] = "</td>";
    $html[] = "<td class='info2'>"._("Select certificate and private key file to upload")."</td>";
    $html[] = "</tr>";
}
// public + private
elseif($POST->type=="website") {
	$html[] = "<tr>";
	$html[] = "<td></td><td>";
	$html[] = "<div class='row'>";
	$html[] = "<div class='input-group'>";
	$html[] = "	<input class='form-control input-sm' name='website' value='https://'>";
	$html[] = "	<span class='input-group-btn'>";
	$html[] = "	<button class='btn btn-sm btn-success fetch_certificate' type='button'>"._("Fetch")."</button>";
	$html[] = "	</span>";
	$html[] = "</div>";
	$html[] = "</div>";
	$html[] = "</td>";
	$html[] = "<td class='info2'>"._("Enter website URL")."</td>";
	$html[] = "</tr>";

	$html[] = "<tr>";
	$html[] = "<td></td><td>";
	$html[] = " <input type='checkbox' id='verify_peer' checked='On'>";
	$html[] = "</td>";
	$html[] = "<td class='info2'>"._("Verify certificate chain")."</td>";
	$html[] = "</tr>";

	$html[] = "<tr>";
	$html[] = "<td colspan='3'><div class='fetch-result' style='margin-top: 5px;'></td>";
	$html[] = "</td>";
}
// error
else {
	$html[] = "<tr>";
	$html[] = "<td></td>";
	$html[] = "<td colspan='2'>".$Result->show("danger", _("Invalid certificate type"), false, false, true)."</td>";
	$html[] = "</tr>";
}

// print
print implode("\n", $html);
?>

<script>
$(function(){

    var ul = $('#csvimport ul');

    $('#drop a').click(function(){
        // Simulate a click on the file input button to show the file browser dialog
        $(this).parent().find('input').click();
    });

    // Initialize the jQuery File Upload plugin
    $('#csvimport').fileupload({
        // This element will accept file drag/drop uploading
        dropZone: $('#drop'),
        // This function is called when a file is added to the queue;
        // either via the browse button, or via drag/drop:
        add: function (e, data) {
        	//remove all old references
        	$('ul.progressUl li').remove();
        	//add name to hidden class for magic.js
        	$('.fname').text(data.files[0].name);
            var tpl = $('<li class="alert"><p></p><span></span></li>');
            // Append the file name and file size
            //--tpl.find('p').text(data.files[0].name).append(' (<i>' + formatFileSize(data.files[0].size) + '</i>)');
            // Add the HTML to the UL element
            data.context = tpl.appendTo(ul);
            // Listen for clicks on the cancel icon
            tpl.find('span').click(function(){
                if(tpl.hasClass('working')){
                    jqXHR.abort();
                }
                tpl.fadeOut(function(){
                    tpl.remove();
                });

            });

            // append pass if required
            if($('#csvimport').attr('data-type')=="pkcs12" || $('#csvimport').attr('data-type')==("certificate")) {
                var pkey_pass = prompt(<?php print '"Enter private key password"'; ?>);
                data.formData = $.extend({}, {'pkey_pass': pkey_pass}, data.formData);
            }

            // Automatically upload the file once it is added to the queue
            var jqXHR = data.submit();
        },

        fail:function(e, data){
            // Something has gone wrong!
            $('ul.progressUl li.alert').addClass('alert alert-danger');
        },
        success:function(e, data){
            // All good, check for response!
            try {
                var resp = jQuery.parseJSON(e);
            } catch (e) {
                // error
            	$('ul.progressUl li.alert').addClass('alert alert-danger');		//add error class
            	$('li.alert p').append("<strong>Error: Error parsing json response</strong>");
                return;
            }
            //success
            if(resp['status'] == "success") {
            	// save to session storage
				if (typeof(Storage) !== "undefined") {
					$('#drop').remove();
					sessionStorage.certificate = resp['certificate']
                    append_cert_field (resp['certificate'])
				} else {
	            	$('ul.progressUl li.alert').addClass('alert alert-danger');		//add error class
	            	$('li.alert p').append("<strong>Error: Session storage not supoorted</strong>");
				}

            	$('ul.progressUl li.alert').addClass('alert-success');		//add success class
            	$('ul.progressUl li.alert p').append('Upload successfull.');	//add ok sign
            }
            //error
            else {
            	//get error message
				var respErr = resp['error'];
            	$('ul.progressUl li.alert').addClass('alert alert-danger');		//add error class
            	$('li.alert p').append("<strong>Error: "+respErr+"</strong>");
            }

        }
    });

    // Prevent the default action when a file is dropped on the window
    $(document).on('drop dragover', function (e) {
        e.preventDefault();
    });

    // Helper function that formats the file sizes
    function formatFileSize(bytes) {
        if (typeof bytes !== 'number') 	{ return ''; }
        if (bytes >= 1000000000) 		{  return (bytes / 1000000000).toFixed(2) + ' GB'; }
        if (bytes >= 1000000) 			{ return (bytes / 1000000).toFixed(2) + ' MB'; }
        //return result
        return (bytes / 1000).toFixed(2) + ' KB';
    }

    // append certificate
    function append_cert_field (value) {
        // remove if exists
        if($('input[name=certificate]').length) {
            $('input[name=certificate]').remove();
        }
        // append new
        $('td.td-items').append("<input type='hidden' name='certificate' value='"+value+"'>")
    }
})
</script>
