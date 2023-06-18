<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListEnvCommand extends Command
{

    public function configure()
    {
        $this->setName('env:list');
        $this->setDescription('list all projects');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $environmentsDir = ROOTER_HOME_DIR . '/environments';

        $jsonFiles = glob("$environmentsDir/*.json");

        $jsonFiles = $jsonFiles === false ? [] : $jsonFiles;

        $projects = [];
        foreach ($jsonFiles as $jsonFile) {
            $jsonData = file_get_contents($jsonFile);

            try {
                $envData = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $output->writeln("<error>Could not decode '$jsonFile' : '{$e->getMessage()}'</error>");
                continue;
            }

            $projects[] = [
                'Name' => $envData['name'] ?? '',
                'Host' => $envData['host'] ?? '',
                'Status' => '',
            ];
        }

        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaders(['Name', 'Host', 'Status']);
        $table->setRows($projects);
        $table->render();

        return self::SUCCESS;
    }
}
