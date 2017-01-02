<?php
declare(strict_types = 1);
namespace mheinzerling\dist\bin;

use mheinzerling\commons\FtpConnection;
use mheinzerling\dist\config\DeploymentDescriptor;
use mheinzerling\dist\config\Local;
use mheinzerling\dist\config\Remote;
use mheinzerling\dist\Template;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class UploadCommand extends DeploymentDescriptorAwareCommand
{
    const ABORT = "Abort";
    const SCRIPT = "Deployment script";


    protected function innerConfigure(): void
    {
        $this->setName('upload')
            ->setAliases([])
            ->setDescription('Upload dist to server');
    }

    protected function innerExecute(DeploymentDescriptor $config, InputInterface $input, OutputInterface $output): int
    {
        $local = $config->local();
        $remote = $config->remote();

        $choice = static::selectArchive($local, $remote, $this->getHelper("question"), $input, $output);

        if ($choice == self::ABORT) {
            $output->writeln("Abort upload");
        } else if ($choice == self::SCRIPT) {
            static::uploadDeploymentScript($remote, $output);
        } else {
            static::uploadArchive($local, $remote, $choice, $output);
        }
        return 0;
    }

    protected static function selectArchive(Local $local, Remote $remote, QuestionHelper $question, InputInterface $input, OutputInterface $output): string
    {
        $remoteFiles = array_flip($remote->getArchives());
        $selection = ["Abort", "Deployment script"];
        foreach ($local->getArchives() as $file) {
            $exist = array_key_exists($file, $remoteFiles);
            $selection[] = $file . ($exist ? " (Overwrite)" : "");
        }

        return $question->ask($input, $output, new ChoiceQuestion("Select file to upload", $selection, 0));
    }


    protected static function uploadDeploymentScript(Remote $remote, OutputInterface $output): void
    {
        $ftp = $remote->createFtpConnection();
        $output->write("Starting setup of deployment script ...\n");
        self::createDirectory($ftp, $remote->getScriptDirectory(), $output);
        $output->write("Uploading unzip.php\n");
        $replacements = $remote->getScriptReplacement();
        Template::upload($ftp, $output, Template::get("unzip.php"), $remote->getScriptFile("unzip.php"), $replacements);
        $output->write("Uploading .htaccess\n");
        Template::upload($ftp, $output, Template::get("script.htaccess"), $remote->getScriptFile(".htaccess"), $replacements);
        $output->write("Uploading .htpasswd\n");
        Template::upload($ftp, $output, Template::get("script.htpasswd"), $remote->getScriptFile(".htpasswd"), $replacements);
    }


    protected static function uploadArchive(Local $local, Remote $remote, string $archiveName, OutputInterface $output): void
    {
        $ftp = $remote->createFtpConnection();
        self::createDirectory($ftp, $remote->getArchiveDirectory(), $output);

        $version = str_replace(["dist.", " (Overwrite)", ".zip"], "", $archiveName);
        $source = $local->getAbsoluteArchiveFile($version);
        $target = $remote->getArchiveFile($version);
        $progress = new ProgressBar($output, filesize($source));
        $progress->start();
        $callback = function ($serverSize, $localSize) use ($progress) {
            $progress->setProgress($serverSize);
            $progress->setBarWidth($localSize);
        };
        $ftp->upload($target, $source, FTP_BINARY, $callback);
    }


    protected static function createDirectory(FtpConnection $ftp, $directory, OutputInterface $output): void
    {
        if (!$ftp->ls($directory)) {
            $output->write("Creating " . $directory . " ...\n");
            $ftp->mkdir($directory);
            $output->write("... done\n");
        } else {
            $output->write($directory . " already exists\n");
        }
    }
}