<?php

/**
 *	Edit logical circuit details
 ************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# perm check popup
if($_POST['action']=="edit") {
    $User->check_module_permissions ("circuits", User::ACCESS_RW, true, true);
}
else {
    $User->check_module_permissions ("circuits", User::ACCESS_RWA, true, true);
}

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "circuitsLogical");

# strip tags - XSS
$_POST = $User->strip_input_tags ($_POST);

# validate action
$Admin->validate_action ($_POST['action'], true);

# fetch custom fields
$custom = $Tools->fetch_custom_fields('circuitsLogical');

# ID must be numeric
if($_POST['action']!="add" && !is_numeric($_POST['circuitid']))	{ $Result->show("danger", _("Invalid ID"), true, true); }

# fetch circuit details
if( ($_POST['action'] == "edit") || ($_POST['action'] == "delete") ) {
	$logical_circuit = $Admin->fetch_object("circuitsLogical", "id", $_POST['circuitid']);
	// false
	if ($circuit===false)                                          { $Result->show("danger", _("Invalid ID"), true, true);  }
}
// defaults
else {
	$circuit = new StdClass ();
	$circuit->provider = 0;
}

# fetch all providers, devices, locations
$circuit_providers = $Tools->fetch_all_objects("circuitProviders", "name");
$all_devices       = $Tools->fetch_all_objects("devices", "hostname");
$all_locations     = $Tools->fetch_all_objects("locations", "name");
$all_circuits 	   = $Tools->fetch_all_circuits();
$circuit_types 	   = $Tools->fetch_all_objects ("circuitTypes", "ctname");
$type_hash = [];
foreach($circuit_types as $t) {
	$type_hash[$t->id] = $t->ctname;
}

# no providers
if($circuit_providers===false) 	{
	$btn = $User->is_admin(false) ? "<hr><a href='' class='btn btn-sm btn-default open_popup' data-script='app/admin/circuits/edit-provider.php' data-class='700' data-action='add' data-providerid='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> "._('Add provider')."</a>" : "";
	$Result->show("danger", _("No circuit providers configured.")."<hr>".$btn, true, true);
}

# set readonly flag
$readonly = $_POST['action']=="delete" ? "readonly" : "";
?>

<script>

function addAllCircuitsHandlers(){
	$('#all_circuits tbody tr td').on("click","a[name='addbtn']",function(event){
		var row = $(this).parents('tr').clone();
		var id = row.find("td:first input").val();
		//var row_clone = row.clone();
		//var id = row_clone.find("td:last input").val();
		row.find("td:first").remove();

		row.prepend("<td><a name='rembtn' class='btn btn-xs btn-default btn-danger' rel='tooltip' title='Remove'><i class='fa fa-times'></i></a></td>");
		row.append("\
							 <td><input class='id' type='hidden' value='"+ id +"'> \
<div class='input-group pull-right'> \
<a name='mvdnbtn' class='btn btn-xs btn-default' rel='tooltip' title='Move down'><i class='fa fa-angle-down'></i></a>\
<a name='mvupbtn' class='btn btn-xs btn-default' rel='tooltip' title='Move up'><i class='fa fa-angle-up'></i></a>\
\
</div>\
							 </td> \
		");
		$('#selected_circuits tbody').append(row);
		update_hidden_input();
		if(!verifySelectedCircuits()){
			$('#duplicate_error').css('display','block');
		}else{
			$('#duplicate_error').css('display','none');
		}
		//Since the new row is added, need to make sure it has its event handler
		addSelectedCircuitsHandlers();
		$(".tooltip").hide();
	});
}

function addSelectedCircuitsHandlers(){
	//Remove from list
	$('#selected_circuits tr td').on("click","a[name='rembtn']",function(event){
		$(this).parents('tr').remove();
		update_hidden_input();
		if(verifySelectedCircuits()){
			$('#duplicate_error').css('display','none');
		}
	});

	//Move selected circuit up
	$('#selected_circuits tr td').on("click","a[name='mvupbtn']",function(event){
		var r = $(this).parents("tr:first");
		r.insertBefore(r.prev());
		update_hidden_input();
	});

	//Move selected circuit down
	$('#selected_circuits tr td').on("click","a[name='mvdnbtn']",function(event){
		var r = $(this).parents("tr:first");
		r.insertAfter(r.next());
		update_hidden_input();
	});
}

function verifySelectedCircuits(){
	var list = $('#circuit_list').val();
	var ids = list.split(".");
	var seen_ids = [];
	for(var i = 0; i < ids.length; i++){
		if(seen_ids.includes(ids[i])){
			return false;
		}
		seen_ids.push(ids[i]);
	};
	return true;
};

function update_hidden_input(){
	var id_string = "";
	$('#selected_circuits tbody tr').each(function(){
		id_string += $(this).find("input.id").val();
		id_string += ".";
	});
	$('#circuit_list').val(id_string);
 };

 //This is a little nasty, is a result of using jQuery only before using bootstrap tables
 $(document).ready(function(){
	 addSelectedCircuitsHandlers();
	 update_hidden_input();
     if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }
 		 $('#all_circuits').bootstrapTable({
 			 pagination:true,
 			 search:true,
 			 pageSize:5,
 			 pageList:[5],
			 onPostBody: function(data) { addAllCircuitsHandlers(); }
 		 });
 		 $('#selected_circuits').bootstrapTable({
 			 pagination:false,
 			 search:true,
			 onPostBody: function(data) { addSelectedCircuitsHandlers(); update_hidden_input(); }
 		 });
     });
</script>





<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('Logical circuit'); ?></div>


<!-- content. Override first div to place to the left of the form -->
<br>
<!-- Selected circuit table and form -->
<div class="pContent">

	<form id="circuitManagementEdit">
	<input id="circuit_list" type="hidden" value="" name="circuit_list">
	<table class="table table-noborder table-condensed">
		<!-- name -->
		<tr>
			<td><?php print _('Circuit ID'); ?></td>
			<td>
				<input type="text" name="logical_cid" style='width:200px;' class="form-control input-sm" placeholder="<?php print _('ID'); ?>" value="<?php if(isset($logical_circuit->logical_cid)) print $Tools->strip_xss($logical_circuit->logical_cid); ?>" <?php print $readonly; ?>>
				<?php
				if( ($_POST['action'] == "edit") || ($_POST['action'] == "delete") ) {
					print '<input type="hidden" name="id" value="'. $_POST['circuitid'] .'">'. "\n";
				} ?>
				<input type="hidden" name="action" value="<?php print escape_input($_POST['action']); ?>">
				<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
			</td>
		</tr>


		<!-- purpose -->
		<tr>
			<td><?php print _('Purpose'); ?></td>
			<td>
				<input type="text" name="purpose" style='width:200px;'  class="form-control input-sm" placeholder="<?php print _('Purpose'); ?>" value="<?php if(isset($logical_circuit->purpose)) print $Tools->strip_xss($logical_circuit->purpose); ?>" <?php print $readonly; ?>>
			</td>
		</tr>

		<!-- comment -->
		<tr>
			<td colspan="2"><hr></td>
		</tr>
		<tr>
			<td><?php print _('Comments'); ?></td>
			<td>
				<textarea name="comments" class="form-control input-sm" <?php print $readonly; ?>><?php if(isset($logical_circuit->comments)) print $logical_circuit->comments; ?></textarea>
			</td>
		</tr>


		<!-- Custom -->
		<?php
		if(sizeof($custom) > 0) {

			print '<tr>';
			print '	<td colspan="2"><hr></td>';
			print '</tr>';

			# count datepickers
			$timepicker_index = 0;

			# all my fields
			foreach($custom as $field) {
				// readonly
				$disabled = $readonly == "readonly" ? true : false;
	    		// create input > result is array (required, input(html), timepicker_index)
	    		$custom_input = $Tools->create_custom_field_input ($field, $logical_circuit, $timepicker_index, $disabled);
	    		$timepicker_index = $custom_input['timepicker_index'];
	            // print
				print "<tr>";
				print "	<td>".ucwords($Tools->print_custom_field_name ($field['name']))." ".$custom_input['required']."</td>";
				print "	<td>".$custom_input['field']."</td>";
				print "</tr>";
			}
		}

		?>

		<tr>
			<td colspan="2">
				<p id="duplicate_error" style="display:none" class='alert alert-danger'><?php print _("Duplicate circuits have been found. Please remove! Reordering will be affected."); ?></p>
			</td>
		</tr>

	</table>
	</form>


	<p style='margin-bottom:0px;margin-top:50px;'><strong><?php print _("Logical circuit physical members"); ?>:</strong></p>

	<table class="table table-striped table-condensed table-top table-no-bordered" id='selected_circuits'>
		<thead>
			<th></th>
			<th><?php print _("Circuit ID"); ?></th>
			<th><?php print _("Type"); ?></th>
			<th><?php print _("Point A"); ?></th>
			<th><?php print _("Point B"); ?></th>
			<?php if($_POST['action'] != "delete") { ?>
			<th></th>
			<?php } ?>
		</thead>

		<tbody>
			<?php
				// print existing logical circuits on edit / delete
				if(isset($logical_circuit)){
					$member_circuits = $Tools->fetch_all_logical_circuit_members($logical_circuit->id);
					// reformat locations
					if($member_circuits != false){
						foreach($member_circuits as $circuit){
							$locationA = $Tools->reformat_circuit_location ($circuit->device1, $circuit->location1);
							$locationA_html = "<span class='text-muted'>"._("Not set")."</span>";
							if($locationA!==false) {
								$locationA_html = "<a href='".create_link('tools',$locationA['type'],$locationA['id'])."' target='_blank'>$locationA[name]</a>";
							}

							$locationB = $Tools->reformat_circuit_location ($circuit->device2, $circuit->location2);
							$locationB_html = "<span class='text-muted'>"._("Not set")."</span>";
							if($locationB!==false) {
								$locationB_html = "<a href='".create_link('tools',$locationB['type'],$locationB['id'])."' target='_blank'>$locationB[name]</a>";
							}

							print "<tr>";
							print "	<td><a name='rembtn' class='btn btn-xs btn-default btn-danger' rel='tooltip' title="._('Remove')."><i class='fa fa-times'></i></a></td>";
							print "	<td><a class='btn btn-xs btn-default' href='".create_link('tools',"circuits",$circuit->id)."' target='_blank'><i class='fa fa-random prefix'></i> $circuit->cid</a></td>";
							print "	<td>".$type_hash[$circuit->type]."</td>";
							print "	<td class='hidden-xs hidden-sm'>$locationA_html</td>";
							print "	<td class='hidden-xs hidden-sm'>$locationB_html</td>";
							if($_POST['action'] != "delete") {
								print "	<td class='text-right'>";
								print "		<input class='id' type='hidden' value='$circuit->id'>";
								print "		<div class='input-group pull-right'>";
								print "		<a name='mvdnbtn' class='btn btn-xs btn-default' rel='tooltip' title="._('Move down')."><i class='fa fa-angle-down'></i></a>";
								print "		<a name='mvupbtn' class='btn btn-xs btn-default' rel='tooltip' title="._('Move up')."><i class='fa fa-angle-up'></i></a>";
								print "		</div>";
								print "	</td>";
						    }
						    else {
						    	print "<tr>";
						    }
							print "</tr>";
						}
					}
				}
			?>
	  </tbody>
	</table>
</div>

<!-- All circuit table -->
<div class="pContent" style="<?php if( $_POST['action'] == "delete") { echo "display:none;"; } ?>; padding-top:50px;" >

	<p style='margin-bottom:0px;'><strong><?php print _("Available physical circuits"); ?>:</strong></p>

	<table id="all_circuits" class="table table-striped table-condensed table-top table-no-bordered">
		<thead>
			<th></th>
			<th><?php print _("Circuit ID"); ?></th>
			<th><?php print _("Type"); ?></th>
			<th><?php print _("Point A"); ?></th>
			<th><?php print _("Point B"); ?></th>
		</thead>
		<tbody>
		<?php
		//Loop through and create list of circuits to choose from
		//Also, open up links in a new window to not interrupt creation
		if($all_circuits!==false) {
			foreach($all_circuits as $circuit) {
				// reformat locations
				$locationA = $Tools->reformat_circuit_location ($circuit->device1, $circuit->location1);
				$locationA_html = "<span class='text-muted'>"._("Not set")."</span>";
				if($locationA!==false) {
					$locationA_html = "<a href='".create_link('tools',$locationA['type'],$locationA['id'])."' target='_blank'>$locationA[name]</a>";
				}

				$locationB = $Tools->reformat_circuit_location ($circuit->device2, $circuit->location2);
				$locationB_html = "<span class='text-muted'>"._("Not set")."</span>";
				if($locationB!==false) {
					$locationB_html = "<a href='".create_link('tools',$locationB['type'],$locationB['id'])."' target='_blank'>$locationB[name]</a>";
				}

				print '<tr>'. "\n";
				print " <td><input class='id' type='hidden' value='$circuit->id'><a name='addbtn' class='btn btn-xs btn-success' rel='tooltip' title="._('Add to logical circuit')."><i class='fa fa-plus'></i></a></td>";
				print "	<td><a class='btn btn-xs btn-default' href='".create_link('tools',"circuits",$circuit->id)."' target='_blank'><i class='fa fa-random prefix'></i> $circuit->cid</a></td>";
				print "	<td>".$type_hash[$circuit->type]."</td>";
				print "	<td class='hidden-xs hidden-sm'>$locationA_html</td>";
				print "	<td class='hidden-xs hidden-sm'>$locationB_html</td>";
				print '</tr>'. "\n";
			}
		}
		?>
	</tbody>
	</table>
</div>





<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default submit_popup <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" data-script="app/admin/circuits/edit-logical-circuit-submit.php" data-result_div="circuitManagementEditResult" data-form='circuitManagementEdit'>
			<i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i>
			<?php print escape_input(ucwords(_($_POST['action']))); ?>
		</button>
	</div>

	<!-- result -->
	<div class='circuitManagementEditResult' id="circuitManagementEditResult"></div>
</div>
