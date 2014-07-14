<?php
/**
 * User: dongww
 * Date: 14-7-14
 * Time: 上午9:28
 */

namespace Dongww\Db\Doctrine\Dbal\Core;


interface QueryInterface
{
    public function select($table, $join = null, $columns = null, $where = null);

    public function get($table, $columns, $where = null);

    public function has($table, $join, $where = null);

    public function count($table, $join = null, $column = null, $where = null);

    public function query($query);
}
