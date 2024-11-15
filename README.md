# OpenLDAP

OpenLDAP Software is an Open Source suite of directory software developed by the Internet community, is a implementation of the Lightweight Directory Access Protocol (LDAP).
You can read about that product on page [https://www.openldap.org/](https://www.openldap.org/).

## About project

The image definition basing on the official image of Ubuntu on DockerHub: [Ubuntu](https://hub.docker.com/_/ubuntu).

### Predefined Schemas

During the first start of the service, the database is initialized with the supported schemas. The following objectClass definitions are characteristic of this LDAP database:
- cszuAttrs - additional attributes used in authentication and authorization mechanisms for groups and users; schema based on **"Central System 'Z' of management Users (CSZU)"** standard.

> [!IMPORTANT]  
> Object class `cszuAttrs` is deprecated, please use `cszuPrivs`. The name of `cszuPrivs` is more readable.

- cszuPrivs - additional attributes used in authentication and authorization mechanisms for groups and users; schema based on **"Central System 'Z' of management Users (CSZU)"** standard.
- cszuUser - additional attributes used in authentication and authorization mechanisms for users; schema based on **"Central System 'Z' of management Users (CSZU)"** standard.
- cszuGroup - additional attributes used in authentication and authorization mechanisms for groups; schema based on **"Central System 'Z' of management Users (CSZU)"** standard.
- aDPerson - additional attributes allowing the simulation of mechanisms characteristic for MS Active Directory (AD)

#### Schema cszu
The schema with definitions of `cszuAttrs`, `cszuPrivs`, `cszuUser` and `cszuGroup` object classes:

```text
#
# Author's scheme supporting the Central User Management System 
# (Centralny System Zarządzania Użytkownikami - CSZU)
#
# The schema supports data synchronization between OpenLDAP and IBM BPM. 
# Additionally, it contains the attribute 'allowSystem', which is used 
# as an additional filter in integration with sssd (Unix)
#
# Value's format in 'allowSystem' attribute is:
# 	<host_name>;<service_name>;<expiration_date_in_format_YYYYMMDDHH24mm>;<task_ID>
# Where:
#  - host_name: name of host
#  - service_name - name of service
#  - expiration_date_in_format_YYYYMMDDHH24mm - date of expiration of privilege 
#  - task_ID - task identifier in the service system
# Samples:
#   admin.scisoftware.pl;shell;203512300000;POC-01
#   admin.scisoftware.pl;IBMBPM;203512300000;POC-01
# Sample of using the 'allowSystem' attribute as additional user's filter (sssd configuration):
# LDAP_ACCESS_FILTER=(&(objectclass=shadowaccount)(objectclass=posixaccount)(allowSystem=admin.scisoftware.pl;shell;*))
#
#
#	Created by: Sławomir Cichy (slawas@slawas.pl)
#   Copyright 2014-2024 SciSoftwere Sławomir Cichy Inc.
#
dn: cn=cszu,cn=schema,cn=config
objectClass: olcSchemaConfig
cn: cszu
#
olcAttributeTypes: ( 1.3.6.1.4.1.2021.3.1.4 NAME 'primaryGroup' SUP distinguishedName )
olcAttributeTypes: ( 1.3.6.1.4.1.2021.3.1.5 NAME 'primaryGroupName' EQUALITY caseIgnoreMatch SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
olcAttributeTypes: ( 1.3.6.1.4.1.2021.3.1.3 NAME 'department' EQUALITY caseIgnoreMatch SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
olcAttributeTypes: ( 1.3.6.1.4.1.2021.3.1.6 NAME 'departmentCode' EQUALITY caseIgnoreMatch SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
olcAttributeTypes: ( 1.3.6.1.4.1.2021.3.1.7 NAME 'isChief' EQUALITY booleanMatch SYNTAX 1.3.6.1.4.1.1466.115.121.1.7 SINGLE-VALUE )
olcAttributeTypes: ( 1.3.6.1.4.1.2021.3.1.9 NAME 'isActive' EQUALITY booleanMatch SYNTAX 1.3.6.1.4.1.1466.115.121.1.7 SINGLE-VALUE )
olcAttributeTypes: ( 1.3.6.1.4.1.2021.3.1.8 NAME 'hrNumber' DESC 'RFC2307: An integer uniquely identifying ih HR System' EQUALITY integerMatch SYNTAX 1.3.6.1.4.1.1466.115.121.1.27 SINGLE-VALUE )
olcAttributeTypes: ( 1.3.6.1.4.1.2021.3.0.2 NAME 'allowSystem' EQUALITY caseIgnoreMatch SUBSTR caseIgnoreSubstringsMatch SYNTAX '1.3.6.1.4.1.1466.115.121.1.15')
olcAttributeTypes: ( 1.3.6.1.4.1.2021.3.0.3 NAME 'entryDistinguishedName' SUP distinguishedName )
olcAttributeTypes: ( 1.3.6.1.4.1.2021.3.2.4 NAME 'managerGroup' SUP distinguishedName )
olcAttributeTypes: ( 1.3.6.1.4.1.2021.3.2.3 NAME 'managerGroupName' EQUALITY caseIgnoreMatch SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
olcObjectClasses: ( 1.3.6.1.4.1.2021.3.0.1 NAME 'cszuAttrs' DESC 'Attributes used by CSZU' AUXILIARY MUST ( allowSystem $ entryDistinguishedName ))
olcObjectClasses: ( 1.3.6.1.4.1.2021.3.0.2 NAME 'cszuPrivs' DESC 'Granted access to systems' AUXILIARY MUST ( allowSystem $ entryDistinguishedName ))
olcObjectClasses: ( 1.3.6.1.4.1.2021.3.1.1 NAME 'cszuUser' DESC 'Attributes used by CSZU for user entries' SUP cszuAttrs AUXILIARY MUST ( primaryGroup ) MAY ( primaryGroupName $ department $ departmentCode $ isChief $ isActive $ hrNumber $ allowSystem $ entryDistinguishedName) )
olcObjectClasses: ( 1.3.6.1.4.1.2021.3.2.1 NAME 'cszuGroup' DESC 'Attributes used by CSZU for group entries' SUP cszuAttrs AUXILIARY MUST ( cn $ managerGroup ) MAY ( mail $ name $ displayName $ description $ manager $ member $ memberOf))
```

#### ObjectClass adperson
The schema with definitions of `aDPerson` objectClass:

```text
#
# Substitute for MS Active Directory schema
#
# created by: Sławomir Cichy (slawas@slawas.pl)
#
dn: cn=adperson,cn=schema,cn=config
objectClass: olcSchemaConfig
cn: adperson
#
olcAttributeTypes: ( 1.2.840.113556.1.4.221 NAME 'sAMAccountName' EQUALITY caseIgnoreMatch SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
olcAttributeTypes: ( 1.2.840.113556.1.4.656 NAME 'userPrincipalName' EQUALITY caseIgnoreMatch SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
olcAttributeTypes: ( 1.2.840.113556.1.4.657 NAME 'msExchUserCulture' EQUALITY caseIgnoreMatch SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
olcAttributeTypes: ( 1.2.840.113556.1.4.700 NAME 'MPK1Name' DESC 'Name of the first cost center - MPK (Cost Center)' EQUALITY caseIgnoreMatch  SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
olcAttributeTypes: ( 1.2.840.113556.1.4.701 NAME 'MPK1Code' DESC 'Code of the first cost center - MPK (Cost Center)' EQUALITY caseIgnoreMatch  SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
olcAttributeTypes: ( 1.2.840.113556.1.4.702 NAME 'MPK2Name' DESC 'Name of the second cost center - MPK (Cost Center)' EQUALITY caseIgnoreMatch  SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
olcAttributeTypes: ( 1.2.840.113556.1.4.703 NAME 'MPK2Code' DESC 'Code of the second cost center - MPK (Cost Center)' EQUALITY caseIgnoreMatch  SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
olcAttributeTypes: ( 1.3.114.7.4.2.0.33 NAME 'memberOf' SUP distinguishedName )
olcObjectClasses: ( 1.2.840.113556.1.4.220 NAME 'aDPerson' DESC 'MS Active Directory Person Entry' SUP inetOrgPerson  STRUCTURAL MUST ( uid $ sAMAccountName ) MAY ( userPrincipalName $ msExchUserCulture $ MPK1Code $ MPK1Name $ MPK2Code $ MPK2Name $ userPassword ) )
olcObjectClasses: ( 1.2.840.113556.1.4.803 NAME 'groupOfMembers' DESC 'MS Active Directory group entry' SUP top STRUCTURAL MUST ( cn ) MAY ( cn $ mail $ name $ displayName $ description $ manager $ member $ memberOf $ sAMAccountName ) X-ORIGIN 'AD Group' )
olcObjectClasses: ( 1.2.840.113556.1.4.804 NAME 'team' DESC 'MS Active Directory group entry with required common name and display name' SUP top STRUCTURAL MUST ( cn $ displayName ) MAY ( mail $ name $ description $ manager $ member $ memberOf $ sAMAccountName ) X-ORIGIN 'AD Group' )

```

### Predefined entries
During the first start of the service, the database is initialized with predefined entries: 
- Four organization units (OU):
  - ``ou=Groups,${LDAP_OLC_SUFFIX}`` for user groups entries
  - ``ou=People,${LDAP_OLC_SUFFIX}`` for users entries
  - ``ou=Technical,${LDAP_OLC_SUFFIX}`` for technical users entries; user entries from the OU has read privileges to all entries; you can use them in connection definition for external systems
  - ``ou=Admins,${LDAP_OLC_SUFFIX}`` for administrators entries; user entries from the OU has write privileges to all entries 
- Predefined group entries:
  - ``cn=mrc-admin,ou=Groups,${LDAP_OLC_SUFFIX}`` - group with administrator privileges to IBPM Mercury (HgDB) system
  - ``cn=mrc-user,ou=Groups,${LDAP_OLC_SUFFIX}`` - group with user privileges to IBPM Mercury (HgDB) system
- Predefined user entries:
  - ``${LDAP_ROOT_DN}`` - LDAP manager, user entry has all privileges to all entries; password for the user is should be define in ``LDAP_ROOT_PASSWD`` environment variable (default value: "secret") and should be changed on production environments.
  - ``cn=Administrator,ou=Admins,${LDAP_OLC_SUFFIX}`` - administrator, user entry has write privileges to all entries; password for the user is should be define in ``LDAP_TECHNICAL_USER_PASSWD`` environment variable (default value: "secret") and should be changed on production environments
  - ``uid=ldapui,ou=Admins,${LDAP_OLC_SUFFIX}`` - administrator, user entry has write privileges to all entries; password for the user is should be define in ``LDAP_TECHNICAL_USER_PASSWD`` environment variable (default value: "secret") and should be changed on production environments. The entry can be use for integration with LDAP UI. Recomended UI is ldap-ui https://github.com/dnknth/ldap-ui. Sample compose with the UI is in project https://github.com/slawascichy/docker-ssh-sssd.
   - ``cn=${LDAP_TECHNICAL_USER_CN},ou=Technical,${LDAP_OLC_SUFFIX}``, default value of ``LDAP_TECHNICAL_USER_CN`` is "FrontendAccount", user entry has read privileges to all entries; password for the user is should be define in ``LDAP_TECHNICAL_USER_PASSWD`` environment variable (default value: "secret") and should be changed on production environments.
  - ``uid=mrcmanager,ou=People,${LDAP_OLC_SUFFIX}`` - sample user with administrator privileges to IBPM Mercury (HgDB) system; password for the user is should be define in ``LDAP_TECHNICAL_USER_PASSWD`` environment variable (default value: "secret") and should be changed on production environments.
  - ``uid=mrcuser,ou=People,${LDAP_OLC_SUFFIX}`` - sample user with user privileges to IBPM Mercury (HgDB) system; password for the user is should be define in ``LDAP_TECHNICAL_USER_PASSWD`` environment variable (default value: "secret") and should be changed on production environments.

Sample of predefined tree:

![Sample of predefined tree](
https://raw.githubusercontent.com/slawascichy/docker-openldap/refs/heads/main/doc/sample_predefinied_tree.png)

## Build image
For building the image you should use docker command:

```shell
docker build --no-cache -f Dockerfile -t scisoftware/openldap:ubuntu-0.1 .
```
where ``ubuntu-0.1`` tag name is required by compositions (see composition definitions).

### Push image to DockerHub

Publishing images: The documentation recommends basing it on a working container - then you are basically sure that the image works.
 - We make an image tag from a container. Follow example where `b9a8f7e273fa` is the running container id:
 ```shell
docker container ls
docker container commit b9a8f7e273fa openldap:latest
 ```

 - We create a target image tag. Follow example where `ubuntu-0.1` is current version of image:
 ```shell
docker image tag openldap:latest scisoftware/openldap:latest
docker image tag openldap:latest scisoftware/openldap:ubuntu-0.1
 ```
 
 - Send created images into the world. Use the `-a` (or `--all-tags`) option to push all tags of a local image:
 ```shell
docker image push -a scisoftware/openldap 
 ```

Published images you can find on DockerHub: [scisoftware/openldap](https://hub.docker.com/repository/docker/scisoftware/openldap/general).

## Run image

Required environment variables:

| Name       | Description |
|------------|-------------|
| LDAP_ORG_DC | acronym of organization name e.g. "scisoftware" |
| LDAP_OLC_SUFFIX | main root DN (Distinguished Name); in DN should be used value of LDAP_ORG_DC variable as DC attribute by pattern: dc=${LDAP_ORG_DC},dc=example,dc=com; e.g. "dc=scisoftware,dc=pl" |
| LDAP_ROOT_CN | admin user CN (Common Name) attribute e.g. "manager" |
| LDAP_ROOT_DN | admin user DN (Distinguished Name) attribute; in DN should be used values of LDAP_ROOT_CN variable as CN attribute and LDAP_OLC_SUFFIX by pattern: cn=${LDAP_ROOT_CN},${LDAP_OLC_SUFFIX}; e.g. "cn=manager,dc=scisoftware,dc=pl"|
| LDAP_ROOT_PASSWD | admin user password |
| LDAP_TECHNICAL_USER_CN | (optional) technical user name, with read privileges to all entries, default value: "FrontendAccount" |
| LDAP_TECHNICAL_USER_PASSWD | (optional) technical user password, default value: "secret"|
| LDAP_OLC_ACCESS | (optional) Additional access definition for LDAP. Default value is "by anonymous auth by * read" |
| SERVER_DEBUG | OpenLDAP server debug mode, default value: 32 |

Required volumes:

| Name       | Description |
|------------|-------------|
| /var/lib/openldap |  database' files directory |
| /etc/openldap/slapd.d |  database' configuration files directory |

For running the container based on the image you can use docker command:

- Linux

```shell
docker run --name openldap -p 389:389 -p 636:636 \
 --env LDAP_ORG_DC="scisoftware" \
 --env LDAP_OLC_SUFFIX=dc=scisoftware,dc=pl \
 --env LDAP_ROOT_CN=manager \
 --env LDAP_ROOT_DN=cn=manager,dc=scisoftware,dc=pl \
 --env LDAP_ROOT_PASSWD=secret \
 --env LDAP_OLC_ACCESS="by anonymous auth by * read" \
 --env SERVER_DEBUG=32 \
 --volume slapd_database:/var/lib/ldap \
 --volume slapd_config:/etc/ldap/slapd.d \
 --detach scisoftware/openldap:ubuntu-0.1
```
- Linux - docker compose

```shell
docker compose --env-file ldap-conf.env up
```


- Windows, PowerShell

```shell
docker run --name openldap -p 389:389 -p 636:636 `
 --env LDAP_ORG_DC="scisoftware" `
 --env LDAP_OLC_SUFFIX=dc=scisoftware,dc=pl `
 --env LDAP_ROOT_CN=manager `
 --env LDAP_ROOT_DN=cn=manager,dc=scisoftware,dc=pl `
 --env LDAP_ROOT_PASSWD=secret `
 --env LDAP_OLC_ACCESS="by anonymous auth by * read" `
 --env SERVER_DEBUG=32 `
 --volume slapd_database:/var/lib/ldap `
 --volume slapd_config:/etc/ldap/slapd.d `
 --detach scisoftware/openldap:ubuntu-0.1

```
