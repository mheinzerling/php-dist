<?php
declare(strict_types = 1);
namespace mheinzerling\dist;


use mheinzerling\commons\GitUtils;
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
        $version = GitUtils::getVersion();
        $output->writeln("Current version: >" . $version . "<");
        return 0;
    }
}