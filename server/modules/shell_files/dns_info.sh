#!/bin/bash

fqdn="$(/bin/hostname -f)"
fqdn="usavm3list.gadix.net"
#Get DNS configuration
recorda=$(/usr/bin/dig -t A +short "$fqdn")
recordMX=$(/usr/bin/dig -t MX +short "$fqdn" | awk '{print $2}')
recordspf=$(/usr/bin/dig -t TXT +short "$fqdn" | grep spf1 | grep -o -P "(?<=\").*(?=\")")
recorddkim=$(/usr/bin/dig default._domainkey."$fqdn" TXT | grep -o -P "(?<=p=).*(?=\")")
recordaok=$(/bin/hostname -i)
recordMXok="$fqdn"
recordspfok="v=spf1 a mx ~all"

recorddkimok=$(/bin/cat /etc/opendkim/keys/"$fqdn"/default.txt | grep -o -P "(?<=\"p=).*(?=\")")

echo [" {\"Type\": \"A\", \"val\": \"$recorda\", \"valok\": \"$recordaok\"}, {\"Type\": \"MX\", \"val\": \"$recordMX\", \"valok\": \"$recordMXok\"}, {\"Type\": \"SPF\", \"val\": \"$recordspf\", \"valok\": \"$recordspfok\"}, {\"Type\": \"DKIM\", \"val\": \"$recorddkim\", \"valok\": \"$recorddkimok\"}"]
