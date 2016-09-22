#!/bin/bash
# Script to create Apache virtual hos when a new record is detected
# in ldap. It also add a new folder for the domain in /var/www/websites
#with a index.html file.
# ifa use remove a domain it will be disabled and removed in apache 
# configuration, such as the domain folder in /var/www/websites/
# This script uses the phamm user to bind ldap and must be added to the cronjob
# For example, tu run this script each 5 minutes add this line to root corntab.
# Must be root as Apache has to be reloaded:
# */5 * * * * /var/www/html/cpanel/cron/ldapsearch.sh
#
# GNU General Public License v3
# based on this topic: http://stackoverflow.com/questions/14171340/dilemma-realtime-crate-virtual-hosts-or-with-a-crontab/38901618#38901618
# TODO: add let's encrypt script to create certificates for the domain

bindpass=$(awk -F\" '/RDNPSW/{print $(NF-1)}'  ../site-config.php)
binddn=$(awk -F\" '/READDN/{print $(NF-1)}'  ../site-config.php)
ldapbase=$(awk -F\" '/LDAP_BASE/{print $(NF-1)}'  ../site-config.php)
echo $binddn;
echo $ldapbase;
#echo $bindpass;
has_new_domains=false #No new domains by default = do not reload the apache config.
vhroot='/etc/apache2/sites-available/'
ldapresult=()
#do not delete defaults virtualhost that are not in ldap
defaultvhost='000-default.conf'
defaultssl='default-ssl.conf'
documenRoot='/var/www/html'

while read domain 
do
	ldapresult+=("$domain".conf)
	if [ -f $vhroot/"$domain".conf ];then
		echo 'file existe'
	else
		#New domain. Let's create virtual host
     	has_new_domains=true #true = at least one new domain = reload apache config
      	echo "<VirtualHost *:80>
		ServerName  "$domain"
		ServerAlias www."$domain"
		Alias /cpanel '"$documenRoot"/cpanel'
		Alias /owncloud '"$documenRoot"/owncloud'
		Alias /webmail '"$documenRoot"/webmail'
		ServerAdmin postmaster@"$domain"
		DocumentRoot /var/www/html/"$domain"
		</VirtualHost>" > $vhroot/"$domain".conf

		mkdir $documenRoot/$domain

		echo "<!DOCTYPE html>
		<html>
		<head>
		<title>Welcome to nginx on Debian!</title>
		<style>
		body {
			width: 35em;
			margin: 0 auto;
			font-family: Tahoma, Verdana, Arial, sans-serif;
			}
		</style>
		</head>
        <body>
        <h1>Welcome to "$domain"</h1>
        <p>If you see this page, the Aoache web server is successfully installed and
        working on Debian. Further configuration is required.</p>

        <p>
          Please start builing your website 
        </p>
        </body> </html>">$documenRoot/$domain/index.html
        a2ensite "$domain".conf
		
	fi	
done < <(ldapsearch -x -D "$binddn"  -p 389 -h ldap://localhost -b "$ldapbase" -s one "(objectclass=VirtualDomain)" -w $bindpass | grep -o -P '(?<=vd=).*(?=,o=hosting,dc=example)') 
#delete vhost that are not anymre in ldap tree but still in apache
printf "%s\n" "${ldapresult[@]}"
for vhost in "$vhroot"/*;
do
	basevhost=$(basename $vhost)
	folderdomain=${basevhost:0:-5}
	# Exclude default and default-ssl virtual 
	[[ $basevhost =~ ^($defaultvhost|$defaultssl)$ ]] && continue
	#echo $(basename $vhost)
	
	if [[ " ${ldapresult[@]} " =~ " $basevhost " ]]; then
		#do nothing
		echo $basevhost 'is present in ldap'
		echo $folderdomain 'is present in system'
	else
		if [ ! -z "$basevhost" ]; then
			#disable and delete apache virtualhost, and web files
			echo $basevhost 'is NOT present in ldap and is not' $defaultvhost 'or' $defaultssl ' so we can delete it'
			a2dissite "$basevhost"
			echo "dominio "$basevhost" eliminado"
			rm $vhroot"$basevhost"
		fi
	fi
	done
	#reload apache with new vhosts
	if $has_new_domains ; then #only reload the apache config if there is at least one new domain
	  /etc/init.d/apache2 reload
fi
