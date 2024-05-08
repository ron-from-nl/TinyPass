# TinyPass

New on GitHub since: 1st of May 2024

PHP Password Manager with 100% Custom Fields and a built-in SQLite Database (author: [Ron de Jong](https://www.tiny-server.com/#contact))

<!-- MDTOC maxdepth:2 firsth1:0 numbering:0 flatten:0 bullets:1 updateOnSave:1 -->

- [Why](#why)
- [Easy](#easy)
- [Try Demo](https://www.tiny-server.com/web/share/drive/public/tinypass/index.php)
- [Functions](#functions)
- [Read More](#read-more)
- [User Licence](#user-licence)
- [Screenshots](#screenshots)
- [Demo Video](#demo-video)
- [Requirements](#requirements)
- [Installation](#installation)

<!-- MDTOC -->


## Why

TinyPass has Secrets with unlimited Custom Fields, supports mobiles screens and many other fun features.
Most Password Managers use Secret Templates with predefined Fixed Fields causing unused and missing fields.


## Easy

TinyPass is easy to install, no database server, no db user and access rights or manual paths / URLs to specify.
At first run TinyPass makes a single database file called "tinypass.db" which makes backup & restore also easy.

- Copy TinyPass to a web directory
- Configure your (Apache) Web Server
- Browse to https://your.ip/tinypass/


## Why TinyPass

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

## Read More

<details>
<summary>Click to expand</summary>

## Description

### Secrets

TinyPass works with "Secrets" holding any number of Fields. New Secrets start with just a Name and an optional Group.
Then add, change or remove just the Fields you need and rearrange their order as you wish.

### Fields

Field Types:

URL - Link that also opens a browser	<br />
Mail - Email address (can be a Login)	<br />
Pass - Password Field (encrypted)		<br />
Text - General purpose text field		<br />
Note - Multi-line resizable textarea	<br />

### Groups

Optionally, Secrets can be members of a "Group" selected from a list. The group selection list is editable and non-existent groups are automatically added to the user's groups, making group management a breeze.

### Search

The interface has a good Search Engine that also searches deep into the Fields of your Secrets, optionally filtered by Group, so you can find your Secrets in an instant (SQL wildcards "_" and "%" are supported).

### Sort

You can sort all columns in "Show Secrets", "Show Groups" and "Show Users" in ascending and descending order. Sorting the first "Id" column can be handy when you need to remove the last CSV import.

### Select

You can select multiple records in "Show Secrets", "Show Groups" and "Show Users" for deletion or CSV export. 

### Users

By default there are two users named **User: "admin"** (role: "Admin") and **User: "tiny"** (role: "User"). Admins can also add, change, or delete users.
When an Admin deletes a user then all user-related Secrets and Groups are deleted. Admins cannot read other user Secrets and cannot delete users: "admin", "tiny".
When a user performs a Password Change, then all Password Fields are re-encrypted. TinyPass also has a Shell interface that enables automated Password Changes.  

### Import / Export

TinyPass supports CSV Import / Export (with automatic format recognition) and is compatible with more than 10 different password manager formats, including various browsers and other well-known formats.
The group filter is also useful here, allowing you to export only Secrets being a member of a certain Group for instance when using Groups as names of people for whom you keep Secrets.

### Security

Password fields are AES-256-CTR encrypted in the database with a SHA512 hash key of your user password (not written anywhere), so remember your password!

### Other

TinyPass is designed with performance in mind and also supports mobile screens.

</details>


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
Alias /tinypass/ /var/www/html/tinypass/

<Directory /var/www/html/tinypass>
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


