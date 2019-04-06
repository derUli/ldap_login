<?php

class LDAPLogin extends Controller
{

    private $logger;

    private $moduleName = "ldap_login";

    public function beforeInit()
    {
        $cfg = $this->getConfig();
        // FIXME: Use LoggingRegistry for logger handling
        $logPath = Path::resolve("ULICMS_ROOT/content/log/ldap_login");
        if (isset($cfg["log_enabled"]) and $cfg["log_enabled"]) {
            if (! file_exists($logPath)) {
                mkdir($logPath, null, true);
            }
            $this->logger = new Katzgrau\KLogger\Logger($logPath, Psr\Log\LogLevel::DEBUG, array(
                "extension" => "log"
            ));
        }
        
        // if validate_certificate is equal to false set an environment variable
        // disable certificate validation
        if ($cfg and $cfg["validate_certificate"] === false) {
            $this->debug("certificate validation is disabled");
            putenv('LDAPTLS_REQCERT=never');
        }
    }

    // Include javascript to disable password field
    public function adminFooter()
    {
        if (is_true($_SESSION["ldap_login"])) {
            enqueueScriptFile(ModuleHelper::buildRessourcePath($this->moduleName, "js/edit-user.js"));
            combinedScriptHtml();
        }
    }

    public function sessionDataFilter($sessionData)
    {
        // empty passwords are not supported
        if (empty($_POST["user"]) or empty($_POST["password"])) {
            return $sessionData;
        }
        $cfg = $this->getConfig();
        
        // if this option is enabled and the login fails
        // the system tries to login with the regular login procedure
        $skip_on_error = is_true($cfg["skip_on_error"]);
        if (! $skip_on_error) {
            $sessionData = false;
        } else {
            $this->debug("skip_on_error is enabled");
        }
        $authenticator = new LDAPAuthenticator($this->getConfig(), $this);
        if ($authenticator->connect()) {
            if ($authenticator->login($_POST["user"], $_POST["password"])) {
                
                $this->info("Authentication successful: user: {$_POST["user"]} password: " . str_repeat("*", strlen($_POST["password"])));
                $user = getUserByName($_POST["user"]);
                
                $username = $_POST["user"];
                $fieldMapping = $this->getFieldMapping();
                $userData = $authenticator->getUserData($username, array_values($fieldMapping));
                
                $lastname = "Doe";
                $firstname = "John";
                $email = $_POST["user"] . "@" . $cfg["domain"];
                $password = $_POST["password"];
                
                // map UliCMS user data fields to ldap fields
                if ($userData) {
                    $this->debug("LDAP data found for user {$_POST["user"]}");
                    $username = $userData[$fieldMapping["username"]][0];
                    $firstname = $userData[$fieldMapping["firstname"]][0];
                    $lastname = $userData[$fieldMapping["lastname"]][0];
                    $email = $userData[$fieldMapping["email"]][0];
                }
                // Create user if it doesn't exists
                if (! $user and isset($cfg["create_user"]) and $cfg["create_user"]) {
                    $this->debug("Create Account {$_POST["user"]}...");
					
					$user = new User();
					$user->setUsername($username);
					$user->setLastname($lastname);
					$user->setFirstname($firstname);
					$user->setEmail($email);
					$user->setPassword($password);
					$user->setPrimaryGroupId(Settings::get("default_acl_group") ? intval(Settings::get("default_acl_group")) : null );
					$user->save();
				
                    $this->debug("Account {$_POST["user"]} created");
                } else if ($user and $userData and isset($cfg["sync_data"]) and $cfg["sync_data"]) {
                    // Sync data from LDAP to UliCMS
                    $user = new User();
                    $user->loadByUsername($username);
                    $user->setFirstname($firstname);
                    $user->setLastname($lastname);
                    $user->setEmail($email);
                    $user->save();
                    $this->debug("Sync LDAP data to UliCMS User: {$_POST["user"]} ");
                }
                
                $user = getUserByName($_POST["user"]);
                $sessionData = $user;
                // save original ldap to have it for login on password change.
                $_SESSION["ldap_login"] = true;
                $_SESSION["original_ldap_password"] = $_POST["password"];
                // if sync_passwords is enabled. change UliCMS user password to password from LDAP
                if (isset($cfg["sync_passwords"]) and $cfg["sync_passwords"] and $user) {
                    $user = new User();
                    $user->loadByUsername($_POST["user"]);
                    if ($user->getPassword() != Encryption::hashPassword($_POST["password"])) {
                        $user->setPassword($_POST["password"]);
                        $user->save();
                        $this->debug("LDAP Password changed - Sync password of user {$_POST["user"]} ");
                    }
                }
            } else {
                $this->info("LDAP Login failed");
                
                if ($skip_on_error) {
                    $this->debug("Try UliCMS Login");
                    $login = validate_login($_POST["user"], $_POST["password"]);
                    if ($login) {
                        $this->debug("Login Successful - User: {$_POST["user"]}");
                    } else {
                        $this->debug("Login failed - User: {$_POST["user"]}");
                    }
                    return $login;
                }
                $error = $authenticator->getError();
                
                $this->error($error);
                // show own error messages for ldap errors
                switch (strtolower($error)) {
                    case "invalid credentials":
                        $error = get_translation("user_or_password_incorrect");
                        break;
                    case "can't contact ldap server":
                        $error = get_translation("connect_to_ldap_failed");
                        break;
                }
                $_REQUEST["error"] = $error;
            }
        }
        return $sessionData;
    }

    public function debug($message, $context = array())
    {
        if ($this->logger) {
            $this->logger->debug($message, $context);
        }
    }

    public function info($message, $context = array())
    {
        if ($this->logger) {
            $this->logger->info($message, $context);
        }
    }

    public function error($message, $context = array())
    {
        if ($this->logger) {
            $this->logger->error($message, $context);
        }
    }

    private function getFieldMapping()
    {
        $cfg = $this->getConfig();
        $fieldMapping = ($cfg and isset($cfg["field_mapping"])) ? $cfg["field_mapping"] : array(
            "username" => "uid",
            "firstname" => "givenname",
            "lastname" => "sn",
            "email" => "mail"
        );
        return $fieldMapping;
    }

    private function getConfig()
    {
        $cfg = new CMSConfig();
        if (! isset($cfg->ldap_config)) {
            return null;
        }
        return $cfg->ldap_config;
    }
}
