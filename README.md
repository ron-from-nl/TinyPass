# TinyPass

PHP Password Manager with 100% Custom Fields and a built-in SQLite Database (author: [Ron de Jong](https://www.tiny-server.com/#contact))

<!-- MDTOC maxdepth:2 firsth1:0 numbering:0 flatten:0 bullets:1 updateOnSave:1 -->

- [Easy](#easy)
- [Functions](#functions)
- [Description](#description)
- [User Licence](#user-licence)
- [Screenshots](#screenshots)
- [Demo Video](#demo-video)
- [Requirements](#requirements)
- [Installation](#installation)

<!-- MDTOC -->


## Easy

TinyPass is easy to install with no installation hassle, no database server, no db user & access rights or manual Paths and URLs to specify, no websetup to go through.
TinyPass creates a single database file called "tinypass.db" in the home directory when it's not there, which also makes backup & restore super simple.

- Copy TinyPass to a web directory
- Configure your (Apache) Web Server
- Browse to https://your.ip/tinypass/

The great thing about TinyPass is that it has a clean, non-distracting interface and works with secrets and 100% custom fields that can be added, changed, rearranged, moved up and down and deleted at any time, along with many other fun features.


## Functions

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


## Description

### Secrets

TinyPass works with "Secrets" having any number of "Custom Fields" Every Secret has a Name and optionally is a member of a Group. Every new Secret starts with no Fields at all.
Simply add or remove any number of Custom Fields you need and rearrange their order up and down in which they should appear.

### Fields

TinyPass Secrets can have any number of Custom Fields of Type: URL, Mail, Pass, Text, Note (multi-line textarea) giving you exactly the Fields you need for your Secrets.
Traditional Password Managers work with Fixed Fields supplemented with a limited number of Custom Fields. This often causes missing or redundant fields in your Secrets.
The Fixed Field problem even gets worse when the user is offered a large list of Secret Types (with predefined fields) making it even more laborious to create secrets.
TinyPass was designed to revolutionize Password Managers and come up with 100% Custom Fields making unnecessary and missing Fields a thing of the past.

### Groups

Optionally Secrets can be members of a "Group" selected from a list. The group selection list is editable and non existing Groups are automatically added to the user's groups.

### Search

The interface has a powerful search engine that searches deep into "Secrets" and "Custom Fields", optionally filtered by the Group Search field (SQLite "LIKE" wildcards "_" and "%" apply.

### Sort

You can sort all columns in ascending and descending order. Sorting the first ID column makes it easy to sort and remove secrets there were added first or last for instance with a CSV import.

### Select

You can select multiple records in "Show Secrets", "Show Groups" and "Show Users" for deletion or CSV export. 

### Users

By default there are two users named **User: "admin"** (role: "Admin") and **User: "tiny"** (role: "User"). Administrators can also add, change, or delete users (including their secrets).
When an Admininistrator deletes a user then all user-related Secrets, Custom Fields and Groups are deleted. For security Admins cannot read other user's Secrets without the user password.
When a user performs a Password Change, then all Password Fields are re-encrypted. Another great feature is that TinyPass has a Shell interface that enables automating Password Changes.  

### Import / Export

TinyPass supports CSV Import / Export (with automatic format recognition) and compatible with more than 10 different password managers, including various browsers and other well-known formats.
The group filter is also useful here, allowing you to export only Secrets being a member of a certain Group for instance when using Groups as names of people for whom you keep Secrets.

### Security

Password fields are AES-256-CTR encrypted in the database with a SHA512 hash key of your user password (not written anywhere), so remember your password!

### Other

TinyPass is designed with performance in mind and also supports mobile screens.


## User Licence

[Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 (CC BY-NC-ND 4.0 LEGAL CODE)](https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode.en)


## Screenshots


### Desktop Screenshots

Login                                                           |  Show Secrets
:--------------------------------------------------------------:|:-------------------------------------------------------------:
![Login](/img/01-tinypass.png?raw=true "Login")                 |  ![Show Secrets](/img/02-tinypass.png?raw=true "Show Secrets")

View Secret                                                     |  Edit Secret
:--------------------------------------------------------------:|:-------------------------------------------------------------:
![View Secret](/img/03-tinypass.png?raw=true "View Secret")     |  ![Edit Secret](/img/04-tinypass.png?raw=true "Edit Secret")


### Mobile Screenshots

Login                                                           |  Show Secrets                                                 |Show Secrets Menu                                              
:--------------------------------------------------------------:|:-------------------------------------------------------------:|:-------------------------------------------------------------:
![Login](/img/05-tinypass.jpg?raw=true "Login")                 |  ![Show Secrets](/img/06-tinypass.jpg?raw=true "Show Secrets")|![View Secret](/img/07-tinypass.jpg?raw=true "View Secret")    

|  View Secret                                                  |  Edit Secret                                                  |
|:-------------------------------------------------------------:|:-------------------------------------------------------------:|
|  ![Edit Secret](/img/08-tinypass.jpg?raw=true "Edit Secret")  |  ![Edit Secret](/img/09-tinypass.jpg?raw=true "Edit Secret")  |

## Demo Video

[![Watch the video](/img/01-tinypass.png)](https://www.tiny-server.com/desktop/video/07-tinypass-manager.m4v)


## Requirements

* Web Server (Apache2)
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

- **Create a directory to copy TinyPass (e.g. "/var/www/html/tinypass")**

```
sudo -u www-data mkdir -v "/var/www/html/tinypass";
```

or create dir automatically to a subdir of your real DocumentRoot as the user running Apache2

```
sudo -u "$(apachectl -S | grep "User" | awk -F"\"" '{ print $2 }')" mkdir -v "$(sudo apachectl -S | grep "DocumentRoot" | awk -F"\"" '{ print $2 }')tinypass";
```

---

- **Copy TinyPass to your new directory (e.g. "/var/www/html/tinypass")**

```
sudo -u www-data git clone https://github.com/ron-from-nl/TinyPass.git "/var/www/html/tinypass";
```

or copy automatically to a subdir of your real DocumentRoot as the user running Apache2

```
sudo -u "$(apachectl -S | grep "User" | awk -F"\"" '{ print $2 }')" git clone https://github.com/ron-from-nl/TinyPass.git "$(sudo apachectl -S | grep "DocumentRoot" | awk -F"\"" '{ print $2 }')tinypass";
```

---

- **Configure your webserver (Apache2 example)**

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

---

- **Test Config**

```
sudo apachectl configtest;
```

- **Reload Config**

```
sudo systemctl reload apache2;
```

- **Test Status**

```
sudo systemctl status apache2;
```

- Open a browser to https://your.ip/tinypass/


