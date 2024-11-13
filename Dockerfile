FROM ubuntu

ENV LDAP_ORG_DC=sample
ENV LDAP_OLC_SUFFIX=dc=${LDAP_ORG_DC},dc=example,dc=com
ENV LDAP_ROOT_CN=Manager
ENV LDAP_ROOT_DN=cn=${LDAP_ROOT_CN},${LDAP_OLC_SUFFIX}
ENV LDAP_ROOT_PASSWD=secret
ENV LDAP_TECHNICAL_USER_CN=FrontendAccount
ENV LDAP_TECHNICAL_USER_PASSWD=secret
ENV LDAP_OLC_ACCESS="by anonymous auth by * read"
ENV SERVER_DEBUG=32

RUN apt update -q && \
    apt install locales && \
    locale-gen pl_PL.UTF-8 && \
    dpkg-reconfigure locales

RUN apt install -y \
	ca-certificates \
	ldap-utils \
	ldapscripts \
	ldapvi \
	slapd \
	vim-nox \
	nginx \
	net-tools
	
# rm -fr /var/lib/ldap && rm -fr /etc/ldap/slapd.d && rm -fr /opt/modify && rm -fr /opt/service 
RUN apt clean && \
	rm -fr /var/lib/apt/lists/* && \
	rm -fr /var/lib/ldap && \
	rm -fr /etc/ldap/slapd.d

# cp schemas/* /etc/ldap/schema
COPY schemas/* /etc/ldap/schema

# cp init/slapd.ldif /etc/ldap/slapd.ldif.docker-init
COPY init/slapd.ldif /etc/ldap/slapd.ldif.docker-init
RUN mkdir -p /opt/modify && mkdir -p /opt/service
# cp -r modify/* /opt/modify/
COPY modify/* /opt/modify
# cp -r service/* /opt/service/
COPY service/* /opt/service

RUN chmod a-x /etc/ldap/schema/* \
 && chmod a-w /etc/ldap/schema/* \
 && chmod a+r /etc/ldap/schema/* \
 && mkdir -p /var/lib/ldap \
 && mkdir -p /etc/ldap/slapd.d \
 && chown -R openldap:openldap /var/lib/ldap \
 && chown root:openldap /etc/ldap/ldap.conf \
 && chmod 640 /etc/ldap/ldap.conf \
 && rm -rf /tmp/* \
 && rm -rf /var/tmp/* \
 && chmod a+x /opt/service/startServer.sh

VOLUME ["/var/lib/ldap", "/etc/ldap/slapd.d"]
EXPOSE 80 389 636
WORKDIR /opt/service
CMD ["/bin/sh", "-c", "/opt/service/startServer.sh"]

