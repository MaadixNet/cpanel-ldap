#!/bin/bash
user=$1
uspswd=$2
export HISTIGNORE='*echo*'
 
echo "$(uspswd)" | su -u $user -S ldapsearch -H ldapi:// -LLL -Q -Y EXTERNAL -b 'cn=config' '(olcRootDN=*)' dn olcRootDN olcRootPW 2>&1 | tee /usr/share/cpanel/files/ldap/dnconfig.txt

