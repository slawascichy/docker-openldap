# Modyfikacje bazy LDAP

## Konfiguracja SSL

- **1. Skopiuj szablon `addcerts.ldif.template` do pliku o nazwie `addcerts.ldif`**.
```bash
cp addcerts.ldif.template addcerts.ldif
```

- **2. Popraw ścieżki kierujące do odpowiednich plików z certyfikatami i kluczami.**

- **3. Dokonaj modyfikacji konfiguracji bazy danych LDAP.**
```bash
ldapmodify -H ldapi:// -Y EXTERNAL -f addcerts.ldif
```

- **4. Popraw odpowiednio plik `/etc/ldap/ldap.conf` w kontenerze (zobacz [Szablony z dodatkową konfiguracją -> Konfiguracja SSL](../config/README.md#konfiguracja-ssl)).** Możesz też wprowadzić odpowiednie zmiany w definicji kompozycji dodając wolumen wskazujący na plik konfiguracji np:
```yaml
    volumes:
      - openldap:/var/lib/ldap:rw,Z
      - slapd-d:/etc/ldap/slapd.d:rw,Z
      - /opt/openldap/ldap.conf:/etc/ldap/ldap.conf:ro,Z
```

## Zmiana metody haszowania hasła użytkownika

### 1. Skrypt LDIF: Zmiana na {SHA}

- **Zaloguj się do kontenera z bazą openLdap `openldap_database`: **
```bash
podman exec -it openldap_database /bin/bash
```

- **Utwórz/sprawdź plik `set-passwordHash-SHA.ldif`, w kontenerze znajduje się on w katalogu `/opt/modify/`:**
```ldif
dn: olcDatabase={-1}frontend,cn=config
changetype: modify
replace: olcPasswordHash
olcPasswordHash: {SHA}

```

### 2. Skrypt LDIF: Zmiana na {SSHA} (Domyślny/Bezpieczniejszy)

- **Zaloguj się do kontenera z bazą openLdap `openldap_database`: **
```bash
podman exec -it openldap_database /bin/bash
```

- **Utwórz/sprawdź plik `set-passwordHash-SSHA.ldif`, w kontenerze znajduje się on w katalogu `/opt/modify/`:**
```ldif
dn: olcDatabase={-1}frontend,cn=config
changetype: modify
replace: olcPasswordHash
olcPasswordHash: {SSHA}

```

### 3. Jak zaaplikować zmiany?

Wykonaj komendę `ldapmodify` w kontenerze. Ponieważ to zmiana w `cn=config`, musisz użyć uprawnień administratora konfiguracji (zazwyczaj przez `EXTERNAL` czyli lokalny socket roota):

- **Zaloguj się do kontenera z bazą openLdap `openldap_database`: **
```bash
podman exec -it openldap_database /bin/bash
```

- **Aby zmienić na SHA:**
```bash
cd /opt/modify/
ldapmodify -Y EXTERNAL -H ldapi:/// -f set-passwordHash-SHA.ldif
```

- **Aby zmienić na SSHA:**
```bash
cd /opt/modify/
ldapmodify -Y EXTERNAL -H ldapi:/// -f set-passwordHash-SSHA.ldif
```

### 4. Weryfikacja: Co jest obecnie ustawione?

Zanim zaczniesz zmieniać, sprawdź aktualny stan:

```bash
ldapsearch -Q -LLL -Y EXTERNAL -H ldapi:/// -b "cn=config" "(olcDatabase={-1}frontend)" olcPasswordHash
```
