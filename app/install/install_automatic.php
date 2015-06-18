<div class="widget-dash col-xs-12 col-md-8 col-md-offset-2">
<div class="inner install" style="min-height:auto;">
	<h4>Automatic database installation</h4>

	<div class="hContent">

		<div class="text-muted" style="margin:10px;">
		Please provide required inputs in below form for automatic database installation, once finished click Install database.
		<br>
		Before you install please fill in all settings in <strong>config.php</strong> file!
		</div>
		<hr>

		<form name="installDatabase" id="install" class="form-inline" method="post">
		<div class="row" style="margin-top:10px;padding:20px 10px;">

			<!-- MySQL install username -->
			<div class="col-xs-12 col-md-4"><strong>MySQL username</strong></div>
			<div class="col-xs-12 col-md-8">
				<input type="text" style="width:100%;" name="mysqlrootuser" class="form-control" autofocus="autofocus" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
			</div>

			<!-- MySQL install password -->
			<div class="col-xs-12 col-md-4"><strong>MySQL password</strong></div>
			<div class="col-xs-12 col-md-8">
				<input type="password" style="width:100%;" name="mysqlrootpass" class="form-control" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
				<div class="text-muted">* User must have permissions to create new MySQL database</div>
			</div>
			<hr>

			<!-- Database location -->
			<div class="col-xs-12 col-md-4"><strong>MySQL database location</strong></div>
			<div class="col-xs-12 col-md-8">
				<input type="text" style="width:100%;" name="mysqllocation" class="form-control" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" disabled="disabled" value="<?php print $db['host']; ?>">
				<div class="text-muted"></div>
			</div>

			<!-- Database name -->
			<div class="col-xs-12 col-md-4"><strong>MySQL database name</strong></div>
			<div class="col-xs-12 col-md-8">
				<input type="text" style="width:100%;" name="mysqltable" class="form-control" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" disabled="disabled" value="<?php print $db['name']; ?>">
				<div class="text-muted">* change database details on config.php</div>
			</div>

			<!-- toggle advanced options -->
			<div class="col-xs-12"><hr></div>
			<div class="col-xs-12 col-md-4"></div>
			<div class="col-xs-12 col-md-8" style="padding-top:7px;">
				<a class="btn btn-sm btn-default" id="toggle-advanced"><i class='fa fa-cogs'></i> Show advanced options</a>
			</div>

			<!-- advanced -->
			<div class="col-xs-12" id="advanced" style="display:none;padding:20px 0px;">

			<div class="col-xs-12 col-md-4"><strong>Drop exisitng database</strong></div>
			<div class="col-xs-12 col-md-8">
				<input type="checkbox" name="dropdb" value="on">
				<span class="text-muted">Drop existing database if it exists</span>
			</div>
			<div class="col-xs-12 col-md-4"><strong>Create database</strong></div>
			<div class="col-xs-12 col-md-8">
				<input type="checkbox" name="createdb" value="on" checked="checked">
				<span class="text-muted">Create new database</span>
			</div>
			<div class="col-xs-12 col-md-4"><strong>Create permissions</strong></div>
			<div class="col-xs-12 col-md-8">
				<input type="checkbox" name="creategrants" value="on" checked="checked">
				<span class="text-muted">Set permissions to tables</span>
			</div>
			</div>

			<!-- submit -->
			<div class="col-xs-12 text-right" style="margin-top:10px;">
				<hr>
				<div class="btn-block">
					<!-- Back -->
					<a class="btn btn-sm btn-default" href="<?php print create_link("install",null,null,null,null,true); ?>" ><i class='fa fa-angle-left'></i> Back</a>
					<a class="install btn btn-sm btn-info" version="0">Install phpipam database</a>
				</div>
			</div>
			<div class="clearfix"></div>

			<!-- result -->
			<div class="upgradeResult" style="margin-top:15px;">
			</div>

		</div>
		</form>


	</div>
</div>
</div>