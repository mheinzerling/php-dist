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
        $maintenance = isset($config['remote']['maintenance']) ? $config['remote']['maintenance'] : "";

        $ftp = new FtpConnection($config['ftp']['server'], $config['ftp']['user'], $config['ftp']['password']);

        $content = $ftp->get($rootHtaccess);
        if ($content != null) {
            $currentVersion = preg_replace("@.*(dist\\..*)/.*@ism", "\\1", $content);
        } else {
            $currentVersion = "None";
        }
        $context = $this->createBasicAuthContext($config["remote"]["authuser"], $config["remote"]["authpwd"], $maintenance);
        $unzip = file_get_contents($fs->getUnzipUrl(), false, $context);

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

        MaintenanceCommand::setMaintenance($ftp, $output, true);
        $this->uploadTemplate($ftp, $output, $template, $rootHtaccess,
            array(
                'VERSION' => FileUtils::append($fs->getRemoteDeployDir(), $selection[$choice]),
                '/PATH' => $fs->hasPath() ? ("/" . $config['remote']['path']) : ""
            )
        );

        if (!isset($config['callback'])) return;
        if (!isset($config['callback']['afterDeploy'])) return;
        foreach ($config['callback']['afterDeploy'] as $callback) {
            $output->writeln("Calling " . $callback['url'] . " ...");
            if (isset($callback['authuser']) && isset($callback['authpwd'])) {
                $context = $this->createBasicAuthContext($callback['authuser'], $callback['authpwd'], $maintenance, $callback['method']);
                $output->writeln("with context " . $callback['authuser'] . " " . $callback['authpwd'] . " " . $callback['method']);
            } else {
                $context = null;
            }

            $result = file_get_contents($callback['url'], false, $context);
            $output->writeln($result);
        }
        if ($dialog->askConfirmation($output, "Disable maintenance mode?", false)) {
            MaintenanceCommand::setMaintenance($ftp, $output, false);
        }
    }

    protected function createBasicAuthContext($user, $password, $agent = "", $method = "GET")
    {
        $opts = array('http' =>
            array(
                'method' => $method,
                'header' => "Content-Type: text/html\r\n" .
                    "Authorization: Basic " . base64_encode($user . ":" . $password) . "\r\n",
                'content' => '',
                'user_agent' => $agent,
                'timeout' => 60
            )
        );
        return stream_context_create($opts);
    }
}