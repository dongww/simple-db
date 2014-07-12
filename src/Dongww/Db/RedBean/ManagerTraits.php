<?php
/**
 * User: dongww
 * Date: 14-7-12
 * Time: 上午9:32
 */

namespace Dongww\Db\RedBean;


trait ManagerTraits
{
    /**
     * 将表名转换为RedBean可以接受的格式
     *
     * @return string
     */
    protected function fixTableName()
    {
        return strtolower($this->getTableName());
    }

    protected function fixFields()
    {
        $fields = $this->getFields();

        foreach ($fields as &$field) {
            $field['name'] = strtolower($field['name']);
        }

        return $fields;
    }

    protected function fixMany2One()
    {
        return array_map('strtolower', $this->getMany2One());
    }

    /**
     * 新增数据
     *
     * @param array $data
     * @return int|string
     */
    public function add(array $data)
    {
        $bean = \R::dispense($this->getTableName());

        return $this->save($data, $bean);
    }

    /**
     * @param array $data
     * @param $id
     * @param array $exclude 排除的属性
     * @throws \Exception
     * @return int|string
     */
    public function update(array $data, $id, $exclude = [])
    {
        if (!$id) {
            throw new \Exception('查询 id 不能为空！');
        }

        $bean = \R::load($this->getTableName(), $id);
        if (!$bean) {
            throw new \Exception($this->getTableName() . '：' . $id . ' 不存在');
        }

        return $this->save($data, $bean, $exclude);
    }

    protected function save(array $data, $bean, $exclude = [])
    {
        $exclude = array_map('strtolower', $exclude);

        $tmpData = [];
        foreach ($data as $k => $v) {
            $tmpData[strtolower($k)] = $v;
        }
        $data = $tmpData;

        foreach ($this->getFields() as $f) {
            $fieldName = $f['name'];

            if (in_array($fieldName, $exclude)) {
                continue;
            }

            $value = $this->cleanValue($f['type'], isset($data[$fieldName]) ? $data[$fieldName] : null);

            if (isset($f['options']['unique']) && $f['options']['unique']) {
                if ($bean->id) {
                    $exist = \R::findOne(
                        $this->getTableName(),
                        sprintf(' %s = ? and id != ? ', $fieldName),
                        [$value, $bean->id]
                    );
                } else {
                    $exist = \R::findOne($this->getTableName(), sprintf(' %s = ? ', $f['name']), [$value]);
                }
                if ($exist) {
                    throw new \Exception('数据唯一性错误！');
                }
            }

            $bean->$f['name'] = $value;
        }
//print_r($this->getMany2One());exit;
        foreach ($this->getMany2One() as $i) {
            $relTable = $i;
            $relKey   = $relTable . '_id';

            if (in_array($relKey, $exclude)) {
                continue;
            }

//print_r($data);exit;
            $relId = $data[$relKey];
            if ($relId < 1) {
                throw new \Exception(sprintf('关联ID %s 必须大于0！', $relKey));
            }
            $relBean = \R::load($relTable, $relId);

            $bean->$relTable = $relBean;
        }

        $id = \R::store($bean);

        return $id;
    }

    public function cleanValue($type, $oldValue)
    {
        $value = null;
        switch ($type) {
            case 'date':
                $value = (new \DateTime($oldValue))->format('Y-m-d');
                break;
            case 'datetime':
                $value = (new \DateTime($oldValue))->format('Y-m-d H:i:s');
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

        return \R::load($this->getTableName(), (int)$id);
    }

    public function delete($id /*, $options = []*/)
    {
        \R::trash($this->get($id));
    }

    public function findAll($sql = null, $bindings = array())
    {
        $sql = strtolower($sql);
        return \R::findAll($this->getTableName(), $sql, $bindings);
    }

    public function findOne($sql = null, $bindings = array())
    {
        $sql = strtolower($sql);
        return \R::findOne($this->getTableName(), $sql, $bindings);
    }

    public function count($sql = null, $bindings = array())
    {
        $sql = strtolower($sql);
        return \R::count($this->getTableName(), $sql, $bindings);
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
        $sql   = strtolower($sql);
        $limit = (int)$limit;
        $page  = (int)$page;
        if ($limit < 1) {
            throw new \Exception('limit 不允许小于1');
        }

        if ($page < 1) {
            $page = 1;
        }

        $pagingSql = $sql . sprintf(' limit %s, %s ', ($page - 1) * $limit, $limit);
        $data      = $this->findAll($pagingSql, $bindings);

        $count = \R::count($this->getTableName(), $sql, $bindings);
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
        \R::wipe($this->getTableName());
    }

    /**
     * 新建一个 Bean
     *
     * @return array|\RedBeanPHP\OODBBean
     */
    public function create()
    {
        return \R::dispense($this->getTableName());
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

    public function asArray($id)
    {
        $bean = $this->get($id);
        $arr  = [];

        foreach ($this->getFields() as $f) {
            $fieldName       = $f['name'];
            $arr[$fieldName] = $this->cleanValue($f['type'], $bean->$fieldName);
        }

        foreach ($this->getMany2One() as $o) {
            $fieldName       = strtolower($o) . '_id';
            $arr[$fieldName] = $bean->$fieldName;
        }

        return $arr;
//        return \R::getRow(sprintf('select * from %s where id = %d', $this->getTableName(), $id));
    }
}
