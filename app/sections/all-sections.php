<?php

/* prints all subnets in section */

# user must be authenticated
$User->check_user_session ();

# fetch all sections
$sections = $Sections->fetch_all_objects ("sections", "order");

# Lets do some reordering to show slaves!
if ($sections !== false) {
	foreach($sections as $s) {
		if($s->masterSection=="0") {
			# it is master
			$s->class = "master";
			$sectionssorted[] = $s;
			# check for slaves
			foreach($sections as $ss) {
				if($ss->masterSection==$s->id) {
					$ss->class = "slave";
					$sectionssorted[] = $ss;
				}
			}
		}
	}
	# set new array
	$sections_sorted = @$sectionssorted;
}

# colspan
$colspan = 8;
?>

<h4 style="margin-top: 30px;"><?php print _("Available sections"); ?></h4>
<hr>


<!-- show sections -->
<?php if($sections!==false) { ?>
<table class="table sorted table-striped table-condensed table-top table-td-top" data-cookie-id-table="all_sections">
<!-- headers -->
<thead>
<tr>
    <th><?php print _('Name'); ?></th>
    <th><?php print _('Description'); ?></th>
    <th><?php print _('Parent'); ?></th>
    <th><?php print _('Strict mode'); ?></th>
    <?php if($User->get_module_permissions ("vlan")>0) { ?>
    <th><?php print _('Show VLANs'); ?></th>
    <?php $colspan--;} ?>
    <?php if ($User->get_module_permissions ("vrf")>0) { ?>
    <th><?php print _('Show VRFs'); ?></th>
    <?php $colspan--;} ?>
    <th><?php print _('Subnets'); ?></th>
    <?php if($User->is_admin(false)) { ?>
    <th><?php print _('Group Permissions'); ?></th>
    <th></th>
    <?php $colspan--;} ?>
</tr>
</thead>

<tbody>
<!-- existing sections -->
<?php
if(isset($sections_sorted)) {
	$count = 0;
	foreach ($sections_sorted as $section) {
		// check permissions for user
		$perm = $Sections->check_permission ($User->user, $section->id);
		if($perm > 0 ) {
			$count++;
			//cast
			$section = (array) $section;

			print '<tr class="'.$section['class'].'">'. "\n";

		    print '	<td><a class="btn btn-xs btn-default" href="'.create_link("subnets", $section['id']).'">'. str_replace("_", " ", $section['name']).'</a></td>'. "\n";
		    print '	<td>'. $section['description'] .'</td>'. "\n";
		    //master Section
		    if($section['masterSection']!=0) {
				# get section details
				$ssec = $Tools->fetch_object("sections", "id", $section['masterSection']);
			    print "	<td>$ssec->name</td>";
		    } else {
			    print "	<td>"._('Root')."</td>";
		    }
		    //strictMode
		    $mode = $section['strictMode']==0 ? "<span class='badge badge1 badge5 alert-danger'>"._("No") : "<span class='badge badge1 badge5 alert-success'>"._("Yes");
		    print '	<td>'. $mode .'</span></td>'. "\n";
		    //Show VLANs
		    if($User->get_module_permissions ("vlan")>0) {
		    print " <td>";
		    print @$section['showVLAN']==1 ? "<span class='badge badge1 badge5 alert-success'>"._("Yes") : "<span class='badge badge1 badge5 alert-danger'>"._("No");
		    print "	</span></td>";
			}
		    //Show VRFs
		    if($User->get_module_permissions ("vrf")>0) {
		    print " <td>";
		    print @$section['showVRF']==1 ? "<span class='badge badge1 badge5 alert-success'>"._("Yes") : "<span class='badge badge1 badge5 alert-danger'>"._("No");
		    print "	</span></td>";
			}
		    // subnets
		    $cnt = $Tools->count_database_objects ("subnets", "sectionId", $section['id']);
		    print " <td><span class='badge badge1 badge5 alert-success'>$cnt</span></td>";

			//permissions
			if($User->is_admin(false)) {
	    		print "<td>";
	    	    if(strlen($section['permissions'])>1 && !is_null($section['permissions'])) {
	    	    	$permissions = $Sections->parse_section_permissions($section['permissions']);
	    	    	# print for each if they exist
	    	    	if(sizeof($permissions) > 0) {
	    	    		foreach($permissions as $key=>$p) {
	    		    		# get subnet name
	    		    		$group = $Tools->fetch_object("userGroups", "g_id", $key);
	    		    		# parse permissions
	    		    		$perm  = $Subnets->parse_permissions($p);
	    		    		print $group->g_name." : ".$perm."<br>";
	    		    	}
	    	    	}
	    	    	else {
	    		    	print _("All groups: No access");
	    	    	}
	    	    }
	    	    else {
	    			print _("All groups: No access");
	    	    }
	    		print "</td>";

	    	   	print '	<td class="actions">'. "\n";

	    	   	// links
		        $links = [];
	            $links[] = ["type"=>"header", "text"=>"Show"];
	            $links[] = ["type"=>"link", "text"=>"View section", "href"=>create_link( "subnets", $section['id']), "icon"=>"eye", "visible"=>"dropdown"];
	            $links[] = ["type"=>"divider"];
	            $links[] = ["type"=>"header", "text"=>"Manage"];
	            $links[] = ["type"=>"link", "text"=>"Edit section", "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/sections/edit.php' data-class='700' data-action='edit' data-sectionid='$section[id]'", "icon"=>"pencil"];
	            $links[] = ["type"=>"link", "text"=>"Delete section", "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/sections/edit.php' data-class='700' data-action='delete' data-sectionid='$section[id]'", "icon"=>"times"];
		        // print links
		        print $User->print_actions($User->user->compress_actions, $links);

	    		print '	</td>'. "\n";
	        }
			print '</tr>'. "\n";
		}
	}
	// none
	if($count==0) {
		print "<tr>";
		print "	<td colspan='$colspan'>".$Result->show("info", _("No sections available"), false, false, true)."</td>";
		print "</tr>";
	}
}
?>
</tbody>
</table>	<!-- end table -->

<!-- show no configured -->
<?php } else { ?>
<div class="alert alert-warn alert-absolute"><?php print _('No sections configured'); ?>!</div>
<?php } ?>