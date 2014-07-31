<?php
/**
 * User: dongww
 * Date: 14-5-27
 * Time: 下午3:57
 */

namespace Dongww\Db\Doctrine\Dbal\Manager;

use Dongww\Db\Doctrine\Dbal\Medoo;
use Doctrine\DBAL\Connection;
use Dongww\Db\Doctrine\Dbal\Core\Structure;
use Doctrine\DBAL\Logging\DebugStack;

class ManagerFactory
{
    /** @var  Connection */
    protected $conn;
    /** @var  Structure */
    protected $structure;

    /** @var Manager[] */
    protected static $managers = [];

    /** @var  Medoo\MedooAdapter */
    protected $medoo;

    /** @var  Medoo\Reader */
    protected $reader;

    protected $debug;

    /** @var DebugStack */
    protected $logger;

    public function __construct($conn, Structure $structure, $debug = false)
    {
        $this->debug = (bool)$debug;
        $this->setConnection($conn);
        $this->setStructure($structure);
    }

    public function setConnection(Connection $conn)
    {
        $this->conn = $conn;

        if ($this->debug) {
            $this->logger = new DebugStack();
            $this->conn->getConfiguration()->setSQLLogger($this->logger);
        }
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }

    public function getMedoo()
    {
        if (!($this->medoo instanceof Medoo\MedooAdapter)) {
            $this->medoo = new Medoo\MedooAdapter($this->getConnection());
        }

        return $this->medoo;
    }

    public function getReader()
    {
        if (!($this->reader instanceof Medoo\Reader)) {
            $this->reader = new Medoo\Reader($this->getMedoo());
        }

        return $this->reader;
    }

    /**
     * @param Structure $structure
     */
    public function setStructure(Structure $structure)
    {
        $this->structure = $structure;
    }

    public function getStructure()
    {
        return $this->structure;
    }

    /**
     * @param $name
     * @return Manager|TreeManager
     */
    public function getManager($name)
    {
        if (!isset(self::$managers[$name])) {
            $tblStructure = $this->getStructure()->getTableStructure($name);
            if ($tblStructure['tree_able']) {
                self::$managers[$name] = new TreeManager($this, $name);
            } else {
                self::$managers[$name] = new Manager($this, $name);
            }
        }

        return self::$managers[$name];
    }

    public function getSqlStack( /*$simple = true*/)
    {
        if (!$this->debug) {
            return null;
        }

        return $this->logger->queries;
    }
}
