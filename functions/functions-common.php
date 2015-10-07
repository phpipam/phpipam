<?php

/**
 *
 *	functions for general use
 *
 */


/**
 * create links function
 *
 *	if rewrite is enabled in settings use rewrite, otherwise ugly links
 *
 *	levels: page=$1&section=$2&subnetId=$3&sPage=$4&ipaddrid=$5
 */
function create_link ($l1 = null, $l2 = null, $l3 = null, $l4 = null, $l5 = null, $install = false ) {
	# get settings
	global $User;

	# set rewrite
	if($User->settings->prettyLinks=="Yes") {
		if(!is_null($l5))		{ $link = "$l1/$l2/$l3/$l4/$l5/"; }
		elseif(!is_null($l4))	{ $link = "$l1/$l2/$l3/$l4/"; }
		elseif(!is_null($l3))	{ $link = "$l1/$l2/$l3/"; }
		elseif(!is_null($l2))	{ $link = "$l1/$l2/"; }
		elseif(!is_null($l1))	{ $link = "$l1/"; }
		else					{ $link = ""; }

		# prepend base
		$link = BASE.$link;
	}
	# normal
	else {
		if(!is_null($l5))		{ $link = "?page=$l1&section=$l2&subnetId=$l3&sPage=$l4&ipaddrid=$l5"; }
		elseif(!is_null($l4))	{ $link = "?page=$l1&section=$l2&subnetId=$l3&sPage=$l4"; }
		elseif(!is_null($l3))	{ $link = "?page=$l1&section=$l2&subnetId=$l3"; }
		elseif(!is_null($l2))	{ $link = "?page=$l1&section=$l2"; }
		elseif(!is_null($l1))	{ $link = "?page=$l1"; }
		else					{ $link = ""; }

		# prepend base
		$link = BASE.$link;
	}

	# result
	return $link;
}
?>