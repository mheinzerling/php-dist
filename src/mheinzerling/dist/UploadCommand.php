<?php

namespace mheinzerling\dist;


use mheinzerling\commons\FtpConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UploadCommand extends Command
{
    protected function configure()
    {
        $this->setName('upload')
            ->setAliases(array())
            ->setDescription('Upload dist to server');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $server = "test.example.com";
        $user = "username";
        $password = "password";
        $remoteDeployDir = "deploy";
        $remote = "";
        $localDeployDir = "deploy" . $remote; //TODO root

        $ftp = new FtpConnection($server, $user, $password);


        $remoteFiles = array_flip($ftp->ls($remoteDeployDir, '@dist.*\.zip@', true));
        $localFiles = array_map("basename", glob($localDeployDir . "/dist*.zip"));

        $selection = array("0" => "Abort");
        foreach ($localFiles as $file) {
            $exist = array_key_exists($file, $remoteFiles);
            $selection[] = $file . ($exist ? " (Overwrite)" : "");
        }

        $dialog = $this->getHelper("dialog");
        $choice = $dialog->select($output, "Select file to upload", $selection, 0);

        if ($choice == null) $output->writeln("Abort upload");
        else {
            $name = $selection[$choice];
            $ftp->upload('/' . $remoteDeployDir . '/' . $name, $localDeployDir . '/' . $name, FTP_BINARY);
        }


    }
}