<?php
error_reporting(E_ALL);
require_once __DIR__ . '/../vendor/autoload.php';

use Dongww\Db\Dbal\ManagerFactory;
use Dongww\Db\Dbal\Core\Structure;

$structure = Structure::createFromYaml(__DIR__ . '/config/structure.yml');

/** @var \Doctrine\DBAL\Connection $conn */
$conn = require_once __DIR__ . '/config/config.php';
$mf   = new ManagerFactory($conn, $structure);

//$qb = $conn->createQueryBuilder();

//$dt = \DateTime::createFromFormat("Y-m-d H:i:s", '2012-12-12 12:12:12');
//print_r($dt);exit;

//$conn->insert('goods', [
//        'category_id' => 1,
//        'name'        => 'asdf',
//        'created_at'  => \DateTime::createFromFormat("Y-m-d H:i:s", '2012-12-12 12:12:12'),
//        'updated_at'  => new \DateTime(),
//    ], [
//        \Doctrine\DBAL\Types\Type::getType('integer'),
//        \Doctrine\DBAL\Types\Type::getType('string'),
//        \Doctrine\DBAL\Types\Type::getType('datetime'),
//        \Doctrine\DBAL\Types\Type::getType('datetime'),
//    ]
//);

$gm                = $mf->getManager('goods');
//$bean              = $gm->createBean();
//$bean->category_id = 1;
//$bean->name        = 'asdf'; //echo $bean->name;exit;
//print_r($bean);exit;
//$gm->store($bean);

$bean = $gm->get(12);
$bean->name = 'aa';
$gm->store($bean);
//$gm->remove($bean);
