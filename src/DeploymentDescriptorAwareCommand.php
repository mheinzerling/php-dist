<?php
declare(strict_types = 1);

namespace mheinzerling\dist;


use mheinzerling\commons\FtpConnection;
use mheinzerling\commons\JsonUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class DeploymentDescriptorAwareCommand extends Command
{

    protected function configure(): void
    {
        $this->addArgument("descriptor", InputArgument::REQUIRED)
            ->setDescription('Deployment descriptor file');
        $this->innerConfigure();
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $descriptor = $input->getArgument("descriptor");
        $config = JsonUtils::parseToArray(file_get_contents($descriptor));
        return $this->innerExecute($config, $input, $output);
    }

    protected abstract function innerConfigure(): void;

    protected abstract function innerExecute(array $config, InputInterface $input, OutputInterface $output): int;


    protected function uploadTemplate(FtpConnection $ftp, OutputInterface $output, string $sourceTemplate, string $target, array $replacements): void
    {
        $template = file_get_contents($sourceTemplate);
        foreach ($replacements as $needle => $replacement) {
            $template = str_replace($needle, $replacement, $template);
        }
        $source = tmpfile();
        fwrite($source, $template);
        $stats = fstat($source);
        fseek($source, 0);

        $progress = new ProgressBar($output, $stats['size']);
        $progress->start();
        $callback = function ($serverSize, $localSize) use ($progress) {
            $progress->setProgress($serverSize);
            $progress->setBarWidth($localSize);
        };
        $ftp->upload($target, $source, FTP_BINARY, $callback);
        $output->writeln("");
    }
}