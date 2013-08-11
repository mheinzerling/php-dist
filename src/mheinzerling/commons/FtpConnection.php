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

    public function upload($target, $source, $mode = FTP_ASCII)
    {
        $upload = ftp_put($this->connection_id, $target, $source, $mode);
        if (!$upload) {
            throw new \Exception("Connection failed"); //TODO
        }
    }
}