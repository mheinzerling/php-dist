<?php
namespace mheinzerling\dist;


use mheinzerling\commons\FtpConnection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MaintenanceCommand extends DeploymentDescriptorAwareCommand
{

    public static function setMaintenance(FtpConnection $ftp, OutputInterface $output, $enable)
    {
        $maintenanceFlag = (new FileSystemHelper(null))->getMaintenanceFlag();

        if ($ftp->get($maintenanceFlag) == null) {
            if ($enable) {
                $ftp->upload($maintenanceFlag, fopen('data://text/plain,' . time(), 'r'));
                $output->writeln("Maintenance Mode enabled");
            } else {
                $output->writeln("Maintenance Mode is already disabled");
            }
        } else {
            if ($enable) {
                $output->writeln("Maintenance Mode is already enabled");
            } else {
                $success = $ftp->delete($maintenanceFlag);
                if ($success) $output->writeln("Maintenance Mode disabled");
            }
        }
    }

    protected function innerConfigure()
    {
        $this->setName('maintenance')
            ->addArgument("status", InputArgument::REQUIRED, "true or false")
            ->setAliases(array())
            ->setDescription('Enable/Disable maintenance mode');
    }

    protected function innerExecute(array $config, InputInterface $input, OutputInterface $output)
    {

        $enable = $input->getArgument("status") !== "false";
        $ftp = new FtpConnection($config['ftp']['server'], $config['ftp']['user'], $config['ftp']['password']);
        self::setMaintenance($ftp, $output, $enable);

    }
}