# ldap_login
LDAP Integration Services for UliCMS

## Description
* Login with your LDAP Account
* Synchronize Passwords between UliCMS and LDAP Directory
* Create accounts if not exists and import user data such as Firstname, Lastname, and E-Mail Address
* Easy configuration
* Pseudo Load-Balancing if multiple LDAP hosts are specified
* Tested only with OpenLDAP but should work with every LDAP v3 database.
* Secure TLS connections supported

## Installation and Configuration
### Requirements
* UliCMS 2017.4 or newer
* PHP with ldap extension
* LDAP Directory Server (Tested only with OpenLDAP but also other LDAP servers should work)

### Installation
1. In UliCMS `Packages`> `Install package`
2. Upload ldap_login-{version number}.sin
3. Click install

### Configuration
Copy this code snippet to `cms-config.php` and adjust the configuration. Explanations about the Configuration parameters are located in the next chapter.

```php
var $ldap_config = array (
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
		"validate_certificate" => true  // if this is false LDAPTLS_REQCERT=never will be set.
);

```
#### Configuration parameters

Coming Soon

## Troubleshooting
If you can't login to your website
* Delete "ldap_login" module folder
or 
* Disable ldap_login by this SQL
  ```sql
  update {prefix}modules set enabled = 0 where name = 'ldap_login';
  ```