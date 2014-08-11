<?php
/**
 * User: dongww
 * Date: 14-6-27
 * Time: 下午2:31
 */

namespace Dongww\Db\Doctrine\Dbal\Behavior;

use Doctrine\DBAL\Schema\Table;

class TreeBehavior implements BehaviorInterface
{

    public function doIt(Table $table)
    {
        $table->addColumn("title", "string", ['notnull' => false]);
        $table->addColumn("sort", "integer", ['notnull' => false]);
        $table->addColumn("path", "string", ['notnull' => false]);
        $table->addColumn("level", "integer", ['notnull' => false]);

        $table->addColumn("parent_id", "integer", ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $table,
            array('parent_id'),
            array("id"),
            array("onUpdate" => "CASCADE")
        );
    }
}
