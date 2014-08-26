<?php
/**
 * User: dongww
 * Date: 14-7-12
 * Time: 下午3:31
 */

namespace Dongww\Db\Doctrine\Dbal\Medoo;

use Doctrine\DBAL\Connection;

class MedooAdapter extends \medoo
{
    public function __construct(Connection $conn)
    {
        $this->pdo = $conn;

        $params   = $conn->getParams();
        $driver   = $params['driver'];
        $commands = [];

        switch ($driver) {
            case 'pdo_mysql':
                $commands[] = 'SET SQL_MODE=ANSI_QUOTES';
                break;
            case 'pdo_sqlite':
                break;
            case 'pdo_pgsql':
                break;
            case 'pdo_oci':
                break;
            case 'pdo_sqlsrv':
                $commands[] = 'SET QUOTED_IDENTIFIER ON';
                break;
        }

        foreach ($commands as $comm) {
            $this->pdo->exec($comm);
        }
    }
}
