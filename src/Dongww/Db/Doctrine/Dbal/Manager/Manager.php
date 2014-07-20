<?php
/**
 * User: dongww
 * Date: 14-5-27
 * Time: 下午2:32
 */

namespace Dongww\Db\Doctrine\Dbal\Manager;

use Doctrine\DBAL\Types\Type;
use Dongww\Db\Doctrine\Dbal\Core\Bean;
use Dongww\Db\Doctrine\Dbal\Core\Structure;

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
     * 获取表名
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    public function getManagerFactory()
    {
        return $this->mf;
    }

    /**
     * 创建一个没有数据的 Bean
     *
     * @param array $data
     * @return Bean
     */
    public function createBean(array $data = null)
    {
        $bean = new Bean($this);
        if ($data) {
            $bean->import($data);
        }

        return $bean;
    }

    /**
     * 保存一个 Bean
     *
     * @param Bean $bean
     * @return int
     */
    public function store(Bean $bean)
    {
        $tblStructure = $this
            ->getManagerFactory()
            ->getStructure()
            ->getTableStructure($this->getTableName());
        $data         = [];
        $types        = [];

        if (is_array($tblStructure['fields'])) {
            foreach ($tblStructure['fields'] as $name => $field) {
                $quotedName        = $this->getConnection()->quoteIdentifier($name);
                $data[$quotedName] = $bean->$name;
                $types[]           = Type::getType($field['type']);
            }
        }

        if (isset($tblStructure['parents']) && is_array($tblStructure['parents'])) {
            foreach ($tblStructure['parents'] as $name) {
                $fid        = self::foreignKey($name);
                $data[$fid] = $bean->get($fid);
                $types[]    = Type::getType('integer');
            }
        }

        if (isset($tblStructure['tree_able']) ? (bool)$tblStructure['tree_able'] : false) {
            $data['title']     = $bean->title;
            $data['sort']      = $bean->sort;
            $data['path']      = $bean->path;
            $data['level']     = $bean->level;
            $data['parent_id'] = $bean->parent_id;

            $types[] = Type::getType('string');
            $types[] = Type::getType('integer');
            $types[] = Type::getType('string');
            $types[] = Type::getType('integer');
            $types[] = Type::getType('integer');
        }

        if ($bean->id) {
            if (isset($tblStructure['timestamp_able']) ? (bool)$tblStructure['timestamp_able'] : false) {
                $data['updated_at'] = new \DateTime();
                $types[]            = Type::getType('datetime');
            }

            return $this->getConnection()->update(
                $this->getTableName(),
                $data,
                ['id' => $bean->id],
                $types
            );
        } else { //todo behavior 的插入需要重构，不能写死，须以注册的方式，以便可使用自定义behavier。
            if (isset($tblStructure['timestamp_able']) ? (bool)$tblStructure['timestamp_able'] : false) {
                $data['created_at'] = new \DateTime();
                $data['updated_at'] = new \DateTime();
                $types[]            = Type::getType('datetime');
                $types[]            = Type::getType('datetime');
            }

            return $bean->id = $this->getConnection()->insert(
                $this->getTableName(),
                $data,
                $types
            );
        }
    }

    /**
     * 从数据库删除指定Bean相对应的数据行
     *
     * @param Bean $bean
     * @return int
     */
    public function remove(Bean $bean)
    {
        return $this->getConnection()->delete(
            $this->tableName,
            ['id' => $bean->id]
        );
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

    protected function allFields($tableName = null)
    {
        if ($tableName == null) {
            $tableName = $this->aliases();
        }

        return $tableName . '.*';
    }

    protected function aliases($tableName = null)
    {
        if ($tableName == null) {
            $tableName = $this->tableName;
        }

        return $tableName;
    }

    protected function idField($tableName = null)
    {
        return $this->aliases($tableName) . '.id';
    }

    public static function foreignKey($foreignTable)
    {
        return $foreignTable . '_id';
    }

    public function getReader()
    {
        return $this->getManagerFactory()->getReader();
    }

    /**
     * 根据一个Id获取一个Bean
     *
     * @param $id
     * @return Bean
     * @throws \Exception
     */
    public function get($id)
    {
        if ((int)$id < 1) {
            throw new \Exception('ID必须大于0！');
        }

        $qb = $this->getConnection()->createQueryBuilder();
        $qb
            ->select($this->allFields())
            ->from($this->tableName, $this->aliases())
            ->where($this->idField() . ' = ?')
            ->setMaxResults(1)
            ->setParameter(0, $id);

        $data = $this->getConnection()->fetchAssoc($qb->getSQL(), $qb->getParameters());
        $bean = $this->createBean($data);

        return $bean;
    }

    public function select($join = null, $where = null)
    {
        $query = $this->getReader();
        $data  = $query->select($this->getTableName(), $join, '*', $where);

        $beanArr = [];

        foreach ($data as $row) {
            $beanArr[] = $this->createBean($row);
        }

        return $this->createBean($data);
    }

    public function has($join = null, $where = null)
    {
        $query = $this->getReader();
        $data  = $query->has($this->getTableName(), $join, $where);

        return $this->createBean($data);
    }

    public function count($join = null, $where = null)
    {
        $query = $this->getReader();
        $data  = $query->count($this->getTableName(), $join, '*', $where);

        return $this->createBean($data);
    }

    public function query($sql)
    {
        $conn = $this->getConnection();
        $stmt = $conn->query($sql);

        $beanArr = [];

        foreach ($stmt->fetchAll() as $row) {
            $beanArr[] = $this->createBean($row);
        }

        return $beanArr;
    }

    public function getSelectQueryBuilder()
    {
        return $this->getConnection()
            ->createQueryBuilder()
            ->from($this->tableName, $this->aliases());
    }

    public function getUpdateQueryBuilder()
    {
        return $this->getConnection()
            ->createQueryBuilder()
            ->update($this->tableName, $this->aliases());
    }
}
