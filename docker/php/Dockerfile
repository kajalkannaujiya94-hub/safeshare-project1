FROM php:8.2-apache
ENV APACHE_RUN_DIR /var/run/apache2
ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data
ENV APACHE_LOG_DIR=/var/log/apache2

# Install required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Add this line with your other extensions
RUN docker-php-ext-install mysqli && \
    pecl install apcu && \
    docker-php-ext-enable apcu

# Install curl, unzip, git, mysql-client
RUN apt-get update && apt-get install -y curl unzip git default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

COPY docker/php/apache.conf /etc/apache2/sites-available/000-default.conf
# Install Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# Add apcu config
RUN echo "apc.enabled=1" >> /usr/local/etc/php/conf.d/apcu.ini && \
    echo "apc.enable_cli=1" >> /usr/local/etc/php/conf.d/apcu.ini

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY ./backend /var/www/html
COPY ./frontend/. /var/www/html/

# Copy init.sql
COPY ./docker/mysql/init.sql /var/www/html/init.sql

# Copy entrypoint script
COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Install AWS SDK
RUN composer require aws/aws-sdk-php

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]