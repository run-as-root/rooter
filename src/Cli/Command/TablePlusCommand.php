<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TablePlusCommand extends Command
{
    public function configure()
    {
        $this->setName('tableplus');
        $this->setDescription('launch Tableplus MacOS App');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tablePlusBin = getenv('TABLEPLUS_BIN') ?? '/Applications/TablePlus.app/Contents/MacOS/TablePlus'; # @todo check if set

        $user = getenv('DEVENV_DB_USER');
        $pass = getenv('DEVENV_DB_PASS');
        $port = getenv('DEVENV_DB_PORT');
        $db = getenv('DEVENV_DB_NAME');

        $query = "mysql://$user:$pass@127.0.0.1:$port/$db?env=rooter&color=e3a333";

        $output->writeln($query);

        shell_exec("open '$query' -a '$tablePlusBin'");

        return 0;
    }
}
