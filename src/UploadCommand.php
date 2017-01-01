<?php
declare(strict_types = 1);
namespace mheinzerling\dist;


use mheinzerling\commons\ExtensionFtpConnection;
use mheinzerling\commons\FileUtils;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class UploadCommand extends DeploymentDescriptorAwareCommand
{
    protected function innerConfigure(): void
    {
        $this->setName('upload')
            ->setAliases([])
            ->setDescription('Upload dist to server');
    }

    protected function innerExecute(array $config, InputInterface $input, OutputInterface $output): int
    {
        $fs = new FileSystemHelper($config);
        $remoteDistDir = $fs->getRemoteDistDir();
        $remoteScriptDir = $fs->getRemoteScriptDir();
        $localDistDir = $fs->getLocalDistDir();

        $ftp = new ExtensionFtpConnection($config['ftp']['server'], $config['ftp']['user'], $config['ftp']['password']);


        $remoteFiles = array_flip($ftp->ls($remoteDistDir, '@dist.*\.zip@', true));
        $localFiles = array_map("basename", glob($localDistDir . "/dist*.zip"));

        $selection = ["0" => "Abort", "1" => "Deploy script"];
        foreach ($localFiles as $file) {
            $exist = array_key_exists($file, $remoteFiles);
            $selection[] = $file . ($exist ? " (Overwrite)" : "");
        }

        /**
         * @var $dialog QuestionHelper
         */
        $dialog = $this->getHelper("question");
        $choice = $dialog->ask($input, $output, new ChoiceQuestion("Select file to upload", $selection, 0));


        if ($choice == $selection[0]) {
            $output->writeln("Abort upload");
            return 0;
        }


        if ($choice == $selection[1]) {
            $resources = __DIR__ . "/../../remote/";
            $output->write("Starting setup of deployment script ...\n");
            if (!$ftp->ls($remoteScriptDir)) {
                $output->write("Creating >" . $remoteScriptDir . "< ...\n");
                $ftp->mkdir($remoteScriptDir);
                $output->write("... done\n");
            } else {
                $output->write(">" . $remoteScriptDir . "< already exists\n");
            }
            $output->write("Uploading unzip.php\n");
            $this->uploadTemplate($ftp, $output, $resources . "unzip.php",
                FileUtils::append($remoteScriptDir, "unzip.php"),
                ['SCRIPT_DIR' => $fs->getAbsoluteRemoteScriptDir(),
                    'DEPLOY_DIR' => $fs->getAbsoluteRemoteDeployDir()]);
            $output->write("Uploading .htaccess\n");
            $this->uploadTemplate($ftp, $output, $resources . "script.htaccess",
                FileUtils::append($remoteScriptDir, ".htaccess"),
                ['AUTH_USER_FILE' => FileUtils::append($fs->getAbsoluteRemoteScriptDir(), ".htpasswd"),
                    'HTACCESS' => $config['remote']['htaccess']]);
            $output->write("Uploading .htpasswd\n");
            $this->uploadTemplate($ftp, $output, $resources . "script.htpasswd",
                FileUtils::append($remoteScriptDir, ".htpasswd"),
                ['USER' => $config['remote']['authuser'],
                    'PWD' => password_hash($config['remote']['authpwd'], PASSWORD_BCRYPT)]);


        } else {

            if (!$ftp->ls($remoteScriptDir)) {
                $output->write("Creating >" . $remoteScriptDir . "< ...\n");
                $ftp->mkdir($remoteScriptDir);
            }

            if (!$ftp->ls($remoteDistDir)) {
                $output->write("Creating >" . $remoteDistDir . "< ...\n");
                $ftp->mkdir($remoteDistDir);
            }

            $name = str_replace(" (Overwrite)", "", $choice);
            $source = FileUtils::append($localDistDir, $name);
            $target = FileUtils::append($remoteDistDir, $name);
            $progress = new ProgressBar($output, filesize($source));
            $progress->start();
            $callback = function ($serverSize, $localSize) use ($progress) {
                $progress->setProgress($serverSize);
                $progress->setBarWidth($localSize);
            };
            $ftp->upload($target, $source, FTP_BINARY, $callback);

        }
        return 0;
    }
}