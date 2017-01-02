<?php
declare(strict_types = 1);
namespace mheinzerling\dist\bin;


use mheinzerling\commons\FtpConnection;
use mheinzerling\dist\config\DeploymentDescriptor;
use mheinzerling\dist\config\Remote;
use mheinzerling\dist\Template;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DeployCommand extends DeploymentDescriptorAwareCommand
{
    const ABORT = "Abort";

    protected function innerConfigure(): void
    {
        $this->setName('deploy')
            ->setAliases([])
            ->setDescription('Activate a dist one the server');
    }

    protected function innerExecute(DeploymentDescriptor $config, InputInterface $input, OutputInterface $output): int
    {
        $remote = $config->remote();

        $unzip = $remote->unzip();
        $output->writeln("Unzip result:\n" . $unzip);

        $currentVersion = $remote->currentVersion();
        $output->writeln("Current version: " . $currentVersion);

        $directory = self::selectDirectory($remote, $this->getHelper("question"), $input, $output);

        if ($directory == self::ABORT) {
            $output->writeln("Abort deployment");
            return 0;
        }

        $ftp = $remote->createFtpConnection();
        MaintenanceCommand::setMaintenance($ftp, $output, true);

        self::linkDirectory($directory, $ftp, $remote, $output);

        /**
         * @var $dialog QuestionHelper
         */
        $dialog = $this->getHelper("question");
        if ($dialog->ask($input, $output, new ConfirmationQuestion("Disable maintenance mode?", false))) {
            MaintenanceCommand::setMaintenance($ftp, $output, false);
        }
        return 0;
    }


    public static function selectDirectory(Remote $remote, QuestionHelper $question, InputInterface $input, OutputInterface $output): string
    {
        $remoteFiles = $remote->getExtractedDirectories();
        $selection = ["0" => self::ABORT];
        foreach ($remoteFiles as $file) {
            $selection[] = $file;
        }
        $choice = $question->ask($input, $output, new ChoiceQuestion("Select dist to deploy", $selection, 0));
        return $choice;
    }

    public static function linkDirectory(string $directory, FtpConnection $ftp, Remote $remote, OutputInterface $output)
    {
        Template::upload($ftp, $output, Template::get("root.htaccess"), $remote->getHtaccess(), $remote->getHtaccessReplacement($directory));

        foreach ($remote->afterDeploy() as $call) {
            $output->writeln("Calling " . $call->getUrl() . " ...");
            $result = file_get_contents($call->getUrl(), false, $call->getContext());
            $output->writeln($result);
        }


    }

}