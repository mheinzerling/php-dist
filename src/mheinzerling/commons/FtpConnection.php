<?php

namespace mheinzerling\commons;


class FtpConnection
{

    private $connection_id;

    public function __construct($server, $user, $password)
    {
        $this->connection_id = ftp_connect($server);
        $login_result = ftp_login($this->connection_id, $user, $password);
        if ((!$this->connection_id) || (!$login_result)) {
            throw new \Exception("Connection failed"); //TODO
        }
    }

    public function ls($dir = "", $filter = null, $basename = false)
    {
        $entries = ftp_nlist($this->connection_id, $dir);
        if ($filter == null && $basename == false) return $entries;
        $result = array();
        foreach ($entries as $entry) {
            if ($basename) $entry = basename($entry, strlen($dir));

            if ($filter == null || preg_match($filter, $entry)) $result[] = $entry;
        }
        return $result;
    }

    public function __destruct()
    {
        ftp_quit($this->connection_id);
    }

    /**
     * @param callable $progressCallback with parameters $serverSize and $localSize
     */
    public function upload($target, $source, $mode = FTP_ASCII, \Closure $progressCallback = null)
    {
        $localSize = filesize($source);
        $fh = fopen($source, "r");
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
}