<?php
declare(strict_types = 1);

namespace mheinzerling\dist\config;


use mheinzerling\commons\FileUtils;
use mheinzerling\commons\JsonUtils;
use mheinzerling\commons\Separator;

class Local
{
    /**
     * @var string
     */
    private $distDir;
    /**
     * @var string
     */
    private $overwriteDir;

    public function __construct(array $json)
    {
        JsonUtils::validProperties($json, ["distDir", "overwriteDir"]);
        //with trailing / and unix separator TODO
        $this->distDir = rtrim(FileUtils::to(JsonUtils::required($json, "distDir"), Separator::UNIX()), Separator::UNIX) . Separator::UNIX;
        $this->overwriteDir = rtrim(FileUtils::to(JsonUtils::required($json, "overwriteDir"), Separator::UNIX()), Separator::UNIX) . Separator::UNIX;

        $root = stristr(__DIR__, "vendor") !== false ? __DIR__ . '/../../../../..' : __DIR__ . '/../..';
        $this->root = FileUtils::to(realpath($root) . "/", Separator::UNIX());
    }

    public function getRoot(): string
    {
        return $this->root;
    }

    public function stripRoot($absolutePath): string
    {
        return str_replace($this->root, '', $absolutePath);
    }

    public function toOverwritePath($realPath): string
    {
        return str_replace($this->root, $this->getOverwriteDirectory(), $realPath);
    }

    public function getOverwriteDirectory(): string
    {
        return FileUtils::append($this->root, $this->overwriteDir);
    }


    public function getAbsoluteArchivesDirectory(): string
    {
        return FileUtils::append($this->root, $this->distDir);
    }

    public function getAbsoluteArchiveFile($version): string
    {
        $distName = 'dist.' . $version . '.zip';
        return FileUtils::append($this->getAbsoluteArchivesDirectory(), $distName);
    }

    /**
     * @return string[]
     */
    public function getArchives(): array
    {
        return array_map("basename", glob($this->getAbsoluteArchivesDirectory() . "dist*.zip"));
    }

    public function getVersionFile(): string
    {
        return FileUtils::append($this->root, "VERSION");
    }

    public function getProjectIterator(): \RecursiveIteratorIterator
    {
        $directories = new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS);
        return new \RecursiveIteratorIterator($directories, \RecursiveIteratorIterator::SELF_FIRST);
    }


}