<?php
declare(strict_types = 1);

namespace mheinzerling\dist;


use mheinzerling\commons\FileUtils;
use mheinzerling\commons\FtpConnection;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class Template
{

    public static function get($name)
    {
        return FileUtils::append(FileUtils::append(realpath(__DIR__ . "/.."), "remote"), $name);
    }


    public static function upload(
        FtpConnection $ftp,
        OutputInterface $output,
        string $sourceTemplate,
        string $target,
        array $replacements): void
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