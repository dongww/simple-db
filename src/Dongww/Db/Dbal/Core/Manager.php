<?php
/**
 * User: dongww
 * Date: 14-5-27
 * Time: 下午2:32
 */

namespace Dongww\Db\Dbal\Core;

use Dongww\Db\Dbal\ManagerFactory;

class Manager
{
    protected $tableName = null;

    /** @var  ManagerFactory */
    protected $mf;

    /**
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection()
    {
        return $this->mf->getConnection();
    }

    public function __construct(ManagerFactory $mf, $tableName)
    {
        $this->setTableName($tableName);
        $this->mf = $mf;

        if (!$this->tableName) {
            throw new \Exception('未定义表名');
        }
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

        $qb = $this->getConnection()->createQueryBuilder();
        $qb
            ->select($this->tableName . '.*')
            ->from($this->tableName, $this->tableName)
            ->where($this->tableName . '.id = ?')
            ->setMaxResults(1)
            ->setParameter(0, $id);

        $stmt = $this->getConnection()->executeQuery($qb->getSQL(), $qb->getParameters());

        $bean = new Bean($this);
        $bean->import($stmt->fetch());

        return $bean;
    }

    /**
     * 创建一个没有数据的 Bean
     *
     * @return Bean
     */
    public function createBean()
    {
        return new Bean($this);
    }

    /**
     * 保存一个 Bean
     *
     * @param Bean $bean
     */
    public function store(Bean $bean)
    {

    }

    public function cleanData($type, $oldValue)
    {
        $value = null;
        switch ($type) {
            case Structure::TYPE_DATE:
            case Structure::TYPE_DATETIME:
            case Structure::TYPE_TIME:
                $value = new \DateTime($oldValue);
                break;
            case Structure::TYPE_INTEGER:
                $value = (int)trim($oldValue);
                break;
            case Structure::TYPE_FLOAT:
                $value = (float)trim($oldValue);
                break;
            case Structure::TYPE_BOOLEAN:
                $value = (bool)$oldValue;
                break;
            case Structure::TYPE_STRING:
            case Structure::TYPE_TEXT:
            default:
                $value = (string)$oldValue;
        }

        return $value;
    }
}
