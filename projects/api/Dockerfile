#
# Project
#

ARG BUILDER_IMAGE=wolfulus/directus-builder:latest
ARG API_VERSION=2.0.1

FROM ${BUILDER_IMAGE} AS builder

ENV BUILDER_IMAGE ${BUILDER_IMAGE:-wolfulus/directus-builder:latest}
ENV API_VERSION ${API_VERSION:-2.0.1}

RUN directus fetch api ${API_VERSION}

#
# Project
#

FROM php:7.1-apache

# Change DocumentRoot path
RUN sed -i 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf

# Install needed packages
RUN apt-get update && \
    apt-get install -y git zip libpng-dev libmagickwand-dev --no-install-recommends

RUN pecl install imagick && \
    docker-php-ext-enable imagick && \
    rm -r /var/lib/apt/lists/*

# Configure PHP extentions
RUN docker-php-ext-configure \
    gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/

# Install PHP Extensions
RUN docker-php-ext-install pdo_mysql mbstring gd

# Rewrite
RUN a2enmod rewrite

# Copy project
COPY --chown=www-data:www-data --from=builder /directus/tmp/api/ /var/www/html/

# Copy configuration file
# COPY --chown=www-data:www-data api.php /var/www/html/config/api.php
