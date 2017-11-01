<?php
class LDAPLogin extends Controller {
	public function sessionDataFilter($sessionData) {
		// empty passwords are not supported
		if (empty ( $_POST ["user"] ) or empty ( $_POST ["password"] )) {
			return $sessionData;
		}
		$sessionData = false;
		$cfg = $this->getConfig ();
		$authenticator = new LDAPAuthenticator ( $this->getConfig () );
		if ($authenticator->connect ()) {
			if ($authenticator->login ( $_POST ["user"], $_POST ["password"] )) {
				$user = getUserByName ( $_POST ["user"] );

				$username = $_POST ["user"];
				$fieldMapping = $this->getFieldMapping ();
				$userData = $authenticator->getUserData ( $username, array_values ( $fieldMapping ) );

				$lastname = $cfg ["default_lastname"] ?? "Doe";
				$firstname = $cfg ["default_firstname"] ?? "John";
				$email = $_POST ["user"] . "@" . $cfg ["domain"];
				$password = $_POST ["password"];
				// map UliCMS user data fields to ldap fields
				if ($userData) {
					$username = $userData [$fieldMapping ["username"]] [0];
					$firstname = $userData [$fieldMapping ["firstname"]] [0];
					$lastname = $userData [$fieldMapping ["lastname"]] [0];
					$email = $userData [$fieldMapping ["email"]] [0];
				}
				// Create user if it doesn't exists
				if (! $user and isset ( $cfg ["create_user"] ) and $cfg ["create_user"]) {
					adduser ( $username, $lastname, $firstname, $email, $password, false );
				} else if ($user and $userData and isset ( $cfg ["sync_data"] ) and $cfg ["sync_data"]) {
					// Sync data from LDAP to UliCMS
					$user = new User ();
					$user->loadByUsername ( $username );
					$user->setFirstname ( $firstname );
					$user->setLastname ( $lastname );
					$user->setEmail ( $email );
					$user->save ();
				}

				$user = getUserByName ( $_POST ["user"] );
				$sessionData = $user;
				// save original ldap to have it for login on password change.
				$_SESSION ["original_ldap_password"] = $_POST ["password"];
				// if sync_passwords is enabled. change UliCMS user password to password from LDAP
				if (isset ( $cfg ["sync_passwords"] ) and $cfg ["sync_passwords"] and $user) {
					$user = new User ();
					$user->loadByUsername ( $_POST ["user"] );
					if ($user->getPassword () != Encryption::hashPassword ( $_POST ["password"] )) {
						$user->setPassword ( $_POST ["password"] );
						$user->save ();
					}
					$user->getPassword ();
				}
			} else {
				$error = $authenticator->getError ();

				// show own error messages for ldap errors
				switch (strtolower ( $error )) {
					case "invalid credentials" :
						$error = get_translation ( "user_or_password_incorrect" );
						break;
					case "can't contact ldap server" :
						$error = get_translation ( "connect_to_ldap_failed" );
						break;
				}
				$_REQUEST ["error"] = $error;
			}
		}
		return $sessionData;
	}
	public function beforeInit() {
		$cfg = $this->getConfig ();
		// if validate_certificate is equal to false set an environment variable
		// disable certificate validation
		if ($cfg and $cfg ["validate_certificate"] === false) {
			putenv ( 'LDAPTLS_REQCERT=never' );
		}
	}
	public function afterEditUser() {
		$cfg = $this->getConfig ();
		if (empty ( $_POST ["admin_password"] ) or ! (isset ( $cfg ["sync_passwords"] ) and $cfg ["sync_passwords"])) {
			return;
		}
		$user = new User ();
		$user->loadByUsername ( $_POST ["admin_username"] );
		// Now a user can only sync his own password
		// TODO: Implement password sync on change other users passwords
		if ($user->getId () != get_user_id ()) {
			return;
		}
		$authenticator = new LDAPAuthenticator ( $this->getConfig () );
		if ($authenticator->connect ()) {
			// FIXME: Handle Errors
			if ($authenticator->login ( $_POST ["admin_username"], $_SESSION ["original_ldap_password"] )) {
				$authenticator->changePassword ( $_POST ["admin_username"], $_POST ["admin_password"] );
				$_SESSION ["original_ldap_password"] = $_POST ["admin_password"];
			}
		}
	}
	private function getFieldMapping() {
		$cfg = $this->getConfig();
		$fieldMapping = ($cfg and isset ( $cfg ["field_mapping"] )) ? $this->cfg ["field_mapping"] : array (
				"username" => "uid",
				"firstname" => "givenname",
				"lastname" => "sn",
				"email" => "mail"
		);
		return $fieldMapping;
	}
	private function getConfig() {
		$cfg = new config ();
		if (! isset ( $cfg->ldap_config )) {
			return null;
		}
		return $cfg->ldap_config;
	}
}
