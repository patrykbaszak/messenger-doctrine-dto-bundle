FROM debian:latest as certs
RUN apt-get update && apt-get install -y openssl
RUN ["/bin/bash", "-c", "mkdir /certs && cd /certs && openssl req -x509 -out localhost.crt -keyout localhost.key -newkey rsa:2048 -nodes -sha256 -subj '/CN=localhost' -extensions EXT -config <( printf \"[dn]\nCN=localhost\n[req]\ndistinguished_name = dn\n[EXT]\nsubjectAltName=DNS:localhost\nkeyUsage=digitalSignature\nextendedKeyUsage=serverAuth\")"]

FROM php:8.2.4-fpm-alpine

# php installation
ADD docker/app/php/php.ini /usr/local/etc/php/conf.d/custom.ini
ADD docker/app/php/entrypoint /entrypoint
RUN chmod +x /entrypoint

RUN apk update \
    && apk add git \
    && apk add --no-cache pcre-dev ${PHPIZE_DEPS} zip unzip \
    && apk add --no-cache icu-dev libzip-dev linux-headers openssl-dev \
    && apk add --no-cache postgresql-dev rabbitmq-c-dev \
    && docker-php-ext-install bcmath sockets intl opcache zip pdo_pgsql \
    && pecl install xdebug \
    && pecl install redis \
    && pecl install amqp \
    && docker-php-ext-enable xdebug opcache redis pdo_pgsql amqp \
    && php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer \
    && apk del pcre-dev ${PHPIZE_DEPS} \
    && rm -rf /tmp/pear

# nginx installation
RUN apk --update add nginx \
    && rm -rf /var/cache/apk/* \
    && mkdir -p /run/nginx \
    && mkdir -p /etc/nginx

COPY --from=certs /certs /certs

WORKDIR /app

ENTRYPOINT [ "/entrypoint" ]

EXPOSE 80

CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
