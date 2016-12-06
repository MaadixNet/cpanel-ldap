#!/bin/bash -x
PATH=/sbin:/bin:/usr/bin
#newpass="$1"
#readdn="$2"
delete="0"
#[ -z "$newpass" ] && echo "Error: No password provided" >&2 && exit 1
#[ -z "$readdn" ] && echo "Error: No username provided" >&2 && exit 1
#[[ ! "$readdn" =~ ^[a-zA-Z0-9=,] ]] && echo "Error: Invalid Username" >&2 && exit 1
#[[ ! "$delete" =~ ^[01] ]] && echo "Error: Invalid param" >&2 && exit 1
## Need to
# 1 - Get current cn=config password and save for later use
# 2- Update it with  new one
# 3- Updtae ldap norma DIT
# 4-  Bring cn=config password back to previous one or random one ????
checkfile="/tmp/checkfile.txt"
cat "$checkfile"
token=$(cat /tmp/checkfile.txt |  python -c "import sys, json; print json.load(sys.stdin)['token']")
username=$(cat /tmp/checkfile.txt |  python -c "import sys, json; print json.load(sys.stdin)['username']")
ldiffile="/tmp/update-"$token".ldif" 
if [ -f "$checkfile" ] && [ -f "$ldiffile" ];then
  ldapmodify -H ldapi:// -Y EXTERNAL -f "$ldiffile" && delete="1" 2> /tmp/dnconfig.error
fi
# If all process was successfully send confirmaion mail to user and delete files
if [ "$delete" == "1" ];then
  mail=$(ldapsearch -H ldapi:// -Y EXTERNAL -b "dc=example,dc=tld" "(&(objectClass=extensibleObject)(cn=admincp))" email | grep -o -P "(?<=email: ).*")

  mail -s "Contraseña cambiada" "$mail" <<< "El proceso de recuperación de contraseña ha terminado. Ahora puedes acceder al Cpanel con tu nueva contraseña"

  rm "$ldiffile" 
  rm "$checkfile" 
fi
