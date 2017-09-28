#!/bin/bash -x
# Check if some user asked for a reset password
# if /tmp/checkfile.txt && /tmp/update-"$token".ldif
# exists it means that somebody is willing to recover password
#
PATH=/sbin:/bin:/usr/bin

# If another ldapsearch.sh cron is running exit
for pid in $(pidof -x ldapsearch.sh); do
    if [ $pid != $$ ]; then
        echo "[$(date)] : ldapsearch.sh : Process is already running with PID $pid"
        exit 1
    fi
done
# Set ldap variables
suffix="dc=example,dc=tld"
ldapbase="o=hosting,"$suffix
peopletree="ou=sshd,ou=People,"$suffix
delete="0"
checkfile="/tmp/checkfile.txt"

# Initial delay to wait puppetcron to start
sleep 10

# Search lock status
url="ldapi://"
basedn="dc=example,dc=tld"
cpaneldn="ou=cpanel"
lockattribute="status"
status=`ldapsearch -Q -Y EXTERNAL -H "$url" -b "$basedn" "$cpaneldn" | awk -F ": " '$1 == "'"$lockattribute"'" {print $2}'`
echo "$status"

# If cpanel is locked or running, exit
if [ "$status" = 'locked' ] || [ "$status" = 'running' ]
  then
    echo "Cpanel has status locked or running, exit cron"
    exit 0
fi

#get ldap admin username and email. it's used by Pets' Encrypt
# Get the ldap admin name
ldapadmin=$(ldapsearch -H ldapi:// -Y EXTERNAL -b "$suffix" "(&(objectClass=extensibleObject)(cn=*))" cn | grep -o -P "(?<=cn: ).*")
mail=$(ldapsearch -H ldapi:// -Y EXTERNAL -b "$suffix" "(&(objectClass=extensibleObject)(cn="$ldapadmin"))" email | grep -o -P "(?<=email: ).*")


if [ -f "$checkfile" ];then
  token=$(cat /tmp/checkfile.txt |  python -c "import sys, json; print json.load(sys.stdin)['token']")
  username=$(cat /tmp/checkfile.txt |  python -c "import sys, json; print json.load(sys.stdin)['username']")
  ldiffile="/tmp/update-"$token".ldif"
fi

if [ -f "$checkfile" ] && [ -f "$ldiffile" ];then
  ldapmodify -H ldapi:// -Y EXTERNAL -f "$ldiffile" && delete="1" 2> /tmp/dnconfig.error
fi

# If all process was successfully send confirmaion mail to user and delete files
if [ "$delete" == "1" ];then
  mail -s "Contraseña cambiada" "$mail" <<< "El proceso de recuperación de contraseña ha terminado. Ahora puedes acceder al Cpanel con tu nueva contraseña"
  if [ -f "$checkfile" ] && [ -f "$ldiffile" ];then
    rm "$ldiffile"
    rm "$checkfile"
  fi
fi

# Script to create Apache virtual hos when a new record is detected
# in ldap.
# The vhosts are create in custom location in order to no mix with 
# shell created Vhosts . The location is /etc/papache2/ldap-enabled.
# For that you have to modify apache3.conf file and add this string
# IncludeOptional ldap-enabled/*.conf
#
# The script also adds a new folder for the domain in /var/www/html
# with an index.html file.
# if a user removes a domain it will be removed from apache 
# configuration, but not nthe files in the domain folder in /var/www/html/domainfolder
# This script uses the phamm user to bind ldap and must be added to the cronjob
# For example, tu run this script each 5 minutes add this line to root corntab.
# Must be run as root
# */5 * * * * /PATH/TO/cron/ldapsearch.sh
#
# This script also mounts wbsite folders located in /var/www/html into users folder
# In ldap tree the vd=domain,o=hosting,cn=example,cn=tld hace an adminID attribute
# Cpanel applicatio uses this attribute in order to allow normal users to upload or 
# edit website through sftp...avoidin terminal or beein root.
# So each domain may have or not a webmaster
# If they have the owner of the corresponding domain will be cahnged and the folder
# will be mounted (--bind) into his home
#
# GNU General Public License v3
# based on this topic: http://stackoverflow.com/questions/14171340/dilemma-realtime-crate-virtual-hosts-or-with-a-crontab/38901618#38901618
# TODO: add let's encrypt script to create certificates for the domain

PATH=/sbin:/bin:/usr/bin
dir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
echo "directory is " $dir;
echo "suffix is " $suffix;
cd $dir
has_new_domains=false #No new domains by default = do not reload the apache config.
vhroot='/etc/apache2/ldap-enabled'
sftpusershome="/home/sftpusers"
ldapresult=()
#do not delete defaults virtualhost that are not in ldap
#defaultvhost='000-default.conf'
#defaultssl='default-ssl.conf'
documenRoot='/var/www/html'
# This is the path of other web applications we want to Alias for each new domain
appsWebRoot='/usr/share'
#chek all monted point
mountresult=()
fqdn="$(hostname -f)"
myip=$(ip route get 8.8.8.8 | awk '/8.8.8.8/ {print $NF}')
#The default user which is sudo (in our configurations is usually user 10000
defaultsudouser=$(ldapsearch -LLL -Y EXTERNAL -H ldapi:/// -b "$peopletree" "(&(objectClass=person)(gidnumber=27))" | grep -o -P "(?<=uid: ).*")

while read domain 
do
    # Create array with all the domains in ldap and add .conf to all them
    # We will use this arryay to check deleted domains from ldap that are
    # still present in /etc/apache2/ldap-enabled, so we can remove them.

    ldapresult+=("$domain".conf)

    # Check if there is a webmaster for current domain. We are using adminID
    # attribute, which is not a required attribute. so is better to check if
    # this value is empty or not

    webmaster=$(ldapsearch -LLL -Y EXTERNAL -H ldapi:/// -b "vd=$domain,$ldapbase" "adminID=*" | grep -o -P "(?<=adminID: ).*")
    #issudouser=$(ldapsearch -LLL -Y EXTERNAL -H ldapi:/// -b "uid="$webmaster",ou=sshd,ou=People,dc=example,dc=tld" "gidNumber=*" | grep -o -P "(?<=gidNumber: ).*")
    #webmaster=$(ldapsearch -x -D "cn=admin,dc=example,dc=tld" -p 389 -h ldap://localhost -b "vd=$domain,o=hosting,dc=example,dc=tld" "adminID=*" -w $bindpass | grep -o -P "(?<=adminID: ).*")

    # Check DNS for current domain
    domainip="$(dig +short "$domain")"

    # If virtualhost does not exists Let's create it
    if [ "$domainip" == $myip ] && [ ! -f $vhroot/"$domain".conf ];
    then
        #New domain. Let's create virtual host
        has_new_domains=true #true = at least one new domain = reload apache config
        echo "<VirtualHost *:80>
        ServerName  "$domain"
        ServerAlias www."$domain"
        ServerAdmin postmaster@"$domain"
        DocumentRoot $documenRoot/"$domain"
        </VirtualHost>" > $vhroot/"$domain"-nossl-.conf

        mkdir $documenRoot/$domain
        echo 'Folder created'

        # Crete the index.html file for the root domain only if it
        # does not exists. Do not override previous user content

        if [ ! -f $documenRoot/"$domain"/index.html ];
        then
          echo "<!DOCTYPE html>
          <html>
          <head>
          <title>Welcome! </title>
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
          <h3>You can now start building your website</h3>

          </body> </html>">$documenRoot/$domain/index.html
        fi
        # Get domain webmaster. If no webmaster is set use the sudo user
        userexists=$(getent passwd | grep "<\$webmaster\>")
        if [[ -z "$webmaster" ]];
        then
          $webmaster="$defaultsudouser"
        fi

        # Set right permission for 
        chown -R $webmaster:web $documenRoot/$domain
        chmod -R 2775 $documenRoot/$domain

        #a2ensite "$domain".conf
        #Need to reload apache to create ssl certifciate with webroot and 
        # Let's encrypt
        # in production remove --staging
        #/etc/init.d/apache2 reload && certbot certonly --agree-tos --staging --non-interactive --text --rsa-key-size 4096 --email $mail --webroot-path $documenRoot/$domain --domains "$domain, www.$domain" && \                 
        # En modo producció : https://acme-v01.api.letsencrypt.org/directory
        # En modo prueba : https://acme-staging.api.letsencrypt.org/directory 

        cerbotdomain="$domain"
        wwwdomainip="$(dig +short "www.$domain")"
        if [ "$wwwdomainip" == $myip ];then
          cerbotdomain+=" -d www."$domain""
        fi

          /etc/init.d/apache2 reload && letsencrypt --server https://acme-v01.api.letsencrypt.org/directory  \
            -d $cerbotdomain --agree-tos --email $mail --webroot --webroot-path $documenRoot/$domain --non-interactive --text --rsa-key-size 4096  certonly &&  \
        echo "<VirtualHost *:80>
        ServerName "$domain"
        ServerAlias www."$domain"

        ## Vhost docroot
        DocumentRoot "/var/www/html/$domain"

        ## Directories, there should at least be a declaration for /var/www/html

        <Directory "$documenRoot/$domain">
          Options Indexes FollowSymLinks MultiViews
          AllowOverride All 
          Require all granted
        </Directory>

        ## Logging
        ErrorLog "/var/log/apache2/$domain-nonssl_error.log"
        ServerSignature Off
        CustomLog "/var/log/apache2/$domain-nonssl_access.log" combined 
        ## Rewrite rules
        RewriteEngine On

        #redirect non-SSL traffic to SSL site but certbot .well-known folder
        RewriteCond %{REQUEST_URI} !^/\.well\-known/acme\-challenge/
        RewriteRule (.*) https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]

        </VirtualHost>  
        <VirtualHost *:443>
        ServerName $domain
        ServerAlias www."$domain"

        ## Vhost docroot
        DocumentRoot "$documenRoot/$domain"

        ## Directories, there should at least be a declaration for /var/www/html

        <Directory "$documenRoot/$domain">
          Options Indexes FollowSymLinks MultiViews
          AllowOverride All
          Require all granted
        </Directory>

        ## Logging
        ErrorLog "/var/log/apache2/$domain-ssl_error_ssl.log"
        ServerSignature Off
        CustomLog "/var/log/apache2/$domain-ssl_access_ssl.log" combined 

        ## SSL directives
        SSLEngine on
        SSLCertificateFile      "/etc/letsencrypt/live/$domain/fullchain.pem"
        SSLCertificateKeyFile   "/etc/letsencrypt/live/$domain/privkey.pem"
        SSLCACertificatePath    "/etc/ssl/certs"
        php_value max_execution_time "3600"
        php_value max_input_time "3600"
        php_value memory_limit "512M"
        php_value post_max_size "2G"
        php_value upload_max_filesize "2G"

        </VirtualHost>" > $vhroot/"$domain".conf
        rm $vhroot/"$domain"-nossl-.conf
    else
      echo 'Cambia los dns'
    fi
    # Check ownership and mountpoints for all domains in ldap
    # as administrator should change this ownership in any time
    # 
    # @TODO: We have  just created new vhosts and folder with $webmaster as owner
    # web folder in /var/www/html

    if [[ ! -z $webmaster ]];
    then

        echo $domain ' Webmaster is: ' $webmaster
        # Check webfolder owner
        owner=$(find $documenRoot/$domain -maxdepth 0 -printf '%u\n')
        # If current owner is not the webmaster change permissions
        if [ ! -z "$owner" ] && [ "$owner" != "$webmaster" ];
        then
          chown -R $webmaster:www-data $documenRoot/$domain
        fi
        # Crete the Mounting point for the website into sftpuser's home 
        # if webmaster is the default user don't mount . Default user
        # is not jailed and will acces websites folder directlyfrom /var/www/
        # NOTE: sftp users home is created the first time they login
        # If it's a new created user this home does not exixst, so we 
        # create before mounting

        if [[ ! -d $sftpusershome/$webmaster && $defaultsudouser != $webmaster ]];
        then
          mkdir $sftpusershome/$webmaster && chmod 700 $sftpusershome/$webmaster && chown $webmaster:sftpusers $sftpusershome/$webmaster
        fi
        mountedsource=$(findmnt | grep "\[$documenRoot/$domain\]")
        # Check all mounted points that a website may have 
        printf '%s\n' "$mountedsource" | while IFS= read -r line
        do
          echo $line
          # Extract only username. We only check in /home/sftpusers/ folder. Other mount points 
          # for the website are indifferent to us 
          activemount=$(echo "$line" | awk -v FS="(\/sftpusers\/|\/$domain)" '{print $2}')

          # store all users  space separeted ("$var") in whose home a  website is mounted
          mountresult+=("$activemount")
          # If there is a mounting point in a user home, and this user is not the website owner
          # umont it.It probably means ownership changed
          if [ "$activemount" != "$webmaster" ] && [ ! -z "$activemount" ];then
            umount $sftpusershome/$activemount/$domain
          fi
          # If $owner is not listed between the active mounting points we have collected 
          # by username in $mountresult() it means that we have to mount the web folder on his home 
          # We check exact match (between spaces) in order to avoid parcial matches (mari is not maria)
          if [[ ! ${mountresult[@]} =~ $(echo '\<'$webmaster'\>') && $defaultsudouser != $webmaster ]];then
            mkdir -p $sftpusershome/$webmaster/$domain
            chown $webmaster:www-data $sftpusershome/$webmaster/$domain
            mount --bind  $documenRoot/$domain $sftpusershome/$webmaster/$domain
          fi
      done
    fi     
  done < <(ldapsearch -LLL -Y EXTERNAL -H ldapi:/// -b "$ldapbase" "(objectclass=VirtualDomain)" | grep -o -P '(?<=vd=).*(?=,o=hosting,dc=example)') 
#delete vhost that are not anymore in ldap tree but still in apache
printf "%s\n" "${ldapresult[@]}"
for vhost in "$vhroot"/*;
do
    basevhost=$(basename $vhost)
    # Exclude default and default-ssl virtual 
    [[ $basevhost =~ ^($defaultvhost|$defaultssl)$ ]] && continue
    #echo $(basename $vhost)

    if echo ${ldapresult[@]} | grep -q -w "$basevhost"; then
        #do nothing
        echo $basevhost 'is present in ldap'
        echo $folderdomain 'is present in system'
    else
        if [ ! -z "$basevhost" ]; then
            folderdomain=${basevhost:0:-5}

            #disable and delete apache virtualhost, and web files
            echo $basevhost 'is NOT present in ldap so we can delete it'
            echo "dominio "$basevhost" eliminado"
            rm $vhroot/"$basevhost" && has_new_domains=true
            # Clean letsencrypt certificates
            # We need to remove renewal and also live and archive, so if a user creates the same domain
            # after removing it all the let's encrypt certs will be generated again from scratch
            letsencryptfolders='/etc/letsencrypt'
            live="$letsencryptfolders/live/$folderdomain"
            archive="$letsencryptfolders/archive/$folderdomain"
            renew="$letsencryptfolders/renewal/$basevhost"
            echo $live '\n'
            echo $archive '\n'
            echo $renew '\n'

            if [[ -d $live ]];
            then 
              rm -r $live
            fi

            if [[ -d $archive ]];
            then 
              rm -r $archive
            fi

            if [[ -f $renew ]];
            then
              rm $renew
            fi

            # We won't delete the web folder in /var/www/htnl, so we set the ownwer as the default sudo user again
            chown -R $defaultsudouser:www-data $documenRoot/$folderdomain

            #Check if there was a mounted pojnt for the deleted domain. if so umount it
            mounteddomains=$(findmnt | grep "\[$documenRoot/$folderdomain\]")

            # Check all mounted points that a website may have greping with current domain 
            printf '%s\n' "$mounteddomains" | while IFS= read -r line
            do
              echo $line
              # Get username for current domain mounted point. Only check fir sftpusers homes mounted points
              delmount=$(echo "$line" | awk -v FS="(\/sftpusers\/|\/$folderdomain)" '{print $2}')

              # If there is a mounting point for a deleted domain in a user home lets' umount it.
              if [ ! -z "$delmount" ];then
                umount $sftpusershome/$delmount/$folderdomain && rm -R $sftpusershome/$delmount/$folderdomain
              fi

            done
        fi
    fi
    done
    #reload apache with new vhosts
    if $has_new_domains ; then #only reload the apache config if there is at least one new domain
      /etc/init.d/apache2 reload
fi
cd

# Now we are out of the domain loop
# Check if there are some homes in sftpuser's home folder
# without existing user
# When deleting an sftuser from ldap, his home will still be there
# and only root will be able to remove it.
# To avoid that and allow the defaullt user to acees these folders
# with an sftp connection Let's move it into the default user 
# home and change ownerships


sftpusershome="/home/sftpusers"
existingusers=$(ldapsearch -H ldapi:// -Y EXTERNAL -b "$peopletree" "(&(objectClass=person)(authorizedservice=sshd)(uid=*)(!(gidnumber=27)))" uid | grep -o -P "(?<=uid: ).*")
# Create the directory in which to move the orphaned homes

if [[ ! -d '/home/'"$defaultsudouser" ]];then
  mkhomedir_helper "$defaultsudouser" 0077
fi

if [[ -d '/home/'"$defaultsudouser"'/sftp-deleted' ]];then
  moveto='/home/'"$defaultsudouser"'/sftp-deleted'
else
  mkdir '/home/'"$defaultsudouser"'/sftp-deleted'
  chown "$defaultsudouser" '/home/'"$defaultsudouser"'/sftp-deleted'
  moveto='/home/'"$defaultsudouser"'/sftp-deleted'
fi
echo "$moveto"
for sftphome in "$sftpusershome"/*/;
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
