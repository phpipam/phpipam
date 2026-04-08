<?php

/*
 * Print select vlan in subnets
 *******************************/

/* required functions */
if(!isset($User)) {
	/* functions */
	require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

	# initialize user object
	$Database 	= new Database_PDO;
	$User 		= new User ($Database);
	$Tools	 	= new Tools ($Database);
	$Sections	= new Sections ($Database);
	$Result 	= new Result ();
}

# verify that user is logged in
$User->check_user_session();

# fetch all permitted domains
$permitted_domains = $Sections->fetch_section_domains ($POST->sectionId);

# fetch all belonging vlans
$cnt = 0;
foreach($permitted_domains as $k=>$d) {
	//fetch domain
	$domain = $Tools->fetch_object("vlanDomains","id",$d);
	// fetch vlans and append
	$vlans = $Tools->fetch_multiple_objects("vlans", "domainId", $domain->id, "number");
	//save to array
	$out[$d]['domain'] = $domain;
	$out[$d]['vlans']  = $vlans;
	//count add
	$cnt++;
}
//filter out empty
$permitted_domains = array_filter($out);
?>

<select name="vlanId" class="form-control input-sm input-w-auto">
	<option disabled="disabled"><?php print _('Select VLAN'); ?>:</option>
	<option value="0"><?php print _('No VLAN'); ?></option>
	<?php
	# print all available domains
	foreach($permitted_domains as $d) {
		//more than default
			print "<optgroup label='".$d['domain']->name."'>";
			//add
			print "<option value='Add' data-domain='".$d['domain']->id."'>"._('+ Add new VLAN')."</option>";

			if(is_array($d['vlans']) && $d['vlans'][0]!==null) {
				foreach($d['vlans'] as $v) {
					// set print
					$printVLAN = $v->number;
					if(!empty($v->name)) {
						$printVLAN .= " (" . $Tools->shorten_text($v->name, 25) . ")";
					}

					/* selected? */
					if(@$subnet_old_details['vlanId']==$v->vlanId) 	{ print '<option value="'. $v->vlanId .'" selected>'. $printVLAN .'</option>'. "\n"; }
					elseif($POST->vlanId == $v->vlanId) 	{ print '<option value="'. $v->vlanId .'" selected>'. $printVLAN .'</option>'. "\n"; }
					else 										{ print '<option value="'. $v->vlanId .'">'. $printVLAN .'</option>'. "\n"; }
				}
			}
			else {
				print "<option value='0' disabled>"._('No VLANs')."</option>";
			}
			print "</optgroup>";
	}
	?>
</select>