# Gebruik de officiële PHP 8.2 image met Apache-webserver
FROM php:8.2-apache

# Installeer de benodigde PHP-extensies om met MySQL (PDO) te kunnen praten
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Stel de werkmap in de container in
WORKDIR /var/www/html

# Kopieer alle websitebestanden naar de container en geef Apache (www-data) eigenaarschap
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html

# Stel poort 80 beschikbaar voor HTTP-verkeer
EXPOSE 80
