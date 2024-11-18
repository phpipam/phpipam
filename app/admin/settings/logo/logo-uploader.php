<?php

/**
 *	Site settings
 **************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "settings", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true, true) : "";

# clear identifier
$clear = true;

// set width
$logo_width = isset($config['logo_width']) ? $config['logo_width'] : 220;

if(!file_exists( dirname(__FILE__)."/../../../../css/images/logo/logo.png")) {
    require( dirname(__FILE__).'/logo-builtin.php' );
    $clear = false;
}
else {
    $img = "<img style='max-width:".$logo_width."px;margin-top:15px;margin-bottom:20px;' alt='phpipam' src='css/images/logo/logo.png'>";
}
?>

<!-- header -->
<div class="pHeader"><?php print $User->get_post_action(); ?> <?php print _('Upload custom logo'); ?></div>

<!-- content -->
<div class="pContent">

    <?php if (!is_writeable( dirname(__FILE__) . '/../../../../css/images/logo' )) $Result->show("danger", _("'css/images/logo' folder is not writeable."), false, false); ?>

    <h4><?php print _("Current"); ?><?php if($clear) print "<a class='btn btn-xs btn-danger logo-clear pull-right' style='text-shadow:none'><i class='fa fa-times'></i></a>"; ?></h4>
    <div class='logo-current'>
        <?php print $img; ?>
    </div>
    <hr>

    <h4><?php print _("New"); ?></h4>

    <span class="text-muted"><?php print _("Image will be shown in")." ".$logo_width._("px width in phpipam header and mail headers"); ?>.</span>

	<form id="csvimport" method="post" action="app/admin/settings/logo/import-verify.php" enctype="multipart/form-data">
	<div id="drop">
		<input type="file" name="file" id="csvfile" style="display:none;">

		<?php print _('Select image'); ?>: <a class="btn btn-sm btn-default"><?php print _("Browse"); ?></a>
	</div>
	<span class="fname" style="display:none"></span>

	<ul class="progressUl">
	<!-- The file uploads will be shown here -->
	</ul>

	</form>


    <!-- jQuery File Upload Dependencies -->
    <script src="js/uploader/jquery.ui.widget.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
    <script src="js/uploader/jquery.iframe-transport.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
    <script src="js//uploader/jquery.fileupload.js?v=<?php print SCRIPT_PREFIX; ?>"></script>


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
	            tpl.find('p').text(data.files[0].name).append(' (<i>' + formatFileSize(data.files[0].size) + '</i>)');

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
	            	$('li.alert p').append("<br><strong>Error: Error parsing json response</strong>");

	                return;
	            }
	            //get status
	            var respStat = resp['status'];
	            //success
	            if(respStat == "success") {
	            	$('ul.progressUl li.alert').addClass('alert-success');		//add success class
	            	$('ul.progressUl li.alert p').append('<br><strong>Upload successfull</strong>');	//add ok sign
	            	// reload
	            	$('div.loading').show();
	            	setTimeout(function (){window.location.reload();}, 1000);
	            }
	            //error
	            else {
	            	//get error message
					var respErr = resp['error'];
	            	$('ul.progressUl li.alert').addClass('alert alert-danger');		//add error class
	            	$('li.alert p').append("<br><strong>Error: "+respErr+"</strong>");
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

	});
    </script>


</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
	</div>
	<!-- result -->
	<div class="save-logo-result"></div>
</div>
