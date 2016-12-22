<?php 
define ("LDAP_HOST_NAME","localhost");

// The protocol version [2,3]
define ("LDAP_PROTOCOL_VERSION","3");

// The server port (To use ldapssl change to 636)
define ("LDAP_PORT","389");

// Set LDAP_TLS to 1 if you want to use TLS
define ("LDAP_TLS",0);

//The LDAP container
define ("SUFFIX","dc=example,dc=tld");

// The Phamm container - change it if your installation has different structure
define ("LDAP_BASE","o=hosting," . SUFFIX);

//The People container for sftp users and vpn users
define ("LDAP_PEOPLE","ou=sshd,ou=People," .  SUFFIX);

define ("LDAP_SERVICES", "ou=groups," . SUFFIX);

//To create internal links
define ("BASE_PATH" , basename(__DIR__));  

// The languages available
 $supported_languages = array();
// $supported_languages["de_DE"] = "Deutsch";
 $supported_languages["en_GB"] = "English";
 $supported_languages["es_ES"] = "Español";
// $supported_languages["fr_FR"] = "French";
// $supported_languages["hu_HU"] = "Hungarian";
// $supported_languages["it_IT"] = "Italiano";
// $supported_languages["pl_PL"] = "Polish";
// $supported_languages["ru_RU"] = "Russian";
// $supported_languages["vi_VN"] = "Tiếng Việt"; // Vietnamese
// $supported_languages["da_DK"] = "Dansk"; // Danish
// $supported_languages["pt_BR"] = "Portuguese";
