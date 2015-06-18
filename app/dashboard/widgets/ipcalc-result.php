<?php

/**
 *
 * Script to calculate IP subnetting
 *
 */


# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects
$Database 	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);
$Subnets	= new Subnets ($Database);
$Sections	= new Sections ($Database);
$Tools	    = new Tools ($Database);

# verify that user is logged in
$User->check_user_session();

# get requested IP addresses in CIDR format
$cidr = $_POST['cidr'];

# verify input CIDR and die if errors
$errors = $Subnets->verify_cidr_address ($cidr, false);
$errors===true ? : $Result->show("danger", _('Invalid input').': '.$errors,true);

# fetch all sections
$Sections->fetch_sections();

# calculate results
$calc_results = $Tools->calculate_ip_calc_results($cidr);
?>

<h4><?php print _('Subnetting details for');?> <?php print $cidr; ?>:</h4>

<!-- IPcalc result table -->
<table class="table table-condensed">

    <!-- IP details -->
    <?php
    $m = 0;		//needed for add subnet mapping
    foreach ($calc_results as $key=>$line) {
        print '<tr>';
        print ' <td>'._("$key").'</td>';
        print ' <td id="sub'. $m .'">'. $line .'</td>';
        print '</tr>';

        $m++;
    }
    ?>
</table>
</div>