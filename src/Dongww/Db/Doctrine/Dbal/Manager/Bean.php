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
    protected $tableName;
    protected $one2Many = [];
    protected $many2One = [];
    protected $many2Many = [];
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
        $structure = $this->getStructure();

        if (empty($this->one2Many)) {
            foreach ($structure['tables'] as $tblName => $table) {
                if (in_array($this->getTableName(), $table['belong_to'])) {
                    $this->one2Many[] = $tblName;
                }
            }
        }

        return $this->one2Many;
    }

    public function getMany2Many()
    {
        $structure = $this->getStructure();

        if (empty($this->many2Many)) {
            foreach ($structure['many_many'] as $mm) {
                if (in_array($this->getTableName(), $mm)) {
                    $this->many2Many[] = ($mm[0] == $this->getTableName()) ? $mm[1] : $mm[0];
                }
            }
        }

        return $this->many2Many;
    }

    public function getMany2One()
    {
        $structure = $this->getStructure();

        if (empty($this->many2One)) {
            $this->many2One = $structure['tables'][$this->getTableName()]['belong_to'];
        }

        return $this->many2One;
    }

    /**
     * @return array
     */
    protected function getStructure()
    {
        return $this->getManagerFactory()
            ->getStructure()
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
}
