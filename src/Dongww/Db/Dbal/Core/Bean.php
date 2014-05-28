<?php
/**
 * User: dongww
 * Date: 14-5-27
 * Time: 下午2:14
 */

namespace Dongww\Db\Dbal\Core;


class Bean
{
    protected $data = [];
    protected $parents = [];
    protected $many = [];
    /** @var  Manager */
    protected $manager;

    public function getTableName()
    {
        return $this->manager->getTableName();
    }

    /**
     * @return \Dongww\Db\Dbal\Core\Manager
     */
    public function getManager()
    {
        return $this->manager;
    }

    public function setManager(Manager $manager)
    {
        $this->manager = $manager;
    }

    public function getManagerFactory()
    {
        return $this->manager->getManagerFactory();
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    public function __isset($name)
    {
//        return isset($this->data[$name]) ? true : false;
        return true;
    }

    public function import(array $data)
    {
        $this->data = $data;
    }

    public function getParent($name)
    {
        $parent_id = $name . '_id';
        if (!isset($this->data[$parent_id])) {
            return null;
        }

        $m = $this
            ->getManagerFactory()
            ->createManager($name);

        return $m->get($this->data[$parent_id]);
    }
}
