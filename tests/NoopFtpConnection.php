<?php
declare(strict_types = 1);

namespace mheinzerling\dist;


use mheinzerling\commons\FtpConnection;

class NoopFtpConnection implements FtpConnection
{

    /**
     * @param string $dir
     * @param string|null $pregFilter
     * @param bool $basename
     * @return string[]
     */
    public function ls($dir = "", string $pregFilter = null, $basename = false): array
    {
        return [];
    }

    public function mkdir(string $dir): string
    {
        return $dir;
    }

    public function delete(string $file): void
    {
    }

    /**
     * @param $target
     * @param string|resource $source
     * @param int $mode
     * @param callable|\Closure $progressCallback with int parameters $serverSize and $localSize
     * @throws \Exception
     */
    public function upload(string $target, $source, int $mode = FTP_ASCII, \Closure $progressCallback = null): void
    {
        if ($progressCallback != null) {
            $progressCallback(0, 1);
            $progressCallback(1, 1);
        }
    }

    public function get(string $target, $mode = FTP_ASCII): ?string
    {
        return null;
    }
}