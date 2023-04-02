<?php
/*
 * IP Addresses Import
 ************************************************/

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$Tools = new Tools ($Database);
$User = new User ($Database);

# verify that user is logged in, to guard against direct access of page and possible exploits
$User->check_user_session();

# load data from uploaded file
include 'import-load-data.php';
# check data and mark the entries to import/update
include 'import-schema-check.php';

?>

<!-- header -->
<div class="pHeader"><?php print _("Schema Subnet import"); ?></div>

<!-- content -->
<div class="pContent">

<?php

$msg = "";
$rows = "";

# import a device
foreach ($data as &$cdata) {
	if (($cdata['action'] == "add") || ($cdata['action'] == "edit")) {
		if ($cdata['action']=="add"){
			if (strtolower($cdata['parent'])=="none"){$cdata=0;}
			else {
				$schemas=$Tools->fetch_multiple_objects("schemasubnets","locationSize",$cdata['locationSize']);
				foreach ($schemas as $schema){
					if ($schema->description==$cdata['parent']){
						$cdata['parent']=$schema->id;
						break;
					}
				}
			}
		}

		// # set update array

		$values = array(
            'id'	    	=>$cdata['id'],
            'locationSize' 	=>$cdata['locationSize'],
            'addressType'	=>$cdata['addressType'],
            'vrf'			=>$cdata['vrf'],
            'description'	=>$cdata['description'],
            'isSummary'	    =>$cdata['isSummary'],
            'parent'	    =>$cdata['parent'],
            'mask'	    	=>$cdata['mask'],
            'offset'	    =>$cdata['offset'],
            'base'	    	=>$cdata['base'],
            'vlanDef'	    =>$cdata['vlanDef']
        );


		# update
		$cdata['result'] = $Admin->object_modify("schemasubnets", $cdata['action'], "id", $values);

		if ($cdata['result']) {
			$trc = $colors[$cdata['action']];
			$msg = "Hardware  ".$cdata['action']." successful.";
		} else {
			$trc = "danger";
			$msg = "Hardware  ".$cdata['action']." failed.";
		}

		$rows.="<tr class='".$trc."'><td><i class='fa ".$icons[$action]."' rel='tooltip' data-placement='bottom' title='"._($msg)."'></i></td>";
		foreach ($expfields as $cfield) { $rows.= "<td>".$cdata[$cfield]."</td>"; }
		$rows.= "<td>"._($msg)."</td></tr>";

	}
}
print "<table class='table table-condensed table-hover' id='resultstable'><tbody>";
print "<tr class='active'>".$hrow."<th>Result</th></tr>";
print $rows;
print "</tbody></table><br>";
?>

</div>

<!-- footer -->
<div class="pFooter">
	<button class="btn btn-sm btn-default hidePopups"><?php print _('Close'); ?></button>
</div>
