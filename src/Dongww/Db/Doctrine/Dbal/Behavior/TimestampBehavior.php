<?php
/**
 * User: dongww
 * Date: 14-6-27
 * Time: 下午2:23
 */

namespace Dongww\Db\Doctrine\Dbal\Behavior;

use Doctrine\DBAL\Schema\Table;

class TimestampBehavior implements BehaviorInterface
{

    public function doIt(Table $table)
    {
        $table->addColumn('created_at', "datetime");
        $table->addColumn('updated_at', "datetime");
    }
}
