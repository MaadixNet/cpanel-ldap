#!/bin/bash -x
# Check if there are some homes in sftpuser's home folder
# without existing user
# When deleting an sftuser from ldap, his home will still be there
# and only root will be able to remove it.
# To avoid that and allow the defolt user to acees these folders
# with an sftp connecction Let's move it into the default user 
# home and change ownerships


PATH=/sbin:/bin:/usr/bin

suffix="dc=example,dc=tld"
ldapbase="o=hosting,"$suffix
peopletree="ou=sshd,ou=People,"$suffix
defaultsudouser=$(ldapsearch -LLL -Y EXTERNAL -H ldapi:/// -b "$peopletree" "(&(objectClass=person)(gidnumber=27))" | grep -o -P "(?<=uid: ).*")
ldapadmin=$(ldapsearch -H ldapi:// -Y EXTERNAL -b "$suffix" "(&(objectClass=extensibleObject)(cn=*))" cn | grep -o -P "(?<=cn: ).*")
sftpfolder="/home/sftpusers"
existingusers=$(ldapsearch -H ldapi:// -Y EXTERNAL -b "$peopletree" "(&(objectClass=person)(authorizedservice=sshd)(cn=*))" cn | grep -o -P "(?<=cn: ).*")
# Create the directory in which to move the orphaned homes

if [[ -d '/home/'"$defaultsudouser"'/sftp-deleted' ]];then
  moveto='/home/'"$defaultsudouser"'/sftp-deleted'
else
  mkdir '/home/'"$defaultsudouser"'/sftp-deleted'
  chown "$defaultsudouser" '/home/'"$defaultsudouser"'/sftp-deleted'
  moveto='/home/'"$defaultsudouser"'/sftp-deleted'
fi
echo "$moveto"
for sftphome in "$sftpfolder"/*;
do
  basehome=$(basename $sftphome)
  if echo ${existingusers[@]} | grep -q -w "$basehome"; then
    #do nothing
    echo "$basehome" 'is present in ldap'
  else
    if [ ! -z "$basehome" ]; then
      echo "$sftphome"  es huerfana y la movemos
      chown -R "$defaultsudouser" "$sftphome"
      mv "$sftphome" "$moveto"
    fi
  fi
done
