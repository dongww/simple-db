<?php
/**
 * User: dongww
 * Date: 14-6-10
 * Time: 下午4:37
 */

namespace Dongww\Db\RedBean\Provider;

use Silex\ServiceProviderInterface;
use Silex\Application;

class RbProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
    }

    public function boot(Application $app)
    {
        $dsn = null;
        switch ($app['db.options']['driver']) {
            case 'pdo_mysql':
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=%s',
                    $app['db.options']['host'],
                    $app['db.options']['dbname'],
                    $app['db.options']['charset']
                );
                break;
            case 'pdo_sqlite':
                $dsn = sprintf(
                    'sqlite:%s',
                    $app['db.options']['path']
                );
                break;
            default:
                throw new \Exception('目前只支持 pdo_mysql 和 pdo_sqlite ');
        }
        \R::setup(
            $dsn,
            $app['db.options']['user'],
            $app['db.options']['password']
        );
    }
}
 