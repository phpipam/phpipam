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

# check permissions
if(!($User->is_admin(false) || $User->user->editCircuits=="Yes")) { $Result->show("danger", _("You are not allowed to modify Circuit details"), true, true); }

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "logicalCircuit");

# strip tags - XSS
$_POST = $User->strip_input_tags ($_POST);

# validate action
$Admin->validate_action ($_POST['action'], true);

# fetch custom fields
$custom = $Tools->fetch_custom_fields('logicalCircuit');

# ID must be numeric
if($_POST['action']!="add" && !is_numeric($_POST['circuitid']))	{ $Result->show("danger", _("Invalid ID"), true, true); }

# fetch circuit details
if( ($_POST['action'] == "edit") || ($_POST['action'] == "delete") ) {
	$logical_circuit = $Admin->fetch_object("logicalCircuit", "id", $_POST['circuitid']);
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
$all_circuits 		 = $Tools->fetch_all_circuits();
$circuit_types = $Tools->fetch_all_circuit_types();
$type_hash = [];
foreach($circuit_types as $t){  $type_hash[$t->id] = $t->ctname; }

# no providers
if($circuit_providers===false) 	{
	$btn = $User->is_admin(false) ? "<hr><a href='' class='btn btn-sm btn-default open_popup' data-script='app/admin/circuits/edit-provider.php' data-class='700' data-action='add' data-providerid='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> "._('Add provider')."</a>" : "";
	$Result->show("danger", _("No circuit providers configured."."<hr>".$btn), true, true);
}

# set readonly flag
$readonly = $_POST['action']=="delete" ? "readonly" : "";
?>

<script type="text/javascript">


function addAllCircuitsHandlers(){
	console.log('Adding event handlers to rows of all circuit table');
	$('#all_circuits tbody tr td').on("click","a[name='addbtn']",function(event){
		var row = $(this).parents('tr').clone();
		var id = row.find("td:last input").val();
		//var row_clone = row.clone();
		//var id = row_clone.find("td:last input").val();
		row.find("td:last").remove();
		row.append("\
							 <td><input class='id' type='hidden' value='"+ id +"'></td> \
							 <td> \
								 <a name='mvdnbtn' class='btn btn-xs btn-default'><i class='fa fa-angle-down'></i></a> \
								 <a name='mvupbtn' class='btn btn-xs btn-default'><i class='fa fa-angle-up'></i></a> \
							 </td> \
							 <td> \
								 <a name='rembtn' class='btn btn-xs btn-default'><i class='fa fa-times'></i></a> \
							 </td> \
		");
		$('#selected_circuits tbody').append(row);
		update_hidden_input();
		if(!verifySelectedCircuits()){
			console.log("Duplicates found!");
			$('#duplicate_error').css('display','block');
		}else{
			$('#duplicate_error').css('display','none');
		}
		//Since the new row is added, need to make sure it has its event handler
		addSelectedCircuitsHandlers();
	});
}

function addSelectedCircuitsHandlers(){
	console.log('Adding event handling to selected circuits');
	//Remove from list
	$('#selected_circuits tr td').on("click","a[name='rembtn']",function(event){
		$(this).parents('tr').remove();
		update_hidden_input();
		if(verifySelectedCircuits()){
			console.log("Duplicate removed!");
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
 			 height:400,
 			 search:true,
 			 pageSize:5,
 			 pageList:[5],
			 onPostBody: function(data) { addAllCircuitsHandlers(); }
 		 });
 });
</script>
<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('Logical circuit'); ?></div>


<!-- content. Override first div to place to the left of the form -->

<!-- All circuit table -->

<div class="pContent" style="float:left;<?php if( $_POST['action'] == "delete") { echo "display:none;"; } ?>"  >
	<table id="all_circuits" class="table">
		<thead>
			<th>Circuit ID</th>
			<th>Type</th>
			<th>Point A</th>
			<th>Point B</th>
			<th></th>
		</thead>
		<tbody>
		<?php
		//Loop through and create list of circuits to choose from
		//Also, open up links in a new window to not interrupt creation
		foreach($all_circuits as $circuit){
			// reformat locations
			$locationA = $Tools->reformat_circuit_location ($circuit->device1, $circuit->location1);
			$locationA_html = "<span class='text-muted'>Not set</span>";
			if($locationA!==false) {
				$locationA_html = "<a href='".create_link('tools',$locationA['type'],$locationA['id'])."' target='_blank'>$locationA[name]</a> <i class='fa fa-gray $locationA[icon]'></i>";
			}

			$locationB = $Tools->reformat_circuit_location ($circuit->device2, $circuit->location2);
			$locationB_html = "<span class='text-muted'>Not set</span>";
			if($locationB!==false) {
				$locationB_html = "<a href='".create_link('tools',$locationB['type'],$locationB['id'])."' target='_blank'>$locationB[name]</a> <i class='fa fa-gray $locationB[icon]'></i>";
			}

			print '<tr>'. "\n";
			print "	<td><a class='btn btn-xs btn-default' href='".create_link('tools',"circuits",$circuit->id)."' target='_blank'><i class='fa fa-random prefix'></i> $circuit->cid</a></td>";
			print "	<td>".$type_hash[$circuit->type]."</td>";
			print "	<td class='hidden-xs hidden-sm'>$locationA_html</td>";
			print "	<td class='hidden-xs hidden-sm'>$locationB_html</td>";
			print "<td><input class='id' type='hidden' value='$circuit->id'><a name='addbtn' class='btn btn-xs btn-default'><i class='fa fa-plus'></i></a></td>";
			//print "<td><input type='submit' class='btn btn-xs btn-default fa fa-plus'></td>";
			print '</tr>'. "\n";

		}
		?>
	</tbody>
	</table>
</div>

<!-- Selected circuit table and form -->
<div class="pContent" style="min-height:400px;">

		<table class="table" id='selected_circuits'>
			<thead>
				<th>ID</th>
				<th>Type</th>
				<th>Point A</th>
				<th>Point B</th>
				<th></th>
				<th></th>
				<th></th>
			</thead>

			<tbody>
				<?php
					if(isset($logical_circuit)){
						$member_circuits = $Tools->fetch_all_logical_circuit_members($logical_circuit->id);
						// reformat locations
						if($member_circuits != false){
							foreach($member_circuits as $circuit){
								$locationA = $Tools->reformat_circuit_location ($circuit->device1, $circuit->location1);
								$locationA_html = "<span class='text-muted'>Not set</span>";
								if($locationA!==false) {
									$locationA_html = "<a href='".create_link('tools',$locationA['type'],$locationA['id'])."' target='_blank'>$locationA[name]</a> <i class='fa fa-gray $locationA[icon]'></i>";
								}

								$locationB = $Tools->reformat_circuit_location ($circuit->device2, $circuit->location2);
								$locationB_html = "<span class='text-muted'>Not set</span>";
								if($locationB!==false) {
									$locationB_html = "<a href='".create_link('tools',$locationB['type'],$locationB['id'])."' target='_blank'>$locationB[name]</a> <i class='fa fa-gray $locationB[icon]'></i>";
								}

								print '<tr>'. "\n";
								print "	<td><a class='btn btn-xs btn-default' href='".create_link('tools',"circuits",$circuit->id)."' target='_blank'><i class='fa fa-random prefix'></i> $circuit->cid</a></td>";
								print "	<td>".$type_hash[$circuit->type]."</td>";
								print "	<td class='hidden-xs hidden-sm'>$locationA_html</td>";
								print "	<td class='hidden-xs hidden-sm'>$locationB_html</td>";
								if($_POST['action'] != "delete"){
								  print "<td><input class='id' type='hidden' value='$circuit->id'></td>
															 <td>
																 <a name='mvdnbtn' class='btn btn-xs btn-default'><i class='fa fa-angle-down'></i></a>
																 <a name='mvupbtn' class='btn btn-xs btn-default'><i class='fa fa-angle-up'></i></a>
															 </td>
															 <td>
																 <a name='rembtn' class='btn btn-xs btn-default'><i class='fa fa-times'></i></a>
															 </td>";
								//print "<td><input type='submit' class='btn btn-xs btn-default fa fa-plus'></td>";
							  }
								print '</tr>'. "\n";
							}
						}
					}
				?>
		  </tbody>
		</table>

	<form id="circuitManagementEdit">
	<input id="circuit_list" type="hidden" value="" name="circuit_list">
	<table class="table table-noborder table-condensed">
		<tr>
			<td>
			<td>
				<p id="duplicate_error" style="display:none">Duplicate circuits have been found. Please remove! Reordering will be affected.</p>
			</td>
		</tr>
  <hr>

	<!-- name -->
	<tr>
		<td><?php print _('Circuit ID'); ?></td>
		<td>
			<input type="text" name="logical_cid" style='width:200px;' class="form-control input-sm" placeholder="<?php print _('ID'); ?>" value="<?php if(isset($logical_circuit->logical_cid)) print $Tools->strip_xss($logical_circuit->logical_cid); ?>" <?php print $readonly; ?>>
			<?php
			if( ($_POST['action'] == "edit") || ($_POST['action'] == "delete") ) {
				print '<input type="hidden" name="id" value="'. $_POST['circuitid'] .'">'. "\n";
			} ?>
			<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
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
    		$custom_input = $Tools->create_custom_field_input ($field, $circuit, $_POST['action'], $timepicker_index, $disabled);
    		// add datepicker index
    		$timepicker_index = $timepicker_index + $custom_input['timepicker_index'];
            // print
			print "<tr>";
			print "	<td>".ucwords($Tools->print_custom_field_name ($field['name']))." ".$custom_input['required']."</td>";
			print "	<td>".$custom_input['field']."</td>";
			print "</tr>";
		}
	}

	?>

	</table>
	</form>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default submit_popup <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" data-script="app/admin/circuits/edit-logical-circuit-submit.php" data-result_div="circuitManagementEditResult" data-form='circuitManagementEdit'>
			<i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i>
			<?php print ucwords(_($_POST['action'])); ?>
		</button>
	</div>

	<!-- result -->
	<div class='circuitManagementEditResult' id="circuitManagementEditResult"></div>
</div>
