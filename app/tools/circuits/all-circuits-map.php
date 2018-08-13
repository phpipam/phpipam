<?php

#
# Prints map of all Circuits
#
print "<h3>Map of all circuits</h3>";
$mapping_circuits = $Tools->fetch_all_circuits();
print "<div style='height:900px'>";
include("circuit-mapping.php");
print "</div>";
