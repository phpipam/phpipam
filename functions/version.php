<?php
/* set latest version */
define("VERSION", "1.40");
/* set latest version - visible version in footer */
define("VERSION_VISIBLE", "1.4.0");
/* set latest revision - revisions of stable releases */
define("REVISION", "0");
/* set database schema version - Incremented on each SCHEMA change from 1.32 release */
define("DBVERSION", "1");


/* prefix for css/js - css and js folder prefix to prevent caching */
define("SCRIPT_PREFIX", VERSION_VISIBLE.'_r'.REVISION.'_v'.DBVERSION);
/* set last possible upgrade - minimum required version to be able to upgrade */
define("LAST_POSSIBLE", "1.32");
