<?php

/**
 *	Print all available nameserver sets and configurations
 ************************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->csrf_cookie ("create", "nat");

# get NAT object
if($_POST['action']!="add") {
	$nat = $Admin->fetch_object ("nat", "id", $_POST['id']);
	$nat!==false ? : $Result->show("danger", _("Invalid ID"), true, true);
}

# disable edit on delete
$readonly = $_POST['action']=="delete" ? "readonly" : "";
$link = $readonly ? false : true;
?>


<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('NAT'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="editNat">
	<table id="editNat" class="table table-noborder table-condensed">

	<tbody>
    	<!-- name -->
    	<tr>
        	<th><?php print _('Name'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="name" value="<?php print $nat->name; ?>" placeholder='<?php print _('Name'); ?>' <?php print $readonly; ?>>
            	<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
            	<input type="hidden" name="id" value="<?php print $nat->id; ?>">
            	<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Select NAT name"); ?></span>
        	</td>
        </tr>

    	<!-- type -->
    	<tr>
        	<th><?php print _('Type'); ?></th>
        	<td>
            	<?php
                $nat_types = array("source", "static", "destination");
                ?>
            	<select name="type" class="form-control input-sm input-w-auto" <?php print $readonly; ?>>
                <?php
                foreach ($nat_types as $t) {
                    $selected = $nat->type==$t ? "selected" : "";
                    print "<option value='$t' $selected>$t NAT</option>";
                }
                ?>
            	</select>
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Select NAT type"); ?> (<?php print implode(", ", $nat_types); ?>)</span>
        	</td>
        </tr>

    	<!-- Device -->
    	<tr>
        	<th><?php print _('Device'); ?></th>
        	<td>
            	<?php
                $devices = $Tools->fetch_all_objects ("devices", "hostname");
                ?>
            	<select name="device" class="form-control input-sm input-w-auto" <?php print $readonly; ?>>
        	    <option value="0"><?php print _('None'); ?></option>
                <?php
                if($devices !== false) {
                    foreach ($devices as $d) {
                        $selected = $nat->device==$d->id ? "selected" : "";
                        print "<option value='$d->id' $selected>$d->hostname</option>";
                    }
                }
                ?>
            	</select>
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Select Device"); ?></span>
        	</td>
        </tr>

    	<!-- Source -->
    	<?php if($_POST['action']!=="add") { ?>
    	<tr>
        	<td colspan="3"><hr></td>
    	</tr>
    	<tr>
        	<th><?php print _('Source objects'); ?></th>
        	<td class='nat-src'>
            	<?php
                // print sources
                $sources = $Tools->translate_nat_objects_for_display ($nat->src, $nat->id, $link);
                // sources
                if($sources!==false) {
                    print implode("<br>", $sources);
                }
                else {
                    print $Result->show("info", _('No objects'), false, false, true);
                }
                ?>
        	</td>
        	<td>
            	<span class="text-muted"></span>
        	</td>
        </tr>
        <?php if($_POST['action']!=="delete") { ?>
    	<tr>
        	<th></th>
        	<td>
            	<?php
                print "<hr><a class='btn btn-xs btn-success addNatItem' data-id='$nat->id' data-type='src'><i class='fa fa-plus'></i></a> "._('Add new object');
                ?>
        	</td>
        	<td>
            	<span class="text-muted"></span>
        	</td>
        </tr>
        <?php } ?>

    	<!-- Destination -->
    	<tr>
        	<td colspan="3"><hr></td>
    	</tr>
    	<tr>
        	<th><?php print _('Destination objects'); ?></th>
        	<td class='nat-dst'>
            	<?php
                // print sources
                $destinations = $Tools->translate_nat_objects_for_display ($nat->dst, $nat->id, $link);
                // destinations
                if($destinations!==false) {
                    print implode("<br>", $destinations);
                }
                else {
                    print $Result->show("info", _('No objects'), false, false, true);
                }
                ?>
        	</td>
        	<td>
            	<span class="text-muted"></span>
        	</td>
        </tr>
        <?php if($_POST['action']!=="delete") { ?>
    	<tr>
        	<th></th>
        	<td>
            	<?php
                print "<hr><a class='btn btn-xs btn-success addNatItem' data-id='$nat->id' data-type='dst'><i class='fa fa-plus'></i></a> "._('Add new object');
                ?>
        	</td>
        	<td>
            	<span class="text-muted"></span>
        	</td>
        </tr>
        <?php } ?>

    	<!-- port -->
    	<tr>
        	<td colspan="3"><hr></td>
    	</tr>
    	<tr class='port'>
        	<th><?php print _('Src Port'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="src_port" value="<?php print $nat->src_port; ?>" placeholder='<?php print _('Port'); ?>' <?php print $readonly; ?>>
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Source port"); ?></span>
        	</td>
        </tr>
    	<tr class='port'>
        	<th><?php print _('Dst Port'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="dst_port" value="<?php print $nat->dst_port; ?>" placeholder='<?php print _('Port'); ?>' <?php print $readonly; ?>>
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Destination port"); ?></span>
        	</td>
        </tr>
        <?php } ?>

    	<tr>
        	<th><?php print _('Description'); ?></th>
        	<td colspan="2">
            	<textarea class="form-control input-sm" name="description" placeholder='<?php print _('Port'); ?>' <?php print $readonly; ?>><?php print $nat->description; ?></textarea>
        	</td>
        </tr>

	</tbody>

	</table>
	</form>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopupsReload"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editNatSubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>
	<!-- result -->
	<div class="editNatResult"></div>
</div>
