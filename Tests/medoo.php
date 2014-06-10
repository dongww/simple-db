<?php
error_reporting(E_ALL);
require_once __DIR__ . '/../vendor/autoload.php';

$db = new \medoo([
    'database_type' => 'mysql',
    'database_name' => 'dbal_wrap',
    'server'        => 'localhost',
    'username'      => 'root',
    'password'      => '',

    'charset'       => 'utf8',
]);

$data = $db->get('goods', '*', [
    'id' => 12,
]);

print_r($data);