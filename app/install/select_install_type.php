<div class="widget-dash col-xs-12 col-md-8 col-md-offset-2">
<div class="inner install" style="min-height:auto;">
	<h4>Welcome to phpipam installation</h4>

	<div class="hContent">
	<div style="padding:10px;">

		<div class="text-mute2d" style="margin:10px;">
		Hi, we are glad you decided to install phpipam. Before you can start using it you must create MySQL database for phpipam, you can do this three ways.
		<br><br>
		Before you start installation please read INSTALL.txt file to make sure all requirements for installation are met!
		<br><br>
		Please select preferred database installation type:
		</div>
		<hr>
	
		<ol style="margin-top:20px;">
		<!-- automatic -->
		<li>
			<a href="<?php print create_link("install","install_automatic",null,null,null,true); ?>" class="btn btn-sm btn-default">Automatic database installation</a>
			<br>
			<div class="text-muted">phpipam installer will create database for you automatically.</div>
			<br>
		</li>
		
		<!-- Mysql inport -->
		<li>
			<a href="<?php print create_link("install","install_mysqlimport",null,null,null,true); ?>" class="btn btn-sm btn-default">MySQL import instructions</a>	
			<br>
			<div class="text-muted">Install DB files with mysqlimport tool.</div>
			<br>		
		</li>
		
		<!-- Manual install -->
		<li>
			<a href="<?php print create_link("install","install_manual",null,null,null,true); ?>" class="btn btn-sm btn-default">Manual database installation</a>	
			<br>
			<div class="text-muted">Install database manually with SQL queries.</div>
			<br>		
		</li>
		
		</ul>
			
	</div>
	</div>
</div>	
</div>