
<?php
/**
 *	Preview instructions
 ***************************/
header('X-XSS-Protection:1; mode=block');
?>

<div class="normalTable" style="padding: 5px;">
<?php
print "<div class='well'>";
print $_POST['instructions'];
print "</div>";
?>
</div>