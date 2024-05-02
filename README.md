# TinyPass

Password Manager with SQLite DB and 100% Custom Fields

Tiny Pass is how Password Managers should be. No installation hassle, no database server, tables & data setup, no websetup as it does all these things automatically.
Copy the contents to your webdirectory, configure your webserver and open a browser to e.g. https://bla/tinypass/ and change the default user passwords when prompted.

Video: https://www.tiny-server.com/desktop/video/07-tinypass-manager.m4v

<!-- MDTOC maxdepth:2 firsth1:0 numbering:0 flatten:0 bullets:1 updateOnSave:1 -->

- [Licence Agreement](#licence-agreement)
- [Requirements](#requirements)
- [Installation](#installation)

<!-- /MDTOC -->

## Website

[Part of Tiny Server](https://tiny-server.com/)

## Licence Agreement

Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 (CC BY-NC-ND 4.0)

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
	sudo apachectl configtest;

Reload Config
	sudo systemctl reload apache2;

Test Status
	sudo systemctl status apache2;

- Open a browser to https://www.your.host/tinypass/
