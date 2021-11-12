<?php

# Check we have been included and not called directly
require( dirname(__FILE__) . '/../../../functions/include-only.php' );

?>
<ul class='nav nav-tabs' style='margin-top:0px;margin-bottom:20px;'>
    <li role='presentation' <?php if(!isset($_GET['subnetId'])||is_numeric($_GET['subnetId'])) print " class='active'"; ?>>
        <a href='<?php print create_link($_GET['page'], "circuits"); ?>'><?php print _("Physical Circuits"); ?></a>
    </li>
    <li role='presentation' <?php if(@$_GET['subnetId']=="logical") print " class='active'"; ?>>
        <a href='<?php print create_link($_GET['page'], "circuits", "logical"); ?>'><?php print _("Logical Circuits"); ?></a>
    </li>
    <li role='presentation' <?php if(@$_GET['subnetId']=="providers") print " class='active'"; ?>>
        <a href='<?php print create_link($_GET['page'], "circuits", "providers"); ?>'><?php print _("Circuit providers"); ?></a>
    </li>
    <?php if($User->settings->enableLocations=="1") { ?>
    <li role='presentation' <?php if(@$_GET['subnetId']=="circuit_map") print " class='active'"; ?>>
        <a href='<?php print create_link($_GET['page'], "circuits", "circuit_map"); ?>'><?php print _("Circuit map"); ?></a>
    </li>
	<?php } ?>
    <?php if($User->is_admin(false)) { ?>
    <li role='presentation' <?php if(@$_GET['subnetId']=="options") print " class='active'"; ?>>
        <a href='<?php print create_link($_GET['page'], "circuits", "options"); ?>'><?php print _("Options"); ?></a>
    </li>
    <?php } ?>
</ul>
