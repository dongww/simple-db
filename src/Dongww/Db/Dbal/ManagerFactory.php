<?php
/**
 * User: dongww
 * Date: 14-5-27
 * Time: 下午3:57
 */

namespace Dongww\Db\Dbal;

use Doctrine\DBAL\Connection;
use Dongww\Db\Dbal\Core\Manager;

class ManagerFactory
{
    protected $conn;

    public function __construct($conn)
    {
        $this->setConnection($conn);
    }

    public function setConnection(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function createManager($name)
    {
        return new Manager($this, $this->conn, $name, []);
    }
}
