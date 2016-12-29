<?php
declare(strict_types = 1);

namespace mheinzerling\commons;


class FtpConnection
{
    /**
     * @var resource
     */
    private $connection_id;

    public function __construct($server, $user, $password)
    {
        $this->connection_id = ftp_connect($server);
        ftp_pasv($this->connection_id, true);
        $login_result = ftp_login($this->connection_id, $user, $password);
        if ((!$this->connection_id) || (!$login_result)) {
            throw new \Exception("Connection failed"); //TODO
        }
    }


    /**
     * @param string $dir
     * @param string|null $filter
     * @param bool $basename
     * @return string[]
     */
    public function ls($dir = "", string $filter = null, $basename = false): array
    {
        $entries = ftp_nlist($this->connection_id, $dir);
        if ($entries === false) return [];
        if ($filter == null && $basename == false) return $entries;
        $result = [];
        foreach ($entries as $entry) {
            if ($basename) $entry = basename($entry, $dir);
            if ($filter == null || preg_match($filter, $entry)) $result[] = $entry;
        }
        return $result;
    }

    public function mkdir(string $dir): string
    {
        return ftp_mkdir($this->connection_id, $dir);
    }

    public function delete(string $file): bool
    {
        return ftp_delete($this->connection_id, $file);
    }

    public function __destruct()
    {
        ftp_quit($this->connection_id);
    }

    /**
     * @param $target
     * @param string|resource $source
     * @param int $mode
     * @param callable|\Closure $progressCallback with parameters $serverSize and $localSize
     * @throws \Exception
     */
    public function upload(string $target, $source, int $mode = FTP_ASCII, \Closure $progressCallback = null): void
    {
        if (is_resource($source)) {
            $fh = $source;
            $stats = fstat($fh);
            $localSize = $stats['size'];
        } else {
            $localSize = filesize($source);
            $fh = fopen($source, "r");
        }
        $ret = ftp_nb_fput($this->connection_id, $target, $fh, $mode);
        while ($ret == FTP_MOREDATA) {
            if ($progressCallback != null) {
                $serverSize = ftell($fh);
                $progressCallback($serverSize, $localSize);
            }
            $ret = ftp_nb_continue($this->connection_id);
        }

        if ($ret != FTP_FINISHED) {
            throw new \Exception("Connection failed"); //TODO
        }
        if ($progressCallback != null) {
            $progressCallback($localSize, $localSize);
        }
    }

    public function get(string $target, $mode = FTP_ASCII, \Closure $progressCallback = null): ?string
    {
        $temp = fopen('php://memory', 'r+');
        if (@ftp_fget($this->connection_id, $temp, $target, $mode, 0)) {
            rewind($temp);
            return stream_get_contents($temp);
        } else {
            return null;
            //throw new \Exception("Connection failed"); //TODO
        }
    }
}