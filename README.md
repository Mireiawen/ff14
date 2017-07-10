# FF14 tools website

Code for Mireiawen's [Final Fantasy XIV tools website](https://ff14.mireiawen.net/)

## Installation

### Requirements
This has been tested with Linux servers only. It may or may not work on Windows server. Live sites runs on PHP 5.6 and Nginx, with MariaDB 10.1.

### Nginx
Clean URLs should work with Nginx with following `try_files` directive, if the site is installed under the root folder:
    try_files $uri $uri/ /index.php?$args;
To get the site to work, you need to point your Nginx `document_root` to the `public` folder of the application.

### PHP
PHP should not require any special configuration, following extensions are known as required:
 - cURL
 - Hash
 - Intl
 - MySQLi
Redis will be used for caching if it is loaded, but it is not required.

### Database
Importing the application database takes 3 steps:
 1. Framework database: with SQL client, load `fw/database.sql`
 2. Application database structure: with SQL client, load `database.sql`
 3. Application data: Execute `php import_data.php`
This should load the framework configuration with its default username and password, application database structure as well as the data used by the application via [XIVDB](http://xivdb.com/) API and [Garland Tools](http://garlandtools.org/)
