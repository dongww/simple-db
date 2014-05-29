<?php
/**
 * User: dongww
 * Date: 14-5-27
 * Time: 下午3:57
 */

namespace Dongww\Db\Dbal;

use Doctrine\DBAL\Connection;
use Dongww\Db\Dbal\Core\Manager;
use Dongww\Db\Dbal\Core\Structure;

class ManagerFactory
{
    protected $conn;
    /** @var  Structure */
    protected $structure;

    /** @var Core\Manager[] */
    protected static $managers = [];

    public function __construct($conn, Structure $structure)
    {
        $this->setConnection($conn);
        $this->setStructure($structure);
    }

    public function setConnection(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @return mixed
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * @param \Dongww\Db\Dbal\Core\Structure $structure
     */
    public function setStructure(Structure $structure)
    {
        $this->structure = $structure;
    }

    public function getManager($name)
    {
        if (!isset(self::$managers[$name])) {
            self::$managers[$name] = new Manager($this, $this->conn, $name);
        }

        return self::$managers[$name];
    }
}
