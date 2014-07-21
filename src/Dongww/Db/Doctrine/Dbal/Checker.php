<?php
/**
 * User: dongww
 * Date: 14-5-27
 * Time: 上午9:07
 */

namespace Dongww\Db\Doctrine\Dbal;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Dongww\Db\Doctrine\Dbal\Core\Structure;
use Dongww\Db\Doctrine\Dbal\Behavior;

class Checker
{
    protected $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * 获得数据库的更改后的 SQL语句 数组
     *
     * @param string $fileName 数据结构的文件名
     * @return array
     */
    public function getDiffSql($fileName)
    {
        $structure    = Structure::createFromYaml($fileName);
        $data         = $structure->getStructure();
        $structureMap = Structure::getTypeMap();
        $tables       = [];

        $newSchema = new Schema();

        if (is_array($data['tables'])) {
            foreach ($data['tables'] as $tblName => $tbl) {
                /** @var \Doctrine\DBAL\Schema\Table $newTable */
                $newTable = $tables[$tblName] = $newSchema->createTable($tblName);

                foreach ($tbl['fields'] as $fieldName => $field) {
                    $options            = [];
                    $options['notnull'] = isset($field['required']) ? (bool)$field['required'] : false;

                    if (isset($structureMap[$field['type']])) {
                        $newTable->addColumn($fieldName, $structureMap[$field['type']], $options);
                    }
                }

                $newTable->addColumn("id", "integer", array('autoincrement' => true));
                $newTable->setPrimaryKey(array("id"));

                /** timestamp_able 创建时间，更改时间 */
                $timeAble = isset($tbl['timestamp_able']) ? $tbl['timestamp_able'] : false;
                if ($timeAble) {
                    (new Behavior\TimestampBehavior())->doIt($newTable);
                }

                /** tree_able 可进行树状存储 */
                $treeAble = isset($tbl['tree_able']) ? $tbl['tree_able'] : false;
                if ($treeAble) {
                    (new Behavior\TreeBehavior())->doIt($newTable);
                }
            }
        }

        /** 多对一 */
        if (is_array($data['tables'])) {
            foreach ($data['tables'] as $tblName => $tbl) {
                if (isset($tbl['parents'])) {
                    foreach ($tbl['parents'] as $p) {
                        $parentTblName = is_array($p) ? key($p) : $p;
                        $this->addForeign($tables[$tblName], $tables[$parentTblName]);
                    }
                }
            }
        }

        /** 多对多 */
        if (is_array($data['many_many'])) {
            foreach ($data['many_many'] as $mm) {
                $tblName0 = is_array($mm[0]) ? key($mm[0]) : $mm[0];
                $tblName1 = is_array($mm[1]) ? key($mm[1]) : $mm[1];
                $tblName  = $tblName0 . '_' . $tblName1;

                $tables[$tblName] = $newSchema->createTable($tblName);

                $this->addForeign($tables[$tblName], $tables[$tblName0]);
                $this->addForeign($tables[$tblName], $tables[$tblName1]);
            }
        }

        $oldSchema = $this->conn->getSchemaManager()->createSchema();

        return $sql = $oldSchema->getMigrateToSql($newSchema, $this->conn->getDatabasePlatform());
    }

    public function addForeign(Table $table, Table $foreignTable)
    {
        $columnName = $foreignTable->getName() . '_id';
        $table->addColumn($columnName, "integer", ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $foreignTable,
            array($columnName),
            array("id"),
            array("onUpdate" => "CASCADE")
        );
    }
}
