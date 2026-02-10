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

