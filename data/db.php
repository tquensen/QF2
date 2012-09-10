<?php
switch (QF_ENV) {
    case 'dev':
        //dev-specific DB settings
        //break;
    default:
    $db['default'] = array(
        'driver' => 'mysql:host=localhost;dbname=qfdb', //A valid PDO dsn. @see http://de3.php.net/manual/de/pdo.construct.php
        'username' => 'root', //The user name for the DSN string. This parameter is optional for some PDO drivers.
        'password' => '', //The password for the DSN string. This parameter is optional for some PDO drivers.
        'options' => array() //A key=>value array of driver-specific connection options. (optional)
    );
}
