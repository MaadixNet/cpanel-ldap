Cpanel LDAP
=========

A simplified cpanel to manage LDAP server. It's based on Phamm schema and adds a VPN Organization Unit in which users can be added in order to authenticate themselves against LDAP.



## Requirements

* LDAP Server
* Phamm schema
* mOpenVPN server installed and running whit authentication against ldap
* PHP
* Apache

## Installation
Download or clone files  
Copy site-config-example.php to site-config.php  
Edit site-config.php filling with your data  


```php
<?php
	define ("LDAP_HOST_NAME","localhost");

	// The protocol version [2,3]
	define ("LDAP_PROTOCOL_VERSION","3");

	// The server port (To use ldapssl change to 636)
	define ("LDAP_PORT",'389');

	// Set LDAP_TLS to 1 if you want to use TLS
	define ("LDAP_TLS",0);

	// The container
	define ("SUFFIX","dc=example,dc=tld");

	// The admin bind dn (could be rootdn)
	define ("BINDDN","cn=admin,dc=example,dc=tld");

	//reader user for ldapserch.sh script. If you don't have any 'only read' user you can use admin,
	//but for security it would be better to have a lower privilege user with no write permissions
	//we can use the default phamm user
	define ("READDN","cn=phamm,dc=example,dc=tld");

	//read user password to allow ldapserch.sh to bind ldap
	define ("READDNPSW","rhx");

	// The base container - change it if your installation has different structure
	define ("LDAP_BASE","o=hosting,dc=example,dc=tld");

	//The VPN container
	define ("LDAP_VPN","ou=vpn,dc=example,dc=tld");

	//the cpanel URL installation. As we always will install Cpanel in a path under the default domain, we could avoid this, but
	//for other users in cutsom installation could be useful
	define("BASE_URL", "http://example.com/cpanel/");

```

if you want to automatically create apache vhosts and Document Root folder when a new domain is addedd, you need to
add the conjob that runs the cron/ldapsearch.sh script.  
In this example the script will run each 5 minutes.  
As root run crontab -e and add this line  


    */5 * * * * /var/www/html/cpanel/cron/ldapsearch.sh

## Usage

Login :  
for cn=admin,dc=example,dc=tld use  
User: admin  
  
for email users will be their complete email  
user@mydomain.com  
<br />
for domain administrator will be the full domain  
eg: mydomain.com  
It will login user as postmaster@mydomain.com  

