<?php
if (file_exists($file = __DIR__ . '/../../../autoload.php') ||
    file_exists($file = __DIR__ . '/../autoload.php') ||
    file_exists($file = __DIR__ . '/../vendor/autoload.php')
) {
    require_once $file;
} elseif (file_exists($file = __DIR__ . '/../autoload.php.dist')) {
    require_once $file;
}
error_reporting(E_ALL);
use Symfony\Component\Console\Application;
use Dongww\Db\Dbal\Command;

$app = new Application('simple-db', 'v0.0.1');
$app->add(new Command\UpdateCommand());

$app->run();