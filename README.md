# ldap_login
LDAP Integration Services for UliCMS

## Features
* Login with your LDAP Account
* Synchronize Passwords between UliCMS and LDAP Directory
* Create accounts if not exists and import user data such as Firstname, Lastname, and E-Mail Address
* Easy configuration
* Pseudo Load-Balancing if multiple LDAP hosts are specified
* Secure TLS connections supported

## Installation and Configuration
### Requirements

* UliCMS 2017.4 or newer
* PHP 7.0 or newer with ldap extension
* LDAP Directory Server (Tested only with OpenLDAP but  other LDAP servers should also work)
* `klogger` UliCMS module
* Basic LDAP knowledge is required


### Installation
1. In UliCMS `Packages` -> `Install package`
2. Upload ldap_login-{version number}.sin
3. Click install

**Note:**
Installation will disable "password reset" feature.

### Configuration
Copy this code snippet to `cms-config.php` and adjust the configuration. Explanations about the Configuration parameters are located in the next chapter.

```php
<?php
public $ldap_config = array(
    "ldap_host" => [
        "domaincontroller1.firma.de",
        "domaincontroller2.firma.de",
        "domaincontroller3.firma.de"
    ],
    "port" => 389,
    "use_tls" => false,
    "domain" => "firma.de",
    "user_dn" => "uid=%user%,dc=%domain%",
    "filter_dn" => "(uid=%user%)",
    "search_dn" => "cn=users,dc=firma,dc=de",
    // all field names must be lower case
    "field_mapping" => [
        "username" => "uid",
        "firstname" => "givenname",
        "lastname" => "sn",
        "email" => "mail"
    ],
    "password_field" => "userPassword",
    "create_user" => true, // create a new user if it doesn't exists
    "sync_data" => true, // Update user data from ldap on login
    "sync_passwords" => true, // Synchronize passwords
    "validate_certificate" => true, // if this is false LDAPTLS_REQCERT=never will be set.
    "skip_on_error" => true, // try to login with standard UliCMS login if LDAP Login fails
    "log_enabled" => false // Should ldap_login write a log file?
);
```

#### Configuration parameters
`ldap_host` LDAP server hostname or ip address
String or an array of strings.
If multiple LDAP hosts are specified ldap_login will perform pseudo load-balancing by selecting a random host from list.

`port` LDAP Server Port

`use_tls` Use a secure connection

`domain` Name of the Domain

`user_dn` dn for user login

Placeholders `%user%` and `%domain%` may be used.

`filter_dn` dn for querying an user by an unique identifier

`search_dn` dn of a folder which is used as base for filter_dn

`field_mapping` Mapping of UliCMS user fields to LDAP fields

`password_field` Name of the field containing the user password

`create_user` Should ldap_login create an user, if it doesn't exist in UliCMS?

`sync_data` Should ldap_login synchronize user data with LDAP?

`sync_passwords` Should ldap_login synchronize user passwords with LDAP?

`validate_certificate` should php-ldap validate a certificate when using a secure connection?

Set this to `false` if you have issues establishing a secure connection.

`skip_on_error` Should ldap_login fallback to UliCMS default login procedure, if LDAP Login fails? (Wrong password or LDAP server unavailable)

`log_enabled` Should ldap_login write a log file?


## Limitations
* Password synchronization when changing another users password is not supported
* Password synchronization on `Reset Password` is not supported
* Data synchronization is only one direction (LDAP -> UliCMS)
## Troubleshooting
If you can't login to your website
* Delete "ldap_login" module folder
or
* Disable ldap_login by this SQL

  ```sql
  update {prefix}modules set enabled = 0 where name = 'ldap_login';
  ```
