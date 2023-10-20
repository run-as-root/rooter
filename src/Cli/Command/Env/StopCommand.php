<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use Exception;
use RunAsRoot\Rooter\Cli\Command\Traefik\RemoveTraefikConfigCommand;
use RunAsRoot\Rooter\Config\DevenvConfig;
use RunAsRoot\Rooter\Exception\FailedToStopProcessException;
use RunAsRoot\Rooter\Exception\ProcessNotRunningException;
use RunAsRoot\Rooter\Manager\ProcessManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StopCommand extends Command
{
    public function __construct(
        private readonly DevenvConfig $devenvConfig,
        private readonly ProcessManager $processManager,
        private readonly RemoveTraefikConfigCommand $removeTraefikConfigCommand,
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('stop');
        $this->setAliases(['env:stop']);
        $this->setDescription('Stop environment');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = true;
        $projectName = getenv('PROJECT_NAME');

        try {
            $output->writeln("Stopping environment $projectName");
            $pidFile = $this->devenvConfig->getPidFile();
            $this->processManager->stop($pidFile);
            $output->writeln("environment $projectName stopped");
        } catch (ProcessNotRunningException $e) {
            $output->writeln("environment $projectName already stopped");
        } catch (FailedToStopProcessException $e) {
            $output->writeln("<error>environment could not be stopped: {$e->getMessage()}</error>");
            $result = false;
        } catch (Exception $e) {
            $output->writeln("<error>environment unknown error: {$e->getMessage()}</error>");
            $result = false;
        }

        $resultTraefik = $this->removeTraefikConfigCommand->run(new ArrayInput([]), $output) === Command::SUCCESS;

        return $result && $resultTraefik ? Command::SUCCESS : Command::FAILURE;
    }

}
