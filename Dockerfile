FROM php:8.2-apache

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Force remove conflicting MPM modules (keep only prefork for mod_php)
RUN rm -f /etc/apache2/mods-enabled/mpm_event*.load /etc/apache2/mods-enabled/mpm_event*.conf \
          /etc/apache2/mods-enabled/mpm_worker*.load /etc/apache2/mods-enabled/mpm_worker*.conf \
    && ls /etc/apache2/mods-enabled/mpm* 2>/dev/null || true

# Enable rewrite module
RUN a2enmod rewrite

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Railway uses dynamic PORT
EXPOSE ${PORT:-80}

# At runtime: set port, fix ServerName warning, then start Apache
CMD rm -f /etc/apache2/mods-enabled/mpm_event* /etc/apache2/mods-enabled/mpm_worker* \
    && sed -i "s/Listen 80/Listen ${PORT:-80}/g" /etc/apache2/ports.conf \
    && sed -i "s/:80/:${PORT:-80}/g" /etc/apache2/sites-available/000-default.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && apache2-foreground
