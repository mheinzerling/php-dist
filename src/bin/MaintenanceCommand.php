<?php
declare(strict_types = 1);
namespace mheinzerling\dist\bin;


use mheinzerling\commons\FtpConnection;
use mheinzerling\dist\config\DeploymentDescriptor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MaintenanceCommand extends DeploymentDescriptorAwareCommand
{
    const FLAG = "maintenance";

    protected function innerConfigure(): void
    {
        $this->setName('maintenance')
            ->addArgument("status", InputArgument::REQUIRED, "true or false")
            ->setAliases([])
            ->setDescription('Enable/Disable maintenance mode');
    }

    protected function innerExecute(DeploymentDescriptor $config, InputInterface $input, OutputInterface $output): int
    {
        $enable = $input->getArgument("status") !== "false";
        self::setMaintenance($config->remote()->createFtpConnection(), $output, $enable);
        return 0;
    }

    public static function setMaintenance(FtpConnection $ftp, OutputInterface $output, bool $enable): void
    {
        if ($ftp->get(self::FLAG) === null) {
            if ($enable) {
                $ftp->upload(self::FLAG, fopen('data://text/plain,' . time(), 'r'));
                $output->writeln("Maintenance Mode enabled");
            } else {
                $output->writeln("Maintenance Mode is already disabled");
            }
        } else {
            if ($enable) {
                $output->writeln("Maintenance Mode is already enabled");
            } else {
                $ftp->delete(self::FLAG);
                $output->writeln("Maintenance Mode disabled");
            }
        }
    }


}