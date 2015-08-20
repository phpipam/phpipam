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
foreach($permitted_nameservers as $k=>$n) {
	// fetch nameserver sets and append
	$nameserver_set = $Tools->fetch_multiple_objects("nameservers", "id", $n, "name", "namesrv1", "namesrv2", "namesrv3");

	//save to array
	$nsout[$n] = $nameserver_set;

	//count add
	$cnt++;
}
//filter out empty
$permitted_nameservers = array_filter($nsout);
?>

<select name="nameserverId" class="form-control input-sm input-w-auto">
	<optgroup label='<?php print _('Select nameserver set'); ?>'>

	<option value="0"><?php print _('No nameservers'); ?></option>
	<?php
	# print all available nameserver sets
	foreach($permitted_nameservers as $n) {

		if($n[0]!==null) {
			foreach($n as $ns) {
				// set print
				$printNS = "$ns->name";
				$printNS .= " (" . $ns->namesrv1;

					// Only print namesrv2+3 if present
				if ( !empty($ns->namesrv2) ) {
					$printNS .= ", " . $ns->namesrv2;
				}
				if ( !empty($ns->namesrv3) ) {
					$printNS .= ", " . $ns->namesrv3;
				}
					$printNS .= ")";


				/* selected? */
				if(@$subnet_old_details['nameserverId']==$ns->id) 	{ print '<option value="'. $ns->id .'" selected>'. $printNS .'</option>'. "\n"; }
				elseif(@$_POST['nameserverId'] == $ns->id) 			{ print '<option value="'. $ns->id .'" selected>'. $printNS .'</option>'. "\n"; }
				else 												{ print '<option value="'. $ns->id .'">'. $printNS .'</option>'. "\n"; }
			}
		}
	}
	?>
	</optgroup>


</select>
