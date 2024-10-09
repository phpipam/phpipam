<?php
if (!isset($User)) { exit(); }

/**
 *	Script to write instructions for users
 ******************************************/


# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create-if-not-exists", "instructions");

// default
if(!isset($GET->subnetId)) { $GET->subnetId = 1; }

// validate
if($GET->subnetId==1 || $GET->subnetId==2)  {
    # fetch instructions
    $instructions = $Tools->fetch_instructions($GET->subnetId);

    # set params
    if($GET->subnetId==1)  {
        $title = _("Edit user instructions");
    } else {
        $title = _("Edit IP request instructions");
    }

    //count rows
    $rowcount = substr_count($instructions, "\n");
    $rowcount++;

    // max rowcount
    if($rowcount < 18) { $rowcount = 18; }
    ?>

    <ul class="nav nav-tabs" style="margin-bottom: 30px;">
        <li role="presentation" <?php if($GET->subnetId==1) { print "class='active'"; } ?>><a href="<?php print create_link("administration", "instructions", 1); ?>"><?php print _("User instructions"); ?></a></li>
        <li role="presentation" <?php if($GET->subnetId==2) { print "class='active'"; } ?>><a href="<?php print create_link("administration", "instructions", 2); ?>"><?php print _("IP request instructions"); ?></a></li>
    </ul>

    <!-- title -->
    <h4><?php print _($title); ?></h4>
    <hr>


    <!-- form -->
    <form name="instructions" id="instructionsForm">

    	<textarea style="width:100%;" name="instructions" id="instructions" rows="<?php print $rowcount; ?>"><?php print $instructions; ?></textarea>
    	<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
    	<input type="hidden" name="id" value="<?php print escape_input($GET->subnetId); ?>">

    	<script src="js/ckeditor/ckeditor.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
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
    <?php
}
else {
    $Result->show("danger", _("Invalid ID"), false);
}
