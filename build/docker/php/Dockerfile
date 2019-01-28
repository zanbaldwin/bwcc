FROM php:7.3-fpm-alpine3.8
MAINTAINER Zan Baldwin "hello@zanbaldwin.com"

WORKDIR /srv
EXPOSE 9000

# https://engineeringblog.yelp.com/2016/01/dumb-init-an-init-for-docker.html
RUN apk add --no-cache --virtual .build-deps bash gcc git libc-dev make \
    && git clone git://github.com/Yelp/dumb-init.git /tmp/dumb-init \
    && (cd /tmp/dumb-init; git checkout $(git describe --tags); make) \
    && mv /tmp/dumb-init/dumb-init /sbin/dumb-init \
    && rm -rf /tmp/dumb-init \
    && apk del .build-deps \
    && chmod +x /sbin/dumb-init
ENTRYPOINT [ "/sbin/dumb-init", "--" ]

COPY php.ini /usr/local/etc/php/php.ini

RUN    sed -i -e 's/www-data/root/g' /usr/local/etc/php-fpm.d/www.conf \
    && apk add --no-cache --upgrade --virtual .extension-build-deps \
        gettext-dev \
        gmp-dev \
        icu-dev \
        libxml2-dev \
        libxslt-dev \
        sqlite-dev \
    && apk add --no-cache --upgrade --virtual .extension-runtime-deps \
        gettext \
        gmp \
        icu \
        libxml2 \
        libxslt \
        openssl \
        sqlite-dev \
    && docker-php-ext-install \
        dba \
        gettext \
        pdo_sqlite \
        sockets \
        xsl \
    && apk del .extension-build-deps

# PHP 7.3 is still fairly new, fallback to installing the beta version of XDebug
# if there isn't a stable release that compiles yet.
RUN    apk add --no-cache --virtual .xdebug-build-deps ${PHPIZE_DEPS} \
    && (pecl install "xdebug" || pecl install "xdebug-2.7.0beta1") \
    && echo "zend_extension=xdebug.so" > /usr/local/etc/php/conf.d/pecl-xdebug.ini \
    && apk del .xdebug-build-deps

# Run everything as root because we like to live wild \m/
ENV COMPOSER_ALLOW_SUPERUSER 1
CMD [ "php-fpm", "--allow-to-run-as-root", "--nodaemonize" ]
