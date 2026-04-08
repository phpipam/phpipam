<?php

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object, if not already set
if (!isset($Database)) { $Database 	= new Database_PDO; }
if (!isset($User)) { $User = new User ($Database); }
if (!isset($Tools)) { $Tools = new Tools ($Database); }

# verify that user is logged in, to guard against direct access of page and possible exploits
$User->check_user_session();

?>

<form id="dataimport" method="post" action="app/admin/import-export/import-verify.php" enctype="multipart/form-data">
	<table><tr><td align="left">
	<div id="drop">
		<input type="file" name="file" id="csvfile" style="display:none;">
		<input type="hidden" name="expfields" value="<?php print implode('|',$expfields); ?>" style="display:none;">

		<?php print _('Select file'); ?>: <a class="btn btn-sm btn-default"><i class="fa fa-folder-open"></i> <?php print _("Browse / Drag & Drop"); ?></a>
	</div>
	</td><td>&nbsp;</td><td align="right">
	<!-- Download template -->
	<?php print "<a class=\"csvtemplate btn btn-sm btn-default\" id=\"". $templatetype . "template\">"._("Download template")."</a>"; ?>
	</td></tr></table>
	<span class="fname" style="display:none"></span>
	<br>
	<!-- Download result -->
	<div id="uploadResult"></div>
</form>

<!-- jQuery File Upload Dependencies -->
<script src="js/uploader/jquery.ui.widget.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
<script src="js/uploader/jquery.iframe-transport.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
<script src="js/uploader/jquery.fileupload.js?v=<?php print SCRIPT_PREFIX; ?>"></script>

<script>
$(function(){

	$('#drop a').click(function(){
		// Simulate a click on the file input button to show the file browser dialog
		$(this).parent().find('input').click();
	});

	// Initialize the jQuery File Upload plugin
	$('#dataimport').fileupload({

		// This element will accept file drag/drop uploading
		dropZone: $('#drop'),

		// This function is called when a file is added to the queue;
		// either via the browse button, or via drag/drop:
		add: function (e, data) {

			//remove all old references
			// $('ul.progressUl li').remove();
			$('#uploadResult').empty();
			$('#uploadResult').removeClass('alert alert-success alert-danger');

			//clear the fields selection div
			$('#fieldsrow').remove();
			$('#bottommsg').empty();
			$('#bottommsg').removeClass('alert alert-success alert-warning');

			//add name to hidden class for magic.js
			$('.fname').text(data.files[0].name);

			// Append the file name and file size
			$('#uploadResult').append(data.files[0].name + ' (<i>' + formatFileSize(data.files[0].size) + '</i>)');
			$('#uploadResult').append(' <span rel="tooltip" data-placement="bottom" title="<?php print _("Cancel upload");?>"> <i class="fa fa-times-circle" ></i></span>');

			// Listen for clicks on the cancel icon
			$('#uploadResult').find('span').click(function(){
				if($('#uploadResult').hasClass('working')){
					jqXHR.abort();
				}
				$('#uploadResult').empty();
				$('#uploadResult').removeClass('alert alert-success alert-danger');
			});

			// Automatically upload the file once it is added to the queue
			var jqXHR = data.submit();
		},

		fail:function(e, data){
			// Something has gone wrong!
			$('#uploadResult').addClass('alert alert-danger');
		},
		success:function(e, data){
            // All good, check for response!
            try {
                var resp = jQuery.parseJSON(e);
            } catch (e) {
                // error
                $('ul.progressUl li.alert').addClass('alert alert-danger');		//add error class
                $('li.alert p').append('<?php print "<br><strong>"._("Error: Error parsing json response")."</strong>"; ?>');

                return;
            }
            //get status
            var respStat = resp['status'];

			//success
			if(respStat == "success") {
				$('#uploadResult').addClass('alert alert-success');		//add success class
				$('#uploadResult').append('<?php print "<br><strong>"._("Upload successfull")."</strong>"; ?>');	//add ok sign
				$('#uploadResult').find('span').remove(); // remove cancel upload button

				if (resp.impfields && resp.expfields) {
					var matches = 0;

					$('#topmsg').empty();
					$('#bottommsg').empty();
					$('#topmsg').append('<?php print "<h4>"._("Match fields")."</h4><hr>"._("Please match the DB fields with the uploaded fields:"); ?>');
				    $("#fieldstable > tbody").append('<tr id="fieldsrow"></tr>');

					resp.expfields.forEach(function(expfield) {
						//console.log(resp.fields);
						var td= $('<td></td>').appendTo("#fieldstable > tbody #fieldsrow");
						var s = $('<select name="importFields__' + expfield.replace(/\s/g,"_") + '" class="form-control input-sm input-w-auto" rel="tooltip" data-placement="bottom" title="<?php print _("Pick import colum for"); ?> ' + expfield + ' <?php print _("field"); ?>"/>');
						$('<option />', {value: "-", text: "-"}).appendTo(s);
						resp.impfields.forEach(function(impfield) {
                                                        if (expfield.toUpperCase() === impfield.toUpperCase().replace("IP ADDRESS", "IP_ADDR")) {
								$('<option />', {value: impfield, text: impfield, selected: true}).appendTo(s);
								matches++;
							} else {
								$('<option />', {value: impfield, text: impfield}).appendTo(s);
							}
						});
						s.appendTo(td);
				    });
					if (matches == 0) {
						$('#bottommsg').addClass('alert alert-danger');
						$('#bottommsg').append('<i class="fa fa-exclamation-triangle"></i> <?php print _("No fields were automatically matched. The import file needs to have a header row!"); ?><br>');
					}
					if ((matches > 0) && (matches != resp.expfields.length)) {
						// console.log(matches + " mismatches vs " + resp.expfields.length);
						$('#bottommsg').addClass('alert alert-warning');
						$('#bottommsg').append('<i class="fa fa-exclamation-triangle"></i> <?php print _("Not all the fields were automatically matched. Please check manually."); ?><br>');
					}
					if (matches == resp.expfields.length) {
						// console.log(matches + " matches vs " + resp.expfields.length);
						$('#bottommsg').addClass('alert alert-success');
						$('#bottommsg').append('<i class="fa fa-info-circle"></i> <?php print _("All the fields were automatically matched. Please check if correct."); ?><br>');
					}
					// enable preview button
				    $('#dataImportPreview').removeAttr('disabled');
				    $('#dataImportPreview').removeClass('btn-default');
				    $('#dataImportPreview').addClass('btn-success');
					// add the filetype to the hidden input to be used in the preview section
					$('#filetype').val(resp.filetype);

				} else {
					$('#topmsg').append('<?php print _("No header row found in uploaded file. Please check."); ?><br>');
				}

			}
			//error
			else {
				//get error message
				var respErr = resp['error'];
				$('#uploadResult').addClass('alert alert-danger');		//add error class
				$('#uploadResult').append("<br><strong><?php print _("Error"); ?>: "+respErr+"</strong>");
				$('#uploadResult').find('span').remove(); // remove cancel upload button
				// disable preview button
				$('#dataImportPreview').attr('disabled', 'disabled');
			    $('#dataImportPreview').removeClass('btn-success');
			    $('#dataImportPreview').addClass('btn-default');
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
