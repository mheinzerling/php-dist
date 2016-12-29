<?php
declare(strict_types = 1);

namespace mheinzerling\dist;


use mheinzerling\commons\FileUtils;
use mheinzerling\commons\Separator;
use mheinzerling\commons\StringUtils;

class FileSystemHelper
{
    /**
     * @var array
     */
    private $config;
    /**
     * @var string
     */
    private $root;

    public function __construct(array $config)
    {
        $this->config = $config;
        $root = stristr(__DIR__, "vendor") !== false ? __DIR__ . '/../../../../..' : __DIR__ . '/../../lib';
        $this->root = FileUtils::to(realpath($root) . "/", Separator::UNIX());
    }

    public function getLocalDistDir(): string
    {
        return $this->config['local']['distDir'];
    }

    public function getLocalDist($version): string
    {
        $distDir = $this->getLocalDistDir();
        $distName = 'dist.' . $version . '.zip';
        return FileUtils::append($this->root, FileUtils::append($distDir, $distName));
    }


    public function getRemoteDeployDir(): string
    {
        return $this->config['remote']['deployDir'];
    }

    public function getAbsoluteRemoteDeployDir(): string
    {
        return FileUtils::append($this->config['remote']['root'], $this->config['remote']['deployDir']);
    }

    public function getRemoteScriptDir(): string
    {
        return $this->config['remote']['scriptDir'];
    }

    public function getRemoteDistDir(): string
    {
        return FileUtils::append($this->getRemoteScriptDir(), "dist");
    }

    public function getAbsoluteRemoteDistDir(): string
    {
        return FileUtils::append($this->getAbsoluteRemoteScriptDir(), "dist");
    }

    public function getAbsoluteRemoteScriptDir(): string
    {
        return FileUtils::append($this->config['remote']['root'], $this->getRemoteScriptDir());
    }

    public function getMaintenanceFlag(): string
    {
        return "maintenance";
    }

    public function getLocalVersionFile(): string
    {
        return FileUtils::append($this->root, "VERSION");
    }

    public function createArchivePath($realPath): string
    {
        return str_replace($this->root, '', $realPath);
    }

    public function toOverwritePath($realPath): string

    {
        $overwriteDir = $this->config['local']['overwriteDir'];
        return str_replace($this->root, $this->root . $overwriteDir . "/", $realPath);
    }

    public function getProjectIterator(): \Iterator
    {
        return new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS), \RecursiveIteratorIterator::SELF_FIRST);
    }

    public function getRoot(): string
    {
        return $this->root;
    }

    public function getUnzipUrl(): string
    {
        $path = "http://" . $this->config['remote']['url'];
        if ($this->hasPath()) $path = FileUtils::append($path, $this->config['remote']['path']);
        return FileUtils::append(FileUtils::append($path, $this->getRemoteScriptDir()), "unzip.php");
    }

    public function hasPath(): bool
    {
        return isset($this->config['remote']['path']) && !StringUtils::isBlank($this->config['remote']['path']);
    }
}