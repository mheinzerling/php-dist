<?php
declare(strict_types = 1);

namespace mheinzerling\dist\bin;


use mheinzerling\dist\config\DeploymentDescriptor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class DeploymentDescriptorAwareCommand extends Command
{
    protected final function configure(): void
    {
        $this->addArgument("descriptor", InputArgument::REQUIRED)
            ->setDescription('Deployment descriptor file');
        $this->innerConfigure();
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected final function execute(InputInterface $input, OutputInterface $output): ?int
    {
        return $this->innerExecute(DeploymentDescriptor::loadFile($input->getArgument("descriptor")), $input, $output);
    }

    protected abstract function innerConfigure(): void;

    protected abstract function innerExecute(DeploymentDescriptor $config, InputInterface $input, OutputInterface $output): int;


}