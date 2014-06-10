<?php
/**
 * User: dongww
 * Date: 14-6-10
 * Time: 上午9:13
 */

namespace Dongww\Db\Orm\Provider;

use Silex\ServiceProviderInterface;
use Silex\Application;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

class DoctrineOrmProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['dm'] = $app->share(function ($app) {
            $config = Setup::createAnnotationMetadataConfiguration(array($app['orm.source.dir']), $app['debug']);
            if (isset($app['orm.proxy.dir'])) {
                $config->setProxyDir($app['orm.proxy.dir']);
                $config->setProxyNamespace($app['orm.proxy.namespace']);
            }

            return EntityManager::create($app['db'], $config);
        });
    }

    public function boot(Application $app)
    {

    }
}
