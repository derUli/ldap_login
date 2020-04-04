<?php

class CMSConfig extends BaseConfig {
    /* [...] */

    public $ldap_config = [
        "ldap_host" => [
            "ldap.forumsys.com"
        ],
        "port" => 389,
        "use_tls" => false,
        "domain" => "ldap.forumsys.com.",
        "user_dn" => "uid=%user%,dc=example,dc=com",
        "filter_dn" => "(uid=%user%)",
        "search_dn" => "dc=example,dc=com",
        // all field names must be lower case
        "field_mapping" => [
            "username" => "uid",
            "firstname" => "givenname",
            "lastname" => "sn",
            "email" => "mail"
        ],
        "password_field" => "unicodePwd",
        "create_user" => true, // create a new user if it doesn't exists
        "sync_data" => true, // Update user data from ldap on login
        "sync_passwords" => true, // Synchronize passwords
        "validate_certificate" => true, // if this is false LDAPTLS_REQCERT=never will be set.
        "skip_on_error" => true, // try to login with standard UliCMS login if LDAP Login fails
        "log_enabled" => false // Should ldap_login write a log file?
    ];

}
