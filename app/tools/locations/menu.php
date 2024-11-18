<ul class='nav nav-tabs' style='margin-top:0px;margin-bottom:20px;'>
    <li role='presentation' <?php if(!isset($GET->subnetId)||is_numeric($GET->subnetId)) print " class='active'"; ?>>
        <a href='<?php print create_link($GET->page, "locations"); ?>'><?php print _("Locations list"); ?></a>
    </li>
    <li role='presentation' <?php if($GET->subnetId=="map") print " class='active'"; ?>>
        <a href='<?php print create_link($GET->page, "locations", "map"); ?>'><?php print _("Locations map"); ?></a>
    </li>
</ul>


<!-- Add link -->
<a href="" class='btn btn-sm btn-success open_popup' data-script='app/admin/locations/edit.php' data-action='add' data-id='' data-action='add' style='margin-bottom:10px;'><i class='fa fa-plus'></i> <?php print _('Add location'); ?></a>
