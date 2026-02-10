# changepassword

Aby to działało sprawnie, potrzebujemy obrazu opartego na PHP z zainstalowanym i włączonym rozszerzeniem **LDAP**. Standardowe obrazy PHP nie mają go aktywnego "z pudełka".

Oto optymalny `Dockerfile` (oparty na lekkim Debianie), który przygotuje środowisko pod Twoją stronę:

## 1. Plik `Dockerfile`

```dockerfile
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

```

## 2. Struktura plików

```text
    src/
    ├── img/
    |	└──ibpm_white.png
    ├── index.php (ten z przekierowaniem)
    ├── changepassword_pl.php
    ├── changepassword_en.php
    └── changepassword_ko.php
    Dockerfile
```

## 3. Budowanie i uruchomienie

W terminalu, w katalogu z `Dockerfile`, wykonaj:

- **Budowanie obrazu:**
```bash
docker build -f Dockerfile -t scisoftware/ibpm-password-change:php-8.2-apache .
```
```bash
podman build -f Dockerfile -t scisoftware/ibpm-password-change:php-8.2-apache .
```

- **Uruchomienie kontenera:**
```bash
# Mapujemy port 8080 na 80 kontenera
docker run -d --name ibpm-web -p 8080:80 scisoftware/ibpm-password-change:php-8.2-apache
```

## 4. Kilka "podpowiedzi" dla Twojej konfiguracji:

1. **Łączność z OpenLDAP:** W skrypcie PHP masz `$server = "openldap";`. Jeśli uruchamiasz ten kontener w tej samej sieci Dockerowej (np. przez `docker-compose`), nazwa usługi musi się zgadzać. Jeśli serwer LDAP jest poza Dockerem, podaj tam jego IP.
2. **Zabezpieczenie (SSL):** Ponieważ przesyłasz hasła w "Plain Text" (aby PPolicy mogło je sprawdzić), **bezwzględnie** powinieneś wystawić tę stronę przez HTTPS (np. używając Nginx Proxy Manager lub Traefika przed tym kontenerem).
3. **Wersja PHP:** Użyłem PHP 8.2. Zgodnie z Twoją prośbą o Javie, trzymamy się nowoczesnych wersji (wszystko powyżej 7.4 jest tu bezpieczne i wydajne).
