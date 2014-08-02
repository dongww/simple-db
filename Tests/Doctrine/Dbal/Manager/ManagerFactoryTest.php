<?php
/**
 * User: dongww
 * Date: 14-8-2
 * Time: 下午2:52
 */

namespace Doctrine\Dbal\Manager;


use Dongww\Db\Doctrine\Dbal\Core\Structure;
use Dongww\Db\Doctrine\Dbal\Manager\ManagerFactory;

class ManagerFactoryTest extends \PHPUnit_Framework_TestCase
{
    protected static $structure;
    /** @var \Doctrine\DBAL\Connection */
    protected static $conn;
    /** @var ManagerFactory */
    protected static $mf;

    public static function setUpBeforeClass()
    {
        $configDir         = __DIR__ . '/../../../config';
        static::$structure = Structure::createFromYaml($configDir . '/structure.yml');
        static::$conn      = require_once $configDir . '/config.php';
        static::$mf        = new ManagerFactory(static::$conn, static::$structure, true);
    }

    public function testGet()
    {
        $this->assertInstanceOf('\Doctrine\DBAL\Connection', static::$mf->getConnection());
        $this->assertInstanceOf('Dongww\Db\Doctrine\Dbal\Core\Structure', static::$mf->getStructure());
        $this->assertInstanceOf('Dongww\Db\Doctrine\Dbal\Medoo\MedooAdapter', static::$mf->getMedoo());
        $this->assertInstanceOf('Dongww\Db\Doctrine\Dbal\Medoo\Reader', static::$mf->getReader());
        $this->assertInstanceOf('Dongww\Db\Doctrine\Dbal\Manager\Manager', static::$mf->getManager('user'));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage 数据表 NotExistTable 不存在
     */
    public function testGetManagerException()
    {
        static::$mf->getManager('NotExistTable');
    }

    public function testGetSqlStack()
    {
        $sql = 'select * from user';
        static::$conn->exec($sql);
        $sqlStack = static::$mf->getSqlStack();

        $this->assertCount(1, $sqlStack);
        $this->assertEquals($sql, $sqlStack[1]['sql']);
    }
}
