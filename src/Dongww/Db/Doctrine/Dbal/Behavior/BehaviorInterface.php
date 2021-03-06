<?php
/**
 * User: dongww
 * Date: 14-6-27
 * Time: 上午9:47
 */

namespace Dongww\Db\Doctrine\Dbal\Behavior;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

interface BehaviorInterface
{
    public function doIt(Table $table, Schema $schema = null);
}
