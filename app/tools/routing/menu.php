<?php

# print
print "<ul class='nav nav-tabs' style='margin-bottom:20px;'>";
$class = $_GET['subnetId']=="bgp" ? "active" : "";
print " <li role='presentation' class='$class'><a href='".create_link("tools", "routing", "bgp")."'>"._('BGP routing')."</a></li>";
$class = $_GET['subnetId']=="ospf" ? "active" : "";
print " <li role='presentation' class='$class'><a href='".create_link("tools", "routing", "ospf")."'>"._("OSPF routing")."</a></li>";
print "</ul>";