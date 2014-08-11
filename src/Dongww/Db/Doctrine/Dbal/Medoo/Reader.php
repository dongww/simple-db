<?php
/**
 * User: dongww
 * Date: 14-7-14
 * Time: 上午9:23
 */

namespace Dongww\Db\Doctrine\Dbal\Medoo;

use Dongww\Db\Doctrine\Dbal\Core\QueryInterface;

class Reader implements QueryInterface
{
    protected $medoo;

    public function __construct(MedooAdapter $ma)
    {
        $this->medoo = $ma;
    }

    public function select($table, $join = null, $columns = '*', $where = null)
    {
        if ($join) {
            return $this->medoo->select($table, $join, $columns, $where);
        } else {
            return $this->medoo->select($table, $columns, $where);
        }
    }

    public function get($table, $columns = '*', $where = null)
    {
        return $this->medoo->get($table, $columns, $where);
    }

    public function has($table, $join = null, $where = null)
    {
        if ($join) {
            return $this->medoo->has($table, $join, $where);
        } else {
            return $this->medoo->has($table, $where);
        }
    }

    public function count($table, $join = null, $column = '*', $where = null)
    {
        if ($join && $column) {
            return $this->medoo->count($table, $join, $column, $where);
        } else {
            return $this->medoo->count($table, $where);
        }
    }

    public function query($query)
    {
        return $this->medoo->query($query);
    }
}
