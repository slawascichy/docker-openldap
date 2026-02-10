# Używamy oficjalnego obrazu PHP z Apache
FROM php:8.2-apache

# Instalacja zależności systemowych dla rozszerzenia LDAP
RUN apt-get update && apt-get install -y \
    libldap2-dev \
    && rm -rf /var/lib/apt/lists/*

# Konfiguracja i instalacja rozszerzenia PHP LDAP
RUN docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-install ldap

# Kopiujemy Twoje pliki strony do kontenera
# Zakładam, że Twoja strona (index.php) i folder img/ są w tym samym katalogu co Dockerfile
COPY ./src /var/www/html/

# Ustawiamy uprawnienia dla serwera Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Otwieramy port 80
EXPOSE 80

# Apache uruchomi się automatycznie w trybie domyślnym