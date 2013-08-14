<?php

namespace mheinzerling\dist;


use mheinzerling\commons\FileUtils;
use mheinzerling\commons\FtpConnection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UploadCommand extends DeploymentDescriptorAwareCommand
{
    protected function innerConfigure()
    {
        $this->setName('upload')
            ->setAliases(array())
            ->setDescription('Upload dist to server');
    }

    protected function innerExecute(array $config, InputInterface $input, OutputInterface $output)
    {
        $fs = new FileSystemHelper($config);
        $remoteDistDir = $fs->getRemoteDistDir();
        $remoteScriptDir = $fs->getRemoteScriptDir();
        $localDistDir = $fs->getLocalDistDir();

        $ftp = new FtpConnection($config['ftp']['server'], $config['ftp']['user'], $config['ftp']['password']);


        $remoteFiles = array_flip($ftp->ls($remoteDistDir, '@dist.*\.zip@', true));
        $localFiles = array_map("basename", glob($localDistDir . "/dist*.zip"));

        $selection = array("0" => "Abort", "1" => "Deploy script");
        foreach ($localFiles as $file) {
            $exist = array_key_exists($file, $remoteFiles);
            $selection[] = $file . ($exist ? " (Overwrite)" : "");
        }

        $dialog = $this->getHelper("dialog");
        $choice = $dialog->select($output, "Select file to upload", $selection, 0);


        if ($choice == 0) {
            $output->writeln("Abort upload");
            return;
        }

        $progress = $this->getHelper("progress");
        $callback = function ($serverSize, $localSize) use ($progress) {
            $progress->setCurrent($serverSize, true);
        };

        if ($choice == 1) {
            $resources = __DIR__ . "/../../../remote/";

            $this->uploadTemplate($ftp, $output, $resources . "unzip.php",
                FileUtils::append($remoteScriptDir, "unzip.php"),
                array('SCRIPT_DIR' => $fs->getAbsoluteRemoteScriptDir()));
            $this->uploadTemplate($ftp, $output, $resources . "script.htaccess",
                FileUtils::append($remoteScriptDir, ".htaccess"),
                array('AUTH_USER_FILE' => FileUtils::append($fs->getAbsoluteRemoteScriptDir(), ".htpasswd"),
                    'HTACCESS' => $config['remote']['htaccess']));

            $this->uploadTemplate($ftp, $output, $resources . "script.htpasswd",
                FileUtils::append($remoteScriptDir, ".htpasswd"),
                array('USER' => $config['remote']['authuser'],
                    'PWD' => crypt($config['remote']['authpwd'])));


        } else {
            $name = str_replace(" (Overwrite)", "", $selection[$choice]);
            $source = FileUtils::append($localDistDir, $name);
            $target = FileUtils::append($remoteDistDir, $name);
            $progress->start($output, filesize($source));
            $ftp->upload($target, $source, FTP_BINARY, $callback);

        }

    }

}