<?php

/*
 * Print edit subnet
 *********************/


/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Sections	= new Sections ($Database);
$Subnets	= new Subnets ($Database);
$Tools		= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

// verify that user has permissions to add subnet
if($Sections->check_permission ($User->user, $_POST['sectionid']) != 3) { $Result->show("danger", _('You do not have permissions to add new subnet in this section')."!", true); }
// verify integers for mask and results
if(!is_numeric($_POST['mask']) || !is_numeric($_POST['results']))       { $Result->show("danger", _('Invalid parameters')."!", true); }

// mask check
if ($_POST['version']=="IPv4" && ($_POST['mask']<8 || $_POST['mask']>32))  { $Result->show("danger", _('Invalid mask')."!", true); }
if ($_POST['version']=="IPv6" && ($_POST['mask']<8 || $_POST['mask']>128)) { $Result->show("danger", _('Invalid mask')."!", true); }

// start and end checks
if(    !is_numeric($_POST['subnet_start'])
    || !is_numeric($_POST['subnet_end'])
    || $_POST['subnet_start'] > $_POST['subnet_end']
    || $_POST['subnet_start'] == $_POST['subnet_end'])                         { $Result->show("danger", _('Invalid range')."!", true); }

// version check
if ($Subnets->identify_address($_POST['subnet_start'])!==$Subnets->identify_address($_POST['subnet_end']))  { $Result->show("danger", _('Invalid range')."!", true); }
$version = $Subnets->identify_address($_POST['subnet_start']);

// fetch all section subnets
$section_subnets = $Subnets->fetch_multiple_objects ("subnets", "sectionId", $_POST['sectionid'], "subnet", true, false, ["id","subnet","mask","isFolder", "masterSubnetId"]);

// result array
$all_subnets = [];              // all existing subnets
$possible_subnets = [];         // all possible subnets
$available_subnets = [];        // all available subnets - result

// loop and filter relevant sections
if ($section_subnets!==false) {
    if(is_array($section_subnets)) {
        foreach ($section_subnets as $s) {
            if ($s->isFolder!="1") {
                if ($Subnets->identify_address ($s->subnet)==$version) {
                    // start and end check
                    if ($s->subnet >= $_POST['subnet_start'] && $s->subnet <= $_POST['subnet_end']) {
                        // only master subnets
                        if(!$Subnets->has_slaves ($s->id)) {
                            $all_subnets[] = $s;
                        }
                    }
                }
            }
        }
    }
}

// no subnets
if (sizeof($all_subnets)==0)        { $Result->show("warning", _('No subnets found')."!", true); }
// single
elseif (sizeof($all_subnets)==1)    { $Result->show("warning", _('Only one subnet found')."!", true); }
// process
else {
    // set start and stop
    $start_subnet = $all_subnets[0];
    $end_subnet   = $all_subnets[sizeof($all_subnets)-1];

    // calculate possible subnets
    $res = 0;
    for ($start = $start_subnet->subnet; $start<$end_subnet->subnet && $res<$_POST['results']; $start=$start+pow(2,32-$_POST['mask'])) {
        $possible_subnets[] = $start;
        // verify overlapping
        $overlap = false;
        foreach ($all_subnets as $s) {
            if ($Subnets->verify_overlapping ($Subnets->transform_to_dotted ($s->subnet)."/".$s->mask, $Subnets->transform_to_dotted ($start)."/".$_POST['mask'])) {
                $overlap = true;
                break;
            }
        }
        // check overlap
        if(!$overlap) {
            $available_subnets[] = $start;
        }
        // bump result
        $res++;
    }
}

// print available
if (sizeof($available_subnets)>0) {
    print "<h4>"._("Following available subnets found for selected mask in this section:")."</h4><hr>";
    print "<div style='padding-left:20px;'>";
    foreach ($available_subnets as $s) {
        print "<div style='padding:1px;'>";
        print "<a class='btn btn-xs btn-default btn-success create_section_subnet_from_search' data-subnet='".$Subnets->transform_address ($s, "dotted")."' data-bitmask='{$_POST['mask']}' data-sectionId='{$_POST['sectionid']}'><i class='fa fa-plus'></i> Create subnet</a> ";
        print $Subnets->transform_address ($s, "dotted")."/".$_POST['mask'];
        print "</div>";
    }
    print "</div>";
}
else {
    $Result->show("info", _('No available subnets found selected for mask')."!", false);
}

print "<div class='hidden'>alert-danger</div>";