<?php
namespace mheinzerling\dist;


use mheinzerling\commons\GitUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VersionCommand extends Command
{
    protected function configure()
    {
        $this->setName('version')
            ->setAliases(array())
            ->setDescription('Display the current working dir version');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $version = GitUtils::getVersion();
        $output->writeln("Current version: >" . $version . "<");
    }
}