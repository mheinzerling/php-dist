<?php
declare(strict_types = 1);
namespace mheinzerling\dist;


use mheinzerling\commons\ExtensionFtpConnection;
use mheinzerling\commons\FtpConnection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MaintenanceCommand extends DeploymentDescriptorAwareCommand
{

    public static function setMaintenance(FtpConnection $ftp, OutputInterface $output, bool $enable): void
    {
        $maintenanceFlag = (new FileSystemHelper([]))->getMaintenanceFlag();

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
                $ftp->delete($maintenanceFlag);
                $output->writeln("Maintenance Mode disabled");
            }
        }
    }

    protected function innerConfigure(): void
    {
        $this->setName('maintenance')
            ->addArgument("status", InputArgument::REQUIRED, "true or false")
            ->setAliases([])
            ->setDescription('Enable/Disable maintenance mode');
    }

    protected function innerExecute(array $config, InputInterface $input, OutputInterface $output): int
    {
        $enable = $input->getArgument("status") !== "false";
        $ftp = new ExtensionFtpConnection($config['ftp']['server'], $config['ftp']['user'], $config['ftp']['password']);
        self::setMaintenance($ftp, $output, $enable);
        return 0;
    }
}