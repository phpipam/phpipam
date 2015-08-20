<?php
# print all domains or show records
if (isset($_GET['sPage']))	{ include("domain-records.php"); }
else						{ include("domains-print.php"); }
?>