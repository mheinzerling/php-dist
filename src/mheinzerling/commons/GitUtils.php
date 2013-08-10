<?php

namespace mheinzerling\commons;


use mheinzerling\commons\Process;

class GitUtils
{
    public static function getVersion($annotated = false)
    {
        $cmd = "git describe --long --dirty=+";
        if (!$annotated) $cmd .= " --tags";
        $p = new Process($cmd);
        $p->run();
        $version = trim($p->getOut());
        if ($p->getErr() == "fatal: No names found, cannot describe anything.\n") {
            $version = "preinitial-0-g0000000";
        }
        return $version;
    }

    public static function hasLocalChanges()
    {
        $p = new Process("git status");
        $p->run(true);
        return stristr($p->getOut(), 'nothing to commit') === false;
    }
}