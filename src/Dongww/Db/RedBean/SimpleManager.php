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
    protected $many2one = [];

    protected $tableName = null;

    protected function __construct(array $structure, StructureParserInterface $parser)
    {
        $this->tableName = $parser->getTableName();
        $this->fields    = $parser->getFields();
        $this->many2one  = $parser->getMany2One();

        $this->tableName = $this->fixTableName();
        $this->fields    = $this->fixFields();
        $this->many2one  = $this->fixMany2One();
    }

    public static function createFromStructure(array $structure, StructureParserInterface $parser)
    {
        return new self($structure, $parser);
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
