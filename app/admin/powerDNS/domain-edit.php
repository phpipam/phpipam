<?php

/**
 *	Edit powerDNS domain
 ************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();
$PowerDNS 	= new PowerDNS ($Database);

# verify that user is logged in
$User->check_user_session();
# perm check popup
if($POST->action=="edit") {
    $User->check_module_permissions ("pdns", User::ACCESS_RW, true, true);
}
else {
    $User->check_module_permissions ("pdns", User::ACCESS_RWA, true, true);
}

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "domain");

# validate action
$Admin->validate_action();

# save settings for powerDNS default
$pdns = $PowerDNS->db_settings;

# get VRF
if($POST->action!="add") {
	$domain = $PowerDNS->fetch_domain ($POST->id);
	$domain!==false ? : $Result->show("danger", _("Invalid ID"), true, true);
} else {
	$domain = new Params();
}

# disable edit on delete
$readonly = $POST->action=="delete" ? "readonly" : "";
?>


<!-- header -->
<div class="pHeader"><?php print $User->get_post_action(); ?> <?php print _('domain'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="domainEdit">
	<table class="table table-noborder table-condensed">

	<!-- name  -->
	<tr>
		<td style="width:150px;"><?php print _('Name'); ?></td>
		<td>
			<input type="text" class="name form-control input-sm" name="name" placeholder="<?php print _('FQDN domain name'); ?>" value="<?php print $domain->name; ?>" <?php print $readonly; ?> <?php if($POST->action!="add") { print "disabled='disabled'"; } ?>>
			<input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
			<input type="hidden" name="id" value="<?php print escape_input($POST->id); ?>">
            <input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
		</td>
	</tr>

	<!-- master -->
	<tr>
		<td><?php print _('Master NS'); ?></td>
		<td>
			<input type="text" class="name form-control input-sm" name="master" placeholder="<?php print _('NULL'); ?>" value="<?php print $domain->master; ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<!-- type -->
	<tr>
		<td><?php print _('Domain type'); ?></td>
		<td>
			<select name="type" class="form-control input-w-auto input-sm" <?php print $readonly; ?>>
			<?php
			// loop
			foreach($PowerDNS->domain_types as $type) {
				// active
				if ($type == @$domain->type)	{ $selected = "selected"; }
				else							{ $selected = ""; }
				// print
				print "<option value='$type' $selected>$type</option>";
			}
			?>
			</select>
		</td>
	</tr>

	<?php
	// we need default parameters only if we create new domain !
	if($POST->action=="add") {
	?>

	<tbody class="defaults">

	<!-- hr -->
	<tr>
		<td colspan="2"><hr><strong><?php print _("Default record values (SOA, NS)"); ?></strong><br><br></td>
	</tr>

	<!-- defualt values for SOA and NS records -->

	<!-- ns -->
	<tr>
		<td><?php print _('Name servers'); ?></th>
		<td>
			<input type="text" class="form-control input-sm" name="ns" value="<?php print $pdns->ns; ?>">
		</td>
	</tr>
	<!-- mail -->
	<tr>
		<td><?php print _('Hostmaster'); ?></th>
		<td>
			<input type="text" class="form-control input-sm" name="hostmaster" value="<?php print $pdns->hostmaster; ?>">
		</td>
	</tr>
	<!-- ttl -->
	<tr>
		<td><?php print _('TTL'); ?></th>
		<td>
		<select name="ttl" class="form-control input-w-auto input-sm" <?php print $readonly; ?>>
		<?php
		// loop
		foreach($PowerDNS->ttl as $k=>$ttl) {
			// active
			if ($k == @$pdns->ttl)	{ $selected = "selected"; }
			else					{ $selected = ""; }
			// print
			print "<option value='$k' $selected>$ttl ($k)</option>";
		}
		?>
		</select>
		</td>
	</tr>
	<!-- refresh -->
	<tr>
		<td><?php print _('Refresh'); ?></th>
		<td>
		<select name="refresh" class="form-control input-w-auto input-sm" <?php print $readonly; ?>>
		<?php
		// loop
		foreach($PowerDNS->ttl as $k=>$ttl) {
			// active
			if ($k == @$pdns->refresh)	{ $selected = "selected"; }
			else						{ $selected = ""; }
			// print
			print "<option value='$k' $selected>$ttl ($k)</option>";
		}
		?>
		</select>
		</td>
	</tr>
	<!-- ttl -->
	<tr>
		<td><?php print _('Retry'); ?></th>
		<td>
		<select name="retry" class="form-control input-w-auto input-sm" <?php print $readonly; ?>>
		<?php
		// loop
		foreach($PowerDNS->ttl as $k=>$ttl) {
			// active
			if ($k == @$pdns->retry)	{ $selected = "selected"; }
			else						{ $selected = ""; }
			// print
			print "<option value='$k' $selected>$ttl ($k)</option>";
		}
		?>
		</select>		</td>
	</tr>
	<!-- ttl -->
	<tr>
		<td><?php print _('NXDOMAIN ttl'); ?></th>
		<td>
		<select name="nxdomain_ttl" class="form-control input-w-auto input-sm" <?php print $readonly; ?>>
		<?php
		// loop
		foreach($PowerDNS->ttl as $k=>$ttl) {
			// max 10800
			if ($k <= 10800) {
				// active
				if ($k == @$pdns->nxdomain_ttl)	{ $selected = "selected"; }
				else							{ $selected = ""; }
				// print
				print "<option value='$k' $selected>$ttl ($k)</option>";
			}
		}
		?>
		</select>
		</td>
	</tr>

    <!-- expire -->
    <tr>
            <td><?php print _('Expire'); ?></th>
            <td>
            <select name="expire" class="form-control input-w-auto input-sm" <?php print $readonly; ?>>
            <?php
            // loop
            foreach($PowerDNS->ttl as $k=>$ttl) {
                    // active
                    if ($k == @$pdns->expire)       { $selected = "selected"; }
                    else                                                    { $selected = ""; }
                    // print
                    print "<option value='$k' $selected>$ttl ($k)</option>";
            }
            ?>
            </select>
            </td>
    </tr>

	</tbody>
	<!-- records -->
	<tr>
		<td></td>
		<td>
			<input type="checkbox" class="hideDefaults" value="1" name="manual"> <?php print _("Dont create default records (SOA, NS)"); ?>
		</td>
	</tr>
	<?php } ?>

	</table>
	</form>

	<?php
	//print delete warning
	if($POST->action == "delete")	{ $Result->show("warning", "<strong>"._('Warning').":</strong> "._("removing Domain will also remove all referenced entries!"), false);}
	?>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default <?php if($POST->secondary=="true") { print "hidePopup2"; } else { print "hidePopups"; } ?>"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($POST->action=="delete") { print "btn-danger"; } else { print "btn-success"; } ?> <?php if($POST->secondary=="true") { print "editDomainSubmit2"; } ?>" id="editDomainSubmit"><i class="fa <?php if($POST->action=="add") { print "fa-plus"; } elseif ($POST->action=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print $User->get_post_action(); ?></button>
	</div>
	<!-- result -->
	<div class="domain-edit-result"></div>
</div>