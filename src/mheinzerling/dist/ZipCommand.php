<?php

namespace mheinzerling\dist;


use mheinzerling\commons\FileUtils;
use mheinzerling\commons\GitUtils;
use mheinzerling\commons\StringUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ZipCommand extends Command
{

    protected function configure()
    {
        $this->setName('zip')
            ->setAliases(array())
            ->setDescription('Generate a dist file ');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filters = array(".git", ".idea", "vendor/phpunit", "vendor/bin",
            "vendor/swiftmailer/swiftmailer/doc", "vendor/swiftmailer/swiftmailer/notes", "vendor/swiftmailer/swiftmailer/test-suite",
            "/Test", "/Tests", "/test", "/tests", "/deploy", "/override", ".gitignore", "composer.phar", "composer.lock", "composer.json",
            "/DEBUG", "/example", "/bin",
            "LICENSE", "README", "CHANGELOG.md", "phpunit.xml.dist", "README.md", "build.xml", "CHANGES", "phpunit.xml", "entities.json",
            "create_pear_package.php", "package.xml.tpl", ".travis.yml", ".exe", "installed.json", "Security/Acl", "openid/identifier_request.html"
        );
        $allowedExtensions = array(".php", ".jpg", ".png", ".gif", ".xlf");

        $root = stristr(__DIR__, "vendor") !== false ? __DIR__ . '/../../../../../..' : __DIR__ . '/../../..';
        $root = FileUtils::to(realpath($root) . "/", FileUtils::UNIX);
        $output->writeln($root);
        $version = GitUtils::getVersion();
        $archiveFile = $root . 'deploy/dist.' . $version . '.zip';
        $versionFile = $root . "VERSION";

        FileUtils::createFile($versionFile, $version);
        $output->writeln("Create file " . $archiveFile . " from " . $root);
        FileUtils::createFile($archiveFile, "");
        $archive = new \ZipArchive();
        $archive->open($archiveFile, \ZipArchive::OVERWRITE);

        $zippedFileCount = 0;
        $totalFileCount = 0;

        echo "Reading directory ...\r";
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $file) {
            $totalFileCount++;
            $realPath = FileUtils::to($file->getRealpath(), FileUtils::UNIX);
            foreach ($filters as $filter) {
                if (strstr($realPath, $filter) !== false) continue 2;
            }

            $pathInArchive = str_replace($root, '', $realPath);
            if ($file->isDir()) {
                $archive->addEmptyDir($pathInArchive . '/');
            } else {
                $override = str_replace($root, $root . '/override/', $realPath);
                if (file_exists($override)) {
                    $archive->addFile($override, $pathInArchive);
                    $output->writeln("Use override for '" . $pathInArchive);
                } else {
                    $ext = substr($pathInArchive, -4);

                    if (!in_array($ext, $allowedExtensions)) {
                        $output->writeln(str_pad("Add non-php file: " . $pathInArchive, 75, " ", STR_PAD_RIGHT));
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