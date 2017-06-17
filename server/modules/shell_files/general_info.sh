#!/bin/bash -x

function displaytime {
  local T=$1
  local D=$((T/60/60/24))
  local H=$((T/60/60%24))
  local M=$((T/60%60))
  local S=$((T%60))
  [[ $D > 0 ]] && printf '%d days ' $D
  [[ $H > 0 ]] && printf '%d hours ' $H
  [[ $M > 0 ]] && printf '%d minutes ' $M
  [[ $D > 0 || $H > 0 || $M > 0 ]] && printf 'and '
  printf '%d seconds\n' $S
}

lsbRelease=$(/usr/bin/lsb_release -ds | sed -e 's/^"//'  -e 's/"$//')
uname=$(/bin/uname -r | sed -e 's/^"//'  -e 's/"$//')
os=`echo $lsbRelease $uname`
hostname=$(/bin/hostname)
uptime_seconds=$(/bin/cat /proc/uptime | awk '{print $1}')
server_time=$(date)
server_ip=$(/bin/ip addr | grep 'state UP' -A2 | tail -n1 | awk '{print $2}' | cut -f1  -d'/')
fqdn=$(hostname -f)
echo { \
    \"OS\": \"$os\", \
    \"Hostname\": \"$hostname\", \
    \"IP Address\": \"$server_ip\", \
    \"fqdn \(fully quaified domain\)\": \"$fqdn\", \
    \"Uptime\": \" $(displaytime ${uptime_seconds%.*}) \", \
    \"Server Time\": \"$server_time\" \
  }
