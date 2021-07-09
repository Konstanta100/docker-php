ARG PHP_VERSION=latest

FROM php:${PHP_VERSION}

ARG TZ='UTC'
RUN ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime && echo ${TZ} > /etc/timezone

RUN apt-get update && apt-get upgrade -y \
    && apt-get install -y \
    libcurl4-gnutls-dev \
    libedit-dev \
    libfreetype6-dev \
    libicu-dev \
    libmcrypt-dev \
    libreadline-dev \
    libzip-dev \
    unzip \ 
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip \
    && docker-php-source delete \
    && apt-get remove -y g++ wget \
    && apt-get autoremove --purge -y && apt-get autoclean -y && apt-get clean -y \
    && rm -rf /var/lib/apt/lists/* \
    && rm -rf /tmp/* /var/tmp/*

RUN docker-php-ext-install mysqli pdo_mysql 

RUN pecl install xdebug-2.9.0 && docker-php-ext-enable xdebug    

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

CMD ["php-fpm"]
