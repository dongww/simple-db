<?php
/**
 * User: dongww
 * Date: 14-5-29
 * Time: 下午2:44
 */

namespace Dongww\Db\Doctrine\Dbal\Core;

use Symfony\Component\Yaml\Parser;

class Structure
{
    const TYPE_STRING   = 'string';
    const TYPE_TEXT     = 'text';
    const TYPE_INTEGER  = 'integer';
    const TYPE_FLOAT    = 'float';
    const TYPE_BOOLEAN  = 'boolean';
    const TYPE_DATETIME = 'datetime';
    const TYPE_DATE     = 'date';
    const TYPE_TIME     = 'time';
    const TYPE_ARRAY    = 'array';

    /**
     * 可操作的数据类型 => DBAL 的真实类型
     *
     * @var array
     */
    protected static $typeMap = [
        self::TYPE_STRING   => 'string',
        self::TYPE_TEXT     => 'text',
        self::TYPE_INTEGER  => 'integer',
        self::TYPE_FLOAT    => 'float',
        self::TYPE_BOOLEAN  => 'boolean',
        self::TYPE_DATETIME => 'datetime',
        self::TYPE_DATE     => 'date',
        self::TYPE_TIME     => 'time',
        self::TYPE_ARRAY    => 'array',
    ];

    /** @var  array */
    protected $data;

    public function __construct(array $structure)
    {
        $this->data = $structure;
    }

    public static function getTypeMap()
    {
        return self::$typeMap;
    }

    public static function createFromYaml($fileName)
    {
        $yaml = new Parser();
        $data = $yaml->parse(file_get_contents($fileName));

        return new self($data);
    }

    /**
     * @return array
     */
    public function getStructure()
    {
        return $this->data;
    }

    public function getTableStructure($tableName)
    {
        return isset($this->data['tables'][$tableName]) ? $this->data['tables'][$tableName] : null;
    }
}
