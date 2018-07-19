<?php

class LDAPUtil
{

    // hash password for Standard LDAP (userPassword)
    public static function hashPassword($password)
    {
        return '{SHA}' . base64_encode(pack('H*', sha1($password)));
    }

    // encode password for Active Directory (unicodePwd)
    public static function encodePasswordForActiveDirectory($password)
    {
        return iconv("UTF-8", "UTF-16LE", '"' . $password . '"');
    }
}