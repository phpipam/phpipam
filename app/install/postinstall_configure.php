<div class="widget-dash col-xs-12 col-md-8 col-md-offset-2">
<div class="inner install" style="min-height:auto;">
	<h4>Postinstall configuration</h4>

	<div class="hContent">

		<div class="text-muted" style="margin:10px;">
		Hi, almost set, lets just set some basic settings. You can change all settings under administration once logged in!
		</div>
		<hr>

		<?php
		# lets verify database
		$errors = $Tools->verify_database ();

		/* print result */
		if( (isset($errors['tableError'])) || (isset($errors['fieldError'])) ) {

			print "<div class='alert alert-danger alert-block'>";
			print "<strong>Some tables or fields are missing in database:</strong><hr>";

			//tables
			if (isset($errors['tableError'])) {
				print '<b>Missing tables:</b>'. "\n";
				print '<ul class="fix-table">'. "\n";
				foreach ($errors['tableError'] as $table) {
					print "<li>$table</li>";
				}
				print '</ul>'. "\n";
			}
			//fields
			if (isset($errors['fieldError'])) {
				print '<b>Missing fields:</b>'. "\n";
				print '<ul class="fix-field">'. "\n";
				foreach ($errors['fieldError'] as $table=>$field) {
					print '<li>';
					print 'Table `'. $table .'`: missing field `'. $field .'`;';
					print '</li>'. "\n";
				}
				print '</ul>'. "\n";
			}
			print "</div>";
		}
		# no db errors, lets configure !
		else {
		?>


		<form name="postinstall" id="postinstall" class="form-inline" method="post">
		<div class="row" style="margin-top:10px;padding:20px 10px;">

			<!-- MySQL install username -->
			<div class="col-xs-12 col-md-4"><strong>Admin password</strong></div>
			<div class="col-xs-12 col-md-8">
				<input type="password" style="width:100%;" name="password1" class="form-control" autofocus="autofocus" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
			</div>

			<!-- MySQL install password -->
			<div class="col-xs-12 col-md-4"></div>
			<div class="col-xs-12 col-md-8">
				<input type="password" style="width:100%;" name="password2" class="form-control" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
				<div class="text-muted">Set password for Admin user</div>
			</div>
			<hr>

			<div class="col-xs-12">
			<hr>
			</div>

			<!-- Database location -->
			<div class="col-xs-12 col-md-4"><strong>Site title</strong></div>
			<div class="col-xs-12 col-md-8">
				<input type="text" style="width:100%;" name="siteTitle" class="form-control" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" value="phpipam">
				<div class="text-muted"></div>
			</div>

			<!-- Database location -->
			<div class="col-xs-12 col-md-4"><strong>Site URL</strong></div>
			<div class="col-xs-12 col-md-8">
				<input type="text" style="width:100%;" name="siteURL" class="form-control" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" value="<?php print $_SERVER['SCRIPT_URI'];  ?>">
				<div class="text-muted"></div>
			</div>

			<!-- submit -->
			<div class="col-xs-12 text-right" style="margin-top:10px;">
				<hr>
				<div class="btn-block">
					<!-- Back -->
					<a class="btn btn-sm btn-default" href="<?php print create_link("install","install_automatic",null,null,null,true); ?>" ><i class='fa fa-angle-left'></i> Back</a>
					<input type="submit" class="btn btn-sm btn-info" version="0" value="Save settings">
				</div>
			</div>
			<div class="clearfix"></div>

			<!-- result -->
			<div class="postinstallresult" style="margin-top:15px;">
			</div>

		</div>
		</form>
	<?php } ?>

	</div>
</div>
</div>
