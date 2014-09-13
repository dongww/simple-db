<?php
/**
 * User: dongww
 * Date: 14-5-27
 * Time: 下午2:14
 */

namespace Dongww\Db\Doctrine\Dbal\Manager;

class Bean
{
    protected $data = [];

    /** @var  Manager */
    protected $manager;

    public function __construct(Manager $manager)
    {
        $this->setManager($manager);
    }

    public function getTableName()
    {
        if (empty($this->tableName)) {
            $this->tableName = $this->manager->getTableName();
        }

        return $this->tableName;
    }

    public function getOne2Many()
    {
        return $this->getManager()->getOne2Many();
    }

    public function getMany2Many()
    {
        return $this->getManager()->getMany2Many();
    }

    public function getMany2One()
    {
        return $this->getManager()->getMany2One();
    }

    /**
     * @return array
     */
    protected function getStructure()
    {
        return $this->getManagerFactory()
            ->getStructure();
    }

    /**
     * @return Manager
     */
    public function getManager()
    {
        return $this->manager;
    }

    protected function setManager(Manager $manager)
    {
        $this->manager = $manager;
    }

    public function getManagerFactory()
    {
        return $this->manager->getManagerFactory();
    }

    public function getTableStructure()
    {
        return $this
            ->getManagerFactory()
            ->getStructure()
            ->getTableStructure($this->getTableName());
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __isset($name)
    {
//        return isset($this->data[$name]) ? true : false;
        return true;
    }

    public function get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return $this->getBelongTo($name);
    }

    public function import(array $data)
    {
        $this->data = $data;
    }

    /**
     * 获得 BelongTo Bean
     *
     * @param $name
     * @return Bean|null
     */
    public function getBelongTo($name)
    {
        $belongToKey = Manager::foreignKey($name);
        if (!isset($this->data[$belongToKey])) {
            return null;
        }

        if ($name == 'parent') {
            $tblStructure = $this->getTableStructure($name);
            if ($tblStructure['tree_able']) {
                $m = $this->getManager();

                return $m->get($this->data[$belongToKey]);
            }
        }

        $m = $this->getManagerFactory()->getManager($name);

        return $m->get($this->data[$belongToKey]);
    }

    public function getMany($name, array $where = [], array $options = [])
    {
        return $this->getManager()->getMany($this->id, $name, $where, $options);
    }
}
