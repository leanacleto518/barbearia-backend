FROM php:8.2-apache

# Habilita mod_rewrite (útil pra APIs)
RUN a2enmod rewrite

# Define o diretório de trabalho
WORKDIR /var/www/html

# Instala dependências do Composer
COPY composer.json composer.lock* ./
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php \
    && php composer.phar install --no-dev \
    && rm composer-setup.php composer.phar

# Copia o resto do projeto
COPY . .

# Permissões
RUN chown -R www-data:www-data /var/www/html

# Expõe a porta 80
EXPOSE 80

# Inicia o Apache
CMD ["apache2-foreground"]
