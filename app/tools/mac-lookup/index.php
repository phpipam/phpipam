<?php
# verify that user is logged in
$User->check_user_session();
?>

<!-- display existing groups -->
<h4><?php print _('MAC lookup'); ?></h4>
<hr><br>


<!-- search form -->
<form id="mac_lookup" name="search" class='form-inline' role="form" style="margin-bottom:20px;" method="post">
	<div class='input-group'>
	<div class='form-group'>
		<input class="search input-md form-control" name="mac" placeholder="<?php print _('MAC address'); ?>" value='<?php print @escape_input($POST->mac); ?>' type="text" autofocus="autofocus" style='width:250px;'>
		<span class="input-group-btn">
			<button type="submit" class="btn btn-md btn-default"><?php print _('search');?></button>
		</span>
	</div>
	</div>
</form>

<hr>


<!-- result -->
<div class="searchResult">
<?php
/* include results if IP address is posted */
if (!is_blank($POST->mac)) 	{ include('results.php'); }
else 							{ include('tips.php');}
?>
</div>