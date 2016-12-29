<?php
declare(strict_types = 1);
namespace mheinzerling\dist;


use mheinzerling\commons\FileUtils;
use mheinzerling\commons\FtpConnection;
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

        $ftp = new FtpConnection($config['ftp']['server'], $config['ftp']['user'], $config['ftp']['password']);


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


        if ($choice == 0) {
            $output->writeln("Abort upload");
            return 0;
        }

        $progress = $this->getHelper("progress");
        $callback = function ($serverSize, $localSize) use ($progress) {
            $progress->setCurrent($serverSize, true);
        };

        if ($choice == 1) {
            $resources = __DIR__ . "/../../../remote/";

            if (!$ftp->ls($remoteScriptDir)) {
                $output->write("Creating >" . $remoteScriptDir . "< ...\n");
                $ftp->mkdir($remoteScriptDir);
            }

            $this->uploadTemplate($ftp, $output, $resources . "unzip.php",
                FileUtils::append($remoteScriptDir, "unzip.php"),
                ['SCRIPT_DIR' => $fs->getAbsoluteRemoteScriptDir(),
                    'DEPLOY_DIR' => $fs->getAbsoluteRemoteDeployDir()]);
            $this->uploadTemplate($ftp, $output, $resources . "script.htaccess",
                FileUtils::append($remoteScriptDir, ".htaccess"),
                ['AUTH_USER_FILE' => FileUtils::append($fs->getAbsoluteRemoteScriptDir(), ".htpasswd"),
                    'HTACCESS' => $config['remote']['htaccess']]);

            $this->uploadTemplate($ftp, $output, $resources . "script.htpasswd",
                FileUtils::append($remoteScriptDir, ".htpasswd"),
                ['USER' => $config['remote']['authuser'],
                    'PWD' => crypt($config['remote']['authpwd'])]);


        } else {

            if (!$ftp->ls($remoteScriptDir)) {
                $output->write("Creating >" . $remoteScriptDir . "< ...\n");
                $ftp->mkdir($remoteScriptDir);
            }

            if (!$ftp->ls($remoteDistDir)) {
                $output->write("Creating >" . $remoteDistDir . "< ...\n");
                $ftp->mkdir($remoteDistDir);
            }

            $name = str_replace(" (Overwrite)", "", $selection[$choice]);
            $source = FileUtils::append($localDistDir, $name);
            $target = FileUtils::append($remoteDistDir, $name);
            $progress->start($output, filesize($source));
            $ftp->upload($target, $source, FTP_BINARY, $callback);

        }
        return 0;
    }
}