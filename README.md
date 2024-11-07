# OpenLDAP

OpenLDAP Software is an Open Source suite of directory software developed by the Internet community, is a implementation of the Lightweight Directory Access Protocol (LDAP).
You can read about that product on page [https://www.openldap.org/](https://www.openldap.org/).

## About project

The image definition basing on the official image of Ubuntu on DockerHub: [Ubuntu](https://hub.docker.com/_/ubuntu).

### Predefined Schemas

During the first start of the service, the database is initialized with the supported schemas. The following objectClass definitions are characteristic of this LDAP database:
- cszuAttrs - additional attributes used in authentication and authorization mechanisms for groups and users; schema based on **"Central System 'Z' of management Users (CSZU)"** standard.
- cszuUser - additional attributes used in authentication and authorization mechanisms for users; schema based on **"Central System 'Z' of management Users (CSZU)"** standard.
- groupCszuAttrs - additional attributes used in authentication and authorization mechanisms for groups; schema based on **"Central System 'Z' of management Users (CSZU)"** standard.
- adperson - additional attributes allowing the simulation of mechanisms characteristic for MS Active Directory (AD)

#### ObjectClass cszuAttrs
Definition of objectClass:
```
dn: cn=cszuAttrs,cn=schema,cn=config
objectClass: olcSchemaConfig
cn: cszuAttrs
olcObjectClasses: ( 1.3.6.1.4.1.2021.3.0.1 NAME 'cszuAttrs' DESC 'Attributes used by CSZU' AUXILIARY MUST ( allowSystem $ entryDistinguishedName ))
olcAttributeTypes: ( 1.3.6.1.4.1.2021.3.0.2 NAME 'allowSystem' EQUALITY caseIgnoreMatch SUBSTR caseIgnoreSubstringsMatch SYNTAX '1.3.6.1.4.1.1466.115.121.1.15')
olcAttributeTypes: ( 1.3.6.1.4.1.2021.3.0.3 NAME 'entryDistinguishedName' SUP distinguishedName)
```

#### ObjectClass cszuUser
Definition of objectClass:
```
dn: cn=cszuUser,cn=schema,cn=config
objectClass: olcSchemaConfig
cn: userCszuAttrs
olcObjectClasses: ( 1.3.6.1.4.1.2021.3.1.1 NAME 'cszuUser' DESC 'Attributes used by CSZU for user entries' SUP cszuAttrs AUXILIARY MUST ( primaryGroup ) MAY ( primaryGroupName $ department $ departmentCode $ isChief $ isActive $ hrNumber ) )
olcAttributeTypes: ( 1.3.6.1.4.1.2021.3.1.4 NAME 'primaryGroup' SUP distinguishedName )
olcAttributeTypes: ( 1.3.6.1.4.1.2021.3.1.5 NAME 'primaryGroupName' EQUALITY caseIgnoreMatch SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
olcAttributeTypes: ( 1.3.6.1.4.1.2021.3.1.3 NAME 'department' EQUALITY caseIgnoreMatch SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
olcAttributeTypes: ( 1.3.6.1.4.1.2021.3.1.6 NAME 'departmentCode' EQUALITY caseIgnoreMatch SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
olcAttributeTypes: ( 1.3.6.1.4.1.2021.3.1.7 NAME 'isChief' EQUALITY caseIgnoreMatch SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
olcAttributeTypes: ( 1.3.6.1.4.1.2021.3.1.9 NAME 'isActive' EQUALITY caseIgnoreMatch SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
olcAttributeTypes: ( 1.3.6.1.4.1.2021.3.1.8 NAME 'hrNumber' DESC 'RFC2307: An integer uniquely identifying ih HR System' EQUALITY integerMatch SYNTAX 1.3.6.1.4.1.1466.115.121.1.27 SINGLE-VALUE )
```

#### ObjectClass groupCszuAttrs
Definition of objectClass:
```
dn: cn=groupCszuAttrs,cn=schema,cn=config
objectClass: olcSchemaConfig
cn: groupCszuAttrs
olcObjectClasses: ( 1.3.6.1.4.1.2021.3.2.1 NAME 'cszuGroup' DESC 'Attributes used by CSZU for group entries' SUP cszuAttrs AUXILIARY MUST ( cn $ managerGroup ) MAY ( mail $ name $ displayName $ description $ manager $ member $ memberOf))
olcAttributeTypes: ( 1.3.6.1.4.1.2021.3.2.4 NAME 'managerGroup' SYNTAX '1.3.6.1.4.1.1466.115.121.1.34' SINGLE-VALUE )
olcAttributeTypes: ( 1.3.6.1.4.1.2021.3.2.3 NAME 'managerGroupName' EQUALITY caseIgnoreMatch SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
```

#### ObjectClass adperson
Definition of objectClass:
```
dn: cn=adperson,cn=schema,cn=config
objectClass: olcSchemaConfig
cn: adperson
olcObjectClasses: ( 1.2.840.113556.1.4.220 NAME 'ADPerson' DESC 'Active Directory Person Entry' SUP inetOrgPerson  STRUCTURAL MUST ( sAMAccountName ) MAY ( userPrincipalName $ msExchUserCulture $ MPK1Code $ MPK1Name $ MPK2Code $ MPK2Name ) )
olcAttributeTypes: ( 1.2.840.113556.1.4.221 NAME 'sAMAccountName' EQUALITY caseIgnoreMatch SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
olcAttributeTypes: ( 1.2.840.113556.1.4.656 NAME 'userPrincipalName' EQUALITY caseIgnoreMatch SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
olcAttributeTypes: ( 1.2.840.113556.1.4.657 NAME 'msExchUserCulture' EQUALITY caseIgnoreMatch SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
olcAttributeTypes: ( 1.2.840.113556.1.4.700 NAME 'MPK1Name' DESC 'Name of the first cost center - MPK (Cost Center)' EQUALITY caseIgnoreMatch  SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
olcAttributeTypes: ( 1.2.840.113556.1.4.701 NAME 'MPK1Code' DESC 'Code of the first cost center - MPK (Cost Center)' EQUALITY caseIgnoreMatch  SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
olcAttributeTypes: ( 1.2.840.113556.1.4.702 NAME 'MPK2Name' DESC 'Name of the second cost center - MPK (Cost Center)' EQUALITY caseIgnoreMatch  SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
olcAttributeTypes: ( 1.2.840.113556.1.4.703 NAME 'MPK2Code' DESC 'Code of the second cost center - MPK (Cost Center)' EQUALITY caseIgnoreMatch  SYNTAX '1.3.6.1.4.1.1466.115.121.1.15' SINGLE-VALUE )
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
  - ``cn=${LDAP_TECHNICAL_USER_CN},ou=Technical,${LDAP_OLC_SUFFIX}``, default value of ``LDAP_TECHNICAL_USER_CN`` is "FrontendAccount", user entry has read privileges to all entries; password for the user is should be define in ``LDAP_TECHNICAL_USER_PASSWD`` environment variable (default value: "secret") and should be changed on production environments.
  - ``uid=mrcmanager,ou=People,${LDAP_OLC_SUFFIX}`` - sample user with administrator privileges to IBPM Mercury (HgDB) system; password for the user is should be define in ``LDAP_TECHNICAL_USER_PASSWD`` environment variable (default value: "secret") and should be changed on production environments.
  - ``uid=mrcuser,ou=People,${LDAP_OLC_SUFFIX}`` - sample user with user privileges to IBPM Mercury (HgDB) system; password for the user is should be define in ``LDAP_TECHNICAL_USER_PASSWD`` environment variable (default value: "secret") and should be changed on production environments.

Sample of predefined tree:

![Sample of predefined tree](
https://raw.githubusercontent.com/slawascichy/docker-openldap/refs/heads/main/doc/sample_predefinied_tree.png)

## Build image
For building the image you should use docker command:

```
docker build --no-cache -f Dockerfile -t ibpm/openldap:ubuntu-0.1 .
```
where ``ubuntu-0.1`` tag name is required by compositions (see composition definitions).

## Run image

Required environment variables:

| Name       | Description |
|------------|-------------|
| LDAP_ORG_DC | acronym of organization name e.g. "scisoftware" |
| LDAP_OLC_SUFFIX | main root DN (Distinguished Name); in DN should be used value of LDAP_ORG_DC variable as DC attribute by pattern: dc=${LDAP_ORG_DC},dc=example,dc=com; e.g. "dc=scisoftware,dc=pl" |
| LDAP_ROOT_CN | admin user CN (Common Name) attribute e.g. "manager" |
| LDAP_ROOT_DN | admin user DN (Distinguished Name) attribute; in DN should be used values of LDAP_ROOT_CN variable as CN attribute and LDAP_OLC_SUFFIX by pattern: cn=${LDAP_ROOT_CN},${LDAP_OLC_SUFFIX}; e.g. "cn=manager,dc=scisoftware,dc=pl"|
| LDAP_ROOT_PASSWD | admin user password |
| LDAP_TECHNICAL_USER_CN | (optional) technical user name, with read privileges to all entries |
| LDAP_TECHNICAL_USER_PASSWD | (optional) technical user password, default value: "secret"|

Required volumes:

| Name       | Description |
|------------|-------------|
| /var/lib/openldap |  database' files directory |
| /etc/openldap/slapd.d |  database' configuration files directory |

For running the container based on the image you can use docker command:

- Linux

```
docker run --name openldap -p 389:389 -p 636:636 \
 --env LDAP_ORG_DC="scisoftware" \
 --env LDAP_OLC_SUFFIX=dc=scisoftware,dc=pl \
 --env LDAP_ROOT_CN=manager \
 --env LDAP_ROOT_DN=cn=manager,dc=scisoftware,dc=pl \
 --env LDAP_ROOT_PASSWD=secret \
 --volume slapd_database:/var/lib/ldap \
 --volume slapd_config:/etc/ldap/slapd.d \
 --detach ibpm/openldap:ubuntu-0.1
```
- Linux - docker compose

```
docker compose --env-file ldap-conf.env up
```


- Windows, PowerShell

```
docker run --name openldap -p 389:389 -p 636:636 `
 --env LDAP_ORG_DC="scisoftware" `
 --env LDAP_OLC_SUFFIX=dc=scisoftware,dc=pl `
 --env LDAP_ROOT_CN=manager `
 --env LDAP_ROOT_DN=cn=manager,dc=scisoftware,dc=pl `
 --env LDAP_ROOT_PASSWD=secret `
 --volume slapd_database:/var/lib/ldap `
 --volume slapd_config:/etc/ldap/slapd.d `
 --detach ibpm/openldap:ubuntu-0.1

```
