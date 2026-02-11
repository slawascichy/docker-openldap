FROM php:8.2-apache

# Instalacja zależności LDAP
RUN apt-get update && apt-get install -y libldap2-dev \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-install ldap

# Tworzymy katalog kontekstu
RUN mkdir -p /var/www/html/ibpmchpase

# Kopiujemy pliki źródłowe do środka
COPY src/ /var/www/html/ibpmchpase/

# Ustawiamy uprawnienia:
# 1. Właściciel: www-data (użytkownik Apache)
# 2. Prawa: 755 (Właściciel: wszystko, Reszta: odczyt i wejście do katalogu)
RUN chown -R www-data:www-data /var/www/html/ibpmchpase \
    && chmod -R 755 /var/www/html/ibpmchpase

# Czyścimy cache apt, żeby obraz był lżejszy
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Otwieramy port 80
EXPOSE 80

# Apache uruchomi się automatycznie w trybie domyślnym