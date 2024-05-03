# TinyPass

Password Manager with SQLite DB and 100% Custom Fields

Tiny Pass is how Password Managers should be. No installation hassle, no database server, tables & data setup, no websetup as it does all these things automatically.
Copy the contents to your webdirectory, configure your webserver and open a browser to e.g. https://bla/tinypass/ and change the default user passwords when prompted.

Functionality

- Groups
- Multi User
- Simple Usage
- Search Engine
- Custom Fields
- Reorder Fields
- CSV Import/Export
- Built-in SQLite DB
- Password Encryption

Tiny Pass works with "Secrets" holding "Custom Fields". When creating or editing Secrets you can add, change, reorder or remove Custom Fields (of different Field Types).
Secrets can be a member of a "Group" and the Interface has powerful Search Engine that can search "Secrets" and "Custom Field" contents optionally filtered by the Group.
You can sort all columns in ascending and descending order and it allows you to mass select multiple "Secret", "User" or "Group" records to be Deleted or CSV Exported
By default there are two users called user: "admin" (with role: "Admin") and user: "tiny" (with role: "User"). Only Admins are allowed to add, change or remove users.
Tiny Pass is CSV Import / Export compatible with more than 10 different Password Managers including different browsers and other widely used well known Password Managers.
Tiny Pass AES-256-CTR en/decrypt all "Password" fields with a SHA512 hash-key of your user password that is written nowhere, so be sure to remember your User Password!  

## Screenshots

Login

![Login](/img/01-tinypass.png?raw=true "Login")

Show Secrets

![Show Secrets](/img/02-tinypass.png?raw=true "Show Secrets")

View Secret

![View Secret](/img/03-tinypass.png?raw=true "View Secret")

Edit Secret

![Edit Secret](/img/04-tinypass.png?raw=true "Edit Secret")

Video: https://www.tiny-server.com/desktop/video/07-tinypass-manager.m4v

<!-- MDTOC maxdepth:2 firsth1:0 numbering:0 flatten:0 bullets:1 updateOnSave:1 -->

- [Licence Agreement](#licence-agreement)
- [Requirements](#requirements)
- [Installation](#installation)

<!-- /MDTOC -->

## Website

[Part of Tiny Server](https://tiny-server.com/)

## Licence Agreement

[Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 (CC BY-NC-ND 4.0 LEGAL CODE)](https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode.en)

## Requirements

* PHP 7.4 or newer,
* PHP extensions:
  
  * standard
  * tokenizer
  * Core
  * date
  * openssl
  * hash
  * pcre
  * mbstring

## Installation

- Copy the contents to your webdirectory
- Configure your webserver (Apache2 example)

```
sudo nano /etc/apache2/sites-available/000-default.conf
```

Add the following example section (edit alias and directory)

```
RewriteRule ^/tinypass$ /tinypass/ [R,L]
Alias /tinypass/ /where/ever/you/want/tinypass/

<Directory /where/ever/you/want/tinypass>
	Options MultiViews SymLinksIfOwnerMatch

	<Files tinypass.db>
		Require all denied
	</Files>
	
	AllowOverride All
	Require all granted
</Directory>
```

Test Config

```
sudo apachectl configtest;
```

Reload Config

```
sudo systemctl reload apache2;
```

Test Status

```
sudo systemctl status apache2;
```

- Open a browser to https://www.your.host/tinypass/
