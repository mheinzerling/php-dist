<?php
declare(strict_types = 1);
namespace mheinzerling\dist\bin;

use mheinzerling\commons\FileUtils;
use mheinzerling\commons\Separator;
use mheinzerling\dist\config\DeploymentDescriptor;
use mheinzerling\dist\config\Local;
use mheinzerling\dist\config\Zip;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ZipCommand extends DeploymentDescriptorAwareCommand
{

    protected function innerConfigure(): void
    {
        $this->setName('zip')
            ->setAliases([])
            ->setDescription('Generate a dist file ');
    }

    protected function innerExecute(DeploymentDescriptor $config, InputInterface $input, OutputInterface $output): int
    {
        $version = static::updateVersionFile($config->local(), $output);
        $this->zipArchive($config->local(), $config->zip(), $version, $output);
        return 0;
    }

    public static function updateVersionFile(Local $local, OutputInterface $output): string
    {
        $version = VersionCommand::getVersion();
        $versionFile = $local->getVersionFile();
        FileUtils::createFile($versionFile, $version);
        $output->writeln("Created or update file " . $versionFile . " with version" . $version);
        return $version;
    }

    protected function zipArchive(Local $local, Zip $zip, string $version, OutputInterface $output): void
    {
        $archiveFile = $local->getAbsoluteArchiveFile($version);
        $output->writeln("Create file " . $archiveFile . " from " . $local->getRoot());
        FileUtils::createFile($archiveFile, "");
        $archive = new \ZipArchive();
        $archive->open($archiveFile, \ZipArchive::OVERWRITE);

        $zippedFileCount = 0;
        $totalFileCount = 0;

        $output->write("Reading directory ...\r");
        $iterator = $local->getProjectIterator();
        foreach ($iterator as $path) {
            /**
             * @var $path \SplFileInfo
             */
            $totalFileCount++;
            $localAbsolutePath = FileUtils::to($path->getRealpath(), Separator::UNIX());
            if ($zip->ignore($localAbsolutePath)) continue;

            $pathInArchive = $local->stripRoot($localAbsolutePath);
            if ($path->isDir()) {
                $archive->addEmptyDir($pathInArchive . '/');
            } else {
                $overwriteAbsolutePath = $local->toOverwritePath($localAbsolutePath);
                if (file_exists($overwriteAbsolutePath)) {
                    $archive->addFile($overwriteAbsolutePath, $pathInArchive);
                    $output->writeln("Use overwrite for " . $pathInArchive);
                } else {
                    if (!$zip->allowedExtension($pathInArchive)) {
                        $output->writeln(str_pad("Add unexpected file: " . $pathInArchive, 75, " ", STR_PAD_RIGHT));
                    }
                    $archive->addFile($localAbsolutePath, $pathInArchive);
                }
            }
            $zippedFileCount++;
            $output->write("Reading directory... " . $totalFileCount . "->" . $zippedFileCount . "\r");
        }
        $output->writeln(str_pad("Read " . $totalFileCount . " files and zipped " . $zippedFileCount . ". ", 75, " ", STR_PAD_RIGHT));
        $archive->setArchiveComment('Version ' . $version);
        $archive->close();
    }

}