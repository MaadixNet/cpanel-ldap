#!/bin/bash -x
PATH=/sbin:/bin:/usr/bin
newpass="$1"
readdn="$2"
delete="$3"
[ -z "$newpass" ] && echo "Error: No password provided" >&2 && exit 1
[ -z "$readdn" ] && echo "Error: No username provided" >&2 && exit 1
[[ ! "$readdn" =~ ^[a-zA-Z0-9=,] ]] && echo "Error: Invalid Username" >&2 && exit 1
[[ ! "$delete" =~ ^[01] ]] && echo "Error: Invalid param" >&2 && exit 1
## Need to
# 1 - Get current cn=config password and save for later use
# 2- Update it with  new one
# 3- Updtae ldap norma DIT
# 4-  Bring cn=config password back to previous one or random one ????
ldapsearch -H ldapi:// -LLL -Q -Y EXTERNAL -b "cn=config" "(olcRootDN="$readdn")" dn olcRootDN olcRootPW > /tmp/dnconfig.txt && awk "/^dn\: olcDatabase/" /tmp/dnconfig.txt > /tmp/dnconfig.ldif && echo -e "changetype: modify\nreplace: olcRootPW\nolcRootPW: "$newpass"" >> /tmp/dnconfig.ldif && sudo ldapmodify -H ldapi:// -Y EXTERNAL -f /tmp/dnconfig.ldif 2> /tmp/dnconfig.error
#Operations are over . Delete al /tmp generated files
if [ "$delete" == "1" ];then
  rm /tmp/dnconfig*
  rm /tmp/checkfile.txt
fi
