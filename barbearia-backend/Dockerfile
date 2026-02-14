FROM php:8.2-apache

# Habilita mod_rewrite e headers
RUN a2enmod rewrite headers

# Define o diretório de trabalho
WORKDIR /var/www/html

# Define DocumentRoot como raiz do projeto
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Instala dependências do Composer
COPY composer.json composer.lock* ./
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php \
    && php composer.phar install --no-dev \
    && rm composer-setup.php composer.phar

# Copia o resto do projeto
COPY . .

# Copia .htaccess se existir
COPY .htaccess ./ 2>/dev/null || true

# Permissões
RUN chown -R www-data:www-data /var/www/html

# Expõe a porta 80
EXPOSE 80

# Inicia o Apache em foreground
CMD ["apache2-foreground"]
