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

    	<?php
    	// Get language code from session and clean it
    	$current_lang = isset($_SESSION['ipamlanguage']) ? $_SESSION['ipamlanguage'] : "en";
    	$current_lang = strtolower(preg_replace('/\..*$/', '', $current_lang));  // Remove .utf-8 and convert to lowercase
    	$current_lang = str_replace('_', '-', $current_lang);  // Convert _ to -
    	
    	// Try specific language file first, then fall back to base language
    	$lang_files = array(
    	    "js/ckeditor/lang/{$current_lang}.js",                                    // Try exact match first
    	    "js/ckeditor/lang/" . explode('-', $current_lang)[0] . ".js"             // Then try base language
    	);
    	
    	foreach ($lang_files as $lang_file) {
    	    $server_path = dirname(__FILE__) . "/../../../" . $lang_file;
    	    if (file_exists($server_path)) {
    	        $current_lang = basename($lang_file, '.js');  // Use the actual language file name
    	        echo "<script>\n";
    	        echo file_get_contents($server_path);
    	        echo "\nCKEDITOR.lang.languages['{$current_lang}'] = 1;\n";
    	        echo "</script>\n";
    	        break;
    	    }
    	}
    	?>
    	<script>
            var editor = CKEDITOR.replace('instructions', {
                uiColor: '#f9f9f9',
    	    	autoParagraph: false,		//wrap inside p
                language: '<?php echo $current_lang; ?>'
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
