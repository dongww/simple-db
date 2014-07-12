<?php
/**
 * User: dongww
 * Date: 14-7-12
 * Time: 上午10:03
 */

namespace Dongww\Db\RedBean;


interface ManagerInterface
{
    public function getFields();

    public function getMany2One();

    public function getTableName();
}
