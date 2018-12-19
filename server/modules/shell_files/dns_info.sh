#!/bin/bash +x

fqdn="$(/bin/hostname -f)"
#Get DNS configuration

# A record
recorda=$(/usr/bin/dig -t A +short "$fqdn")
recordaok=$(/bin/hostname -i)

#MX record
recordMX=$(/usr/bin/dig -t MX +short "$fqdn" | awk '{print $2}')
recordMXok=$fqdn"."

#Record spf
recordspf=$(/usr/bin/dig -t TXT +short "$fqdn" | grep spf1 | grep -o -P "(?<=\").*(?=\")")
recordspfok="v=spf1 a mx ~all"

#record Dkim

recorddkim=$(/usr/bin/dig +short default._domainkey."$fqdn" TXT  | tr -d '\n' | tr -d '\t' | tr -d '\r' | sed "s/\" \"//g" | tr -d '\"' )
#recorddkimKey=$(/bin/cat /etc/opendkim/keys/"$fqdn"/default.txt | grep -o -P "(?<=\"p=).*(?=\")")
#recorddkimok="v=DKIM1; k=rsa; p="$recorddkimKey
recorddkimok=$(/bin/cat /etc/opendkim/keys/"$fqdn"/default.txt | tr -d '\"' | tr -d '\n' | tr -d '\t' | tr -d '\r' | tr -s " " | grep -o -P "(?<=\(\s).*(?=\s\))")
if [ "$recordspfok" != "$recordspf" ];then
  spfClass="error"
fi

if [ "$recorddkim" != "$recorddkimok" ] || [ -z "$recorddkim" ] ;then
  dkimClass="error"
fi

if [ $recorda != $recordaok ];then
  aClass="error"
fi

if [ $recordMX != $recordMXok ];then
  mxClass="error"
fi

echo {\"Fqdn\": \"$fqdn\",\"classA\": \"$aClass\",\"TypeA\": \"A\", \"valA\": \"$recorda\", \"valAok\": \"$recordaok\",\"classMX\": \"$mxClass\",\"TypeMX\": \"MX\", \"valMX\": \"$recordMX\", \"valMXok\": \"$recordMXok\",\"classSPF\": \"$spfClass\",\"TypeSPF\": \"TXT\", \"valSPF\": \"$recordspf\", \"valSPFok\": \"$recordspfok\",\"classDKIM\": \"$dkimClass\",\"TypeDKIM\": \"TXT\", \"valDKIM\": \"$recorddkim\", \"valDKIMok\": \"$recorddkimok\"}
