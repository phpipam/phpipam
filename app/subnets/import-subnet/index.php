<?php

/*
 * CSV import form + guide
 *************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# classes
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Tools	 	= new Tools ($Database);
$Addresses	= new Addresses ($Database);
$Subnets	= new Subnets ($Database);
$Result 	= new Result;

# verify that user is logged in
$User->check_user_session();

# permissions
$permission = $Subnets->check_permission ($User->user, $POST->subnetId);

# die if write not permitted
if($permission < 2) { $Result->show("danger", _('You cannot write to this subnet'), true); }

# fetch subnet details
$subnet = $Subnets->fetch_subnet (null, $POST->subnetId);
if (!is_object($subnet)) {
	$Result->show("danger", _("Invalid ID"), true, true);
}

# full
if ($POST->type!="update-icmp" && $subnet->isFull==1)                { $Result->show("warning", _("Cannot scan as subnet is market as used"), true, true); }

# get custom fields
$custom_address_fields = $Tools->fetch_custom_fields('ipaddresses');
?>

<!-- header -->
<div class="pHeader"><?php print _('XLS / CSV subnet import'); ?></div>


<!-- content -->
<div class="pContent">

	<?php
	# get custom fields
	if(sizeof($custom_address_fields) > 0) {
		$custFields = " | ";
		foreach($custom_address_fields as $myField) {
			$custFields .= "$myField[name] | ";
		}
		# remove last |
		$custFields = substr($custFields, 0,-2);
	}

	# set standard fields
	$standard_fields = array ("ip address","ip state","description","hostname","fw_object","mac","owner","device","port","notes", "location");

	if (!is_writeable( dirname(__FILE__) . '/upload' )) $Result->show("danger", _("'app/subnets/import-subnet/upload' folder is not writeable."), false, false);
	?>

	<!-- notes -->
	<?php print _('To successfully import data please use the following XLS/CSV structure:'); ?><br>
	<div class="alert alert-info alert-absolute">
	<?php print implode(" | ", $standard_fields).$custFields; ?>
	</div>
	<div class="clearfix"></div>

	<!-- Download template -->
	<a class="csvtemplate btn btn-sm btn-default" id="csvtemplate"><?php print _("Download template"); ?></a>

	<br><br>

	<!-- Upload file form -->
	<h4>1.) <?php print _('Upload file'); ?>:</h4>
	<hr>

	<form id="csvimport" method="post" action="app/subnets/import-subnet/import-verify.php" enctype="multipart/form-data">
	<div id="drop">
		<input type="file" name="file" id="csvfile" style="display:none;">

		<?php print _('Select CSV file'); ?>: <a class="btn btn-sm btn-default"><?php print _("Browse"); ?></a>
	</div>
	<span class="fname" style="display:none"></span>

	<ul class="progressUl">
	<!-- The file uploads will be shown here -->
	</ul>

	</form>


    <!-- jQuery File Upload Dependencies -->
    <script src="js/uploader/jquery.ui.widget.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
    <script src="js/uploader/jquery.iframe-transport.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
    <script src="js/uploader/jquery.fileupload.js?v=<?php print SCRIPT_PREFIX; ?>"></script>


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
	            	$('ul.progressUl li.alert p').append('<br><strong>Upload successful</strong>');	//add ok sign
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


	<!-- Import file -->
	<h4>2.) <?php print _('Import file and show addresses'); ?>:</h4>
	<hr>

	<!-- import button -->
	<input type="button" class="btn btn-sm btn-default" value="<?php print _('Show uploaded subnets'); ?>" id="csvimportcheck">

	<!-- verification holder -->
	<div class="csvimportverify"></div>

	<!-- result -->
	<div class="csvImportResult"></div>
</div>

<!-- footer -->
<div class="pFooter importFooter">
	<button class="btn btn-sm btn-default hidePopups"><?php print _('Close window'); ?></button>
</div>
