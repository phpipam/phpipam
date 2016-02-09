<?php

/*
 * CSV import form + guide
 *************************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

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
$permission = $Subnets->check_permission ($User->user, $_POST['subnetId']);

# die if write not permitted
if($permission < 2) { $Result->show("danger", _('You cannot write to this subnet'), true); }

# fetch subnet details
$subnet = $Subnets->fetch_subnet (null, $_POST['subnetId']);
$subnet!==false ? : $Result->show("danger", _("Invalid ID"), true, true);

# full
if ($_POST['type']!="update-icmp" && $subnet->isFull==1)                { $Result->show("warning", _("Cannot scan as subnet is market as used"), true, true); }

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
	?>

	<!-- notes -->
	<?php print _('To successfully import data please use the following XLS/CSV structure:<br>( ip | State | Description | hostname | MAC | Owner | Device | Port | Note '); ?> <?php print $custFields; ?> )
	<br>
	<img src="css/1.2/images/csvuploadexample.jpg" style="border:1px solid #999999">
	<br><br>

	<!-- Download template -->
	<a class="csvtemplate btn btn-sm btn-default pull-right" id="csvtemplate">Download template</a>

	<!-- Upload file form -->
	<h4>1.) <?php print _('Upload file'); ?>:</h4>
	<hr>

	<form id="csvimport" method="post" action="app/subnets/import-subnet/import-verify.php" enctype="multipart/form-data">
	<div id="drop">
		<input type="file" name="file" id="csvfile" style="display:none;">

		<?php print _('Select CSV file'); ?>: <a class="btn btn-sm btn-default">Browse</a>
	</div>
	<span class="fname" style="display:none"></span>

	<ul class="progressUl">
	<!-- The file uploads will be shown here -->
	</ul>

	</form>


    <!-- jQuery File Upload Dependencies -->
    <script src="js/1.2/uploader/jquery.ui.widget.js"></script>
    <script src="js/1.2/uploader/jquery.iframe-transport.js"></script>
    <script src="js/1.2/uploader/jquery.fileupload.js"></script>


    <script type="text/javascript">
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
	        	$('.fname').html(data.files[0].name);

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
				var resp = jQuery.parseJSON(e);
				//get status
				var respStat = resp['status'];
	            //success
	            if(respStat == "success") {
	            	$('ul.progressUl li.alert').addClass('alert-success');		//add success class
	            	$('ul.progressUl li.alert p').append('<br><strong>Upload successfull</strong>');	//add ok sign
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