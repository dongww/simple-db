<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$config = new \Doctrine\DBAL\Configuration();
//..
$connectionParams = array(
    'dbname'   => 'dbal_wrap',
    'user'     => 'root',
    'password' => '',
    'host'     => 'localhost',
    'driver'   => 'pdo_mysql',
);
return \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
