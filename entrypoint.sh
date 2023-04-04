#!/bin/bash

ln -s config.docker.php config.php

#fix import permission issue with a chmod
chmod a+w /phpipam/app/admin/import-export/upload

/sbin/tini -- /bin/sh -c /start_apache2