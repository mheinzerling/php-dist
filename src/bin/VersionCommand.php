<?php
declare(strict_types = 1);
namespace mheinzerling\dist\bin;


use mheinzerling\commons\GitUtils;
use mheinzerling\commons\SvnUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VersionCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('version')
            ->setAliases([])
            ->setDescription('Display the current working dir version');
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output):?int
    {
        $output->writeln("Current version: >" . static::getVersion() . "<");
        return 0;
    }

    public static function getVersion(): string
    {
        $version = GitUtils::getVersion();
        if ($version == null) $version = SvnUtils::getVersion();
        if ($version == null) $version = "UNDEFINED";
        return $version;
    }
}