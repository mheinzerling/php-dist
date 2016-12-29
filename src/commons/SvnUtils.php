<?php
declare(strict_types = 1);

namespace mheinzerling\commons;


class SvnUtils
{
    public static function getVersion(): ?string
    {
        $cmd = "svnversion";
        $p = new Process($cmd);
        $p->run();
        $version = trim($p->getOut());
        if (strstr($p->getErr(), "Unversioned directory")) {
            return null;
        }
        $version = "r" . str_replace(["M", "S", "P"], ["+", "", ""], $cmd, $version);

        return $version;
    }
} 