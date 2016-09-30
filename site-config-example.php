<?php 
define ("LDAP_HOST_NAME","localhos");

// The protocol version [2,3]
define ("LDAP_PROTOCOL_VERSION","3");

// The server port (To use ldapssl change to 636)
define ("LDAP_PORT","389");

// Set LDAP_TLS to 1 if you want to use TLS
define ("LDAP_TLS",0);

// The container
define ("SUFFIX","dc=example,dc=tld");

// The admin bind dn (could be rootdn)
define ("BINDDN","cn=admin,dc=example,dc=tld");

// The Phamm container - change it if your installation has different structure
define ("LDAP_BASE","o=hosting,dc=example,dc=tld");

//The VPN container
define ("LDAP_VPN","ou=vpn,dc=example,dc=tld");

//reader user for ldapserch.sh script. If you don't have any only read user you can use admin,
////but for security it would be better to have a lower privilege user with no write permissions
////we can use the default phamm user
define ("READDN","cn=phamm,dc=example,dc=tld");

////read user password to allow ldapserch.sh to bind ldap
define ("RDNPSW","rhx");
//To create internal links
define ("BASE_PATH" , basename(__DIR__));  