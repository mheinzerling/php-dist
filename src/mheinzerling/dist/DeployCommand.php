<?php

namespace mheinzerling\dist;


use mheinzerling\commons\FileUtils;
use mheinzerling\commons\FtpConnection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeployCommand extends DeploymentDescriptorAwareCommand
{

    protected function innerConfigure()
    {
        $this->setName('deploy')
            ->setAliases(array())
            ->setDescription('Activate a dist one the server');
    }

    protected function innerExecute(array $config, InputInterface $input, OutputInterface $output)
    {
        $fs = new FileSystemHelper($config);
        $remoteDeployDir = $fs->getRemoteDeployDir();
        $rootHtaccess = FileUtils::append($fs->getRemoteDeployDir(), ".htaccess");

        $ftp = new FtpConnection($config['ftp']['server'], $config['ftp']['user'], $config['ftp']['password']);

        $content = $ftp->get($rootHtaccess);
        $currentVersion = preg_replace("@.*(dist\..*)/.*@ism", "\\1", $content);

        $output->writeln("Current version:" . $currentVersion);

        $remoteFiles = $ftp->ls($remoteDeployDir, '@dist.*@', true);

        $selection = array("0" => "Abort");
        foreach ($remoteFiles as $file) {

            $selection[] = $file;
        }

        $dialog = $this->getHelper("dialog");
        $choice = $dialog->select($output, "Select dist to deploy", $selection, 0);


        if ($choice == 0) {
            $output->writeln("Abort deployment");
            return;
        }

        $template = __DIR__ . "/../../../remote/root.htaccess";

        $this->uploadTemplate($ftp, $output, $template, $rootHtaccess,
            array('VERSION' => FileUtils::append($fs->getRemoteDeployDir(), $selection[$choice])));
    }
}