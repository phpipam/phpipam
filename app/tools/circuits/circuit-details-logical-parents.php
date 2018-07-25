<?php

# title
print "<h4>"._('This circuit belongs to the following logical circuits')."</h4>";
print "<hr>";

# circuit
print "<table class='ipaddress_subnet table-condensed table-auto'>";


if(sizeof($logical_circuits) > 0){
  foreach($logical_circuits as $lc){
    print '<tr>';
		print "	<td><a class='btn btn-xs btn-default' href='".create_link($_GET['page'],"circuits",'logical',$lc->id)."'><i class='fa fa-random prefix'></i> $lc->logical_cid</a></td>";
		print " <td>$lc->purpose</td>";
		print "</tr>";
	}
}
print "</table>";
