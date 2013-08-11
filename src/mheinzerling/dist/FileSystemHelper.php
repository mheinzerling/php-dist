<?php
/**
 * Created by JetBrains PhpStorm.
 * User: User
 * Date: 11.08.13
 * Time: 11:13
 * To change this template use File | Settings | File Templates.
 */

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


    public function getRemoteDistDir()
    {
        return $this->config['ftp']['distDir'];
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
        return str_replace($this->root, $this->root . $overwriteDir, $realPath);
    }

    public function getProjectIterator()
    {
        return new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS), \RecursiveIteratorIterator::SELF_FIRST);
    }


    public function getRoot()
    {
        return $this->root;
    }
}