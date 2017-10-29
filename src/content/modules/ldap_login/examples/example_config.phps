<?php
$ldap_config = array (
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