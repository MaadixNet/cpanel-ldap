#!/bin/bash

fqdn="$(/bin/hostname -f)"
#Get DNS configuration
recorda=$(/usr/bin/dig -t A +short "$fqdn")
recordMX=$(/usr/bin/dig -t MX +short "$fqdn" | awk '{print $2}')
recordspf=$(/usr/bin/dig -t TXT +short "$fqdn" | grep spf1 | grep -o -P "(?<=\").*(?=\")")
recorddkim=$(/usr/bin/dig default._domainkey."$fqdn" TXT | grep -o -P "(?<=p=).*(?=\")")
recordaok=$(/bin/hostname -i)
recordMXok="$fqdn"
recordspfok="v=spf1 a mx ~all"

recorddkimok=$(/bin/cat /etc/opendkim/keys/"$fqdn"/default.txt | grep -o -P "(?<=\"p=).*(?=\")")

echo [" {\"TypeA\": \"A\", \"valA\": \"$recorda\", \"valAok\": \"$recordaok\",\"TypeMX\": \"MX\", \"valMX\": \"$recordMX\", \"valMXok\": \"$recordMXok\",\"TypeSPF\": \"SPF\", \"valSPF\": \"$recordspf\", \"valSPFok\": \"$recordspfok\",\"TypeDKIM\": \"DKIM\", \"valDKIM\": \"$recorddkim\", \"valDKIMok\": \"$recorddkimok\"}"]
