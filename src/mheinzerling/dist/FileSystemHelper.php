<?php

namespace mheinzerling\dist;


use mheinzerling\commons\FileUtils;

class FileSystemHelper
{
    private $config;
    private $root;

    public function __construct($config)
    {
        $this->config = $config;
        $root = stristr(__DIR__, "vendor") !== false ? __DIR__ . '/../../../../../..' : __DIR__ . '/../../..';
        $this->root = FileUtils::to(realpath($root) . "/", FileUtils::UNIX);
    }

    public function getLocalDistDir()
    {
        return $this->config['local']['distDir'];
    }

    public function getLocalDist($version)
    {
        $distDir = $this->getLocalDistDir();
        $distName = 'dist.' . $version . '.zip';
        return FileUtils::append($this->root, FileUtils::append($distDir, $distName));
    }


    public function getRemoteDeployDir()
    {
        return $this->config['remote']['deployDir'];
    }

    public function getAbsoluteRemoteDeployDir()
    {
        return FileUtils::append($this->config['remote']['root'], $this->config['remote']['deployDir']);
    }

    public function getRemoteScriptDir()
    {
        return $this->config['remote']['scriptDir'];
    }

    public function getRemoteDistDir()
    {
        return FileUtils::append($this->getRemoteScriptDir(), "dist");
    }

    public function getAbsoluteRemoteDistDir()
    {
        return FileUtils::append($this->getAbsoluteRemoteScriptDir(), "dist");
    }

    public function getAbsoluteRemoteScriptDir()
    {
        return FileUtils::append($this->config['remote']['root'], $this->getRemoteScriptDir());
    }

    public function getLocalVersionFile()
    {
        return FileUtils::append($this->root, "VERSION");
    }

    public function createArchivePath($realPath)
    {
        return str_replace($this->root, '', $realPath);
    }

    public function toOverwritePath($realPath)

    {
        $overwriteDir = $this->config['local']['overwriteDir'];
        return str_replace($this->root, $this->root . $overwriteDir . "/", $realPath);
    }

    public function getProjectIterator()
    {
        return new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS), \RecursiveIteratorIterator::SELF_FIRST);
    }

    public function getRoot()
    {
        return $this->root;
    }

    public function getUnzipUrl()
    {
        return FileUtils::append("http://" . FileUtils::append($this->config['remote']['url'], $this->getRemoteScriptDir()), "unzip.php");
    }
}