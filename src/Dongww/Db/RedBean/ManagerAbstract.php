<?php
/**
 * User: dongww
 * Date: 14-4-21
 * Time: 下午12:44
 */

namespace Dongww\Db\RedBean;

use Dongww\Db\RedBean\ManagerTraits;

abstract class ManagerAbstract implements ManagerInterface
{
    use ManagerTraits;

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
        static::$tableName = $this->fixTableName();
        static::$fields    = $this->fixFields();
        static::$many2one  = $this->fixMany2One();
    }

    public function getFields()
    {
        return static::$fields;
    }

    public function getMany2One()
    {
        return static::$many2one;
    }

    public function getTableName()
    {
        return static::$tableName;
    }
}
