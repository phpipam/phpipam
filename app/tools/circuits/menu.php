<?php

# Check we have been included and not called directly
require( dirname(__FILE__) . '/../../../functions/include-only.php' );

?>
<ul class='nav nav-tabs' style='margin-top:0px;margin-bottom:20px;'>
    <li role='presentation' <?php if(!isset($GET->subnetId)||is_numeric($GET->subnetId)) print " class='active'"; ?>>
        <a href='<?php print create_link($GET->page, "circuits"); ?>'><?php print _("Physical Circuits"); ?></a>
    </li>
    <li role='presentation' <?php if($GET->subnetId=="logical") print " class='active'"; ?>>
        <a href='<?php print create_link($GET->page, "circuits", "logical"); ?>'><?php print _("Logical Circuits"); ?></a>
    </li>
    <li role='presentation' <?php if($GET->subnetId=="providers") print " class='active'"; ?>>
        <a href='<?php print create_link($GET->page, "circuits", "providers"); ?>'><?php print _("Circuit providers"); ?></a>
    </li>
    <?php if($User->settings->enableLocations=="1") { ?>
    <li role='presentation' <?php if($GET->subnetId=="circuit_map") print " class='active'"; ?>>
        <a href='<?php print create_link($GET->page, "circuits", "circuit_map"); ?>'><?php print _("Circuit map"); ?></a>
    </li>
	<?php } ?>
    <?php if($User->is_admin(false)) { ?>
    <li role='presentation' <?php if($GET->subnetId=="options") print " class='active'"; ?>>
        <a href='<?php print create_link($GET->page, "circuits", "options"); ?>'><?php print _("Options"); ?></a>
    </li>
    <?php } ?>
</ul>
