<?php

/*
 * Print edit subnet
 *********************/


/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Sections	= new Sections ($Database);
$Subnets	= new Subnets ($Database);
$Tools		= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "find_free_section_subnets", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# verify that user has permissions to add subnet
if($Sections->check_permission ($User->user, $_POST['sectionid']) != 3) { $Result->show("danger", _('You do not have permissions to add new subnet in this section')."!", true, true); }

// fetch all section subnets
$section_subnets = $Subnets->fetch_multiple_objects ("subnets", "sectionId", $_POST['sectionid'], "subnet", true, false, ["id","subnet","mask","isFolder", "masterSubnetId"]);

// result array
$all_subnets = [];              // all existing subnets

// loop and filter relevant sections
if ($section_subnets!==false) {
    foreach ($section_subnets as $s) {
        if ($s->isFolder!="1") {
            // only master subnets
            if(!$Subnets->has_slaves ($s->id)) {
                $all_subnets[] = $s;
            }
        }
    }
}
?>

<script>
$(document).ready(function() {
    // select dropdown value
    $('.dropdown-subnet_search li a').click(function () {
        var maska = $(this).html();
        $('input.mask').val(maska);
        $('.input-group-btn').removeClass('open');
        return false;
    });
});
</script>

<!-- header -->
<div class="pHeader"><?php print ucwords(_("Search")); ?> <?php print _('for'); ?> <?php print _('subnet'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="search_section_free_subnet">
	<table class="editSubnetDetails table table-noborder table-condensed">

    <!-- name -->
    <tr>
        <td class="middle"><?php print _('Subnet mask'); ?></td>
        <td>
			<div class="input-group">
				<input type="text" class="form-control input-sm mask" name="mask" placeholder="<?php print _('Subnet bitmask'); ?>" value='24'>
				<div class="input-group-btn">
					<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Select <span class="caret"></span></button>
					<ul class="dropdown-menu dropdown-menu-right dropdown-subnet_search">
						<?php
                        for($m=31; $m>7; $m--) {
                            print "<li><a href='#'>$m</a></li>";
                        }
                        ?>
					</ul>
				</div>
			</div>

        </td>
        <td class="info2">
        	<?php print _('Enter or select required subnet bitmask e.g. (/24)'); ?>
        </td>
    </tr>

    <tr>
        <td class="middle"><?php print _('Maximum results'); ?></td>
        <td>
            <div class="input-group">
                <input type="text" class="form-control input-sm input-w-auto" name="results" value='50'>
                <input type="hidden" name="sectionid" value='<?php print escape_input($_POST['sectionid']); ?>'>
            </div>

        </td>
        <td class="info2">
            <?php print _('Enter maximum number of results returned'); ?>
        </td>
    </tr>

    <tr>
        <td class="middle"><?php print _('Start subnet'); ?></td>
        <td>
            <select class="form-control input-sm input-w-auto" name="subnet_start">
                <?php
                foreach ($all_subnets as $s) {
                    print "<option value='$s->subnet'>".$Subnets->transform_to_dotted ($s->subnet)."/".$s->mask."</option>";
                }
                ?>
            </select>
        </td>
        <td class="info2">
            <?php print _('Search start'); ?>
        </td>
    </tr>

    <tr>
        <td class="middle"><?php print _('End subnet'); ?></td>
        <td>
            <select class="form-control input-sm input-w-auto" name="subnet_end">
                <?php
                foreach ($all_subnets as $s) {
                    print "<option value='$s->subnet' selected>".$Subnets->transform_to_dotted ($s->subnet)."/".$s->mask."</option>";
                }
                ?>
            </select>
        </td>
        <td class="info2">
            <?php print _('Search end'); ?>
        </td>
    </tr>

    <tr>
        <td class="middle"></td>
        <td class="text-right">
            <button class='btn btn-sm btn-default submit_popup' data-script="app/admin/subnets/find_free_section_subnets_result.php" data-result_div="search_section_free_subnet_result" data-form='search_section_free_subnet'><?php print _("Search"); ?></button>
        </td>
        <td class="info2"></td>
    </tr>

    <tr>
        <td colspan="3" class="hr"><hr></td>
    </tr>
    <tr>
        <td colspan="3">
            <div id="search_section_free_subnet_result"></div>
        </td>
    </tr>

    </table>
    </form>

</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Close'); ?></button>
	</div>
</div>
