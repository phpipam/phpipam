<?php
# print all domains or show records
if ($GET->sPage=="page")		{ include("domains-print.php"); }
elseif ($GET->sPage=="search")	{ include("domains-print.php"); }
elseif (isset($GET->sPage))		{ include("domain-records.php"); }
else								{ include("domains-print.php"); }