<?php
# print all domains or show records
if (@$_GET['sPage']=="page")		{ include("domains-print.php"); }
elseif (@$_GET['sPage']=="search")	{ include("domains-print.php"); }
elseif (isset($_GET['sPage']))		{ include("domain-records.php"); }
else								{ include("domains-print.php"); }
?>