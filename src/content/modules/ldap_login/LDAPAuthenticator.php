<?php

class LDAPAuthenticator {

    private $cfg;
    private $connection;
    private $errors = array();
    private $mainClass;

    public function __construct($cfg, $mainClass) {
        $this->cfg = $cfg;
        $this->mainClass = $mainClass;
    }

    public function connect() {
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
        $use_tls = is_true($this->cfg["use_tls"]);

        if ($use_tls) {
            $port = LDAP_DEFAULT_TLS_PORT;
            $protocol = "ldaps://";
        }

        $port = isset($this->cfg["port"]) ? intval($this->cfg["port"]) : $port;
        $ldap_url = $protocol . $host;
        $this->mainClass->debug("URL is $ldap_url - Port is $port");
        $connection = ldap_connect($ldap_url, $port);

        // If there is a connection
        if ($connection) {
            $this->mainClass->debug("Connection successful");
            // use LDAP v3 - All LDAP servers still in use supports LDAP v3
            // since LDAP v2 is deprecated since 2003
            ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);
            $this->connection = $connection;
            return $connection;
        }
        $this->mainClass->debug("Connection failed");
        return null;
    }

    public function login($username, $password) {
        if (!$this->connection) {
            throw new Exception("Not connected to ldap.");
        }
        $userDn = $this->cfg["user_dn"];
        $userDn = str_replace("%user%", ldap_escape($username, null, LDAP_ESCAPE_DN), $userDn);
        $userDn = str_replace("%domain%", ldap_escape($this->cfg["domain"], null, LDAP_ESCAPE_DN), $userDn);

        $this->mainClass->debug("User DN: $userDn");
        return @ldap_bind($this->connection, $userDn, $password);
    }

    public function getUserData($username, $fields) {
        if (!$this->connection) {
            throw new Exception("not connected to ldap");
        }
        $entries = null;
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
            $this->mainClass->debug("User data found", $entries);
            if ($entries["count"] >= 1) {
                $entries = $entries[0];
            }
        }

        return $entries;
    }

    public function getError() {
        return ldap_error($this->connection);
    }

}
