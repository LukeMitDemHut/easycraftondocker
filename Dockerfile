#
#   This Dockerfile will install all of the necessary programs to run craft cmd
#              Programs with (opt) are optional, the other ones are not!
#

# using the official php apache image
FROM php:8.2-apache

# set the php max upload size to 32mb and max exec time to 600
RUN echo "upload_max_filesize = 32M" >> /usr/local/etc/php/php.ini && \
    echo "post_max_size = 32M" >> /usr/local/etc/php/php.ini && \
    echo "max_execution_time = 600" >> /usr/local/etc/php/php.ini

#installing dependencies
RUN apt-get update && apt-get install -y \
    vim \
    sudo \
    libcurl4-openssl-dev \
    libonig-dev \
    libicu-dev \
    libmagickwand-dev \
    libzip-dev \
    --no-install-recommends

# enable rewrite
RUN a2enmod rewrite

# installing php extensions
RUN docker-php-ext-configure bcmath && \
    docker-php-ext-install bcmath curl intl mbstring pdo pdo_mysql zip

# installing and enable imgick
RUN pecl install imagick && \
    docker-php-ext-enable imagick

# installing composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

#  enable www-data to restart the server
RUN chown -R www-data:www-data /var/www/ && \
    echo 'www-data ALL=(ALL) NOPASSWD: /etc/init.d/apache2 restart' >> /etc/sudoers

WORKDIR /var/www/html/

# create user craft
RUN useradd -ms /bin/bash craft
USER craft

# Startskript erstellen
RUN echo '#!/bin/sh' > /home/craft/start.sh && \
    echo 'chmod 644 /var/www/html/installed' >> /home/craft/start.sh && \
    echo 'if [ -f "/var/www/html/installed" ]; then' >> /home/craft/start.sh && \
    echo '    if [ -f "/var/www/html/setup" ]; then' >> /home/craft/start.sh && \
    echo '        project=`cat /var/www/html/setup`' >> /home/craft/start.sh && \
    echo '        sudo /usr/bin/sed -i "s|DocumentRoot /var/www/html|DocumentRoot /var/www/html/$project/web|" /etc/apache2/sites-available/000-default.conf' >> /home/craft/start.sh && \
    echo '    fi' >> /home/craft/start.sh && \
    echo 'else' >> /home/craft/start.sh && \
    echo '    sudo service apache2 start' >> /home/craft/start.sh && \
    echo '    cd /var/www/html' >> /home/craft/start.sh && \
    echo '    composer require composer/composer' >> /home/craft/start.sh && \
    echo '    echo "{" > /var/www/html/composer.json' >> /home/craft/start.sh && \
    echo '    echo "  \"require\": {" >> /var/www/html/composer.json' >> /home/craft/start.sh && \
    echo '    echo "        \"composer/composer\": \"^2.6\"" >> /var/www/html/composer.json' >> /home/craft/start.sh && \
    echo '    echo "   }," >> /var/www/html/composer.json' >> /home/craft/start.sh && \
    echo '    echo "   \"require-dev\": {" >> /var/www/html/composer.json' >> /home/craft/start.sh && \
    echo '    echo "        \"composer/composer\": \"dev-master\"" >> /var/www/html/composer.json' >> /home/craft/start.sh && \
    echo '    echo "    }," >> /var/www/html/composer.json' >> /home/craft/start.sh && \
    echo '    echo "        \"config\": {" >> /var/www/html/composer.json' >> /home/craft/start.sh && \
    echo '    echo "              \"process-timeout\": 600" >> /var/www/html/composer.json' >> /home/craft/start.sh && \
    echo '    echo "           }" >> /var/www/html/composer.json' >> /home/craft/start.sh && \
    echo '    echo "}" >> /var/www/html/composer.json' >> /home/craft/start.sh && \
    echo '    sudo chown -R www-data:www-data /var/www/html/vendor' >> /home/craft/start.sh && \
    echo '    touch /var/www/html/installed' >> /home/craft/start.sh && \
    echo 'fi' >> /home/craft/start.sh && \
    echo 'exec apache2-foreground' >> /home/craft/start.sh && \
    chmod +x /home/craft/start.sh

USER root

# giving apache write priveleges
RUN sudo chown -R www-data:www-data /var/www && \
    sudo chmod -R 775 /var/www

# relinking the terminal
RUN ln -sf /bin/bash /bin/sh

# exposing port 80
EXPOSE 80

#
CMD ["/home/craft/start.sh"]