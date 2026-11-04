FROM php:8.3-apache

# PostgreSQL driver for PHP
RUN apt-get update \
 && apt-get install -y libpq-dev \
 && docker-php-ext-install pdo_pgsql \
 && rm -rf /var/lib/apt/lists/*

# Let .htaccess files work, so private folders stay blocked
RUN a2enmod rewrite \
 && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copy the project into the web folder
COPY . /var/www/html/
RUN mkdir -p /var/www/html/uploads/resumes \
 && chown -R www-data:www-data /var/www/html/uploads

EXPOSE 80