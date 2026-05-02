# Upgrading phpIPAM

In general upgrading phpIPAM is a 6 step process:

1. Check PHP requirements
1. Backup database and `config.php` configuration file
1. Prepare for composer package management [ *upgrading from phpIPAM < v1.9.0 ]
1. Upgrade phpIPAM code
1. Update webserver configuration [ *upgrading from phpIPAM < v1.9.0 ]
1. Upgrade phpIPAM database.

__Before upgrading ensure you backup your MySQL database__

From version 1.7 onwards we have multiple branches available:

- `master`: Tracks the latest stable release (choose this if unsure)
- `1.x`: Stable 1.x.y point release branches.
- `develop`: Current development version

Pulling from the `master` git branch will install the latest available stable release, otherwise you need to switch to the required branch.

# Check PHP requirements

Check the `Supported PHP versions` section in [README.md](README.md) and ensure your hosting environment is compatible with your chosen version of phpIPAM.

Check PHP version via GUI
```
Administration -> Version Check
```

Check PHP version via command line

```console
[root@ipam:/]$ php -v
PHP 8.2.30 (cli) (built: Feb 28 2026 07:07:34) (NTS)
Copyright (c) The PHP Group
Zend Engine v4.2.30, Copyright (c) Zend Technologies
    with Zend OPcache v8.2.30, Copyright (c), by Zend Technologies
```

Upgrade PHP to a supported version before proceeding with the upgrade.

# Create backup

All phpIPAM state is stored in `config.php` and the database, separated from the PHP/HTML code. If you have a MySQL backup and archived `config.php` you can always restore if anything goes wrong during the upgrade process. Before proceeding __backup your SQL database and config.php__ by following these steps.

via GUI
```
Administration -> Import / Export -> Prepare MySQL dump
```

via command line

```php
// config.php
/**
 * database connection details
 ******************************/
$db['host'] = 'database_host';
$db['user'] = 'username';
$db['pass'] = 'password';
$db['name'] = 'database_name';
$db['port'] = 3306;
```

(adjust directories etc. according to your installation):

```console
[root@ipam:/]$ cd /var/www/phpipam/
[root@ipam:/var/www/phpipam]$ mysqldump -h 'database_host' -u 'username' -p 'database_name' >db/bkp/phpipam_migration_backup.db
Enter password:
```

Backup your `config.php` file containing database connection settings for phpIPAM.

# Prepare for composer package management [ * upgrading from phpIPAM < v1.9.0 ]

phpIPAM versions prior to 1.9.0 use a mix of git submodule and [composer](https://getcomposer.org/) packages installed in the `functions` sub-directory.

phpIPAM version 1.9.0 and above switched to [composer](https://getcomposer.org/) exclusively for package management in the project root directory.

Before you upgrade to 1.9.0 or above from 1.8.x or below, remove legacy git submodules by running `git submodule deinit`.

__Ensure you have a backup your SQL database and config.php before proceeding__

(adjust directories etc. according to your installation):

```console
[root@ipam:/]$ cd /var/www/phpipam
[root@ipam:/var/www/phpipam/]$ git submodule deinit -f --all
Cleared directory 'app/login/captcha'
Cleared directory 'functions/GoogleAuthenticator'
Cleared directory 'functions/LdapRecord'
Cleared directory 'functions/PHPMailer'
Cleared directory 'functions/parsedown'
Cleared directory 'functions/php-saml'
Cleared directory 'functions/qrcodejs'
Cleared directory 'functions/xmlseclibs'
```

Additionally remove the legacy `functions/vendor` composer package directory.

(adjust directories etc. according to your installation):

```console
[root@ipam:/]$ cd /var/www/phpipam
[root@ipam:/var/www/phpipam/]$ rm -fr functions/vendor
```

# Upgrade phpIPAM code

## git on master 'stable' branch

If you are using `master` branch on GitHub simply pull the latest stable release and update dependencies:

(adjust directories etc. according to your installation):

```console
[root@ipam:/]$ cd /var/www/phpipam
[root@ipam:/var/www/phpipam]$ git pull
[root@ipam:/var/www/phpipam]$ composer install --no-dev --no-scripts
```

## git on specific branch [ * phpIPAM >= v1.9.0 ]

If you use specific branch, pull down new code, switch to desired branch and update dependencies;

(adjust directories etc. according to your installation):

```console
[root@ipam:/]$ cd /var/www/phpipam
[root@ipam:/var/www/phpipam]$ git pull
[root@ipam:/var/www/phpipam]$ git checkout -b 1.9 origin/1.9
[root@ipam:/var/www/phpipam]$ composer install --no-dev --no-scripts
```

## git on specific branch [ * phpIPAM < v1.9.0 ]

If you use specific branch, pull down new code, switch to desired branch and update dependencies;

(adjust directories etc. according to your installation):

```console
[root@ipam:/]$ cd /var/www/phpipam
[root@ipam:/var/www/phpipam]$ git pull
[root@ipam:/var/www/phpipam]$ git checkout -b 1.8 origin/1.8
[root@ipam:/var/www/phpipam]$ git submodule update --init --recursive
[root@ipam:/var/www/phpipam]$ cd functions
[root@ipam:/var/www/phpipam/functions]$ composer install --no-dev --no-scripts
```

# Manual release upgrade

To manually extract new phpipam release head over to [https://github.com/phpipam/phpipam/releases](https://github.com/phpipam/phpipam/releases), check assets and download files. Than extract new code and copy over old config.php file.

(adjust directories etc. according to your installation):

```console
[root@ipam:/]$ cd /var/www/phpipam
[root@ipam:/var/www/phpipam]$ tar -xvf phpipam-v1.9.0.tgz
[root@ipam:/var/www/phpipam]$ cp /backup/location/config.php /var/www/phpipam
[root@ipam:/var/www/phpipam]$ composer install --no-dev --no-scripts
```

# Update webserver configuration [ * upgrading from phpIPAM < v1.9.0 ]

phpIPAM v1.9.0 relocated all HTML application code from the project root directory into the \"public\" sub-directory.

When upgrading from phpIPAM v1.8.x or below, update your webserver, reverse-proxy or load-balancer configuration to point to the new HTML location.

# Upgrade phpIPAM database

To upgrade your phpIPAM database to latest version choose from the multiple options presented on the browser upgrade page:

## Automatic database upgrade

Open browser and follow upgrade procedure.

## Manual query import

In case you have some problems you can manually import each MySQL update statement directly into the database. All upgrade queries are available by following the instructions in `db/UPDATE.sql`, start from the statement that contains a version higher than current.

Alternatively you can use the script below to output the queries needed to upgrade the MySQL database manually.

Assuming your old version is v1.5:

```console
[root@ipam:/]$ cd /var/www/phpipam
[root@ipam:/var/www/phpipam]$ php functions/upgrade_queries.php 1.5
```

# Restore old installation and database

In case anything goes wrong the restore procedure is simple:

- Extract old code (restore version you had prior to upgrade)
- Copy over config.php
- Load old database backed up before starting upgrade

```console
[root@ipam:/var/www/phpipam]$ mysql -h `database_host` -u `username` -p `database_name` <db/bkp/phpipam_migration_backup.db
Enter password:
```