<?php

// TODO: Hier ebenfalls Logausgaben einbauen.
// $this->mainClass->log("Message")
// $this->mainClass->error("Message")
// $this->mainClass->info("Message")
class LDAPAuthenticator
{

    private $cfg;

    private $connection;

    private $errors = array();

    private $mainClass;

    public function __construct($cfg, $mainClass)
    {
        $this->cfg = $cfg;
        $this->mainClass = $mainClass;
    }

    // FIXME: Ports usw. zu Konstanten machen
    public function connect()
    {
        $host = $this->cfg["ldap_host"];
        
        // If multiple ldap hosts are configured
        // do a pseudo load balancing
        // pick a random host
        if (is_array($this->cfg["ldap_host"])) {
            $hosts = $this->cfg["ldap_host"];
            $host = $hosts[array_rand($hosts)];
        }
        $this->mainClass->debug("Connect to LDAP host $host");
        $port = LDAP_DEFAULT_PORT;
        $protocol = "ldap://";
        $use_tls = (isset($this->cfg["use_tls"]) && $this->cfg["use_tls"]);
        
        if ($use_tls) {
            $port = LDAP_DEFAULT_TLS_PORT;
            $protocol = "ldaps://";
        }
        
        $port = isset($this->cfg["port"]) ? intval($this->cfg["port"]) : $port;
        $ldap_url = $protocol . $host;
        $this->mainClass->debug("URL is $ldap_url - Port is $port");
        $connection = ldap_connect($ldap_url, $port);
        
        if ($connection) {
            $this->mainClass->debug("Connection successful");
            ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);
            $this->connection = $connection;
            return $connection;
        }
        $this->mainClass->debug("Connection failed");
        return null;
    }

    public function login($username, $password)
    {
        if (! $this->connection) {
            throw new Exception("Not connected to ldap.");
        }
        $userDn = $this->cfg["user_dn"];
        $userDn = str_replace("%user%", ldap_escape($username, null, LDAP_ESCAPE_DN), $userDn);
        $userDn = str_replace("%domain%", ldap_escape($this->cfg["domain"], null, LDAP_ESCAPE_DN), $userDn);
        
        $this->mainClass->debug("User DN: $userDn");
        return @ldap_bind($this->connection, $userDn, $password);
    }

    public function getUserData($username, $fields)
    {
        if (! $this->connection) {
            throw new Exception("not connected to ldap");
        }
        $result = null;
        $searchDn = $this->cfg["search_dn"];
        $searchDn = str_replace("%domain%", $this->cfg["domain"], $searchDn);
		
        $this->mainClass->debug("Search DN: $searchDn");
        
        $filterDn = $this->cfg["filter_dn"];
        $filterDn = str_replace("%user%", ldap_escape($username, null, LDAP_ESCAPE_FILTER), $filterDn);
        $filterDn = str_replace("%domain%", ldap_escape($this->cfg["domain"], null, LDAP_ESCAPE_FILTER), $filterDn);
		
        $this->mainClass->debug("Filter DN: $filterDn");
		
        $result = ldap_search($this->connection, $searchDn, $filterDn, $fields);
        if ($result) {
            $entries = ldap_get_entries($this->connection, $result);
            if ($entries["count"] >= 1) {
                $entries = $entries[0];
            }
        }
        
        return $entries;
    }

    public function changePassword($username, $password)
    {
        $userDn = $this->cfg["user_dn"];
        $userDn = str_replace("%user%", ldap_escape($username, null, LDAP_ESCAPE_DN), $userDn);
        $userDn = str_replace("%domain%", ldap_escape($this->cfg["domain"], null, LDAP_ESCAPE_DN), $userDn);
        
        $passwordField = isset($this->cfg["password_field"]) ? $this->cfg["password_field"] : "userPassword";
        
        $this->mainClass->debug("Change password for User: $userDn");
        return ldap_mod_replace($this->connection, $userDn, array(
            'userPassword' => LDAPUtil::hashPassword($password)
        ));
    }

    public function getError()
    {
        return ldap_error($this->connection);
    }
}
