<?php
$ds=ldap_connect("localhost");
if ($ds) {
   $sr=ldap_search($ds, "ou=cpanel,dc=example,dc=tld", "ou=cpanel");
   $info = ldap_get_entries($ds, $sr);
   ldap_close($ds);
   echo $info[0]['status'][0];
}else {
  echo "locked";
}
