<?php
/**
 * User: dongww
 * Date: 14-8-2
 * Time: 下午4:10
 */

namespace Doctrine\Dbal\Manager;


use Dongww\Db\Doctrine\Dbal\Core\Structure;
use Dongww\Db\Doctrine\Dbal\Manager\Manager;
use Dongww\Db\Doctrine\Dbal\Manager\ManagerFactory;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    protected static $structure;
    /** @var \Doctrine\DBAL\Connection */
    protected static $conn;
    /** @var ManagerFactory */
    protected static $mf;
    /** @var  Manager */
    protected static $userManager;

    public static function setUpBeforeClass()
    {
        $configDir           = __DIR__ . '/../../../config';
        static::$structure   = Structure::createFromYaml($configDir . '/structure.yml');
        static::$conn        = require_once $configDir . '/config.php';
        static::$mf          = new ManagerFactory(static::$conn, static::$structure, true);
        static::$userManager = static::$mf->getManager('user');
    }

    public function testGetConnection()
    {
        $this->assertEquals(
            static::$mf->getConnection(),
            static::$userManager->getConnection()
        );
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage 未定义表名
     */
    public function testNoTableName()
    {
        $m = new Manager(static::$mf, '');
    }

    public function testGetTableName()
    {
        $this->assertEquals('user', static::$userManager->getTableName());
    }

    public function testGetManagerFactory()
    {
        $this->assertEquals(static::$mf, static::$userManager->getManagerFactory());
    }

    public function testCreateBean()
    {
        $this->assertInstanceOf('\Dongww\Db\Doctrine\Dbal\Manager\Bean', static::$userManager->createBean());

        $bean = static::$userManager->createBean([
            'username' => 'silex',
            'password' => 'pass',
        ]);

        $this->assertEquals('silex', $bean->username);
        $this->assertEquals('pass', $bean->password);
    }

    //todo 还没完
}
