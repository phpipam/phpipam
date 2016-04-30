<?php

/**
 *	Script to write instructions for users
 ******************************************/


# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->csrf_cookie ("create", "instructions");

// default
if(!isset($_GET['subnetId'])) { $_GET['subnetId'] = 1; }

// validate
if($_GET['subnetId']=="1" || $_GET['subnetId']="2")  {
    # fetch instructions
    $instructions = $Admin->fetch_object("instructions", "id", $_GET['subnetId']);

    # set params
    if($_GET['subnetId']=="1")  {
        $title = "Edit user instructions";
    }
    else {
        $title = "Edit IP request instructions";
    }

    //count rows
    $rowcount = substr_count($instructions->instructions, "\n");
    $rowcount++;

    // max rowcount
    if($rowcount < 18) { $rowcount = 18; }
    ?>

    <ul class="nav nav-tabs" style="margin-bottom: 30px;">
        <li role="presentation" <?php if($_GET['subnetId']==1) { print "class='active'"; } ?>><a href="<?php print create_link("administration", "instructions", 1); ?>">User instructions</a></li>
        <li role="presentation" <?php if($_GET['subnetId']==2) { print "class='active'"; } ?>><a href="<?php print create_link("administration", "instructions", 2); ?>">IP request instructions</a></li>
    </ul>

    <!-- title -->
    <h4><?php print _($title); ?></h4>
    <hr>


    <!-- form -->
    <form name="instructions" id="instructionsForm">

    	<textarea style="width:100%;" name="instructions" id="instructions" rows="<?php print $rowcount; ?>"><?php print stripslashes($instructions->instructions); ?></textarea>
    	<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
    	<input type="hidden" name="id" value="<?php print $_GET['subnetId']; ?>">

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
    <?php
}
else {
    $Result->show("danger", "Invalid ID", false);
}
?>