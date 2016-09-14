FROM wordpress:latest
#FROM wordpress:4.5.3
#FROM wordpress:4.6.0
MAINTAINER Kazumichi Yamamoto <yamamoto.febc@gmail.com>
RUN apt-get update && apt-get install -y git vim
RUN a2enmod headers
ADD debug/wp-config.php /var/www/html/wp-config.php
ADD https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar /usr/local/bin/wp
RUN chmod +x /usr/local/bin/wp

# Setup envs
RUN echo 'alias ll="ls -al"' >> /etc/bash.bashrc