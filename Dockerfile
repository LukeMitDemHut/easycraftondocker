#
#   This Dockerfile will install all of the necessary programs to run craft cmd
#              Programs with (opt) are optional, the other ones are not!
#

# using the official php apache image
FROM php:8.1-apache

#installing vim*
RUN apt-get update && apt-get install -y vim

# enable rewrite
RUN a2enmod rewrite

#installing sudo 
RUN apt-get update && apt-get install -y sudo

# installing bcmath
RUN docker-php-ext-configure bcmath
RUN docker-php-ext-install bcmath

# installing cURL
RUN apt-get update && apt-get install -y libcurl4-openssl-dev
RUN docker-php-ext-configure curl
RUN docker-php-ext-install curl

# installing Oniguruma
RUN apt-get update && apt-get install -y libonig-dev

# installing intl
RUN apt-get update && apt-get install -y libicu-dev
RUN docker-php-ext-configure intl
RUN docker-php-ext-install intl

# installing mbstring
RUN docker-php-ext-install mbstring

# installing PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# installing ImageMagick
RUN apt-get update && apt-get install -y libmagickwand-dev --no-install-recommends
RUN pecl install imagick
RUN docker-php-ext-enable imagick

# installing libzip
RUN apt-get update && apt-get install -y libzip-dev

# installing zip
RUN docker-php-ext-configure zip
RUN docker-php-ext-install zip
RUN apt-get update && apt-get install -y unzip

# installing composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

##giving apache write priveleges
RUN chown -R www-data:www-data /var/www/
# setting work dir
WORKDIR /var/www/html/

# enable www-data to restart the server
RUN echo 'www-data ALL=(ALL) NOPASSWD: /etc/init.d/apache2 restart' >> /etc/sudoers

# set the php max upload size to 32mb
RUN echo "upload_max_filesize = 32M" >> /usr/local/etc/php/php.ini && \
    echo "post_max_size = 32M" >> /usr/local/etc/php/php.ini

# Create a new user and switch to it
RUN useradd -ms /bin/bash craft
USER craft


# creating a startup shell
# creating a starup shell
RUN echo '#!/bin/sh' > /home/craft/start.sh
RUN echo 'chmod 644 /var/www/html/installed' >> /home/craft/start.sh
RUN echo 'if [ -f "/var/www/html/installed" ]; then' >> /home/craft/start.sh
RUN echo '    if [ -f "/var/www/html/setup" ]; then' >> /home/craft/start.sh
RUN echo '        project=`cat /var/www/html/setup`' >> /home/craft/start.sh
RUN echo '        sudo /usr/bin/sed -i "s|DocumentRoot /var/www/html|DocumentRoot /var/www/html/$project/web|" /etc/apache2/sites-available/000-default.conf' >> /home/craft/start.sh
RUN echo '        rm /var/www/html/setup' >> /home/craft/start.sh
RUN echo '    fi' >> /home/craft/start.sh
RUN echo 'else' >> /home/craft/start.sh
RUN echo '    sudo service apache2 start' >> /home/craft/start.sh
RUN echo '    cd /var/www/html' >> /home/craft/start.sh
RUN echo '    composer require composer/composer' >> /home/craft/start.sh
RUN echo '    echo "{" > /var/www/html/composer.json' >> /home/craft/start.sh
RUN echo '    echo "  \"require\": {" >> /var/www/html/composer.json' >> /home/craft/start.sh
RUN echo '    echo "        \"composer/composer\": \"^2.6\"" >> /var/www/html/composer.json' >> /home/craft/start.sh
RUN echo '    echo "   }," >> /var/www/html/composer.json' >> /home/craft/start.sh
RUN echo '    echo "   \"require-dev\": {" >> /var/www/html/composer.json' >> /home/craft/start.sh
RUN echo '    echo "        \"composer/composer\": \"dev-master\"" >> /var/www/html/composer.json' >> /home/craft/start.sh
RUN echo '    echo "    }," >> /var/www/html/composer.json' >> /home/craft/start.sh
RUN echo '    echo "        \"config\": {" >> /var/www/html/composer.json' >> /home/craft/start.sh
RUN echo '    echo "              \"process-timeout\": 600" >> /var/www/html/composer.json' >> /home/craft/start.sh
RUN echo '    echo "           }" >> /var/www/html/composer.json' >> /home/craft/start.sh
RUN echo '    echo "}" >> /var/www/html/composer.json' >> /home/craft/start.sh
RUN echo '    sudo chown -R www-data:www-data /var/www/html/vendor' >> /home/craft/start.sh
RUN echo '    touch /var/www/html/installed' >> /home/craft/start.sh
RUN echo 'fi' >> /home/craft/start.sh
RUN echo '    exec apache2-foreground' >> /home/craft/start.sh
RUN chmod +x /home/craft/start.sh

USER root

# giving apache write priveleges to html
RUN sudo chown -R www-data:www-data /var/www
RUN sudo chmod -R 775 /var/www

RUN ln -sf /bin/bash /bin/sh


# xposing port 80
EXPOSE 80

CMD ["/home/craft/start.sh"]
