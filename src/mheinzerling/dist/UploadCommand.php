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
        $localDistDir = $fs->getLocalDistDir();

        $ftp = new FtpConnection($config['ftp']['server'], $config['ftp']['user'], $config['ftp']['password']);


        $remoteFiles = array_flip($ftp->ls($remoteDistDir, '@dist.*\.zip@', true));
        $localFiles = array_map("basename", glob($localDistDir . "/dist*.zip"));

        $selection = array("0" => "Abort");
        foreach ($localFiles as $file) {
            $exist = array_key_exists($file, $remoteFiles);
            $selection[] = $file . ($exist ? " (Overwrite)" : "");
        }

        $dialog = $this->getHelper("dialog");
        $choice = $dialog->select($output, "Select file to upload", $selection, 0);

        if ($choice == null) $output->writeln("Abort upload");
        else {
            $name = str_replace(" (Overwrite)", "", $selection[$choice]);

            $source = FileUtils::append($localDistDir, $name);
            $progress = $this->getHelper("progress");
            $progress->start($output, filesize($source));

            $callback = function ($serverSize, $localSize) use ($progress) {
                $progress->setCurrent($serverSize, true);
                //$output->writeln(round($serverSize * 100 / $localSize));
            };

            $ftp->upload(FileUtils::append($remoteDistDir, $name), $source, FTP_BINARY, $callback);
        }

        //TODO upload deploy.php
    }

}