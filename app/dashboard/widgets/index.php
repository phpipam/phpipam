<?php

# get filelist for all configured widgets


# include requested widget file
if(!file_exists(dirname(__FILE__)."/".$_GET['section'].".php"))		{ $_REQUEST['section']="404"; print "<div id='error'>"; include_once('app/error.php'); print "</div>"; }
else																{ include(dirname(__FILE__) . "/".$_GET['section'].".php"); }

?>