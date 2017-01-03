<?php
declare(strict_types = 1);

namespace mheinzerling\dist\bin;

use mheinzerling\commons\NoopFtpConnection;
use mheinzerling\dist\TestOutput;

class MaintenanceCommandTest extends \PHPUnit_Framework_TestCase
{

    public function testSetMaintenanceFalseTrue()
    {
        $output = new TestOutput();
        MaintenanceCommand::setMaintenance(new class extends NoopFtpConnection
        {

        }, $output, true);
        self::assertEquals("Maintenance Mode enabled\n", $output->output);
    }

    public function testSetMaintenanceTrueTrue()
    {
        $output = new TestOutput();
        /** @noinspection PhpMissingParentCallCommonInspection */
        MaintenanceCommand::setMaintenance(new class extends NoopFtpConnection
        {
            public function get(string $target, $mode = FTP_ASCII): ?string
            {
                return $target == MaintenanceCommand::FLAG ? "" : null;
            }
        }, $output, true);
        self::assertEquals("Maintenance Mode is already enabled\n", $output->output);
    }

    public function testSetMaintenanceFalseFalse()
    {
        $output = new TestOutput();
        MaintenanceCommand::setMaintenance(new class extends NoopFtpConnection
        {

        }, $output, false);
        self::assertEquals("Maintenance Mode is already disabled\n", $output->output);
    }

    public function testSetMaintenanceTrueFalse()
    {
        $output = new TestOutput();
        /** @noinspection PhpMissingParentCallCommonInspection */
        MaintenanceCommand::setMaintenance(new class extends NoopFtpConnection
        {
            public function get(string $target, $mode = FTP_ASCII): ?string
            {
                return $target == MaintenanceCommand::FLAG ? "" : null;
            }
        }, $output, false);
        self::assertEquals("Maintenance Mode disabled\n", $output->output);
    }

}