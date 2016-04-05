<?php

/**
 *	Script to write instructions for users
 ******************************************/


# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->csrf_cookie ("create", "instructions");

# fetch instructions
$instructions = $Admin->fetch_object("instructions", "id", 1);

//count rows
$rowcount = substr_count($instructions->instructions, "\n");
$rowcount++;

if($rowcount < 18) { $rowcount = 18; }
?>

<!-- title -->
<h4><?php print _('Edit user instructions'); ?></h4>
<hr>


<!-- form -->
<form name="instructions" id="instructionsForm">

	<textarea style="width:100%;" name="instructions" id="instructions" rows="<?php print $rowcount; ?>"><?php print stripslashes($instructions->instructions); ?></textarea>
	<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">

	<script src="js/1.2/ckeditor/ckeditor.js"></script>
	<script>
    	CKEDITOR.replace( 'instructions', {
	    	uiColor: '#f9f9f9',
	    	autoParagraph: false		//wrap inside p
    	});
    </script>

	<!-- preview, submit -->
	<br>
	<div style="text-align:right;">
		<input type="button" class="btn btn-sm btn-default" id="preview" value="<?php print _('preview'); ?>">
		<input type="submit" class="btn btn-sm btn-default" value="<?php print _('Save instructions'); ?>">
	</div>
</form>


<!-- result holder -->
<div class="instructionsResult"></div>

<!-- preview holder -->
<div class="instructionsPreview"></div>