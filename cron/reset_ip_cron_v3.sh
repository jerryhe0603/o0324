#!/bin/bash

#cd /var/www/php5classes/cron # go to target cron path

#basic proxy setting

declare -a normal_proxy
normal_proxy=($(php -f $(pwd)/get_normal_proxy_names.php))

for i in ${normal_proxy[@]}; do
	#echo ${i}
	php -f $(pwd)/reset_ip_cron_v3.php ${i} &
done

exit # if auto termination is needed, quote this 'exit'


sleep 600
#ps aux|grep 'reset_ip_cron_v2.php'|grep -v 'grep'
kill -SIGKILL $(ps aux|grep 'reset_ip_cron_v2.php'|grep -v 'grep'|awk '{print $2}')

exit