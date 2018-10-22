<?php
// Disable "[Reset Password]" Button
// Since password sync to LDAP on reset password is not supported
Settings::set("disable_password_reset", "disable_password_reset");