#!/usr/bin/env bash

#cp -a /var/www/project /var/www/cache
#rm -rf /var/www/project
#ln -s /var/www/cache/project /var/www/project
#chmod  0777 -R /var/www/cache
sed -i  "s/\"baseUrl\":.*/\"baseUrl\": \"${SCR_HOST//\//\\/}\",/g" /var/www/config/src.json
#if [[ -f /var/www/cache/project/sites/cta.int.local.json ]] ; then
    # Fix the configuration to match desired hostname, hopefully temporary. .htaccess forwards cta.int -> www.cta.int
#    if [[ "$HTTP_HOSTNAME" == "prod-ecs-stateful-eu-central-1.cta.int" ]] ; then
#        HTTP_HOSTNAME="www.cta.int"
#    fi
#    mv /var/www/cache/project/sites/cta.int.local.json /var/www/cache/project/sites/${HTTP_HOSTNAME}.json
#    sed -i "s/cta\.int\.local/${HTTP_HOSTNAME}/g" /var/www/cache/project/sites/${HTTP_HOSTNAME}.json
#fi

exec /usr/local/bin/apache2-foreground
