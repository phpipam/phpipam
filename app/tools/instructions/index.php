<?php

/**
 *	print instructions
 **********************************************/

/* fetch instructions and print them in instructions div */
$instructions = $Tools->fetch_object("instructions", "id", 1);
$instructions = $instructions->instructions;

/* format line breaks */
$instructions = stripslashes($instructions);		//show html

/* prevent <script> */
$instructions = str_replace("<script", "<div class='error'><xmp><script", $instructions);
$instructions = str_replace("</script>", "</script></xmp></div>", $instructions);

// HSS header
header('X-XSS-Protection:1; mode=block');
?>

<h4><?php print _('Instructions for managing IP addresses');?></h4>
<hr>

<div class="instructions well">
<?php print $instructions; ?>
</div>