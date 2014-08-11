<?php
/**
 * User: dongww
 * Date: 14-7-12
 * Time: 下午2:17
 */

namespace Dongww\Db\RedBean;

class SimpleConfigParser implements StructureParserInterface
{
    protected $tableName = null;
    protected $fields = [];
    protected $many2one = [];

    public function parser(array $structure)
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
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getMany2One()
    {
        return $this->many2one;
    }
}
