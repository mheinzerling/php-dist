<?php
namespace mheinzerling\dist;


use mheinzerling\commons\FileUtils;
use mheinzerling\commons\GitUtils;
use mheinzerling\commons\SvnUtils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ZipCommand extends DeploymentDescriptorAwareCommand
{

    protected function innerConfigure()
    {
        $this->setName('zip')
            ->setAliases(array())
            ->setDescription('Generate a dist file ');
    }

    protected function innerExecute(array $config, InputInterface $input, OutputInterface $output)
    {
        $filters = $config['zip']['ignore'];
        $allowedExtensions = $config['zip']['expectedExtensions'];

        $fs = new FileSystemHelper($config);

        $version = GitUtils::getVersion();
        if ($version == null) $version = SvnUtils::getVersion();
        if ($version == null) $version = "UNDEFINED";

        $archiveFile = $fs->getLocalDist($version);
        $versionFile = $fs->getLocalVersionFile();

        FileUtils::createFile($versionFile, $version);
        $output->writeln("Create file " . $archiveFile . " from " . $fs->getRoot());
        FileUtils::createFile($archiveFile, "");
        $archive = new \ZipArchive();
        $archive->open($archiveFile, \ZipArchive::OVERWRITE);

        $zippedFileCount = 0;
        $totalFileCount = 0;

        echo "Reading directory ...\r";
        $iterator = $fs->getProjectIterator();
        foreach ($iterator as $file) {
            $totalFileCount++;
            $realPath = FileUtils::to($file->getRealpath(), FileUtils::UNIX);
            foreach ($filters as $filter) {
                if (strstr($realPath, $filter) !== false) continue 2;
            }

            $pathInArchive = $fs->createArchivePath($realPath);
            if ($file->isDir()) {
                $archive->addEmptyDir($pathInArchive . '/');
            } else {
                $overwrite = $fs->toOverwritePath($realPath);
                if (file_exists($overwrite)) {
                    $archive->addFile($overwrite, $pathInArchive);
                    $output->writeln("Use overwrite for '" . $pathInArchive);
                } else {
                    $ext = pathinfo($pathInArchive, PATHINFO_EXTENSION);
                    if (!in_array($ext, $allowedExtensions)) {
                        $output->writeln(str_pad("Add unexpected file: " . $pathInArchive, 75, " ", STR_PAD_RIGHT));
                    }
                    $archive->addFile($realPath, $pathInArchive);
                }

            }
            $zippedFileCount++;
            $output->write("Reading directory... " . $totalFileCount . "->" . $zippedFileCount . "\r");
        }
        $output->writeln(str_pad("Read " . $totalFileCount . " files and zipped " . $zippedFileCount . ". ", 75, " ", STR_PAD_RIGHT));
        $archive->setArchiveComment('Version ' . $version);
        $archive->close();
        $output->writeln("Done");
    }

}