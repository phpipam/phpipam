<?php

/**
 * Script to display usermod result
 *************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# fetch all available LDAP servers
$servers = $Admin->fetch_all_objects ("usersAuthMethod");
foreach($servers as $k=>$s) {
	if($s->type!="AD" && $s->type!="LDAP" && $s->type!="NetIQ") {
		unset($servers[$k]);
	}
}

# die if no servers
if(sizeof($servers)==0) 	{ $Result->show("danger", _("No servers available"), true, true); }
?>


<!-- header -->
<div class="pHeader"><?php print _('Search domains in AD'); ?></div>

<!-- content -->
<div class="pContent">

	<table class="table table-noborder">

	<tr>
		<td>Select server</td>
		<td>
			<select name="server" id="adserver" class="form-control input-w-auto">
			<?php
			foreach($servers as $s) {
				print "<option value='$s->id'>$s->description</option>";
			}
			?>
			</select>
		</td>
	</tr>

	<tr>
		<td style="width:150px;">Filter</td>
		<td><input type="text" class="form-control input-sm" id='dfilter' name="dfilter" placeholder="<?php print _('Enter search filter'); ?>"></td>
	</tr>


	<tr>
		<td colspan="2"><hr></td>
	</tr>

	<tr>
		<td></td>
		<td><button class='btn btn-sm btn-default pull-right' class="form-control input-sm" id="adsearchgroupsubmit"><?php print _('Search'); ?></button></td>
	</tr>

	</table>

	<div id="adsearchgroupresult" style='margin-bottom:10px;margin-top:10px;'></div>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopupsReload"><?php print _('Cancel'); ?></button>
	</div>
</div>