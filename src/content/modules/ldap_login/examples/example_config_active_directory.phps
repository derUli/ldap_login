<?php
$ldap_config = array(
    "ldap_host" => "domaincontroller.firma.de",
    "port" => 389,
    "use_tls" => false,
    "domain" => "firma.lc",
    "user_dn" => "firma\\user%",
    "filter_dn" => "(samaccountname=%user%)",
    "search_dn" => "cn=users,dc=firma,dc=lc",
    // all field names must be lower case
    "field_mapping" => [
        "username" => "samaccountname",
        "firstname" => "givenname",
        "lastname" => "sn",
        "email" => "mail"
    ],
    "password_field" => "userPassword",
    "create_user" => true, // create a new user if it doesn't exists
    "sync_data" => true, // Update user data from ldap on login
    "sync_passwords" => true, // Synchronize passwords
    "validate_certificate" => false, // if this is false LDAPTLS_REQCERT=never will be set.
    "skip_on_error" => false, // try to login with standard UliCMS login if LDAP Login fails
    "log_enabled" => false // Should ldap_login write a log file?
);
