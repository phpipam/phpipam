<?php

/*
 * Print select vlan in subnets
 *******************************/

/* required functions */
if(!is_object($User)) {
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
$permitted_timeservers = $Sections->fetch_section_timeserver_sets ($POST->sectionId);

# fetch all belonging timeserver set
$cnt = 0;

# Only parse timeserver if any exists
if($permitted_timeservers != false) {
	foreach($permitted_timeservers as $k=>$n) {
		// fetch timeserver sets and append
		$timeserver_set = $Tools->fetch_multiple_objects("timeservers", "id", $n, "name", "timesrv1");

		//save to array
		$nsout[$n] = $timeserver_set;

		//count add
		$cnt++;
	}
	//filter out empty
	$permitted_timeservers = isset($nsout) ? array_filter($nsout) : false;
}

?>

<select name="timeserverId" class="form-control input-sm input-w-auto">
	<optgroup label='<?php print _('Select timeserver set'); ?>'>

	<option value="0"><?php print _('No timeservers'); ?></option>
	<?php
	# print all available timeserver sets
	if ($permitted_timeservers!==false) {
		foreach($permitted_timeservers as $t) {

			if($t[0]!==null) {
				foreach($t as $ts) {
					// set print
					$printTS = "$ts->name";
					$printTS .= " (" . array_shift(pf_explode(";",$ts->timesrv1)).",...)";

					/* selected? */
					if(@$subnet_old_details['timeserverId']==$ts->id) 	{ print '<option value="'. $ts->id .'" selected>'. $printTS .'</option>'. "\n"; }
					elseif($POST->timeserverId == $ts->id) 			{ print '<option value="'. $ts->id .'" selected>'. $printTS .'</option>'. "\n"; }
					else 							{ print '<option value="'. $ts->id .'">'. $printTS .'</option>'. "\n"; }
				}
			}
		}
	}
	?>
	</optgroup>


</select>
