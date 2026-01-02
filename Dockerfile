FROM php:8.4-cli-alpine

RUN apk add --no-cache make autoconf build-base linux-headers

RUN pecl channel-update pecl.php.net && \
    pecl install xdebug-3.5.0 && \
    docker-php-ext-enable xdebug

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    php -r "if (hash_file('sha384', 'composer-setup.php') === 'c8b085408188070d5f52bcfe4ecfbee5f727afa458b2573b8eaaf77b3419b0bf2768dc67c86944da1544f06fa544fd47') { echo 'Installer verified'.PHP_EOL; } else { echo 'Installer corrupt'.PHP_EOL; unlink('composer-setup.php'); exit(1); }" \
    php composer-setup.php \
    php -r "unlink('composer-setup.php');" \
    mv composer.phar /usr/local/bin/composer

WORKDIR /opt/vom

ADD ./ .
