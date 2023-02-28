<?php

/**
 * Script to print add / edit / delete vault item
 *************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# make sure user has access
if ($User->get_module_permissions ("vaults")<User::ACCESS_RW) { $Result->show("danger", _("Insufficient privileges").".", true, true); }

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "vaultitem");

# validate action
$Admin->validate_action ($_POST['action'], true);

# ID must be numeric
if($_POST['action']!="add" && !is_numeric($_POST['id'])) { $Result->show("danger", _("Invalid ID"), true, true); }

# fetch api for edit / add
if($_POST['action']!="add") {
	# fetch vault details
	$item = $Admin->fetch_object ("vaultItems", "id", $_POST['id']);
	# null ?
	$item===false ? $Result->show("danger", _("Invalid ID"), true) : null;
	# to json and decode
	$item_objects = pf_json_decode($User->Crypto->decrypt($item->values, $_SESSION['vault'.$item->vaultId]));
	# title
	$title =  ucwords($_POST['action']) .' '._('certificate');
} else {
	# generate new code
	$item = new StdClass;
	$item->vaultId = $_POST['vaultid'];
	# title
	$title = _('Add new certificate');
}

# fetch custom fields
$custom = $Tools->fetch_custom_fields('vaultItems');
?>

<!-- header -->
<div class="pHeader"><?php print $title; ?></div>

<!-- content -->
<div class="pContent">

	<form id="vaultItemEdit" name="vaultItemEdit" autocomplete="off">
	<table class="groupEdit table table-noborder table-condensed">

	<!-- id -->
	<tbody>
	<tr>
	    <td><?php print _('Name'); ?></td>
	    <td class='td-items'>
	    	<input type="text" name="name" class="form-control input-sm" value="<?php print $Admin->strip_xss(@$item_objects->name); ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?> autocomplete="off">
	        <input type="hidden" name="id" value="<?php print $item->id; ?>">
	        <input type="hidden" name="vaultId" value="<?php print $item->vaultId; ?>">
    		<input type="hidden" name="action" value="<?php print escape_input($_POST['action']); ?>">
    		<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
	    </td>
       	<td class="info2"><?php print _('Enter name'); ?></td>
    </tr>

    <!-- description -->
    <tr>
    	<td><?php print _('Description'); ?></td>
    	<td>
    		<input type="text" name="description" class="form-control input-sm" value="<?php print $Admin->strip_xss(@$item_objects->description); ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?>>
    	</td>
    	<td class="info2"><?php print _('Enter description'); ?></td>
    </tr>
    <tr>
    	<td colspan="3"><hr></td>
    </tr>

    <?php if($_POST['action']!="delete") { ?>

	<!-- Custom -->
	<?php
	if(sizeof($custom) > 0) {
		# count datepickers
		$timepicker_index = 0;

		# all my fields
		foreach($custom as $field) {
    		// create input > result is array (required, input(html), timepicker_index)
    		$custom_input = $Tools->create_custom_field_input ($field, $item, $_POST['action'], $timepicker_index);
    		// add datepicker index
    		$timepicker_index++;
            // print
			print "<tr>";
			print "	<td>".ucwords($Tools->print_custom_field_name ($field['name']))." ".$custom_input['required']."</td>";
			print "	<td>".$custom_input['field']."</td>";
            print " <td class='info2'>".$field['Comment']."</td>";
			print "</tr>";
		}

		print '<tr>';
		print '	<td colspan="2"><hr></td>';
		print '</tr>';
	}
	?>

    <!-- type -->
    <tr>
    	<td><?php print _('Certificate type'); ?></td>
    	<td>
    		<select name="type" class="form-control input-sm input-w-auto">
                <?php
    			// options
				$options = [
                            "website"=>"Fetch website certificate",
                            "public"=>"Certificate (.cer, .pem, .crt)",
                            "pkcs12"=>"PKCS12 Certificate with key file (.p12, .pfx)",
                            // "certificate"=>"Certificate and key file"
                            ];
				// print
				foreach ($options as $k=>$o) {
					print "<option value='$k'>"._($o)."</option>";
				}
    			?>
    		</select>
    	</td>
    	<td class="info2"><?php print _('Select certificate type'); ?></td>
    </tr>

    <?php } ?>

	</tbody>


    <!-- upload -->
    <tbody id='upload'></tbody>

</table>
</form>

</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class='btn btn-sm btn-default submit_popup <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>' data-script="app/admin/vaults/edit-item-certificate-result.php" data-result_div="vaultItemEditResult" data-form='vaultItemEdit'>
			<i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print escape_input(ucwords(_($_POST['action']))); ?>
		</button>

	</div>
	<!-- Result -->
	<div id="vaultItemEditResult"></div>
</div>



<!-- jQuery File Upload Dependencies -->
<script src="js/uploader/jquery.ui.widget.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
<script src="js/uploader/jquery.iframe-transport.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
<script src="js//uploader/jquery.fileupload.js?v=<?php print SCRIPT_PREFIX; ?>"></script>

<script type="text/javascript">
$(document).ready(function () {

    // default load
    if($('select[name=type]').length) {
        // no change
        if($('input[name=action]').val()=="edit") {
            $('select[name=type]').prepend("<option value='' selected disabled>No change</option>");
        }
        else {
            // load
            $.post("app/admin/vaults/edit-item-certificate-upload-form.php", {"type":"website"}, function(data) {
                $('tbody#upload').html(data)
            })
        }
    }

    // on change
    $('select[name=type]').on('change', function() {
        var value = $(this).find(":selected").val();
        $.post("app/admin/vaults/edit-item-certificate-upload-form.php", {"type":value}, function(data) {
            $('tbody#upload').html(data)
        })
    });

    // fetch
    $(document).on('click', ".fetch_certificate",  function () {
        $.post("app/admin/vaults/fetch_website_certificate.php", {"website": $("input[name=website]").val(),"verify_peer": $('#verify_peer').is(':checked')}, function(data) {
            // check for error
            if(data.indexOf("Error") !== -1 || data.indexOf("Warning") !== -1 || data.indexOf("alert-danger") !== -1) {
                // print error
                $('div.fetch-result').html("<div class='alert alert-danger'>"+data+"</div>")
                // remove
                if($('input[name=certificate]').length) {
                    $('input[name=certificate]').remove()
                }
            }
            else {
                // -- sessionStorage.certificate = data
                // add
                append_cert_field (data)
                // print ok
                $('div.fetch-result').html("<div class='alert alert-success'>Certificate fetched.</div>")
            }
        })

        return false;
    })

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