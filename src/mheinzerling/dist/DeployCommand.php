<?php
namespace mheinzerling\dist;


use mheinzerling\commons\FileUtils;
use mheinzerling\commons\FtpConnection;
use Symfony\Component\Console\Helper\DialogHelper;
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
        if ($content != null) {
            $currentVersion = preg_replace("@.*(dist\\..*)/.*@ism", "\\1", $content);
        } else {
            $currentVersion = "None";
        }
        $opts = array('http' =>
            array(
                'method' => 'GET',
                'header' => "Content-Type: text/html\r\n" .
                    "Authorization: Basic " . base64_encode($config["remote"]["authuser"] . ":" . $config["remote"]["authpwd"]) . "\r\n",
                'content' => '',
                'timeout' => 60
            )
        );

        $context = stream_context_create($opts);
        $unzip = file_get_contents($fs->getUnzipUrl(), false, $context, -1, 40000);

        $output->writeln("Unzip result:\n" . $unzip);

        $output->writeln("Current version: " . $currentVersion);

        $remoteFiles = $ftp->ls($remoteDeployDir, '@dist.*@', true);

        $selection = array("0" => "Abort");
        foreach ($remoteFiles as $file) {

            $selection[] = $file;
        }

        /**
         * @var DialogHelper
         */
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