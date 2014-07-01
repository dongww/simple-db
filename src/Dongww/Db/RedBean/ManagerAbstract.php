<?php
/**
 * User: dongww
 * Date: 14-4-21
 * Time: 下午12:44
 */

namespace Dongww\Db\RedBean;

abstract class ManagerAbstract
{
    protected static $fields = [];
    protected static $one2one = [];
    protected static $one2many = [];
    protected static $many2one = [];

    protected static $tableName = null;

    public function __construct()
    {
        if (!static::$tableName) {
            throw new \Exception('未定义表名');
        }
    }

    public function add(array $data)
    {
        $bean = \R::dispense(static::$tableName);

        return $this->save($data, $bean);
    }

    /**
     * @param array $data
     * @param $id
     * @param array $exclude 排除的属性
     * @throws \Exception
     * @internal param \Symfony\Component\HttpFoundation\Request $request
     * @return int|string
     */
    public function update(array $data, $id, $exclude = [])
    {
        if (!$id) {
            throw new \Exception('查询 id 不能为空！');
        }

        $bean = \R::load(static::$tableName, $id);
        if (!$bean) {
            throw new \Exception(static::$tableName . '：' . $id . ' 不存在');
        }

        return $this->save($data, $bean, $exclude);
    }

    protected function save(array $data, $bean, $exclude = [])
    {
        foreach (static::$fields as $f) {
            $fieldName = $f['name'];
            if (in_array($f['name'], $exclude)) {
                continue;
            }

            $value = $this->cleanValue($f['type'], $data[$fieldName]);

            if ($f['options']['unique']) {
                if ($bean->id) {
                    $exist = \R::findOne(
                        static::$tableName,
                        sprintf(' %s = ? and id != ? ', $f['name']),
                        [$value, $bean->id]
                    );
                } else {
                    $exist = \R::findOne(static::$tableName, sprintf(' %s = ? ', $f['name']), [$value]);
                }
                if ($exist) {
                    throw new \Exception('数据唯一性错误！');
                }
            }

            $bean->$f['name'] = $value;
        }

        foreach (static::$many2one as $i) {
            if (in_array($i . '_id', $exclude)) {
                continue;
            }

            $ManagerClass = '\\DataManager\\' . ucfirst($i) . 'Manager';
            $relKey = $ManagerClass::$tableName . '_id';
            $relId        = $data[$relKey];
            if ($relId < 1) {
                throw new \Exception(sprintf('关联ID %s 必须大于0！', $relKey));
            }
            $rel = \R::load($ManagerClass::$tableName, $relId);

            $fieldName        = strtolower($i);
            $bean->$fieldName = $rel;
        }

        $id = \R::store($bean);

        return $id;
    }

    public function cleanValue($type, $oldValue)
    {
        $value = null;
        switch ($type) {
            case 'date':
                $date  = new \DateTime($oldValue);
                $value = $date->format('Y-m-d');
                break;
            case 'datetime':
                $date  = new \DateTime($oldValue);
                $value = $date->format('Y-m-d H:i:s');
                break;
            case 'integer':
                $value = (int)trim($oldValue);
                break;
            case 'float':
                $value = (float)trim($oldValue);
                break;
            case 'bool':
                $value = (bool)$oldValue;
                break;
            case 'string':
            case 'text':
            default:
                $value = (string)$oldValue;
        }

        return $value;
    }

    public function get($id)
    {
        if ((int)$id < 1) {
            throw new \Exception('ID必须大于0！');
        }

        return \R::load(static::$tableName, (int)$id);
    }

    public function delete($id /*, $options = []*/)
    {
        \R::trash($this->get($id));
    }

    public function findAll($sql = null, $bindings = array())
    {
        return \R::findAll(static::$tableName, $sql, $bindings);
    }

    public function findOne($sql = null, $bindings = array())
    {
        return \R::findOne(static::$tableName, $sql, $bindings);
    }

    public function count($sql = null, $bindings = array())
    {
        return \R::count(static::$tableName, $sql, $bindings);
    }

    public function getLast($by, $limit, $order = 'desc')
    {
        if (!in_array(strtolower($order), ['asc', 'desc'])) {
            $order = 'desc';
        }

        $limit = (int)$limit;
        if ($limit < 1) {
            throw new \Exception('请指定 $limit 参数！');
        }

        return $this->findAll(sprintf(' order by %s %s limit %d ', $by, $order, $limit));
    }

    public function getPaging($page = 1, $limit = 10, $sql = null, $bindings = array())
    {
        $limit = (int)$limit;
        $page  = (int)$page;
        if ($limit < 1) {
            throw new \Exception('$limit 不允许小于1');
        }

        if ($page < 1) {
            $page = 1;
        }

        $pagingSql = $sql . sprintf(' limit %s, %s ', ($page - 1) * $limit, $limit);
        $data      = $this->findAll($pagingSql, $bindings);

        $count = \R::count(static::$tableName, $sql, $bindings);
        $pages = ceil($count / $limit);

        return [
            'data'     => $data,
            'count'    => $count,
            'pages'    => $pages,
            'is_first' => $page <= 1,
            'is_last'  => $page >= $pages,
        ];
    }

    public function wipe()
    {
        \R::wipe(static::$tableName);
    }

    /**
     * 新建一个 Bean
     *
     * @return array|\RedBeanPHP\OODBBean
     */
    public function create()
    {
        return \R::dispense(static::$tableName);
    }

    /**
     * 返回 $key => $value 形式的数组
     *
     * @param $v
     * @param string $k
     * @return array
     */
    public function asMap($v, $k = 'id')
    {
        $data   = $this->findAll();
        $return = [];
        foreach ($data as $d) {
            $return[$d->$k] = [$d->$v];
        }

        return $return;
    }

    public function getFields()
    {
        return static::$fields;
    }

    public function asArray($id)
    {
        $bean = $this->get($id);
        $arr  = [];

        foreach (static::$fields as $f) {
            $fieldName       = $f['name'];
            $arr[$fieldName] = $this->cleanValue($f['type'], $bean->$fieldName);
        }

        foreach (static::$many2one as $o) {
            $fieldName       = strtolower($o) . '_id';
            $arr[$fieldName] = $bean->$fieldName;
        }

        return $arr;
//        return \R::getRow(sprintf('select * from %s where id = %d', static::$tableName, $id));
    }
}
