#!/bin/bash
set -o pipefail
echo "**************************"
echo "* Starting OpenLDAP Server "
echo "**************************"

export LDAP_ORG_DC="scisoftware"
export LDAP_OLC_SUFFIX=dc=scisoftware,dc=pl
export LDAP_ROOT_CN=manager
export LDAP_ROOT_DN=cn=manager,dc=scisoftware,dc=pl
export LDAP_ROOT_PASSWD=secret
export LDAP_TECHNICAL_USER_CN=FrontendAccount
export LDAP_TECHNICAL_USER_PASSWD=secret

export SLAPD_URLS="ldap:/// ldapi:/// ldaps:///"
export SLAPD_OPTIONS="/etc/ldap/slapd.d"
export LDAP_INIT_FILE=/etc/ldap/slapd.ldif
export LDAP_BASE_FILE=/etc/ldap/base.ldif
export WORKSPACE=/opt/modify
export LDAP_INIT_FLAG_FILE=/var/lib/ldap/ldap.init


initDatabase() {
	LDAP_ROOT_PASSWD_ENCRYPTED=`slappasswd -h {SSHA} -s $LDAP_ROOT_PASSWD`
	echo "Init slapd.ldif..."
	cp -f /etc/ldap/slapd.ldif.docker-init $LDAP_INIT_FILE
	sed -i "s|LDAP_OLC_SUFFIX|${LDAP_OLC_SUFFIX}|g" $LDAP_INIT_FILE
	sed -i "s|LDAP_ROOT_DN|${LDAP_ROOT_DN}|g" $LDAP_INIT_FILE
	sed -i "s|LDAP_ROOT_PASSWD_ENCRYPTED|${LDAP_ROOT_PASSWD_ENCRYPTED}|g" $LDAP_INIT_FILE

	echo "Starting LDAP..."
	slapadd -n 0 -F /etc/ldap/slapd.d -l $LDAP_INIT_FILE
  	chown -R openldap:openldap /etc/ldap/slapd.d
  	/usr/sbin/slapd -u openldap -g openldap -h "$SLAPD_URLS" -F $SLAPD_OPTIONS
  	cd $WORKSPACE
  	
	echo "Applay init scripts START"
	echo "Run base.ldif..."
	LDAP_TECHNICAL_USER_ENCRYPTED=`slappasswd -h {SSHA} -s $LDAP_TECHNICAL_USER_PASSWD`
	LDIF_FILE=$WORKSPACE/base.ldif
	cp -f $WORKSPACE/base.ldif.docker-init $LDIF_FILE
	sed -i "s|LDAP_OLC_SUFFIX|${LDAP_OLC_SUFFIX}|g" $LDIF_FILE
	sed -i "s|LDAP_ORG_DC|${LDAP_ORG_DC}|g" $LDIF_FILE
	sed -i "s|LDAP_ROOT_DN|${LDAP_ROOT_DN}|g" $LDIF_FILE
	sed -i "s|LDAP_TECHNICAL_USER_CN|${LDAP_TECHNICAL_USER_CN}|g" $LDIF_FILE
	sed -i "s|LDAP_TECHNICAL_USER_ENCRYPTED|${LDAP_TECHNICAL_USER_ENCRYPTED}|g" $LDIF_FILE
	ldapadd -Y EXTERNAL -H ldapi:/// -f $LDIF_FILE
	
	echo "Run sample-entries.ldif..."
	LDIF_FILE=$WORKSPACE/sample-entries.ldif
	cp -f $WORKSPACE/sample-entries.ldif.docker-init $LDIF_FILE
	sed -i "s|LDAP_OLC_SUFFIX|${LDAP_OLC_SUFFIX}|g" $LDIF_FILE
	sed -i "s|LDAP_ROOT_PASSWD_ENCRYPTED|${LDAP_ROOT_PASSWD_ENCRYPTED}|g" $LDIF_FILE
	ldapadd -Y EXTERNAL -H ldapi:/// -f $LDIF_FILE
	
	touch $LDAP_INIT_FLAG_FILE
	echo "Applay init scripts END"
}


DIR=/var/lib/ldap
if [ "$(ls -A $LDAP_INIT_FLAG_FILE)" ]; then
	echo "Starting LDAP..."
	/usr/sbin/slapd -u openldap -g openldap -h "$SLAPD_URLS" -F $SLAPD_OPTIONS
else
    echo "Init database..."
    initDatabase
fi

/usr/sbin/nginx -g "daemon off;"