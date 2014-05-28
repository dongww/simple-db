<?php
/**
 * User: dongww
 * Date: 14-5-27
 * Time: 下午2:32
 */

namespace Dongww\Db\Dbal\Core;

use Doctrine\DBAL\Connection;
use Dongww\Db\Dbal\ManagerFactory;

class Manager
{
    protected $tableName = null;

    protected $structure;
    /** @var \Doctrine\DBAL\Connection */
    protected $conn;
    /** @var  ManagerFactory */
    protected $mf;

    /**
     * @return \Doctrine\DBAL\Connection
     */
    public function getConn()
    {
        return $this->conn;
    }

    public function __construct(ManagerFactory $mf, Connection $conn, $tableName, array $structure)
    {
        $this->setStructure($structure);
        $this->setTableName($tableName);
        $this->conn = $conn;
        $this->mf   = $mf;

        if (!$this->tableName) {
            throw new \Exception('未定义表名');
        }
    }

    protected function setStructure(array $structure)
    {
        $this->structure = $structure;
    }

    protected function setTableName($name)
    {
        $this->tableName = $name;
    }

    /**
     * @return null
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    public function getManagerFactory()
    {
        return $this->mf;
    }

    public function get($id)
    {
        if ((int)$id < 1) {
            throw new \Exception('ID必须大于0！');
        }

        $qb = $this->conn->createQueryBuilder();
        $qb
            ->select($this->tableName . '.*')
            ->from($this->tableName, $this->tableName)
            ->where($this->tableName . '.id = ?')
            ->setMaxResults(1)
            ->setParameter(0, $id);

        $stmt = $this->conn->executeQuery($qb->getSQL(), $qb->getParameters());

        $bean = new Bean();
        $bean->setManager($this);
        $bean->import($stmt->fetch());

        return $bean;
    }
}
