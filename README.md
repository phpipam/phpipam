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
 - MASTER: Current development release
 - 1.5: Productional branch for 1.5.x release
 - 1.4: Productional branch for 1.4.x release
 - 1.3: Productional branch for 1.3.x release (obsolete)
 - 1.2: Productional branch for 1.2.x release (obsolete)
 - Other branches: Feature testing

## I forgot my Admin password!?
Just run `php functions/scripts/reset-admin-password.php` in the cli and enter your new password

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
