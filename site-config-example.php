<?php 
define ("LDAP_HOST_NAME","localhost");

// The protocol version [2,3]
define ("LDAP_PROTOCOL_VERSION","3");

// The server port (To use ldapssl change to 636)
define ("LDAP_PORT","389");

// Set LDAP_TLS to 1 if you want to use TLS
define ("LDAP_TLS",0);

// The container
define ("SUFFIX","dc=example,dc=tld");

// The admin bind dn 
# Get value from function. if it doesn't work set it manually
$binddn =  get_bind_dn();
define ("BINDDN",$binddn);

// The Phamm container - change it if your installation has different structure
define ("LDAP_BASE","o=hosting," . SUFFIX);

//The People container for sftp users and vpn users
define ("LDAP_PEOPLE","ou=sshd,ou=People," .  SUFFIX);

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


 # Get admin name dinamically throug a ESXTERNAL bind connection
 # Ldap need to be configured in oreder to allow 
 # anonymous bind connection for apache
 # If you don't want this kind od configuratiosn for your ldap
 # server define BINDDN manually...as eg: cn=admin,dc=example,dc=tld 

## Get the admin bame
    function get_bind_dn () {
      //Special connection Only read mode to rertieve cn and mail
      $host = "ldapi:///";
      $base = SUFFIX;

      $ds = ldap_connect($host);

      //buscamos cualquier entrada
      $filter="(&(objectclass=extensibleObject)(!(cn=uidNext)))";
      //de las entradas solo queremos cn 
      $justthese = array("cn");

      $sr=ldap_search($ds, $base, $filter, $justthese);
      $info = ldap_get_entries($ds, $sr);
      $adminname = $info[0]["dn"];
      return $adminname;
      }  



