<?php
/**
 * User: dongww
 * Date: 14-5-27
 * Time: 下午2:32
 */

namespace Dongww\Db\Doctrine\Dbal\Manager;

use Doctrine\DBAL\Types\Type;
use Dongww\Db\Doctrine\Dbal\Core\Structure;

class Manager
{
    protected $tableName = null;

    /** @var  ManagerFactory */
    protected $mf;

    protected $one2Many = [];
    protected $many2One = [];
    protected $many2Many = [];

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

    /**
     * @param $name
     * @return Manager
     */
    protected function setTableName($name)
    {
        $this->tableName = $name;

        return $this;
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
     * @param  array $data
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
     * @param  Bean $bean
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

        if (isset($tblStructure['belong_to']) && is_array($tblStructure['belong_to'])) {
            foreach ($tblStructure['belong_to'] as $name) {
                $fid        = self::foreignKey($name);
                $data[$fid] = $bean->get($fid);
                $types[]    = Type::getType('integer');
            }
        }

        if (isset($tblStructure['tree_able']) ? (bool) $tblStructure['tree_able'] : false) {
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
            if (isset($tblStructure['timestamp_able']) ? (bool) $tblStructure['timestamp_able'] : false) {
                $data['updated_at'] = new \DateTime();
                $types[]            = Type::getType('datetime');
            }

            return $this->getConnection()->update(
                $this->getTableName(),
                $data,
                ['id' => $bean->id],
                $types
            );
        } else { //todo behavior 的插入需要重构，不能写死，须以注册的方式，以便可使用自定义behavior。
            if (isset($tblStructure['timestamp_able']) ? (bool) $tblStructure['timestamp_able'] : false) {
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
     * @param  Bean $bean
     * @return int
     */
    public function remove(Bean $bean)
    {
        return $this->getConnection()->delete(
            $this->tableName,
            ['id' => $bean->id]
        );
    }

    /**
     * @param $type
     * @param $oldValue
     * @return bool|\DateTime|float|int|null|string
     */
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
                $value = (int) trim($oldValue);
                break;
            case Structure::TYPE_FLOAT:
                $value = (float) trim($oldValue);
                break;
            case Structure::TYPE_BOOLEAN:
                $value = (bool) $oldValue;
                break;
            case Structure::TYPE_STRING:
            case Structure::TYPE_TEXT:
            default:
                $value = (string) $oldValue;
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
        if ((int) $id < 1) {
            throw new \Exception('ID必须大于0！');
        }

        $qb = $this->getSelectQueryBuilder()
            ->select($this->allFields())
            ->where($this->idField() . ' = ?')
            ->setMaxResults(1)
            ->setParameter(0, $id);

        $data = $this->getConnection()->fetchAssoc($qb->getSQL(), $qb->getParameters());
        $bean = $this->createBean($data);

        return $bean;
    }

    /**
     * @return array
     */
    public function getOne2Many()
    {
        $structure = $this->getStructure()->getStructure();

        if (empty($this->one2Many)) {
            foreach ($structure['tables'] as $tblName => $table) {
                if (in_array($this->getTableName(), $table['belong_to'])) {
                    $this->one2Many[] = $tblName;
                }
            }
        }

        return $this->one2Many;
    }

    /**
     * @return array
     */
    public function getMany2Many()
    {
        $structure = $this->getStructure();

        if (empty($this->many2Many)) {
            foreach ($structure['many_many'] as $mm) {
                if (in_array($this->getTableName(), $mm)) {
                    $this->many2Many[] = ($mm[0] == $this->getTableName()) ? $mm[1] : $mm[0];
                }
            }
        }

        return $this->many2Many;
    }

    /**
     * @return array
     */
    public function getMany2One()
    {
        $structure = $this->getStructure();

        if (empty($this->many2One)) {
            $this->many2One = $structure['tables'][$this->getTableName()]['belong_to'];
        }

        return $this->many2One;
    }

    public function getStructure()
    {
        return $this->mf->getStructure();
    }

    /**
     * @param  null   $join
     * @param  null   $where
     * @return Bean[]
     */
    public function select($join = null, $where = null)
    {
        $query = $this->getReader();
        $data  = $query->select(
            $this->getTableName(),
            $join,
            '*',
            $where
        );

        $beanArr = [];

        foreach ($data as $row) {
            $beanArr[] = $this->createBean($row);
        }

        return $beanArr;
    }

    /**
     * @param  null $join
     * @param  null $where
     * @return bool
     */
    public function has($join = null, $where = null)
    {
        $query = $this->getReader();
        $data  = $query->has($this->getTableName(), $join, $where);

        return $data;
    }

    /**
     * @param  null $join
     * @param  null $where
     * @return int
     */
    public function count($join = null, $where = null)
    {
        $query = $this->getReader();
        $data  = $query->count($this->getTableName(), $join, '*', $where);

        return $data;
    }

    /**
     * @param  int   $page
     * @param  int   $limit
     * @param  array $where
     * @param  array $join
     * @return array
     */
    public function getPaging($page = 1, $limit = 10, $where = null, $join = null)
    {
        $whereArr = [
            'LIMIT' => [($page - 1) * $limit, $limit],
        ];

        $whereArr = array_merge($whereArr, $where);

        $beans = $this->select($join, $whereArr);
        $count = $this->count($join, $where);
        $pages = ceil($count / $limit);

        return [
            'data'     => $beans,
            'count'    => $count,
            'pages'    => $pages,
            'is_first' => $page <= 1,
            'is_last'  => $page >= $pages,
        ];
    }

    /**
     * @param $sql
     * @return Bean[]
     */
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

    protected function getSelectQueryBuilder()
    {
        return $this->getConnection()
            ->createQueryBuilder()
            ->from($this->tableName, $this->aliases());
    }

    protected function getUpdateQueryBuilder()
    {
        return $this->getConnection()
            ->createQueryBuilder()
            ->update($this->tableName, $this->aliases());
    }

    /**
     * @param               $id
     * @param               $name
     * @param  array        $where
     * @return array|Bean[]
     */
    public function getMany($id, $name, $where = null)
    {
        if (in_array($name, $this->getOne2Many())) {
            $where = array_merge([
                    self::foreignKey($this->getTableName()) => $id
                ],
                $where
            );

            return $this->getManagerFactory()
                ->getManager($name)
                ->select(
                    null,
                    $where
                );
        } elseif (in_array($name, $this->getMany2Many())) {
            $mm = [$this->getTableName(), $name];
            $mm = sort($mm);
            $mm = $mm[0] . '_' . $mm[1];

            $manager = $this->getManagerFactory()
                ->getManager($name);

            $foreignKey     = $mm . '.' . self::foreignKey($manager->getTableName());
            $thisForeignKey = $mm . '.' . self::foreignKey($this->getTableName());

            $where = array_merge(
                [
                    $thisForeignKey => $id
                ],
                $where
            );

            return $manager->select(
                [
                    '[<]' . $mm => [
                        $manager->getTableName() . '.id' => $foreignKey
                    ]
                ],
                $where
            );
        } else {
            return [];
        }
    }

    /**
     * 返回 $key => $value 形式的数组
     *
     * @param  string $v
     * @param  string $k
     * @return array
     */
    public function asMap($v, $k = 'id')
    {
        $data   = $this->select();
        $return = [];
        foreach ($data as $d) {
            $return[$d->$k] = $d->$v;
        }

        return $return;
    }

    /**
     * 获取一部分数据
     *
     * @param  string $order
     * @param  int    $limit
     * @param  array  $where
     * @param  array  $join
     * @return Bean[]
     */
    public function getLimit($order, $limit = 10, $where = [], $join = null)
    {
        $whereArray = [
            'ORDER' => $order,
            'LIMIT' => $limit,
        ];

        $where = array_merge($whereArray, $where);

        return $this->select($join, $where);
    }
}
