<?php
/**
 * User: dongww
 * Date: 14-5-27
 * Time: 下午3:57
 */

namespace Dongww\Db\Dbal;

use Doctrine\DBAL\Connection;
use Dongww\Db\Dbal\Core\Manager;
use Dongww\Db\Dbal\Core\Structure;
use Doctrine\DBAL\Logging\DebugStack;

class ManagerFactory
{
    protected $conn;
    /** @var  Structure */
    protected $structure;

    /** @var Core\Manager[] */
    protected static $managers = [];

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
     * @return mixed
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * @param \Dongww\Db\Dbal\Core\Structure $structure
     */
    public function setStructure(Structure $structure)
    {
        $this->structure = $structure;
    }

    public function getStructure()
    {
        return $this->structure;
    }

    public function getManager($name)
    {
        if (!isset(self::$managers[$name])) {
            self::$managers[$name] = new Manager($this, $name);
        }

        return self::$managers[$name];
    }

    public function getSqlStack($simple = true)
    {
        if (!$this->debug) {
            return null;
        }

        return $this->logger->queries;
    }
}
