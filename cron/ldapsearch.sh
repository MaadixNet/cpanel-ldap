#!/bin/bash -x
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
cd $dir
bindpass=$(awk -F\" '/RDNPSW/{print $(NF-1)}'  ../site-config.php)
binddn=$(awk -F\" '/READDN/{print $(NF-1)}'  ../site-config.php)
ldapbase=$(awk -F\" '/LDAP_BASE/{print $(NF-1)}'  ../site-config.php)
echo $binddn;
echo $ldapbase;
echo $bindpass;
#echo $bindpass;
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

while read domain 
do
    # Create array with all the domains in ldap and add .conf to all them
    # We will use this arryay to check deleted domains from ldap that are
    # still present in /etc/apache2/ldap-enabled, so we can remove them.
    ldapresult+=("$domain".conf)
    #Check is there is a webmaster for current domain. We are using adminID
    # attribute, which i not a required attribute. so is better to check if
    # this value is empty or not
    webmaster=$(ldapsearch -x -D "cn=admin,dc=example,dc=tld" -p 389 -h ldap://localhost -b "vd=$domain,o=hosting,dc=example,dc=tld" "adminID=*" -w $bindpass | grep -o -P "(?<=adminID: ).*")
    # If virtualhost does not exists Let's create it
    if [[ ! -f $vhroot/"$domain".conf ]];
    then
        #New domain. Let's create virtual host
        has_new_domains=true #true = at least one new domain = reload apache config
        echo "<VirtualHost *:80>
        ServerName  "$domain"
        ServerAlias www."$domain"
        Alias /cpanel '"$appsWebRoot"/cpanel'
        Alias /owncloud '"$appsWebRoot"/owncloud'
        Alias /webmail '"$appsWebRoot"/webmail'
        ServerAdmin postmaster@"$domain"
        DocumentRoot /var/www/html/"$domain"
        </VirtualHost>" > $vhroot/"$domain".conf

        mkdir $documenRoot/$domain
        chown -R root:www-data $documenRoot/$domain
        chmod 755 $documenRoot/$domain
        #chmod g+s $documenRoot/$domain
        echo 'Folder created'
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
        #a2ensite "$domain".conf
    fi
    # Check ownership and mountpoints for all domains in ldap
    # as administrator should change this ownership in any time
    # 
    # @TODO: We just created new vhosts and folder with root as owner
    #   We could create the new folder with thw corresponding owner (webmaster)
    #   but as the administrator should change this ownership at any time
    #   let's do it this way for now.
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
        # NOTE: sftp users home is created the first time they login
        # If it's a new created user this home does not exixst, so we 
        # create before mounting
        if [[ ! -d $sftpusershome/$webmaster ]];
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

          # store all users  space separeted ("$var") inwhose home a  website is mounted
          mountresult+=("$activemount")
          # If there is a mounting point in a user home, and this user is not the website owner
          # umont it.It probably means ownership changed
          if [ "$activemount" != "$webmaster" ] && [ ! -z "$activemount" ];then
            umount $sftpusershome/$activemount/$domain
          fi
          # If $owner is not listed between the active mounting points we have collected 
          # by username in $mountresult() it means that we have to mount the web folder on his home 
          # We check exact match (between spaces) in order to avoid parcial matches (mari is not maria)
          if [[ ! ${mountresult[@]} =~ $(echo '\<'$webmaster'\>') ]];then
            mkdir -p $sftpusershome/$webmaster/$domain
            chown $webmaster:www-data $sftpusershome/$webmaster/$domain
            mount --bind  $documenRoot/$domain $sftpusershome/$webmaster/$domain
          fi
      done
    fi     
  done < <(ldapsearch -x -D "$binddn"  -p 389 -h ldap://localhost -b "$ldapbase" "(objectclass=VirtualDomain)" -w $bindpass | grep -o -P '(?<=vd=).*(?=,o=hosting,dc=example)') 
#delete vhost that are not anymre in ldap tree but still in apache
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
            # We won't delete the web folder in /var/www/htnl, so we set the ownwe as root again
            chown -R root:www-data $documenRoot/$folderdomain
            #Check if there was a mounted pojnt for the deleted domain. if so umount is
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
