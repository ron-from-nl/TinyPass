# TinyPass

Password Manager with SQLite DB and 100% Custom Fields (author: [Ron de Jong](https://www.tiny-server.com/#contact))

TinyPass is how Password Managers should be I think. No installation hassle, no database server, no db user & access rights or manual paths and URLs to specify,no websetup to go through.
TinyPass does all that stuff by itself when it can't find the "tinypass.db" file in the home directory also making backups & restores super easy.

- Copy TinyPass to a web directory
- Configure your (Apache) Web Server
- Browse to https://your.ip/tinypass/

The beauty of TinyPass is that every Secret holds any number of Custom Fields which can be Added, Changed, Reordered Up and Down and Deleted at any time together with many other nice features

Functionality

- Groups
- Multi User
- Simple Usage
- Search Engine
- Custom Fields
- Reorder Fields
- Clean Interface
- CSV Import/Export
- Built-in SQLite DB
- Password Encryption

Tiny Pass works with "Secrets". Every Secret can have any number of "Custom Fields" with different "Field Types" such as: "URL" "Mail" "Pass" "Text" "Note".
Secrets can be a member of a "Group" that is selected from a list. The Group selection list is editable so new Groups are easily added to the user's groups. 
The Interface has a powerful Search Engine that deeply searches "Secrets" and "Custom Fields" optionally filtered by Group (SQLite "LIKE" wildcards "_" and "%" apply.
You can sort all columns in ascending and descending order and it allows you to select multiple "Secret", "User" or "Group" records for Deletion or CSV Export.
By default there are two users called user: "admin" (Role: "Admin") and user: "tiny" (Role: "User"). Admins can also Add, Change or Remove Users (including their secrets).
CSV Import / Export (with auto format recognition) is compatible with more than 10 different Password Managers including different browsers and other well known formats.
Tiny Pass AES-256-CTR en/decrypts all "Password" fields with a SHA512 hash-key of your user password that is written nowhere, so please remember your User Password!
TinyPass is designed with performance in mind and also supports mobile screens. 

## Desktop Screenshots

Login                                                           |  Show Secrets
:--------------------------------------------------------------:|:-------------------------------------------------------------:
![Login](/img/01-tinypass.png?raw=true "Login")                 |  ![Show Secrets](/img/02-tinypass.png?raw=true "Show Secrets")

View Secret                                                     |  Edit Secret
:--------------------------------------------------------------:|:-------------------------------------------------------------:
![View Secret](/img/03-tinypass.png?raw=true "View Secret")     |  ![Edit Secret](/img/04-tinypass.png?raw=true "Edit Secret")


## Mobile Screenshots

Login                                                           |  Show Secrets                                                 |Show Secrets Menu                                              
:--------------------------------------------------------------:|:-------------------------------------------------------------:|:-------------------------------------------------------------:
![Login](/img/05-tinypass.jpg?raw=true "Login")                 |  ![Show Secrets](/img/06-tinypass.jpg?raw=true "Show Secrets")|![View Secret](/img/07-tinypass.jpg?raw=true "View Secret")    

|  View Secret                                                  |  Edit Secret                                                  |
|:-------------------------------------------------------------:|:-------------------------------------------------------------:|
|  ![Edit Secret](/img/08-tinypass.jpg?raw=true "Edit Secret")  |  ![Edit Secret](/img/09-tinypass.jpg?raw=true "Edit Secret")  |


Video: https://www.tiny-server.com/desktop/video/07-tinypass-manager.m4v

<!-- MDTOC maxdepth:2 firsth1:0 numbering:0 flatten:0 bullets:1 updateOnSave:1 -->

- [Licence Agreement](#licence-agreement)
- [Requirements](#requirements)
- [Installation](#installation)

<!-- /MDTOC -->

## Website

[Developed by Tiny Server](https://tiny-server.com/)

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

- Create a directory to copy TinyPass (e.g. "/var/www/html/tinypass")

```
sudo -u www-data mkdir -v "/var/www/html/tinypass";
```

or create dir automatically to a subdir of your real DocumentRoot as the user running Apache2

```
sudo -u "$(apachectl -S | grep "User" | awk -F"\"" '{ print $2 }')" mkdir -v "$(sudo apachectl -S | grep "DocumentRoot" | awk -F"\"" '{ print $2 }')tinypass";
```

- Copy TinyPass to your new directory (e.g. "/var/www/html/tinypass")

```
sudo -u www-data git clone https://github.com/ron-from-nl/TinyPass.git "/var/www/html/tinypass";
```

or copy automatically to a subdir of your real DocumentRoot as the user running Apache2

```
sudo -u "$(apachectl -S | grep "User" | awk -F"\"" '{ print $2 }')" git clone https://github.com/ron-from-nl/TinyPass.git "$(sudo apachectl -S | grep "DocumentRoot" | awk -F"\"" '{ print $2 }')tinypass";
```

- Configure your webserver (Apache2 example)

```
sudo nano /etc/apache2/sites-available/000-default.conf
```

Add the following example section (edit alias and directory)

```
RewriteRule ^/tinypass$ /tinypass/ [R,L]
Alias /tinypass/ /where/ever/you/copied/tinypass/

<Directory /where/ever/you/copied/tinypass>
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
