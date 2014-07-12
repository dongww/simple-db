<?php
/**
 * User: dongww
 * Date: 14-7-11
 * Time: 上午10:45
 */

namespace Dongww\Db\RedBean;

class SimpleManager implements ManagerInterface
{
    use ManagerTraits;

    protected $fields = [];
    protected $one2one = [];
    protected $one2many = [];
    protected $many2one = [];

    protected $tableName = null;

    protected function __construct(array $structure)
    {
        $this->tableName = key($structure);

        foreach ($structure['fields'] as $k => $v) {
            $field            = [];
            $field['name']    = $k;
            $field['type']    = $v['type'];
            $field['options'] = $v['options'];

            $this->fields[] = $field;
        }

        $this->many2one = $structure['parents'];

        $this->tableName = $this->fixTableName();
        $this->fields    = $this->fixFields();
        $this->many2one  = $this->fixMany2One();
    }

    public static function createFromStructure(array $structure)
    {
        return new self($structure);
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getMany2One()
    {
        return $this->many2one;
    }

    public function getTableName()
    {
        return $this->tableName;
    }
}
