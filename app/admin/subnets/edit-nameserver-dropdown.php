<?php

/*
 * Print select vlan in subnets
 *******************************/

/* required functions */
if(!is_object($User)) {
	/* functions */
	require( dirname(__FILE__) . '/../../../functions/functions.php');

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
$permitted_nameservers = $Sections->fetch_section_nameserver_sets ($_POST['sectionId']);

# fetch all belonging nameserver set
$cnt = 0;

# Only parse nameserver if any exists
if($permitted_nameservers != false) {
	foreach($permitted_nameservers as $k=>$n) {
		// fetch nameserver sets and append
		$nameserver_set = $Tools->fetch_multiple_objects("nameservers", "id", $n, "name", "namesrv1");

		//save to array
		$nsout[$n] = $nameserver_set;

		//count add
		$cnt++;
	}
	//filter out empty
	$permitted_nameservers = isset($nsout) ? array_filter($nsout) : false;
}

?>

<select name="nameserverId" class="form-control input-sm input-w-auto">
	<optgroup label='<?php print _('Select nameserver set'); ?>'>

	<option value="0"><?php print _('No nameservers'); ?></option>
	<?php
	# print all available nameserver sets
	if ($permitted_nameservers!==false) {
		foreach($permitted_nameservers as $n) {

			if($n[0]!==null) {
				foreach($n as $ns) {
					// set print
					$printNS = "$ns->name";
					$printNS .= " (" . array_shift(explode(";",$ns->namesrv1)).",...)";

					/* selected? */
					if(@$subnet_old_details['nameserverId']==$ns->id) 	{ print '<option value="'. $ns->id .'" selected>'. $printNS .'</option>'. "\n"; }
					elseif(@$_POST['nameserverId'] == $ns->id) 			{ print '<option value="'. $ns->id .'" selected>'. $printNS .'</option>'. "\n"; }
					else 												{ print '<option value="'. $ns->id .'">'. $printNS .'</option>'. "\n"; }
				}
			}
		}
	}
	?>
	</optgroup>


</select>
