<?php
/**
 * User: dongww
 * Date: 14-6-10
 * Time: 下午3:48
 */

namespace Dongww\Db\Medoo\Provider;


class MedooProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['medoo'] = $app->share(function ($app) {
            return new \medoo([
                'database_type' => substr($app['db.options']['driver'], 4),
                'database_name' => $app['db.options']['dbname'],
                'database_file' => $app['db.options']['path'],
                'server'        => $app['db.options']['host'],
                'username'      => $app['db.options']['user'],
                'password'      => $app['db.options']['password'],
                'charset'       => $app['db.options']['charset'],
            ]);
        });
    }

    public function boot(Application $app)
    {

    }
}
