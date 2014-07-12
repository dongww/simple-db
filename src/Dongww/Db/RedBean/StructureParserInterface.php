<?php
/**
 * User: dongww
 * Date: 14-7-12
 * Time: 下午2:13
 */

namespace Dongww\Db\RedBean;

interface StructureParserInterface
{
    public function parser(array $structure);

    public function getTableName();

    public function getFields();

    public function getMany2One();
}
