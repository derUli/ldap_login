<?php
class LDAPUtil {
	// hash password for LDAP
	public static function hashPassword($password) {
		return '{SHA}' . base64_encode ( pack ( 'H*', sha1 ( $password ) ) );
	}
}