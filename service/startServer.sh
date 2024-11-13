#!/bin/bash
set -o pipefail
echo "**************************"
echo "* Starting OpenLDAP Server "
echo "**************************"

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
	sed -i "s|LDAP_OLC_ACCESS|${LDAP_OLC_ACCESS}|g" $LDAP_INIT_FILE

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
	sed -i "s|LDAP_ROOT_CN|${LDAP_ROOT_CN}|g" $LDIF_FILE
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
	SLAPD_PID=`cat /var/run/slapd/slapd.pid`
	kill $SLAPD_PID
	echo "Applay init scripts END"
}


DIR=/var/lib/ldap
if [ "$(ls -A $LDAP_INIT_FLAG_FILE)" ]; then
	echo "Starting LDAP..."
else
    echo "Init database..."
    initDatabase
fi

# Debugging Levels
# +=======+=================+===========================================================+
# | Level |	Keyword			| Description												|
# +=======+=================+===========================================================+
# | -1	  |	any				| enable all debugging										|
# | 0	  |	 	 			| no debugging												|
# | 1	  |	(0x1 trace)		| trace function calls										|
# | 2	  |	(0x2 packets)	| debug packet handling										|
# | 4	  |	(0x4 args)		| heavy trace debugging										|
# | 8	  |	(0x8 conns)		| connection management										|
# | 16	  |	(0x10 BER)		| print out packets sent and received						|
# | 32	  |	(0x20 filter)	| search filter processing									|
# | 64	  |	(0x40 config)	| configuration processing									|
# | 128	  |	(0x80 ACL)		| access control list processing							|
# | 256	  |	(0x100 stats)	| stats log connections/operations/results					|
# | 512	  |	(0x200 stats2)	| stats log entries sent									|
# | 1024  |	(0x400 shell)	| print communication with shell backends					|
# | 2048  |	(0x800 parse)	| print entry parsing debugging								|
# | 16384 | (0x4000 sync)	| syncrepl consumer processing								|
# | 32768 | (0x8000 none)	| only messages that get logged whatever log level is set	|
# +=======+=================+===========================================================+

ulimit -n 8192
/usr/sbin/slapd -u openldap -g openldap -d $SERVER_DEBUG -h "$SLAPD_URLS" -F $SLAPD_OPTIONS > /var/log/slapd.log &
tail -f /var/log/slapd.log
