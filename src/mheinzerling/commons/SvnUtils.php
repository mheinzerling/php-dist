<?php

namespace mheinzerling\commons;


class SvnUtils
{
    public static function getVersion()
    {
        $cmd = "svnversion";
        $p = new Process($cmd);
        $p->run();
        $version = trim($p->getOut());
        if (strstr($p->getErr(), "Unversioned directory")) {
            return null;
        }
        $version = "r" . str_replace(array("M", "S", "P"), array("+", "", ""), $cmd, $version);

        return $version;
    }
} 