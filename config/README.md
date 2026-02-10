# Szablony z dodatkową konfiguracją

## Konfiguracja SSL

- **1. Skopiuj szablon `ldap.conf.template` do pliku o nazwie `ldap.conf`**.
```bash
cp ldap.conf.template ldap.conf.template
```

- **2. Popraw odpowiednio plik `/etc/ldap/ldap.conf`zmieniając ściezki kierujące do plików z certyfikatami.** 
```conf
TLS_CACERT      /var/lib/ldap/certs/scisoftware_intermediate_ca_chain.crt
```

- **3. Możesz też wprowadzić odpowiednie zmiany w definicji kompozycji dodając wolumen wskazujący na plik konfiguracji np:**
```yaml
    volumes:
      - openldap:/var/lib/ldap:rw,Z
      - slapd-d:/etc/ldap/slapd.d:rw,Z
      - /opt/openldap/ldap.conf:/etc/ldap/ldap.conf:ro,Z
```
