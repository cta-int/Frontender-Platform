FROM php:7.1-apache
ADD aws-start.sh /aws-start.sh
RUN apt-get update && apt-get -y install locales libpcre3-dev python-pip libssl-dev && pip install awscli --upgrade && apt-get clean
RUN pecl install mongodb
RUN pecl install oauth
RUN chmod +x /aws-start.sh
RUN a2enmod rewrite
ADD main  /var/www
RUN echo "extension=oauth.so" > /usr/local/etc/php/conf.d/oauth.ini
RUN echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongodb.ini

# Install required locales
RUN echo "en_GB.UTF-8 UTF-8" >> /etc/locale.gen
RUN echo "fr_FR.UTF-8 UTF-8" >> /etc/locale.gen
RUN echo "es_ES.UTF-8 UTF-8" >> /etc/locale.gen
RUN echo "pt_PT.UTF-8 UTF-8" >> /etc/locale.gen
RUN locale-gen

RUN rm -rf /var/www/html && mv /var/www/www /var/www/html
RUN echo "<?php echo 'ok'; ?>" > /var/www/html/health.php
ADD .htaccess /var/www/html
CMD []

