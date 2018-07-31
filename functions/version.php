<?php
/* set latest version */
define("VERSION", "1.4");									//version changes if database structure changes
/* set latest version */
define("VERSION_VISIBLE", "1.4");							//visible version in footer
/* set latest revision */
define("REVISION", "001");									//revision always changes, verision only if database structure changes
/* set database schema version */
define("DBVERSION", "2");									//database schema version (future feature)
/* set last possible upgrade */
define("LAST_POSSIBLE", "1.1");								//minimum required version to be able to upgrade
/* prefix for css/js */
define("SCRIPT_PREFIX", VERSION_VISIBLE.'_r'.REVISION.'_v'.DBVERSION);		//css and js folder prefix to prevent caching