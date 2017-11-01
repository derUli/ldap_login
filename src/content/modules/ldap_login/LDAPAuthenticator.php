<?php
class LDAPAuthenticator {
	private $cfg;
	private $connection;
	private $errors = array ();
	public function __construct($cfg) {
		$this->cfg = $cfg;
	}
	// FIXME: Ports usw. zu Konstanten machen
	public function connect() {
		$host = $this->cfg ["ldap_host"];

		// If multiple ldap hosts are configured
		// do a pseudo load balancing
		// pick a random host
		if (is_array ( $this->cfg ["ldap_host"] )) {
			$hosts = $this->cfg ["ldap_host"];
			$host = $hosts [array_rand ( $hosts )];
		}
		$port = LDAP_DEFAULT_PORT;
		$protocol = "ldap://";
		$use_tls = (isset ( $this->cfg ["use_tls"] ) && $this->cfg ["use_tls"]);

		if ($use_tls) {
			$port = LDAP_DEFAULT_TLS_PORT;
			$protocol = "ldaps://";
		}

		$port = isset ( $this->cfg ["port"] ) ? intval ( $this->cfg ["port"] ) : $port;
		$ldap_url = $protocol . $host;
		$connection = ldap_connect ( $ldap_url, $port );

		if ($connection) {
			ldap_set_option ( $connection, LDAP_OPT_PROTOCOL_VERSION, 3 );
			ldap_set_option ( $connection, LDAP_OPT_REFERRALS, 0 );
			$this->connection = $connection;
			return $connection;
		}
		return null;
	}
	public function login($username, $password) {
		if (! $this->connection) {
			throw new Exception ( "Not connected to ldap." );
		}
		$userDn = $this->cfg ["user_dn"];
		$userDn = str_replace ( "%user%", ldap_escape ( $username, null, LDAP_ESCAPE_DN ), $userDn );
		$userDn = str_replace ( "%domain%", ldap_escape ( $this->cfg ["domain"], null, LDAP_ESCAPE_DN ));
		return @ldap_bind ( $this->connection, $userDn, $password );
	}
	public function getUserData($username, $fields) {
		if (! $this->connection) {
			throw new Exception ( "not connected to ldap" );
		}
		$result = null;
		$searchDn = $this->cfg ["search_dn"];
		$searchDn = str_replace ( "%domain%", $this->cfg ["domain"], $searchDn );

		$filterDn = $this->cfg ["filter_dn"];
		$filterDn = str_replace ( "%user%", ldap_escape ( $username, null, LDAP_ESCAPE_FILTER ), $filterDn );
		$filterDn = str_replace ( "%domain%", ldap_escape ( $this->cfg ["domain"], null, LDAP_ESCAPE_FILTER ), $filterDn );
		$result = ldap_search ( $this->connection, $searchDn, $filterDn, $fields );
		if ($result) {
			$entries = ldap_get_entries ( $this->connection, $result );
			if ($entries ["count"] >= 1) {
				$entries = $entries [0];
			}
		}
		return $entries;
	}
	public function changePassword($username, $password) {
		$userDn = $this->cfg ["user_dn"];
		$userDn = str_replace ( "%user%", ldap_escape ( $username, null, LDAP_ESCAPE_DN ), $userDn );
		$userDn = str_replace ( "%domain%", ldap_escape ( $this->cfg ["domain"], null, LDAP_ESCAPE_DN ), $userDn );

		$passwordField = isset ( $this->cfg ["password_field"] ) ? $this->cfg ["password_field"] : "userPassword";
		return ldap_mod_replace ( $this->connection, $userDn, array (
				'userPassword' => LDAPUtil::hashPassword ( $password )
		) );
	}
	public function getError() {
		return ldap_error ( $this->connection );
	}
}
