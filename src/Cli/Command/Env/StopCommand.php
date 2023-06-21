<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Config\DevenvConfig;
use RunAsRoot\Rooter\Exception\FailedToStopProcessException;
use RunAsRoot\Rooter\Exception\ProcessNotRunningException;
use RunAsRoot\Rooter\Manager\ProcessManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StopCommand extends Command
{
    private DevenvConfig $devenvConfig;
    private ProcessManager $processManager;

    public function configure()
    {
        $this->setName('env:stop');
        $this->setDescription('Stop environment');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->devenvConfig = new DevenvConfig();
        $this->processManager = new ProcessManager();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = true;

        try {
            $pidFile = $this->devenvConfig->getPidFile();
            $this->processManager->stop($pidFile);
            $output->writeln("<info>environment was stopped</info>");
        } catch (ProcessNotRunningException $e) {
            $output->writeln("environment already stopped");
        } catch (FailedToStopProcessException $e) {
            $output->writeln("<error>environment could not be stopped: {$e->getMessage()}</error>");
            $result = false;
        } catch (\Exception $e) {
            $output->writeln("<error>environment unknown error: {$e->getMessage()}</error>");
            $result = false;
        }

        return $result ? Command::SUCCESS : Command::FAILURE;
    }

}
