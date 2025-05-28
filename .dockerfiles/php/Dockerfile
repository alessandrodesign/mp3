FROM php:8.3-apache

# Habilita mod_rewrite
RUN a2enmod rewrite

# Define o DocumentRoot como public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Aplica mudança de DocumentRoot
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

# Copia app
COPY . /var/www/html
