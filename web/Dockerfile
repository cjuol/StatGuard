FROM php:8.3-apache AS base

ENV APACHE_DOCUMENT_ROOT=/var/www/html/statguard

# Base dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip zip curl wget gnupg libzip-dev libicu-dev libxml2-dev \
    && docker-php-ext-install bcmath intl zip opcache dom

# Install PHPUnit (compatible with PHP 8.3)
RUN wget -O /usr/local/bin/phpunit https://phar.phpunit.de/phpunit-10.phar && \
    chmod +x /usr/local/bin/phpunit

ENV PATH="/usr/local/bin:$PATH"

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Enable Apache rewrite
RUN a2enmod rewrite

# Allow Apache user overrides
RUN echo 'export APACHE_RUN_USER=${APACHE_RUN_USER:-www-data}' >> /etc/apache2/envvars && \
    echo 'export APACHE_RUN_GROUP=${APACHE_RUN_GROUP:-www-data}' >> /etc/apache2/envvars

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

WORKDIR /var/www/html/statguard
ENTRYPOINT ["/entrypoint.sh"]

FROM base AS validator

# R runtime and scientific libraries for validation
RUN apt-get update && apt-get install -y \
    r-base libcurl4-openssl-dev libssl-dev

RUN R -e "install.packages(c('MASS', 'DescTools', 'jsonlite'), repos='https://cloud.r-project.org/')"
