# {php}IPAM
Website: https://phpipam.net/

## Description
phpIPAM is an open-source web IP address management application. Its goal is to provide light and simple IP address management application.
It is ajax-based using jQuery libraries, it uses php scripts and javascript and some HTML5/CSS3 features, so some modern browser is preferred
to be able to display javascript quickly and correctly.

## Links
 - [Features & Tools](https://phpipam.net/documents/features/)
 - [Requirements & Installation](https://phpipam.net/documents/installation/)
 - [API guide](https://phpipam.net/api-documentation/)
 - [Update](https://phpipam.net/documents/upgrade/)
 - [Demo page](http://demo.phpipam.net) (Login: `Admin / ipamadmin`)

## Branches
 - MASTER: Latest stable release
 - DEVELOP: Current development branch
 - 1.7: Maintenance branch for 1.7.x releases
 - 1.6: Maintenance branch for 1.6.x releases (obsolete)
 - 1.5: Maintenance branch for 1.5.x releases (obsolete)
 - 1.4: Maintenance branch for 1.4.x releases (obsolete)
 - 1.3: Maintenance branch for 1.3.x releases (obsolete)
 - 1.2: Maintenance branch for 1.2.x releases (obsolete)
 - Other branches: Feature testing

## Supported PHP versions

phpIPAM has been developed and tested on the following PHP versions.\
The use of untested PHP versions is unsupported and may result in compatibility issues.

- MASTER: See latest 1.x.y release version
- DEVELOP: PHP versions 7.2 to 8.4
- 1.7.x: PHP versions 7.2 to 8.3
- 1.6.x: PHP versions 7.2 to 8.3
- 1.5.x: PHP versions 5.4 to 7.4
- 1.4.x: PHP versions 5.4 to 7.4
- 1.3.x: PHP versions 5.4 to 7.1

## Supported MySQL / MariaDB versions

Common Table Expressions (CTE) query support highly recommended : MySQL 8.0+ / MariaDB 10.2.1+ \
As of v1.6.0 support for utf8mb4 is mandatory: MySQL 5.7.7+

## I forgot my Admin password!?
Just run `php functions/scripts/reset-admin-password.php` in the cli and enter your new password

## Reverse-Proxy (Infinite login loops)
As of v1.6.0 when deployed behind a reverse-proxy, set config.php `$trust_x_forwarded_headers = true;` or Docker image environment variable `IPAM_TRUST_X_FORWARDED=true` to accept HTTP X_FORWARDED_ headers.

**WARNING!** The following HTTP headers shoud be filtered and/or overwritten by the reverse-proxy to avoid potential abuse by end-clients.

- X_FORWARDED_FOR
- X_FORWARDED_HOST
- X_FORWARDED_PORT
- X_FORWARDED_PROTO
- X_FORWARDED_SSL
- X_FORWARDED_URI

## What are the credentials for a fresh install?
The Default credentials for a new instance of phpIPAM are the same as the credentials for
the demo page: `Admin / ipamadmin`

## Docker
Community maintained docker images are available at https://hub.docker.com/u/phpipam

## Changelog
See [misc/CHANGELOG](misc/CHANGELOG)

## Roadmap
See [misc/Roadmap](misc/Roadmap)

## Security

See [SECURITY.md](SECURITY.md)

## Contact
miha.petkovsek@gmail.com

Special thanks are going also to the Hosterdam team (http://www.hosterdam.com) for the VPS server
that is used for development of phpIPAM and for demo site.

And also to all users that filed a bug report / feature report and helped with feature testing!

## License
phpIPAM is released under the GPL v3 license, see [misc/gpl-3.0.txt](misc/gpl-3.0.txt).
