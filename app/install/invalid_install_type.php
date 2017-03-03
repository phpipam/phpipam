<div class="widget-dash col-xs-12 col-md-6 col-md-offset-3" style="margin-top:20px;" >
<div class="inner" style="min-height:10px;">
	<h4>Invalid installation type</h4>

	<div class="hContent">
	<div style="padding:10px;">
		<?php $Result->show("danger", "This installation type does not exist. Please select valid installation method!", false); ?>

		<a href="<?php print create_link("install",null,null,null,null,true); ?>" class="btn btn-sm btn-default"><i class='fa fa-angle-left'></i> Back</a>

	</div>
	</div>
</div>
</div>