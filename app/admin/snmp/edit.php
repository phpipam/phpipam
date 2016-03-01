<?php

/**
 * Script to print add / edit / delete snmp
 *************************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Snmp       = new phpipamSNMP ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->create_csrf_cookie ();

# ID must be numeric
if($_POST['action']!="add" && !is_numeric($_POST['snmpid'])) { $Result->show("danger", _("Invalid ID"), true, true); }

# fetch snmp for edit / add
if($_POST['action']!="add") {
	# fetch snmp details
	$snmp_query = $Admin->fetch_object ("snmp", "id", $_POST['snmpid']);
	# null ?
	$snmp_query===false ? $Result->show("danger", _("Invalid ID"), true) : null;
	# title
	$title =  ucwords($_POST['action']) .' '._('SNMP').' '.$snmp_query->app_id;
} else {
	# generate new code
	$snmp_query = new StdClass;
	$snmp_query->app_code = str_shuffle(md5(microtime()));
	# title
	$title = _('Add new snmp');
}
?>


<!-- header -->
<div class="pHeader"><?php print $title; ?></div>

<!-- content -->
<div class="pContent">

	<form id="edit-snmp-methods-edit" name="edit-snmp-methods-edit">
	<table class="groupEdit table table-noborder table-condensed">

	<!-- name -->
	<tr>
	    <td><?php print _('Name'); ?></td>
	    <td>
	    	<input type="text" name="name" class="form-control input-sm" value="<?php print @$snmp_query->name; ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?>>
	        <input type="hidden" name="id" value="<?php print $snmp_query->id; ?>">
    		<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
    		<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
	    </td>
       	<td class="info2"><?php print _('Enter SNMP name'); ?></td>
    </tr>

	<!-- oid -->
	<tr>
	    <td><?php print _('SNMP OID'); ?></td>
	    <td><input type="text" id="appcode" name="oid" class="form-control input-sm"  value="<?php print @$snmp_query->oid; ?>"  maxlength='32' <?php if($_POST['action'] == "delete") print "readonly"; ?>></td>
       	<td class="info2"><?php print _('OID'); ?></td>
    </tr>

	<!-- method -->
	<tr>
	    <td><?php print _('Method'); ?></td>
	    <td>
	    	<select name="method" class="form-control input-sm input-w-auto">
	    	<?php
	    	foreach($Snmp->snmp_groups as $k=>$g) {
		    	if(@$snmp_query->method==$g)		{ print "<option value='$g' selected='selected'>"._($g)."</option>"; }
		    	else					            { print "<option value='$g' 				    >"._($g)."</option>"; }
	    	}
	    	?>
	    	</select>
       	<td class="info2"><?php print _('Select query method'); ?></td>
    </tr>

    <!-- description -->
    <tr>
    	<td><?php print _('Description'); ?></td>
    	<td>
    		<textarea name="description" class="form-control input-sm" <?php if($_POST['action'] == "delete") print "readonly"; ?>><?php print @$snmp_query->description; ?></textarea>
    	</td>
    	<td class="info2"><?php print _('Enter description'); ?></td>
    </tr>

    </table>
    </form>

	<!-- Result -->
	<div id="edit-snmp-methods-result"></div>

</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="edit-snmp-methods-submit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>
</div>
