<?php
/**
 * User: dongww
 * Date: 14-5-26
 * Time: 上午9:39
 */

namespace Dongww\Db\Doctrine\Dbal\Command;

use Doctrine\DBAL\Connection;
use Dongww\Db\Doctrine\Dbal\Checker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends Command
{
    protected $conn;

    public function __construct(Connection $conn = null, $name = null)
    {
        $this->conn = $conn;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('db:update')
            ->setDescription('更新数据库。')
            ->addOption(
                'config',
                null,
                InputOption::VALUE_OPTIONAL,
                'The config file Path.',
                'config/config.php'
            )
            ->addOption(
                'structure',
                null,
                InputOption::VALUE_OPTIONAL,
                'The structure file Path.',
                'config/structure.yml'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Start checking the database!');

        $connFile   = $input->getOption('config');
        $this->conn = file_exists($connFile) ? require_once $connFile : $this->conn;
        if (!($this->conn instanceof Connection)) {
            throw new \Exception('It\'s not available db connection object.');
        }

        $checker = new Checker($this->conn);

        $structureFile = $input->getOption('structure');

        if (!file_exists($structureFile)) {
            throw new \Exception('Structure file not exists!');
        }

        $sql = $checker->getDiffSql($structureFile);

        if (empty($sql)) {
            $output->writeln('Nothing has changed.');
        } else {
            $output->writeln('Began to update the database.');
            $output->writeln('//sql--------------------');
            foreach ($sql as $s) {
                $output->writeln($s . ';');
                $this->conn->query($s);
            }
            $output->writeln('\\\\end sql----------------');
            $output->writeln('Database has been updated!');
        }
    }
}
