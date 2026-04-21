<?php
# user must be authenticated
$User->check_user_session ();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "pass-change");
?>

<div class="col-xs-12 col-md-6 col-md-offset-3" style="margin-top:50px;">
	<h4><?php print _("Password change required"); ?></h4>
	<hr>

	<div class="text-muted">
	<?php print _("You are required to change your password before you can access phpipam."); ?>
	</div>
</div>
<div class="clearfix"></div>

<div class="widget-dash col-xs-12 col-md-6 col-md-offset-3" style="margin-top:20px;">
<div class="inner" style="min-height:auto;">
	<h4><?php print _("Password change"); ?></h4>
	<div class="hContent">

		<form name="changePassRequired" id="changePassRequiredForm" class="form-inline" method="post">
		<div class="row" style="margin-top:30px;">
			<input type="hidden" id="csrf_cookie" name="csrf_cookie" value="<?php print $csrf; ?>">

			<!-- old password -->
			<div class="col-xs-12 col-md-4"><strong><?php print _('Old Password'); ?></strong></div>
			<div class="col-xs-12 col-md-8">
				<input type="password" style="width:100%;" id="oldpassword" name="oldpassword" class="form-control" autofocus="autofocus" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"></input>
			</div>

			<!-- new password -->
			<div class="col-xs-12 col-md-4"  style="margin-top:30px;"><strong><?php print _('New Password'); ?></strong></div>
			<div class="col-xs-12 col-md-8"  style="margin-top:30px;">
				<input type="password" style="width:100%;" id="ipampassword1" name="ipampassword1" class="form-control" autofocus="autofocus" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"></input>
			</div>

			<!-- new password repeat -->
			<div class="col-xs-12 col-md-4" style="margin-top:10px;"><strong><?php print _('New Password repeat'); ?></strong></div>
			<div class="col-xs-12 col-md-8" style="margin-top:10px;">
				<input type="password" style="width:100%;" id="ipampassword2" name="ipampassword2" class="form-control" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"></input>
			</div>

			<!-- submit -->
			<div class="col-xs-12" style="margin-top:10px;">
				<input type="submit" value="<?php print _('Save password'); ?>" class="btn btn-sm btn-default pull-right"></input>
			</div>

		</div>
		</form>


	</div>
</div>
</div>

<div class="clearfix"></div>

<!-- result holder -->
<div class="col-xs-12 col-md-6 col-md-offset-3" id="changePassRequiredResult"></div>