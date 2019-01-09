FROM php:7.1-apache
ADD aws-start.sh /aws-start.sh
RUN apt-get update && apt-get -y install libpcre3-dev python-pip libssl-dev && pip install awscli --upgrade && apt-get clean
RUN pecl install mongodb
RUN pecl install oauth
RUN chmod +x /aws-start.sh
RUN a2enmod rewrite
ADD main  /var/www
RUN echo "extension=oauth.so" > /usr/local/etc/php/conf.d/oauth.ini
RUN echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongodb.ini
RUN rm -rf /var/www/html && mv /var/www/www /var/www/html
RUN echo "<?php echo 'ok'; ?>" > /var/www/html/health.php
ADD .htaccess /var/www/html
CMD []

