<?php

namespace mheinzerling\dist;


use mheinzerling\commons\FileUtils;
use mheinzerling\commons\JsonUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class DeploymentDescriptorAwareCommand extends Command
{

    protected function configure()
    {
        $this->addArgument("descriptor", InputArgument::REQUIRED)
            ->setDescription('Deployment descriptor file');
        $this->innerConfigure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $descriptor = $input->getArgument("descriptor");
        $config = JsonUtils::parseToArray(file_get_contents($descriptor));
        $this->innerExecute($config, $input, $output);
    }

    protected abstract function innerConfigure();

    protected abstract function innerExecute(array $config, InputInterface $input, OutputInterface $output);


}