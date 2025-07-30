#!/bin/bash
#set -o pipefail
echo "**************************"
echo "* Adding proxy to external LDAP... "
echo "**************************"

export LDAP_CONF_DIR=/etc/ldap/

validate() {
	if [ -z ${BIND_LDAP_URI} ]; then
      cat <<EOF

Błąd wywołania skryptu: Brak argumentu BIND_LDAP_URI
Użyj opcji '--help' aby wyświetlić pomoc np. ./add-proxy-to-external-ldap.sh --help
--
EOF
	    exit 1
	fi
	if [ -z ${BIND_DN} ]; then
      cat <<EOF

Błąd wywołania skryptu: Brak argumentu BIND_DN
Użyj opcji '--help' aby wyświetlić pomoc np. ./add-proxy-to-external-ldap.sh --help
--
EOF
	    exit 1
	fi
	if [ -z ${BIND_PASSWD_PLAINTEXT} ]; then
      cat <<EOF

Błąd wywołania skryptu: Brak argumentu BIND_PASSWD_PLAINTEXT
Użyj opcji '--help' aby wyświetlić pomoc np. ./add-proxy-to-external-ldap.sh --help
--
EOF
	    exit 1
	fi
	if [ -z ${BIND_BASE_CTX_SEARCH} ]; then
      cat <<EOF

Błąd wywołania skryptu: Brak argumentu BIND_BASE_CTX_SEARCH
Użyj opcji '--help' aby wyświetlić pomoc np. ./add-proxy-to-external-ldap.sh --help
--
EOF
	    exit 1
	fi
	if [ -z ${LDAP_PROXY_OU_NAME} ]; then
      cat <<EOF

Błąd wywołania skryptu: Brak argumentu LDAP_PROXY_OU_NAME
Użyj opcji '--help' aby wyświetlić pomoc np. ./add-proxy-to-external-ldap.sh --help
--
EOF
	    exit 1
	fi
	if [ -z ${LDAP_BASED_OLC_SUFFIX} ]; then
      cat <<EOF

Błąd wywołania skryptu: Brak argumentu LDAP_BASED_OLC_SUFFIX
Użyj opcji '--help' aby wyświetlić pomoc np. ./add-proxy-to-external-ldap.sh --help
--
EOF
	    exit 1
	fi
}

printHelp() 
{
  cat <<EOF
Skrypt wymaga argumentów:
 BIND_LDAP_URI=<wartość>         - URL wskazujący na zewnętrzną instancję LDAP np. <ldap|ldaps>://example.com
 BIND_DN=<wartość>               - identyfikator użytkownika, za pomocą którego realizowana będzie komunikacja
 BIND_PASSWD_PLAINTEXT=<wartość> - hasło użytkownika, za pomocą którego realizowana będzie komunikacja
 BIND_BASE_CTX_SEARCH=<wartość>  - podstawowa gałąź wyszukiwania podłączanej instancji LDAP
 LDAP_PROXY_OU_NAME=<wartość>    - nazwa jednostki organizacyjnej pod jaką ma występować drzewo podłączanego LDAP'a
EOF
  if [ -z ${LDAP_BASED_OLC_SUFFIX} ]; then
    cat <<EOF
 LDAP_BASED_OLC_SUFFIX=<wartość> - docelowy sufiks bazy danych meta, proxy np. dc=example,dc=local
EOF
  fi
  cat <<EOF
Opcjonalnie można użyć parametrów jednej z opcji:
 --help                          - prezentacja danych pomocy uruchamiania skryptu
 --test                          - testowanie poprawności polecenia

--
Przykład uruchomienia skryptu tworzącego bazę proxy:
./add-proxy-to-external-ldap.sh \\
  BIND_LDAP_URI=ldap://example.com \\
  BIND_DN=CN=Administrator,CN=Users,DC=example,DC=com \\
  BIND_PASSWD_PLAINTEXT=secret \\
  BIND_BASE_CTX_SEARCH=CN=Users,DC=example,DC=com \\
EOF
  if [ -z ${LDAP_BASED_OLC_SUFFIX} ]; then
    cat <<EOF
  LDAP_PROXY_OU_NAME=Users \\
  LDAP_BASED_OLC_SUFFIX=dc=example,dc=local
EOF
  else 
    cat <<EOF
  LDAP_PROXY_OU_NAME=Users 
EOF
  fi

  cat <<EOF
--
Przykład testowania prawidłowości uruchomienia skryptu:
./add-proxy-to-external-ldap.sh \\
  BIND_LDAP_URI=ldap://example.com \\
  BIND_DN=CN=Administrator,CN=Users,DC=example,DC=com \\
  BIND_PASSWD_PLAINTEXT=secret \\
  BIND_BASE_CTX_SEARCH=CN=Users,DC=example,DC=com \\
EOF
  if [ -z ${LDAP_BASED_OLC_SUFFIX} ]; then
    cat <<EOF
  LDAP_PROXY_OU_NAME=Users \\
  LDAP_BASED_OLC_SUFFIX=dc=example,dc=local \\
EOF
  else 
    cat <<EOF
  LDAP_PROXY_OU_NAME=Users \\
EOF
  fi
  echo "  --test"
}

setParameter() 
{
  if [ ${1} == "--help" ]; then
    printHelp
    exit 1
  else 
  if [ ${1} == "--test" ]; then
    export TEST=1
  else
    export $1
  fi
  fi
}


if ! [ -z ${1} ]; then
  setParameter $1
else
  printHelp
  exit 1
fi
if ! [ -z ${2} ]; then
  setParameter $2
else
  printHelp
  exit 1
fi
if ! [ -z ${3} ]; then
  setParameter $3
else
  printHelp
  exit 1
fi
if ! [ -z ${4} ]; then
  setParameter $4
else
  printHelp
  exit 1
fi
if ! [ -z ${5} ]; then
  setParameter $5
else
  printHelp
  exit 1
fi

if ! [ -z ${6} ]; then
  setParameter $6
fi

if ! [ -z ${7} ]; then
  setParameter $7
fi

validate

if ! [ -z ${TEST} ]; then
  export CONNECTION_TEST=`ldapsearch -x -LLL \
   -H ${BIND_LDAP_URI} \
   -D "${BIND_DN}" \
   -w "${BIND_PASSWD_PLAINTEXT}" \
   -b "${BIND_BASE_CTX_SEARCH}" \
   -s sub "(objectClass=*)" dn | grep -c "dn:"`
  if ! [ ${CONNECTION_TEST} -ne 0 ]; then 
    # Błąd połaczenia do zewnętrznego LDA
    echo "[ERROR] Błąd definicji połaczenia do zewnętrznego LDAP. Błąd połączenia."
  fi
  cat ../init/04-add-proxy-to-external-ldap.ldif | envsubst
  echo ""
  echo "[SUCESS] Nawiązano połączenie. Sprawdź wizulanie czy wszystko zostało poprawnie ustawione w komendzie LDIF."
else
  LDIF_FILE=${LDAP_CONF_DIR}/create-meta-database-${LDAP_PROXY_OU_NAME}.ldif
  cat ../init/04-add-proxy-to-external-ldap.ldif | envsubst > $LDIF_FILE
  ldapadd -Y EXTERNAL -H ldapi:/// -f $LDIF_FILE
fi

exit 0