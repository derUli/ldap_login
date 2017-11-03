# ldap_login
LDAP Integration für UliCMS

## Features
* LDAP Logins benutzen
* Passwörter zwischen einem LDAP Verzeichnisdienst und UliCMS werden synchronisiert
* Accounts die noch nicht in UliCMS existieren können automatisch beim ersten Login erstellt werden. Daten wie Vor- und Nachname und die E-Mail Adresse werden aus dem LDAP importiert
* Einfache Konfiguration
* Pseudo Load-Balancing wenn mehrere LDAP Hosts hinterlegt sind.
* Verschlüsselte Verbindungen per TLS werden unterstützt

## Installation und Konfiguration
### Requirements
* UliCMS 2017.4 oder neuer
* PHP 7.0 oder neuer mit der ldap-Erweiterung
* LDAP Verzeichnisdienst (getestet mit OpenLDAP, andere LDAP Server sollten ebenfalls funktionieren)
* Grundlegende LDAP Kenntnisse werden benötigt

### Installation
1. In UliCMS klicken Sie bitte auf `Pakete` -> `Paket installieren`
2. Laden Sie die Datei ldap_login-{version number}.sin hoch
3. Klicken Sie auf `Installieren`

**Hinweis:**
Die Installation deaktiviert das "Passwort zurücksetzen" Feature.

### Konfiguration
Kopieren Sie das folgende Codesnippet in die Datei `cms-config.php` und passen Sie die Konfiguration an. Eine Beschreibung der Parameter finden Sie im nächsten Abschnitt.

```php
<?php
var $ldap_config = array(
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
    "search_dn" => "cn=users,dc=firma,dc=de",
    // all field names must be lower case
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
    "validate_certificate" => true, // if this is false LDAPTLS_REQCERT=never will be set.
    "skip_on_error" => true, // try to login with standard UliCMS login if LDAP Login fails
    "log_enabled" => false // Should ldap_login write a log file?
);

```

#### Configuration parameters
`ldap_host` Hostname oder IP-Adresse des LDAP Hosts
String or an array of strings.
Wenn mehrere Hostnamen angegeben sind, führt ldap_login "Pseudo Load-Balancing" durch in dem es einen zufälligen LDAP Hosts aus der Liste auswählt

`port` LDAP Server Port

`use_tls` Eine verschlüsselte Verbindung nutzen

`domain` Name der Login-Domäne

`user_dn` dn für die Anmeldung von Benutzern

Die Platzhalter `%user%` und `%domain%` dürfen genutzt werden.

`filter_dn` dn um die Benutzerdaten eines Benutzers über ein eindeutiges Attribut (Benutzername) abzufragen. Der Platzhalter `%user%` kann genutzt werden.

`search_dn` Ordner der als Basis für `filter_dn` verwendet wird.

`field_mapping` UliCMS Datenbankfelder auf LDAP Felder mappen

`password_field` Name des Feldes, welches das Passwort des Benutzers enthält

`create_user` Soll ldap_login einen Benutzer anlegen, wenn dieser noch nicht existiert?

`sync_data` Soll ldap_login Benutzerdaten mit dem LDAP synchronisieren?

`sync_passwords` Soll ldap_login Passwörter mit dem LDAP synchronisieren?

`validate_certificate` Soll php-ldap das Zertifikat validieren bei Nutzung einer verschlüsselten Verbindung?

Setzen Sie diesen Parameter auf `false` wenn es Probleme beim Aufbau einer verschlüsselten Verbindung gibt.

`skip_on_error` Soll wenn der Login per LDAP fehlschlägt, ein Fallback auf die Standard Login Funktion von UliCMS erfolgen? (Falsches Passwort oder LDAP Server nicht erreichbar)

`log_enabled` Sollte ldap_login eine Logdatei schreiben?

## Limitations
* Beim Ändern des Passworts eines anderen Benutzers in UliCMS erfolgt keine Synchronisation des Passworts.
* Beim Feature "Passwort zurücksetzen" erfolgt keine Synchronisierung des Passworts
* Synchronisation von Benutzerdaten ist nur in eine Richtung implementiert. (LDAP -> UliCMS)
## Troubleshooting
Falls Sie sich nicht mehr einloggen können
* Löschen Sie den "ldap_login" Modul Ordner
oder
* Führen Sie folgendes SQL aus:
  ```sql
  update {prefix}modules set enabled = 0 where name = 'ldap_login';
  ```
